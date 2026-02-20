<?php

namespace UbeeDev\LibBundle\Tests\Service\DatabaseDumper;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use UbeeDev\LibBundle\Service\DatabaseDumper\MysqlDumper;

class MysqlDumperTest extends TestCase
{
    public function testDumpCallsMysqldump(): void
    {
        if (!$this->isBinaryAvailable('mysqldump')) {
            $this->markTestSkipped('mysqldump is not available');
        }

        $params = $this->getConnectionParams();
        $connection = $this->createMock(Connection::class);
        $connection->method('getParams')->willReturn($params);
        $connection->method('getDatabase')->willReturn($params['dbname']);

        $outputFile = sys_get_temp_dir() . '/mysql_dump_test_' . uniqid() . '.sql';

        $dumper = new MysqlDumper();
        $dumper->dump($connection, $outputFile);

        $this->assertFileExists($outputFile);
        @unlink($outputFile);
    }

    public function testRestoreCallsMysql(): void
    {
        if (!$this->isBinaryAvailable('mysql')) {
            $this->markTestSkipped('mysql client is not available');
        }

        $params = $this->getConnectionParams();
        $connection = $this->createMock(Connection::class);
        $connection->method('getParams')->willReturn($params);
        $connection->method('getDatabase')->willReturn($params['dbname']);

        $dumpFile = sys_get_temp_dir() . '/mysql_restore_test_' . uniqid() . '.sql';
        file_put_contents($dumpFile, "SELECT 1;\n");

        $dumper = new MysqlDumper();
        $dumper->restore($connection, $dumpFile);

        @unlink($dumpFile);
        $this->assertTrue(true);
    }

    private function getConnectionParams(): array
    {
        $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;

        if ($databaseUrl) {
            $parsed = parse_url($databaseUrl);
            return [
                'host' => $parsed['host'] ?? '127.0.0.1',
                'user' => $parsed['user'] ?? 'root',
                'password' => $parsed['pass'] ?? '',
                'dbname' => ltrim($parsed['path'] ?? '/test', '/'),
            ];
        }

        return [
            'host' => '127.0.0.1',
            'user' => 'root',
            'password' => '',
            'dbname' => 'lib_bundle_test',
        ];
    }

    private function isBinaryAvailable(string $binary): bool
    {
        exec('which ' . escapeshellarg($binary) . ' 2>/dev/null', $output, $exitCode);
        return $exitCode === 0;
    }
}
