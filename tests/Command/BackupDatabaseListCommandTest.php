<?php

namespace UbeeDev\LibBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use UbeeDev\LibBundle\Command\BackupDatabaseListCommand;
use UbeeDev\LibBundle\Service\S3Client;

class BackupDatabaseListCommandTest extends TestCase
{
    public function testListDumpWithoutDatabaseName(): void
    {
        $s3Client = $this->createMock(S3Client::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');

        $s3Client
            ->expects($this->once())
            ->method('list')
            ->with(['Bucket' => 'some-bucket', 'Prefix' => 'test_db'])
            ->willReturn(['/tests/file1.txt', '/tests/file2.txt']);

        $command = new BackupDatabaseListCommand($s3Client, $entityManager, 'some-bucket');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString('/tests/file1.txt', $tester->getDisplay());
        $this->assertStringContainsString('/tests/file2.txt', $tester->getDisplay());
    }

    public function testListDumpWithDatabaseName(): void
    {
        $s3Client = $this->createMock(S3Client::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $s3Client
            ->expects($this->once())
            ->method('list')
            ->with(['Bucket' => 'some-bucket', 'Prefix' => 'some-database'])
            ->willReturn(['/tests/file1.txt', '/tests/file2.txt']);

        $command = new BackupDatabaseListCommand($s3Client, $entityManager, 'some-bucket');
        $tester = new CommandTester($command);
        $tester->execute(['--database' => 'some-database']);

        $this->assertStringContainsString('/tests/file1.txt', $tester->getDisplay());
        $this->assertStringContainsString('/tests/file2.txt', $tester->getDisplay());
    }

    public function testListDumpWithEmptyResult(): void
    {
        $s3Client = $this->createMock(S3Client::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');

        $s3Client
            ->expects($this->once())
            ->method('list')
            ->willReturn([]);

        $command = new BackupDatabaseListCommand($s3Client, $entityManager, 'some-bucket');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString('There is no dump in the bucket some-bucket', $tester->getDisplay());
    }
}
