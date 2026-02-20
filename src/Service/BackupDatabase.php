<?php

namespace UbeeDev\LibBundle\Service;

use Doctrine\DBAL\Connection;
use UbeeDev\LibBundle\Entity\DateTime;
use Symfony\Component\Filesystem\Filesystem;

class BackupDatabase
{
    public function __construct(
        private readonly DatabaseDumperInterface $dumper,
    ) {
    }

    public function dump(Connection $connection, string $backupFolder): string
    {
        $databaseName = $connection->getDatabase();
        $backupDir = $backupFolder . '/' . $databaseName;

        (new Filesystem())->mkdir($backupDir);

        $outputFile = $backupDir . '/' . (new DateTime())->format('Y-m-d H:i:s') . '.sql';

        $this->dumper->dump($connection, $outputFile);

        return $outputFile;
    }

    public function restore(Connection $connection, string $dumpFile): void
    {
        $this->dumper->restore($connection, $dumpFile);
    }
}
