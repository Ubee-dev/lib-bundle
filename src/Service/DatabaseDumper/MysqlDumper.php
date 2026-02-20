<?php

namespace UbeeDev\LibBundle\Service\DatabaseDumper;

use Doctrine\DBAL\Connection;
use RuntimeException;
use UbeeDev\LibBundle\Service\DatabaseDumperInterface;

class MysqlDumper implements DatabaseDumperInterface
{
    public function dump(Connection $connection, string $outputFile): void
    {
        $params = $connection->getParams();
        $databaseName = $connection->getDatabase();

        $command = sprintf(
            'mysqldump --user=%s --host=%s --password=%s --databases %s > %s',
            escapeshellarg($params['user'] ?? ''),
            escapeshellarg($params['host'] ?? 'localhost'),
            escapeshellarg($params['password'] ?? ''),
            escapeshellarg($databaseName),
            escapeshellarg($outputFile)
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(sprintf('mysqldump failed with exit code %d for database "%s"', $exitCode, $databaseName));
        }
    }

    public function restore(Connection $connection, string $inputFile): void
    {
        $params = $connection->getParams();
        $databaseName = $connection->getDatabase();

        $filteredFile = $this->filterDumpFile($inputFile);

        $command = sprintf(
            'mysql --force -u%s --password=%s -h%s %s < %s',
            escapeshellarg($params['user'] ?? ''),
            escapeshellarg($params['password'] ?? ''),
            escapeshellarg($params['host'] ?? 'localhost'),
            escapeshellarg($databaseName),
            escapeshellarg($filteredFile)
        );

        exec($command, $output, $exitCode);

        if ($filteredFile !== $inputFile) {
            @unlink($filteredFile);
        }

        if ($exitCode !== 0) {
            throw new RuntimeException(sprintf('mysql restore failed with exit code %d for database "%s"', $exitCode, $databaseName));
        }
    }

    private function filterDumpFile(string $inputFile): string
    {
        $fileContent = file($inputFile);
        $filteredContent = array_filter($fileContent, function (string $line): bool {
            return !str_contains($line, 'enable the sandbox mode');
        });

        $tempFilePath = sys_get_temp_dir() . '/filtered_backup_' . uniqid() . '.sql';
        file_put_contents($tempFilePath, implode('', $filteredContent));

        return $tempFilePath;
    }
}
