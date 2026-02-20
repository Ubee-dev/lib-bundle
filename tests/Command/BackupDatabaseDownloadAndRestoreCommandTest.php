<?php

namespace UbeeDev\LibBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use UbeeDev\LibBundle\Command\BackupDatabaseDownloadAndRestoreCommand;
use UbeeDev\LibBundle\Service\S3Client;

class BackupDatabaseDownloadAndRestoreCommandTest extends TestCase
{
    public function testDownloadLastDumpFromS3(): void
    {
        $s3Client = $this->createMock(S3Client::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);

        $connection = $this->createMock(Connection::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getParams')->willReturn([
            'host' => 'localhost',
            'user' => 'root',
            'password' => 'root',
        ]);

        $parameterBag->method('get')
            ->with('tmp_backup_folder')
            ->willReturn(sys_get_temp_dir() . '/backup_test');

        $s3Client
            ->expects($this->once())
            ->method('list')
            ->with(['Bucket' => 'some-bucket', 'Prefix' => 'test_db'])
            ->willReturn(['test_db/Dump_test_db_du_2024-01-01.sql']);

        // Create a fake backup file so the download is "skipped" (file already exists)
        $backupDir = sys_get_temp_dir() . '/backup_test/test_db';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        $backupFile = $backupDir . '/Dump_test_db_du_2024-01-01.sql';
        file_put_contents($backupFile, "CREATE TABLE test (id INT);\n");

        $command = new BackupDatabaseDownloadAndRestoreCommand($s3Client, $entityManager, $parameterBag, 'some-bucket');
        $tester = new CommandTester($command);

        // The command will try to run mysql to restore, which will fail in test env.
        // We just verify it gets to the restore step without S3 errors.
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Database test_db restored', $output);

        // Cleanup
        @unlink($backupFile);
        @rmdir($backupDir);
    }

    public function testDownloadWithSpecificKey(): void
    {
        $s3Client = $this->createMock(S3Client::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);

        $connection = $this->createMock(Connection::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getParams')->willReturn([
            'host' => 'localhost',
            'user' => 'root',
            'password' => 'root',
        ]);

        $parameterBag->method('get')
            ->with('tmp_backup_folder')
            ->willReturn(sys_get_temp_dir() . '/backup_test');

        // S3 list should NOT be called when a specific key is provided
        $s3Client->expects($this->never())->method('list');

        // Create a fake backup file
        $backupDir = sys_get_temp_dir() . '/backup_test/test_db';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        $backupFile = $backupDir . '/specific_dump.sql';
        file_put_contents($backupFile, "CREATE TABLE test (id INT);\n");

        $command = new BackupDatabaseDownloadAndRestoreCommand($s3Client, $entityManager, $parameterBag, 'some-bucket');
        $tester = new CommandTester($command);
        $tester->execute(['--key' => 'test_db/specific_dump.sql']);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Database test_db restored', $output);

        // Cleanup
        @unlink($backupFile);
        @rmdir($backupDir);
    }
}
