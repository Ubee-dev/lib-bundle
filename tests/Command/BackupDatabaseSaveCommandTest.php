<?php

namespace UbeeDev\LibBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use UbeeDev\LibBundle\Command\BackupDatabaseSaveCommand;
use UbeeDev\LibBundle\Service\BackupDatabase;
use UbeeDev\LibBundle\Service\ObjectStorageInterface;

class BackupDatabaseSaveCommandTest extends TestCase
{
    public function testSaveDatabase(): void
    {
        $objectStorage = $this->createMock(ObjectStorageInterface::class);
        $backupDatabase = $this->createMock(BackupDatabase::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);

        $connection = $this->createMock(Connection::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('getParams')->willReturn([
            'host' => 'database_host',
            'user' => 'database_user',
            'password' => 'database_password',
        ]);
        $connection->method('getDatabase')->willReturn('database_name');

        $parameterBag->method('get')
            ->with('tmp_backup_folder')
            ->willReturn('/tmp/dump');

        $backupDatabase
            ->expects($this->once())
            ->method('dump')
            ->with($connection, '/tmp/dump')
            ->willReturn('/tmp/dump/database_name/database_name.sql');

        $objectStorage
            ->expects($this->once())
            ->method('upload')
            ->with(
                '/tmp/dump/database_name/database_name.sql',
                'some-bucket',
                $this->stringStartsWith('database_name/Dump_database_name_du_')
            )
            ->willReturn('https://aws.com/myexportfile.xls');

        $command = new BackupDatabaseSaveCommand($objectStorage, $backupDatabase, $entityManager, $parameterBag, 'some-bucket');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString('Dumping database database_name', $tester->getDisplay());
        $this->assertStringContainsString('Dump created:', $tester->getDisplay());
        $this->assertStringContainsString('Uploading to bucket some-bucket', $tester->getDisplay());
        $this->assertStringContainsString('Upload complete:', $tester->getDisplay());
    }
}
