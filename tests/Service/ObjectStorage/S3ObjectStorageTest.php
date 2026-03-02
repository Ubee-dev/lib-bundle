<?php

namespace UbeeDev\LibBundle\Tests\Service\ObjectStorage;

use Aws\Result;
use Aws\S3\S3Client as AmazonS3Client;
use PHPUnit\Framework\TestCase;
use UbeeDev\LibBundle\Service\ObjectStorage\S3ObjectStorage;

class S3ObjectStorageTest extends TestCase
{
    private S3ObjectStorage $storage;
    private AmazonS3Client $amazonS3ClientMock;

    protected function setUp(): void
    {
        $this->amazonS3ClientMock = $this->createMock(AmazonS3Client::class);

        $this->storage = (new \ReflectionClass(S3ObjectStorage::class))->newInstanceWithoutConstructor();
        $reflection = new \ReflectionProperty(S3ObjectStorage::class, 's3Client');
        $reflection->setValue($this->storage, $this->amazonS3ClientMock);
    }

    public function testUploadPublicSetsAclPublicRead(): void
    {
        $result = new Result(['ObjectURL' => 'https://bucket.s3.eu-west-1.amazonaws.com/tests/file.txt']);

        $this->amazonS3ClientMock
            ->expects($this->once())
            ->method('__call')
            ->with('putObject', [[
                'Bucket' => 'test-bucket',
                'Key' => 'tests/file.txt',
                'SourceFile' => '/tmp/file.txt',
                'ACL' => 'public-read',
            ]])
            ->willReturn($result);

        $url = $this->storage->upload('/tmp/file.txt', 'test-bucket', 'tests/file.txt');
        $this->assertEquals('https://bucket.s3.eu-west-1.amazonaws.com/tests/file.txt', $url);
    }

    public function testUploadPrivateDoesNotSetAcl(): void
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

        $url = $this->storage->upload('/tmp/file.txt', 'test-bucket', 'tests/file.txt', private: true);
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

        $uri = $this->storage->get('test-bucket', 'tests/file.txt');
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

        $filePath = $this->storage->download('test-bucket', 'tests/file.txt', '/tmp/test', 'test.sql');
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

        $result = $this->storage->delete('test-bucket', 'tests/file.txt');
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

        $list = $this->storage->list('test-bucket', 'tests');
        $this->assertCount(2, $list);
        $this->assertEquals('tests/file1.txt', $list[0]);
        $this->assertEquals('tests/file2.txt', $list[1]);
    }

    public function testListWithoutPrefixReturnsAllKeys(): void
    {
        $objects = [
            ['Key' => 'file1.txt'],
            ['Key' => 'file2.txt'],
        ];

        $this->amazonS3ClientMock
            ->expects($this->once())
            ->method('getIterator')
            ->with('ListObjects', ['Bucket' => 'test-bucket'])
            ->willReturn(new \ArrayIterator($objects));

        $list = $this->storage->list('test-bucket');
        $this->assertCount(2, $list);
    }

    public function testGetPresignedUrlReturnsUrl(): void
    {
        $commandStub = $this->createStub(\Aws\CommandInterface::class);
        $requestMock = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $uriStub = $this->createStub(\Psr\Http\Message\UriInterface::class);

        $this->amazonS3ClientMock
            ->expects($this->once())
            ->method('getCommand')
            ->with(
                $this->equalTo('GetObject'),
                $this->equalTo([
                    'Bucket' => 'test-bucket',
                    'Key' => 'tests/file.txt',
                ])
            )
            ->willReturn($commandStub);

        $this->amazonS3ClientMock
            ->expects($this->once())
            ->method('createPresignedRequest')
            ->with(
                $this->identicalTo($commandStub),
                $this->equalTo('+3600 seconds')
            )
            ->willReturn($requestMock);

        $requestMock
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uriStub);

        $uriStub
            ->method('__toString')
            ->willReturn('https://test-bucket.s3.amazonaws.com/tests/file.txt?X-Amz-Signature=abc123');

        $url = $this->storage->getPresignedUrl('test-bucket', 'tests/file.txt');
        $this->assertEquals('https://test-bucket.s3.amazonaws.com/tests/file.txt?X-Amz-Signature=abc123', $url);
    }

    public function testGetPresignedUrlWithCustomExpiry(): void
    {
        $commandStub = $this->createStub(\Aws\CommandInterface::class);
        $requestMock = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $uriStub = $this->createStub(\Psr\Http\Message\UriInterface::class);

        $this->amazonS3ClientMock
            ->expects($this->once())
            ->method('getCommand')
            ->with(
                $this->equalTo('GetObject'),
                $this->equalTo([
                    'Bucket' => 'test-bucket',
                    'Key' => 'tests/file.txt',
                ])
            )
            ->willReturn($commandStub);

        $this->amazonS3ClientMock
            ->expects($this->once())
            ->method('createPresignedRequest')
            ->with(
                $this->identicalTo($commandStub),
                $this->equalTo('+7200 seconds')
            )
            ->willReturn($requestMock);

        $requestMock
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uriStub);

        $uriStub
            ->method('__toString')
            ->willReturn('https://test-bucket.s3.amazonaws.com/tests/file.txt?X-Amz-Signature=def456');

        $url = $this->storage->getPresignedUrl('test-bucket', 'tests/file.txt', 7200);
        $this->assertEquals('https://test-bucket.s3.amazonaws.com/tests/file.txt?X-Amz-Signature=def456', $url);
    }

    public function testExistsReturnsTrueWhenObjectExists(): void
    {
        $this->amazonS3ClientMock
            ->expects($this->once())
            ->method('doesObjectExist')
            ->with(
                $this->equalTo('test-bucket'),
                $this->equalTo('tests/file.txt'),
            )
            ->willReturn(true);

        $this->assertTrue($this->storage->exists('test-bucket', 'tests/file.txt'));
    }

    public function testExistsReturnsFalseWhenObjectDoesNotExist(): void
    {
        $this->amazonS3ClientMock
            ->expects($this->once())
            ->method('doesObjectExist')
            ->with(
                $this->equalTo('test-bucket'),
                $this->equalTo('tests/nonexistent.txt'),
            )
            ->willReturn(false);

        $this->assertFalse($this->storage->exists('test-bucket', 'tests/nonexistent.txt'));
    }
}
