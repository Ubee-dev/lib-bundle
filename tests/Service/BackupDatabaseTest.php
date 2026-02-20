<?php

namespace UbeeDev\LibBundle\Tests\Service;

use UbeeDev\LibBundle\Service\BackupDatabase;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class BackupDatabaseTest extends AbstractWebTestCase
{

    private string $backupFolderName;

    public function setUp(): void
    {
        parent::setUp();
        $this->backupFolderName = '/tmp/test_backup_db';
    }

    public function testDbBackupCreatesBackupFolderAndCreatesProperlyNamedDbDump()
    {
        $fileSystem = new Filesystem();
        $fileSystem->remove($this->backupFolderName);

        $connection = $this->entityManager->getConnection();
        $databaseParams = $connection->getParams();
        $databaseName = $connection->getDatabase();
        $backupDatabaseService = new BackupDatabase();
        $backupDatabaseService->dump(
            $this->backupFolderName,
            $databaseParams['host'] ?? 'localhost',
            $databaseName,
            $databaseParams['user'] ?? '',
            $databaseParams['password'] ?? ''
        );
        $tmpBackupFolder = $this->backupFolderName.'/'.$databaseName;

        $this->assertTrue($fileSystem->exists($tmpBackupFolder));

        $finder = new Finder();
        $this->assertCount(1, $finder->files()->in($tmpBackupFolder)->name('*.sql'));
    }

    /**
     * @throws Exception
     */
    public function testBackupCanDumpTheSameDbTwiceWithoutCrashing()
    {
        $fileSystem = new Filesystem();
        $fileSystem->remove($this->backupFolderName);
        $connection = $this->entityManager->getConnection();
        $databaseParams = $connection->getParams();
        $databaseName = $connection->getDatabase();

        $backupDatabaseService = new BackupDatabase();
        for($i=0; $i<=1; $i++) {
            $backupDatabaseService->dump(
                $this->backupFolderName,
                $databaseParams['host'] ?? 'localhost',
                $databaseName,
                $databaseParams['user'] ?? '',
                $databaseParams['password'] ?? ''
            );
            if($i === 0) { sleep(1); }
        }

        $tmpBackupFolder = $this->backupFolderName.'/'.$databaseName;

        $this->assertTrue($fileSystem->exists($tmpBackupFolder));

        $finder = new Finder();
        $this->assertCount(2, $finder->files()->in($tmpBackupFolder)->name('*.sql'));
    }
}