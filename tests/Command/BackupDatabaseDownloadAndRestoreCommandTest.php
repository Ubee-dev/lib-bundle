<?php

namespace UbeeDev\LibBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use UbeeDev\LibBundle\Command\BackupDatabaseDownloadAndRestoreCommand;
use UbeeDev\LibBundle\Service\BackupDatabase;
use UbeeDev\LibBundle\Service\ObjectStorageInterface;

class BackupDatabaseDownloadAndRestoreCommandTest extends TestCase
{
    public function testDownloadLastDumpFromStorage(): void
    {
        $objectStorage = $this->createMock(ObjectStorageInterface::class);
        $backupDatabase = $this->createMock(BackupDatabase::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);

        $connection = $this->createMock(Connection::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');

        $parameterBag->method('get')
            ->with('tmp_backup_folder')
            ->willReturn(sys_get_temp_dir() . '/backup_test');

        $objectStorage
            ->expects($this->once())
            ->method('list')
            ->with('some-bucket', 'test_db')
            ->willReturn(['test_db/Dump_test_db_du_2024-01-01.sql']);

        // Create a fake backup file so the download is "skipped" (file already exists)
        $backupDir = sys_get_temp_dir() . '/backup_test/test_db';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        $backupFile = $backupDir . '/Dump_test_db_du_2024-01-01.sql';
        file_put_contents($backupFile, "CREATE TABLE test (id INT);\n");

        $backupDatabase
            ->expects($this->once())
            ->method('restore')
            ->with($connection, $backupFile);

        $command = new BackupDatabaseDownloadAndRestoreCommand($objectStorage, $backupDatabase, $entityManager, $parameterBag, 'some-bucket');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('File already exists locally:', $output);
        $this->assertStringContainsString('Restoring database test_db', $output);
        $this->assertStringContainsString('Database test_db restored successfully', $output);

        // Cleanup
        @unlink($backupFile);
        @rmdir($backupDir);
    }

    public function testDownloadWithSpecificKey(): void
    {
        $objectStorage = $this->createMock(ObjectStorageInterface::class);
        $backupDatabase = $this->createMock(BackupDatabase::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);

        $connection = $this->createMock(Connection::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');

        $parameterBag->method('get')
            ->with('tmp_backup_folder')
            ->willReturn(sys_get_temp_dir() . '/backup_test');

        // list should NOT be called when a specific key is provided
        $objectStorage->expects($this->never())->method('list');

        // Create a fake backup file
        $backupDir = sys_get_temp_dir() . '/backup_test/test_db';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        $backupFile = $backupDir . '/specific_dump.sql';
        file_put_contents($backupFile, "CREATE TABLE test (id INT);\n");

        $backupDatabase
            ->expects($this->once())
            ->method('restore')
            ->with($connection, $backupFile);

        $command = new BackupDatabaseDownloadAndRestoreCommand($objectStorage, $backupDatabase, $entityManager, $parameterBag, 'some-bucket');
        $tester = new CommandTester($command);
        $tester->execute(['--key' => 'test_db/specific_dump.sql']);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('File already exists locally:', $output);
        $this->assertStringContainsString('Restoring database test_db', $output);
        $this->assertStringContainsString('Database test_db restored successfully', $output);

        // Cleanup
        @unlink($backupFile);
        @rmdir($backupDir);
    }
}
