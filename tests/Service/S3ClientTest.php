<?php

namespace UbeeDev\LibBundle\Tests\Service;

use Aws\Result;
use Aws\S3\S3Client as AmazonS3Client;
use PHPUnit\Framework\TestCase;
use UbeeDev\LibBundle\Service\S3Client;

class S3ClientTest extends TestCase
{
    private S3Client $s3Client;
    private AmazonS3Client $amazonS3ClientMock;

    protected function setUp(): void
    {
        $this->amazonS3ClientMock = $this->createMock(AmazonS3Client::class);

        // Use reflection to inject the mock since the constructor creates the real client
        $this->s3Client = (new \ReflectionClass(S3Client::class))->newInstanceWithoutConstructor();
        $reflection = new \ReflectionProperty(S3Client::class, 's3Client');
        $reflection->setValue($this->s3Client, $this->amazonS3ClientMock);
    }

    public function testUploadReturnsObjectUrl(): void
    {
        $result = new Result(['ObjectURL' => 'https://bucket.s3.eu-west-1.amazonaws.com/tests/file.txt']);

        $this->amazonS3ClientMock
            ->expects($this->once())
            ->method('__call')
            ->with('putObject', [[
                'Bucket' => 'test-bucket',
                'Key' => 'tests/file.txt',
                'SourceFile' => '/tmp/file.txt',
            ]])
            ->willReturn($result);

        $url = $this->s3Client->upload('/tmp/file.txt', 'test-bucket', 'tests/file.txt');
        $this->assertEquals('https://bucket.s3.eu-west-1.amazonaws.com/tests/file.txt', $url);
    }

    public function testGetReturnsEffectiveUri(): void
    {
        $result = new Result(['@metadata' => ['effectiveUri' => 'https://bucket.s3.amazonaws.com/tests/file.txt']]);

        $this->amazonS3ClientMock
            ->expects($this->once())
            ->method('__call')
            ->with('getObject', [[
                'Bucket' => 'test-bucket',
                'Key' => 'tests/file.txt',
            ]])
            ->willReturn($result);

        $uri = $this->s3Client->get('test-bucket', 'tests/file.txt');
        $this->assertEquals('https://bucket.s3.amazonaws.com/tests/file.txt', $uri);
    }

    public function testDownloadReturnsFilePath(): void
    {
        $this->amazonS3ClientMock
            ->expects($this->once())
            ->method('__call')
            ->with('getObject', [[
                'Bucket' => 'test-bucket',
                'Key' => 'tests/file.txt',
                'SaveAs' => '/tmp/test/test.sql',
            ]])
            ->willReturn(new Result([]));

        $filePath = $this->s3Client->download('test-bucket', 'tests/file.txt', '/tmp/test', 'test.sql');
        $this->assertEquals('/tmp/test/test.sql', $filePath);
    }

    public function testDeleteReturnsTrue(): void
    {
        $this->amazonS3ClientMock
            ->expects($this->once())
            ->method('__call')
            ->with('deleteObject', [[
                'Bucket' => 'test-bucket',
                'Key' => 'tests/file.txt',
            ]])
            ->willReturn(new Result([]));

        $result = $this->s3Client->delete('test-bucket', 'tests/file.txt');
        $this->assertTrue($result);
    }

    public function testListReturnsKeys(): void
    {
        $objects = [
            ['Key' => 'tests/file1.txt'],
            ['Key' => 'tests/file2.txt'],
        ];

        $this->amazonS3ClientMock
            ->expects($this->once())
            ->method('getIterator')
            ->with('ListObjects', ['Bucket' => 'test-bucket', 'Prefix' => 'tests'])
            ->willReturn(new \ArrayIterator($objects));

        $list = $this->s3Client->list(['Bucket' => 'test-bucket', 'Prefix' => 'tests']);
        $this->assertCount(2, $list);
        $this->assertEquals('tests/file1.txt', $list[0]);
        $this->assertEquals('tests/file2.txt', $list[1]);
    }
}
