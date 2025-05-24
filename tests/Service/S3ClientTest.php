<?php

namespace Khalil1608\LibBundle\Tests\Service;

use Khalil1608\LibBundle\Service\S3Client;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class S3ClientTest extends AbstractWebTestCase
{
    private $exportDir;
    private $filePath;
    
    /** @var S3Client */
    private $s3Client;
    
    private $s3BackupBucket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exportDir = $this->container->getParameter('export_dir').'/';
        $this->cleaner->cleanFolder($this->exportDir);
        $fs = new Filesystem();
        $this->filePath = $this->exportDir.'file.txt';
        $fs->touch($this->filePath);

        $this->s3Client = new S3Client(
            getenv('S3_KEY'),
            getenv('S3_SECRET'),
            $this->container->getParameter('s3_region'),
            $this->container->getParameter('s3_version')
        );

        $this->s3BackupBucket = $this->container->getParameter('s3_backup_bucket');
        $this->s3Client->delete($this->s3BackupBucket, 'tests');
        
    }

    public function testSuccessUpload()
    {
        $s3Url = $this->s3Client->upload($this->filePath, $this->s3BackupBucket, 'tests/file.txt');
        // AWS SDK changed its upload url somewhere between v3.36 and v3.45
        $this->assertEquals('https://'.$this->s3BackupBucket.'.s3.'.$this->container->getParameter('s3_region').'.amazonaws.com/tests/file.txt', $s3Url);
    }

    public function testFailUpload()
    {
        $s3Client = new S3Client('wrongkey', 'wrongsecret', 'eu-west-1', '2006-03-01');
        $s3Url = $s3Client->upload($this->filePath, $this->container->getParameter('s3_backup_bucket'), 'tests/file.txt');
        $this->assertEquals(null, $s3Url);
    }


    public function testSuccessfulGetObject()
    {
        $s3Url = $this->s3Client->upload($this->filePath, $this->s3BackupBucket, 'tests/file.txt');
        $objectUrl = $this->s3Client->get($this->s3BackupBucket, 'tests/file.txt');
        $this->assertNotNull($objectUrl);
        $this->assertEquals($s3Url, $objectUrl);
    }

    public function testSuccessfulDeleteObject()
    {
        $this->s3Client->upload($this->filePath, $this->s3BackupBucket, 'tests/file.txt');
        $result = $this->s3Client->delete($this->s3BackupBucket, 'tests/file.txt');
        $this->assertTrue($result);
        $objectUrl = $this->s3Client->get($this->s3BackupBucket, 'tests/file.txt');
        $this->assertNull($objectUrl);
    }

    public function testSuccessList()
    {
        $this->s3Client->upload($this->filePath, $this->s3BackupBucket, 'tests/file2.txt');
        $this->s3Client->upload($this->filePath, $this->s3BackupBucket, 'tests/file1.txt');

        $dumpsDatabase = $this->s3Client->list(['Bucket' => $this->s3BackupBucket, 'Prefix' => 'tests']);

        $this->assertCount(2, $dumpsDatabase);

        $this->assertEquals('tests/file1.txt', $dumpsDatabase[0]);
        $this->assertEquals('tests/file2.txt', $dumpsDatabase[1]);
    }

    public function testSuccessDownload()
    {
        $this->s3Client->upload($this->filePath, $this->s3BackupBucket, 'tests/file.txt');

        $tmpDumpFilePath = $this->s3Client->download($this->s3BackupBucket, 'tests/file.txt', '/tmp/test', 'test.sql');

        $finder = new Finder();
        $this->assertEquals('/tmp/test/test.sql', $tmpDumpFilePath);
        $this->assertCount(1, $finder->files()->in('/tmp/test')->name('test.sql'));
    }

    public function testDownloadWithoutCrashingIfFolderNotExist()
    {
        $this->s3Client->upload($this->filePath, $this->s3BackupBucket, 'tests/file.txt');

        $tmpDumpFilePath = $this->s3Client->download($this->s3BackupBucket, 'tests/file.txt', '/tmp/test', 'test.sql');

        $finder = new Finder();
        $this->assertEquals('/tmp/test/test.sql', $tmpDumpFilePath);
        $this->assertCount(1, $finder->files()->in('/tmp/test')->name('test.sql'));
    }
}