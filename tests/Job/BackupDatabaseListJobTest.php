<?php

namespace Khalil1608\LibBundle\Tests\Job;

use Khalil1608\LibBundle\Job\BackupDatabaseListJob;
use Khalil1608\LibBundle\Service\S3Client;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;

class BackupDatabaseListJobTest extends AbstractWebTestCase
{
	public function testListDumpWithoutDatabaseName()
	{
        $s3Client = $this->getMockedClass(S3Client::class);
        $bucket = 'some-bucket';
        $s3Client
            ->expects($this->once())
            ->method('list')
            ->with(
                $this->equalTo(['Bucket' => $bucket])
            )
            ->willReturn(['/tests/file1.txt', '/tests/file2.txt']);

        $job = new BackupDatabaseListJob([], $this->output, $this->mailerMock, $s3Client, $bucket);
        $job->run();
	}

    public function testListDumpWithDatabaseName()
    {
        $s3Client = $this->getMockedClass(S3Client::class);
        $databaseName = 'some-database';
        $s3Client
            ->expects($this->once())
            ->method('list')
            ->with(
                $this->equalTo(['Bucket' => $bucket = 'some-bucket', 'Prefix' => $databaseName])
            )
            ->willReturn(['/tests/file1.txt', '/tests/file2.txt']);

        $job = new BackupDatabaseListJob([], $this->output, $this->mailerMock, $s3Client, $bucket, $databaseName);
        $job->run();
    }
}
