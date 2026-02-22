<?php

namespace UbeeDev\LibBundle\Tests\Service\MediaStorage;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UbeeDev\LibBundle\Entity\Media;
use UbeeDev\LibBundle\Service\MediaStorage\ObjectStorageMediaStorage;
use UbeeDev\LibBundle\Service\ObjectStorageInterface;

class ObjectStorageMediaStorageTest extends TestCase
{
    private string $bucket = 'media-bucket';
    private string $cdnBaseUrl = 'https://cdn.example.com';

    public function testStoreUploadsPublicFile(): void
    {
        $mock = $this->createMock(ObjectStorageInterface::class);
        $storage = $this->createStorage($mock);
        $media = $this->createMediaStub('uploads/avatars/202603/abc123.webp', false);

        $mock->expects($this->once())
            ->method('upload')
            ->with(
                $this->equalTo('/tmp/source.webp'),
                $this->equalTo($this->bucket),
                $this->equalTo('public/uploads/avatars/202603/abc123.webp')
            );

        $storage->store('/tmp/source.webp', $media);
    }

    public function testStoreUploadsPrivateFile(): void
    {
        $mock = $this->createMock(ObjectStorageInterface::class);
        $storage = $this->createStorage($mock);
        $media = $this->createMediaStub('uploads/invoices/202603/invoice.pdf', true);

        $mock->expects($this->once())
            ->method('upload')
            ->with(
                $this->equalTo('/tmp/invoice.pdf'),
                $this->equalTo($this->bucket),
                $this->equalTo('private/uploads/invoices/202603/invoice.pdf')
            );

        $storage->store('/tmp/invoice.pdf', $media);
    }

    public function testDeleteRemovesObject(): void
    {
        $mock = $this->createMock(ObjectStorageInterface::class);
        $storage = $this->createStorage($mock);
        $media = $this->createMediaStub('uploads/avatars/202603/abc123.webp', false);

        $mock->expects($this->once())
            ->method('delete')
            ->with(
                $this->equalTo($this->bucket),
                $this->equalTo('public/uploads/avatars/202603/abc123.webp')
            );

        $storage->delete($media);
    }

    public function testGetUrlReturnsCdnUrlForPublicMedia(): void
    {
        $storage = $this->createStorage($this->createStub(ObjectStorageInterface::class));
        $media = $this->createMediaStub('uploads/avatars/202603/abc123.webp', false);

        $url = $storage->getUrl($media);

        $this->assertEquals('https://cdn.example.com/public/uploads/avatars/202603/abc123.webp', $url);
    }

    public function testGetUrlWithoutCdnUsesObjectStorageGet(): void
    {
        $mock = $this->createMock(ObjectStorageInterface::class);
        $storage = $this->createStorage($mock, cdnBaseUrl: null);
        $media = $this->createMediaStub('uploads/avatars/202603/abc123.webp', false);

        $mock->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo($this->bucket),
                $this->equalTo('public/uploads/avatars/202603/abc123.webp')
            )
            ->willReturn('https://media-bucket.s3.amazonaws.com/public/uploads/avatars/202603/abc123.webp');

        $url = $storage->getUrl($media);

        $this->assertEquals('https://media-bucket.s3.amazonaws.com/public/uploads/avatars/202603/abc123.webp', $url);
    }

    public function testGetUrlReturnsPresignedUrlForPrivateMedia(): void
    {
        $mock = $this->createMock(ObjectStorageInterface::class);
        $storage = $this->createStorage($mock);
        $media = $this->createMediaStub('uploads/invoices/202603/invoice.pdf', true);

        $mock->expects($this->once())
            ->method('getPresignedUrl')
            ->with(
                $this->equalTo($this->bucket),
                $this->equalTo('private/uploads/invoices/202603/invoice.pdf'),
                $this->equalTo(3600)
            )
            ->willReturn('https://media-bucket.s3.amazonaws.com/private/uploads/invoices/202603/invoice.pdf?X-Amz-Signature=abc');

        $url = $storage->getUrl($media);

        $this->assertEquals(
            'https://media-bucket.s3.amazonaws.com/private/uploads/invoices/202603/invoice.pdf?X-Amz-Signature=abc',
            $url
        );
    }

    public function testGetUrlReturnsPresignedUrlWithCustomExpiry(): void
    {
        $mock = $this->createMock(ObjectStorageInterface::class);
        $storage = $this->createStorage($mock, presignedUrlExpiry: 7200);
        $media = $this->createMediaStub('uploads/invoices/202603/invoice.pdf', true);

        $mock->expects($this->once())
            ->method('getPresignedUrl')
            ->with(
                $this->equalTo($this->bucket),
                $this->equalTo('private/uploads/invoices/202603/invoice.pdf'),
                $this->equalTo(7200)
            )
            ->willReturn('https://presigned-url');

        $url = $storage->getUrl($media);

        $this->assertEquals('https://presigned-url', $url);
    }

    public function testGetAbsolutePathThrows(): void
    {
        $storage = $this->createStorage($this->createStub(ObjectStorageInterface::class));
        $media = $this->createMediaStub('uploads/avatars/202603/abc123.webp', false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot get absolute path for object storage media');
        $storage->getAbsolutePath($media);
    }

    public function testGetLocalPathDownloadsToTemp(): void
    {
        $mock = $this->createMock(ObjectStorageInterface::class);
        $storage = $this->createStorage($mock);
        $media = $this->createMediaStub('uploads/avatars/202603/abc123.webp', false);

        $mock->expects($this->once())
            ->method('download')
            ->with(
                $this->equalTo($this->bucket),
                $this->equalTo('public/uploads/avatars/202603/abc123.webp'),
                $this->equalTo(sys_get_temp_dir()),
                $this->equalTo('abc123.webp')
            )
            ->willReturn(sys_get_temp_dir() . '/abc123.webp');

        $path = $storage->getLocalPath($media);

        $this->assertEquals(sys_get_temp_dir() . '/abc123.webp', $path);
    }

    public function testIsLocalReturnsFalse(): void
    {
        $storage = $this->createStorage($this->createStub(ObjectStorageInterface::class));

        $this->assertFalse($storage->isLocal());
    }

    private function createStorage(
        ObjectStorageInterface $objectStorage,
        ?string $cdnBaseUrl = 'https://cdn.example.com',
        int $presignedUrlExpiry = 3600,
    ): ObjectStorageMediaStorage {
        return new ObjectStorageMediaStorage(
            objectStorage: $objectStorage,
            bucket: $this->bucket,
            cdnBaseUrl: $cdnBaseUrl,
            presignedUrlExpiry: $presignedUrlExpiry,
        );
    }

    private function createMediaStub(string $storagePath, bool $isPrivate): Media
    {
        $media = $this->createStub(Media::class);
        $media->method('getStoragePath')->willReturn($storagePath);
        $media->method('isPrivate')->willReturn($isPrivate);

        return $media;
    }
}
