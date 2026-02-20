<?php

namespace UbeeDev\LibBundle\Tests\Service;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use UbeeDev\LibBundle\Service\BackupDatabase;
use UbeeDev\LibBundle\Service\DatabaseDumperInterface;

class BackupDatabaseTest extends TestCase
{
    private DatabaseDumperInterface&MockObject $dumperMock;
    private BackupDatabase $backupDatabase;
    private string $backupFolder;

    protected function setUp(): void
    {
        $this->dumperMock = $this->createMock(DatabaseDumperInterface::class);
        $this->backupDatabase = new BackupDatabase($this->dumperMock);
        $this->backupFolder = sys_get_temp_dir() . '/test_backup_db_' . uniqid();
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->backupFolder);
    }

    public function testDumpCreatesDirectoryAndDelegatesToDumper(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');

        $this->dumperMock
            ->expects($this->once())
            ->method('dump')
            ->with(
                $connection,
                $this->matchesRegularExpression('#^' . preg_quote($this->backupFolder) . '/test_db/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.sql$#')
            );

        $result = $this->backupDatabase->dump($connection, $this->backupFolder);

        $this->assertStringStartsWith($this->backupFolder . '/test_db/', $result);
        $this->assertStringEndsWith('.sql', $result);
        $this->assertDirectoryExists($this->backupFolder . '/test_db');
    }

    public function testDumpCanBeCalledTwice(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');

        $this->dumperMock->expects($this->exactly(2))->method('dump');

        $result1 = $this->backupDatabase->dump($connection, $this->backupFolder);
        $result2 = $this->backupDatabase->dump($connection, $this->backupFolder);

        $this->assertStringStartsWith($this->backupFolder . '/test_db/', $result1);
        $this->assertStringStartsWith($this->backupFolder . '/test_db/', $result2);
    }

    public function testRestoreDelegatesToDumper(): void
    {
        $connection = $this->createMock(Connection::class);
        $dumpFile = '/tmp/some_dump.sql';

        $this->dumperMock
            ->expects($this->once())
            ->method('restore')
            ->with($connection, $dumpFile);

        $this->backupDatabase->restore($connection, $dumpFile);
    }
}
