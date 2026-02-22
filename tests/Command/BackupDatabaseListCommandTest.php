<?php

namespace UbeeDev\LibBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use UbeeDev\LibBundle\Command\BackupDatabaseListCommand;
use UbeeDev\LibBundle\Service\ObjectStorageInterface;

class BackupDatabaseListCommandTest extends TestCase
{
    public function testListDumpWithoutDatabaseName(): void
    {
        $objectStorage = $this->createMock(ObjectStorageInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');

        $objectStorage
            ->expects($this->once())
            ->method('list')
            ->with('some-bucket', 'test_db')
            ->willReturn(['/tests/file1.txt', '/tests/file2.txt']);

        $command = new BackupDatabaseListCommand($objectStorage, $entityManager, 'some-bucket');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString('Found 2 backup(s)', $tester->getDisplay());
        $this->assertStringContainsString('/tests/file1.txt', $tester->getDisplay());
        $this->assertStringContainsString('/tests/file2.txt', $tester->getDisplay());
    }

    public function testListDumpWithDatabaseName(): void
    {
        $objectStorage = $this->createMock(ObjectStorageInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $objectStorage
            ->expects($this->once())
            ->method('list')
            ->with('some-bucket', 'some-database')
            ->willReturn(['/tests/file1.txt', '/tests/file2.txt']);

        $command = new BackupDatabaseListCommand($objectStorage, $entityManager, 'some-bucket');
        $tester = new CommandTester($command);
        $tester->execute(['--database' => 'some-database']);

        $this->assertStringContainsString('Found 2 backup(s)', $tester->getDisplay());
        $this->assertStringContainsString('/tests/file1.txt', $tester->getDisplay());
        $this->assertStringContainsString('/tests/file2.txt', $tester->getDisplay());
    }

    public function testListDumpWithEmptyResult(): void
    {
        $objectStorage = $this->createMock(ObjectStorageInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');

        $objectStorage
            ->expects($this->once())
            ->method('list')
            ->willReturn([]);

        $command = new BackupDatabaseListCommand($objectStorage, $entityManager, 'some-bucket');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString('No backups found in bucket some-bucket for database test_db', $tester->getDisplay());
    }
}
