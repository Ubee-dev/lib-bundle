# Commands

This document covers all console commands provided by the UbeeDev LibBundle, as well as the base classes available for building your own commands.

## Table of Contents

- [Database Backup Commands](#database-backup-commands)
  - [backupdb:save](#backupdbsave)
  - [backupdb:list](#backupdblist)
  - [backupdb:download:restore](#backupdbdownloadrestore)
- [Post-Deploy](#post-deploy)
  - [lib:post_deploy](#libpost_deploy)
  - [Creating a Post-Deploy Script](#creating-a-post-deploy-script)
- [Fixtures and Data Management](#fixtures-and-data-management)
  - [UbeeDev:fixtures:load](#ubeedevfixturesload)
  - [UbeeDev:purge:tables](#ubeedevpurgetables)
- [Base Classes](#base-classes)
  - [AbstractMonitoredCommand](#abstractmonitoredcommand)

---

## Database Backup Commands

These commands manage database backups through AWS S3. They require the following configuration:

- **Environment variable:** `S3_BACKUP_BUCKET` -- the S3 bucket where backups are stored.
- **Bundle parameter:** `ubee_dev_lib.tmp_backup_folder` -- the local temporary directory for dump files (default: `/tmp/dump`).
- **Service:** A `DatabaseDumperInterface` implementation (MySQL by default, see [DatabaseDumper Configuration](configuration.md#databasedumper-configuration)).

### backupdb:save

Dumps the current database and uploads the resulting SQL file to S3.

```bash
php bin/console backupdb:save
```

**What it does:**

1. Reads the database name from the active Doctrine connection.
2. Creates a timestamped SQL dump via the `BackupDatabase` service in the configured `tmp_backup_folder`.
3. Uploads the dump to the S3 bucket under the path `{database_name}/Dump_{database_name}_du_{timestamp}.sql`.

**Example output:**

```
Start dumping my_database...
my_database dumped
Start sending my_database to my-app-backups bucket...
my_database uploaded
```

### backupdb:list

Lists available database backups stored in the S3 bucket.

```bash
# List backups for the current database
php bin/console backupdb:list

# List backups for a specific database
php bin/console backupdb:list --database=other_database
```

**Options:**

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--database` | string | Current database name | Filter the listing to backups of a specific database. |

**Example output:**

```
Start listing my-app-backups...
my_database/Dump_my_database_du_2026-02-18 10:30:00.sql
my_database/Dump_my_database_du_2026-02-19 10:30:00.sql
my_database/Dump_my_database_du_2026-02-20 10:30:00.sql
```

If no backups are found:

```
There is no dump in the bucket my-app-backups for the database my_database
```

### backupdb:download:restore

Downloads a backup from S3 and restores it into the local database.

```bash
# Download and restore the most recent backup
php bin/console backupdb:download:restore

# Download and restore a specific backup file
php bin/console backupdb:download:restore --key="my_database/Dump_my_database_du_2026-02-18 10:30:00.sql"
```

**Options:**

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--key` | string | Latest backup | The S3 object key of the backup file to download. When omitted, the most recent backup for the current database is used. |

**What it does:**

1. Determines the S3 key of the backup to restore (latest by default, or the one specified with `--key`).
2. Downloads the dump file to `{tmp_backup_folder}/{database_name}/`. If the file already exists locally, the download is skipped.
3. Restores the dump into the current Doctrine connection using `BackupDatabase::restore()`, which delegates to the configured `DatabaseDumperInterface` (MySQL or PostgreSQL).

**Example output:**

```
Start downloading my_database/Dump_my_database_du_2026-02-20 10:30:00.sql...
Database my_database downloaded...
Database my_database restored...
```

---

## Post-Deploy

### lib:post_deploy

Discovers and executes post-deploy scripts that have not yet been run.

```bash
php bin/console lib:post_deploy
```

**What it does:**

1. Scans the `{project_dir}/src/PostDeploy/` directory for PHP class files.
2. Checks each class against the `post_deploy_execution` database table to determine if it has already been run.
3. Executes any new scripts by calling their `execute()` method.
4. Records the execution in the `PostDeployExecution` entity (class name, timestamp, and duration in milliseconds).
5. If a script fails, sends an error notification to the Slack channel configured in `POST_DEPLOY_SLACK_CHANNEL`, including the exception message and a rerun command.

**Example output when scripts are found:**

```
Executed MigrateUserData in 1234 milliseconds
Executed BackfillSlugs in 567 milliseconds
```

**Example output when nothing to run:**

```
No post deploy command to run
```

### Creating a Post-Deploy Script

Post-deploy scripts are PHP classes placed in `src/PostDeploy/`. They must implement `PostDeployInterface` (or extend `AbstractPostDeploy` for convenience).

The `PostDeployPass` compiler pass auto-registers all classes found in `src/PostDeploy/` as public, autowired services -- no manual service configuration is needed.

**Basic example:**

```php
// src/PostDeploy/MigrateUserData.php
namespace App\PostDeploy;

use UbeeDev\LibBundle\Deploy\AbstractPostDeploy;

class MigrateUserData extends AbstractPostDeploy
{
    public function execute(): void
    {
        // Your one-time migration logic here
    }
}
```

**Example calling a Symfony console command from a post-deploy script:**

```php
// src/PostDeploy/ReindexSearchEngine.php
namespace App\PostDeploy;

use UbeeDev\LibBundle\Deploy\AbstractPostDeploy;

class ReindexSearchEngine extends AbstractPostDeploy
{
    public function execute(): void
    {
        $output = $this->executeCommand([
            'command' => 'app:search:reindex',
            '--no-interaction' => true,
        ]);
    }
}
```

The `AbstractPostDeploy::executeCommand()` method runs a Symfony console command programmatically in the current kernel environment and returns its output as a string.

**Key points:**

- Each script runs only once. Re-deploying will not re-execute previously completed scripts.
- Script names are tracked by their class name (without extension) in the `post_deploy_execution` table.
- If an error occurs during execution or while saving the record, a Slack notification is sent with details and a manual recovery command.

---

## Fixtures and Data Management

These commands are available only in the `dev` and `test` environments.

### UbeeDev:fixtures:load

Resets the database and loads a fresh copy from the latest S3 backup, then runs pending migrations.

```bash
php bin/console UbeeDev:fixtures:load
php bin/console UbeeDev:fixtures:load --env=test
```

**What it does:**

1. Drops the current database (`doctrine:database:drop --force`).
2. Recreates an empty database (`doctrine:database:create`).
3. Downloads and restores the latest backup from S3 (`backupdb:download:restore`).
4. Runs all pending Doctrine migrations (`doctrine:migrations:migrate --no-interaction`).

This command is useful for resetting a development or test database to a known state that mirrors production data.

### UbeeDev:purge:tables

Deletes all data from all database tables (except `migration_versions`).

```bash
php bin/console UbeeDev:purge:tables
php bin/console UbeeDev:purge:tables --env=test
```

**What it does:**

Calls `CleanerInterface::purgeAllTables()` to truncate every table in the database while preserving the migration history. This is useful for clearing test data between test runs without dropping and recreating the schema.

---

## Base Classes

### AbstractMonitoredCommand

A base class for console commands that report their execution status to Sentry Crons (monitoring). It implements the `MonitoredCommandInterface`.

All backup commands (`backupdb:save`, `backupdb:list`, `backupdb:download:restore`) extend this class.

**How it works:**

1. Before the command logic runs, a Sentry check-in is created with status `in_progress`.
2. If the command completes successfully, the check-in is updated to `ok`.
3. If an exception is thrown, the exception is captured by Sentry and the check-in is updated to `error`.

**Usage:**

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UbeeDev\LibBundle\Command\AbstractMonitoredCommand;

#[AsCommand(
    name: 'app:import:products',
    description: 'Import products from external API'
)]
class ImportProductsCommand extends AbstractMonitoredCommand
{
    public function perform(InputInterface $input, OutputInterface $output): void
    {
        // Your command logic here.
        // Exceptions are automatically captured by Sentry.
        $output->writeln('Importing products...');
    }
}
```

**Implement `perform()` instead of `execute()`:** The `execute()` method is reserved by `AbstractMonitoredCommand` to wrap your logic with Sentry monitoring. Place your command logic in the `perform()` method.

**Custom monitoring slug:**

By default, the monitoring slug is derived from the command name by replacing colons with underscores and converting to lowercase (e.g., `app:import:products` becomes `app_import_products`). You can override this:

```php
public function getMonitoringSlug(): string
{
    return 'product-importer';
}
```

You can also override the slug at runtime when running the same command multiple times with different configurations:

```bash
php bin/console app:import:products --monitoring-slug=product-importer-daily
```

**Options inherited from AbstractMonitoredCommand:**

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--monitoring-slug` | string | Auto-generated from command name | Slug sent to Sentry Crons. Useful when the same command is scheduled with different parameters. |
