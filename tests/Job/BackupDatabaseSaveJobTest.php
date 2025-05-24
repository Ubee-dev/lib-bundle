<?php

namespace Khalil1608\LibBundle\Tests\Job;

use Khalil1608\LibBundle\Job\BackupDatabaseSaveJob;
use Khalil1608\LibBundle\Service\BackupDatabase;
use Khalil1608\LibBundle\Service\S3Client;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;

class BackupDatabaseSaveJobTest extends AbstractWebTestCase
{
	public function testSaveDatabase()
	{
	    $backupDatabaseService = $this->getMockedClass(BackupDatabase::class);
        $backupDatabaseService
            ->expects($this->once())
            ->method('dump')
            ->with(
                $this->equalTo('/tmp/dump'),
                $this->equalTo('database_host'),
                $this->equalTo('database_name'),
                $this->equalTo('database_user'),
                $this->equalTo('database_password')
            )
            ->willReturn('/tmp/dump/database_name/database_name.sql');
        $s3Client = $this->getMockedClass(S3Client::class);
        $s3Client
            ->expects($this->once())
            ->method('upload')
            ->with(
                $this->equalTo('/tmp/dump/database_name/database_name.sql'),
                $this->equalTo($bucket = 'some-bucket'),
                $this->equalTo('database_name/'.'Dump_database_name_du_'. (new \DateTime())->format('Y-m-d H:i:s').'.sql')
            )
            ->willReturn('https://aws.com/myexportfile.xls');
        $job = new BackupDatabaseSaveJob([], $this->output, $this->mailerMock, $backupDatabaseService, $s3Client, $bucket, '/tmp/dump', 'database_host', 'database_name', 'database_user', 'database_password');
        $job->run();
	}
}
