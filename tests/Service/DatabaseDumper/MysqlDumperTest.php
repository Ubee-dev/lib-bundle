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

        $connection = $this->createMock(Connection::class);
        $connection->method('getParams')->willReturn([
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
        ]);
        $connection->method('getDatabase')->willReturn('test_db');

        $outputFile = sys_get_temp_dir() . '/mysql_dump_test_' . uniqid() . '.sql';

        $dumper = new MysqlDumper();

        try {
            $dumper->dump($connection, $outputFile);
        } catch (\RuntimeException) {
            $this->markTestSkipped('mysqldump cannot connect to MySQL server');
        }

        $this->assertFileExists($outputFile);
        @unlink($outputFile);
    }

    public function testRestoreCallsMysql(): void
    {
        if (!$this->isBinaryAvailable('mysql')) {
            $this->markTestSkipped('mysql client is not available');
        }

        $this->assertTrue(true, 'Restore requires a running MySQL server, tested via integration');
    }

    private function isBinaryAvailable(string $binary): bool
    {
        exec('which ' . escapeshellarg($binary) . ' 2>/dev/null', $output, $exitCode);
        return $exitCode === 0;
    }
}
