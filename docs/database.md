# Database

## Table of Contents

- [Custom Doctrine DBAL Types](#custom-doctrine-dbal-types)
  - [MoneyType](#moneytype)
  - [EmailType](#emailtype)
  - [NameType](#nametype)
  - [UrlType](#urltype)
  - [HtmlNameType](#htmlnametype)
  - [DateTimeType](#datetimetype)
  - [DateType](#datetype)
  - [Abstract Base Class](#abstract-base-class)
- [Database Backup & Restore](#database-backup--restore)
  - [DatabaseDumperInterface](#databasedumperinterface)
  - [MysqlDumper](#mysqldumper)
  - [PostgresDumper](#postgresdumper)
  - [BackupDatabase Service](#backupdatabase-service)
  - [Switching to PostgreSQL](#switching-to-postgresql)
- [Migration Factory Decorator](#migration-factory-decorator)
  - [MigrationInterface](#migrationinterface)
  - [Configuration](#configuration)
- [Parallel Test Databases](#parallel-test-databases)

---

## Custom Doctrine DBAL Types

The bundle registers custom Doctrine DBAL types in `config/packages/doctrine.yaml`:

```yaml
doctrine:
  dbal:
    types:
      datetime: UbeeDev\LibBundle\Doctrine\DBAL\Types\DateTimeType
      date: UbeeDev\LibBundle\Doctrine\DBAL\Types\DateType
      money: UbeeDev\LibBundle\Doctrine\DBAL\Types\MoneyType
      email: UbeeDev\LibBundle\Doctrine\DBAL\Types\EmailType
      name: UbeeDev\LibBundle\Doctrine\DBAL\Types\NameType
      url: UbeeDev\LibBundle\Doctrine\DBAL\Types\UrlType
      htmlName: UbeeDev\LibBundle\Doctrine\DBAL\Types\HtmlNameType
```

### MoneyType

Stores `Money\Money` objects as an integer column in the database, using minor units (cents). When reading from the database, values are converted back to `Money::EUR()`.

- **SQL type**: `INTEGER`
- **PHP type**: `Money\Money`
- **Binding type**: `ParameterType::INTEGER`

```php
use Money\Money;

#[ORM\Column(type: 'money')]
private Money $price;
```

Example: a price of 12.50 EUR is stored as `1250` in the database.

### EmailType

Stores an email address as a validated string. The `Email` value object is used on the PHP side.

- **SQL type**: `VARCHAR(255)`
- **PHP type**: `UbeeDev\LibBundle\Model\Type\Email`
- **Binding type**: `ParameterType::STRING`

```php
use UbeeDev\LibBundle\Model\Type\Email;

#[ORM\Column(type: 'email')]
private Email $email;
```

### NameType

Stores a name with proper capitalization. Extends Doctrine's `StringType`.

- **SQL type**: `VARCHAR(255)`
- **PHP type**: `UbeeDev\LibBundle\Model\Type\Name`

```php
use UbeeDev\LibBundle\Model\Type\Name;

#[ORM\Column(type: 'name')]
private Name $name;
```

### UrlType

Stores a validated URL. Extends Doctrine's `StringType`.

- **SQL type**: `VARCHAR(255)`
- **PHP type**: `UbeeDev\LibBundle\Model\Type\Url`

```php
use UbeeDev\LibBundle\Model\Type\Url;

#[ORM\Column(type: 'url')]
private Url $url;
```

### HtmlNameType

Stores an HTML-safe, sanitized name. Extends Doctrine's `StringType`.

- **SQL type**: `VARCHAR(255)`
- **PHP type**: `UbeeDev\LibBundle\Model\Type\HtmlName`

```php
use UbeeDev\LibBundle\Model\Type\HtmlName;

#[ORM\Column(type: 'htmlName')]
private HtmlName $htmlName;
```

### DateTimeType

Overrides Doctrine's built-in `datetime` type to return `UbeeDev\LibBundle\Entity\DateTime` instead of PHP's native `\DateTime`. This replacement is transparent -- any entity using the standard `datetime` column type will automatically use this custom type.

- **SQL type**: `DATETIME` / `TIMESTAMP` (platform-dependent)
- **PHP type**: `UbeeDev\LibBundle\Entity\DateTime`

```php
use UbeeDev\LibBundle\Entity\DateTime;

#[ORM\Column(type: 'datetime')]
private DateTime $createdAt;
```

### DateType

Overrides Doctrine's built-in `date` type to return `UbeeDev\LibBundle\Entity\Date` instead of PHP's native `\DateTime`. Like `DateTimeType`, this is a transparent replacement.

- **SQL type**: `DATE`
- **PHP type**: `UbeeDev\LibBundle\Entity\Date`

```php
use UbeeDev\LibBundle\Entity\Date;

#[ORM\Column(type: 'date')]
private Date $birthDate;
```

### Abstract Base Class

All custom types (except `DateTimeType`, `DateType`, `NameType`, `UrlType`, and `HtmlNameType`) extend `UbeeDev\LibBundle\Doctrine\DBAL\Types\Type`, which itself extends `Doctrine\DBAL\Types\Type`. This abstract base class defines the type name constants:

```php
abstract class Type extends BaseType
{
    public const string MONEY = 'money';
    public const string Email = 'email';
    public const string Name = 'name';
    public const string Url = 'url';
    public const string HtmlName = 'htmlName';
}
```

`NameType`, `UrlType`, and `HtmlNameType` extend Doctrine's `StringType` directly. `DateTimeType` and `DateType` extend `Doctrine\DBAL\Types\Type` directly.

---

## Database Backup & Restore

The bundle provides a pluggable database dump and restore system.

### DatabaseDumperInterface

```php
namespace UbeeDev\LibBundle\Service;

use Doctrine\DBAL\Connection;

interface DatabaseDumperInterface
{
    public function dump(Connection $connection, string $outputFile): void;
    public function restore(Connection $connection, string $inputFile): void;
}
```

### MysqlDumper

The default implementation. Uses `mysqldump` for dumping and `mysql` for restoring. During restore, lines containing sandbox-mode statements are filtered out from the dump file before importing.

```php
use UbeeDev\LibBundle\Service\DatabaseDumper\MysqlDumper;
```

- **dump**: runs `mysqldump --user=... --host=... --password=... --databases <dbname> > <outputFile>`
- **restore**: runs `mysql --force -u... --password=... -h... <dbname> < <filteredFile>`

### PostgresDumper

Uses `pg_dump` for dumping and `psql` for restoring. The database password is passed via the `PGPASSWORD` environment variable.

```php
use UbeeDev\LibBundle\Service\DatabaseDumper\PostgresDumper;
```

- **dump**: runs `PGPASSWORD=... pg_dump --host=... --username=... --format=plain --file=<outputFile> <dbname>`
- **restore**: runs `PGPASSWORD=... psql --host=... --username=... --dbname=<dbname> --file=<inputFile>`

### BackupDatabase Service

The `BackupDatabase` service wraps a `DatabaseDumperInterface` and provides a higher-level API with timestamped backup files:

```php
namespace UbeeDev\LibBundle\Service;

class BackupDatabase
{
    public function dump(Connection $connection, string $backupFolder): string;
    public function restore(Connection $connection, string $dumpFile): void;
}
```

- **`dump()`** creates a subdirectory named after the database inside `$backupFolder`, generates a timestamped `.sql` file (e.g., `backups/mydb/2025-01-15 14:30:00.sql`), and returns the full path to the file.
- **`restore()`** delegates directly to the underlying dumper's `restore()` method.

Usage example:

```php
use UbeeDev\LibBundle\Service\BackupDatabase;
use Doctrine\DBAL\Connection;

class MyService
{
    public function __construct(
        private readonly BackupDatabase $backupDatabase,
        private readonly Connection $connection,
    ) {}

    public function createBackup(): string
    {
        return $this->backupDatabase->dump($this->connection, '/var/backups');
        // Returns e.g. "/var/backups/mydb/2025-01-15 14:30:00.sql"
    }

    public function restoreBackup(string $dumpFile): void
    {
        $this->backupDatabase->restore($this->connection, $dumpFile);
    }
}
```

### Switching to PostgreSQL

By default, `DatabaseDumperInterface` is aliased to `MysqlDumper` in `config/services.yaml`:

```yaml
services:
    UbeeDev\LibBundle\Service\DatabaseDumperInterface:
        alias: UbeeDev\LibBundle\Service\DatabaseDumper\MysqlDumper
```

To use PostgreSQL instead, override the alias in your application's service configuration:

```yaml
services:
    UbeeDev\LibBundle\Service\DatabaseDumperInterface:
        alias: UbeeDev\LibBundle\Service\DatabaseDumper\PostgresDumper
```

---

## Migration Factory Decorator

`MigrationFactoryDecorator` wraps Doctrine's default `MigrationFactory` to inject services into migration classes. When a migration implements `MigrationInterface`, the decorator automatically injects the current environment, entity manager, and kernel.

### MigrationInterface

Migrations that need access to application services should implement this interface:

```php
namespace UbeeDev\LibBundle\Migrations\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

interface MigrationInterface
{
    public function setAccountDatabase(string $accountDatabase): void;
    public function setCurrentEnv(string $currentEnv): void;
    public function setEntityManager(EntityManagerInterface $entityManager): void;
    public function setKernel(KernelInterface $kernel): void;
}
```

Example migration using the interface:

```php
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use UbeeDev\LibBundle\Migrations\Factory\MigrationInterface;

final class Version20250115143000 extends AbstractMigration implements MigrationInterface
{
    private EntityManagerInterface $entityManager;
    private string $currentEnv;
    private KernelInterface $kernel;

    public function setAccountDatabase(string $accountDatabase): void {}

    public function setCurrentEnv(string $currentEnv): void
    {
        $this->currentEnv = $currentEnv;
    }

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function setKernel(KernelInterface $kernel): void
    {
        $this->kernel = $kernel;
    }

    public function up(Schema $schema): void
    {
        // You can now use $this->entityManager, $this->currentEnv, $this->kernel
    }
}
```

### Configuration

The decorator is registered in `config/packages/doctrine_migrations.yaml`:

```yaml
doctrine_migrations:
    migrations_paths:
        'DoctrineMigrations': '%kernel.project_dir%/migrations'
    storage:
        table_storage:
            table_name: 'migration_versions'
    services:
        Doctrine\Migrations\Version\MigrationFactory: UbeeDev\LibBundle\Migrations\Factory\MigrationFactoryDecorator
```

---

## Parallel Test Databases

The Doctrine configuration in `config/packages/doctrine.yaml` appends a `_test{TEST_TOKEN}` suffix to the database name when running in the `test` environment:

```yaml
when@test:
    doctrine:
        dbal:
            connections:
                default:
                    # "TEST_TOKEN" is typically set by ParaTest
                    dbname_suffix: '_test%env(TEST_TOKEN)%'
```

This allows [ParaTest](https://github.com/paratestphp/paratest) to run test suites in parallel, with each process using a separate database. ParaTest sets the `TEST_TOKEN` environment variable to a unique value per process (e.g., `1`, `2`, `3`), resulting in database names like:

- `myapp_test1`
- `myapp_test2`
- `myapp_test3`

When running tests without ParaTest, `TEST_TOKEN` defaults to an empty string, giving a database name like `myapp_test`.
