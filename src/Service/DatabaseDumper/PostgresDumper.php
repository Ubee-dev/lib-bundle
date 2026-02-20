<?php

namespace UbeeDev\LibBundle\Service\DatabaseDumper;

use Doctrine\DBAL\Connection;
use RuntimeException;
use UbeeDev\LibBundle\Service\DatabaseDumperInterface;

class PostgresDumper implements DatabaseDumperInterface
{
    public function dump(Connection $connection, string $outputFile): void
    {
        $params = $connection->getParams();
        $databaseName = $connection->getDatabase();

        $command = sprintf(
            'PGPASSWORD=%s pg_dump --host=%s --username=%s --format=plain --file=%s %s',
            escapeshellarg($params['password'] ?? ''),
            escapeshellarg($params['host'] ?? 'localhost'),
            escapeshellarg($params['user'] ?? ''),
            escapeshellarg($outputFile),
            escapeshellarg($databaseName)
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(sprintf('pg_dump failed with exit code %d for database "%s"', $exitCode, $databaseName));
        }
    }

    public function restore(Connection $connection, string $inputFile): void
    {
        $params = $connection->getParams();
        $databaseName = $connection->getDatabase();

        $command = sprintf(
            'PGPASSWORD=%s psql --host=%s --username=%s --dbname=%s --file=%s',
            escapeshellarg($params['password'] ?? ''),
            escapeshellarg($params['host'] ?? 'localhost'),
            escapeshellarg($params['user'] ?? ''),
            escapeshellarg($databaseName),
            escapeshellarg($inputFile)
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(sprintf('psql restore failed with exit code %d for database "%s"', $exitCode, $databaseName));
        }
    }
}
