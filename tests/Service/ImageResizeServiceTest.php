<?php

declare(strict_types=1);

namespace UbeeDev\LibBundle\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UbeeDev\LibBundle\Service\ImageResizeService;
use UbeeDev\LibBundle\Service\MediaStorage\MediaStorageInterface;
use UbeeDev\LibBundle\Service\ObjectStorageInterface;

final class ImageResizeServiceTest extends TestCase
{
    private string $tempDir;
    private ImageResizeService $service;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/image_resize_test_'.uniqid();
        mkdir($this->tempDir.'/uploads/game/202602', 0777, true);

        $storageMock = $this->createStub(MediaStorageInterface::class);
        $storageMock->method('isLocal')->willReturn(true);

        $this->service = new ImageResizeService(
            storage: $storageMock,
            objectStorage: $this->createStub(ObjectStorageInterface::class),
            mediaBucket: '',
            publicDir: $this->tempDir,
            outputFormat: 'webp',
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $dir.'/'.$item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    private function createTestImage(int $width, int $height, string $relativePath = 'game/202602/test.webp'): string
    {
        $fullPath = $this->tempDir.'/uploads/'.$relativePath;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $imagick = new \Imagick();
        $imagick->newImage($width, $height, new \ImagickPixel('red'));
        $imagick->setImageFormat('webp');
        $imagick->writeImage($fullPath);
        $imagick->clear();
        $imagick->destroy();

        return $relativePath;
    }

    // --- bucketWidth tests ---

    public function testBucketWidthRoundsUpToNextBucket(): void
    {
        $this->assertSame(320, $this->service->bucketWidth(300));
        $this->assertSame(375, $this->service->bucketWidth(321));
        $this->assertSame(414, $this->service->bucketWidth(400));
        $this->assertSame(430, $this->service->bucketWidth(415));
        $this->assertSame(600, $this->service->bucketWidth(500));
        $this->assertSame(860, $this->service->bucketWidth(700));
    }

    public function testBucketWidthReturnsExactBucket(): void
    {
        $this->assertSame(320, $this->service->bucketWidth(320));
        $this->assertSame(375, $this->service->bucketWidth(375));
        $this->assertSame(860, $this->service->bucketWidth(860));
    }

    public function testBucketWidthCapsAtMaxBucket(): void
    {
        $this->assertSame(860, $this->service->bucketWidth(1000));
        $this->assertSame(860, $this->service->bucketWidth(2000));
    }

    // --- Local resize tests ---

    public function testResizeCreatesResizedImage(): void
    {
        $path = $this->createTestImage(1280, 1280);

        $cachedPath = $this->service->resize(430, $path);

        $this->assertNotNull($cachedPath);
        $this->assertFileExists($cachedPath);

        $resized = new \Imagick($cachedPath);
        $this->assertSame(430, $resized->getImageWidth());
        $this->assertSame(430, $resized->getImageHeight());
        $resized->destroy();
    }

    public function testResizePreservesAspectRatio(): void
    {
        $path = $this->createTestImage(1280, 960);

        $cachedPath = $this->service->resize(430, $path);

        $this->assertNotNull($cachedPath);

        $resized = new \Imagick($cachedPath);
        $this->assertSame(430, $resized->getImageWidth());
        $this->assertSame(323, $resized->getImageHeight());
        $resized->destroy();
    }

    public function testResizeDoesNotUpscale(): void
    {
        $path = $this->createTestImage(300, 300);

        $cachedPath = $this->service->resize(860, $path);

        $this->assertNotNull($cachedPath);

        $resized = new \Imagick($cachedPath);
        $this->assertSame(300, $resized->getImageWidth());
        $this->assertSame(300, $resized->getImageHeight());
        $resized->destroy();
    }

    public function testResizeReturnsNullWhenOriginalNotFound(): void
    {
        $result = $this->service->resize(400, 'game/202602/nonexistent.webp');

        $this->assertNull($result);
    }

    public function testResizeBlocksPathTraversal(): void
    {
        $this->createTestImage(100, 100);

        $result = $this->service->resize(400, '../../../etc/passwd');

        $this->assertNull($result);
    }

    public function testResizeReturnsNullWhenUploadsDirDoesNotExist(): void
    {
        $storageMock = $this->createStub(MediaStorageInterface::class);
        $storageMock->method('isLocal')->willReturn(true);

        $service = new ImageResizeService(
            storage: $storageMock,
            objectStorage: $this->createStub(ObjectStorageInterface::class),
            mediaBucket: '',
            publicDir: '/nonexistent/path',
            outputFormat: 'webp',
        );

        $result = $service->resize(400, 'game/202602/test.webp');

        $this->assertNull($result);
    }

    public function testResizeUsesNearestBucketForCachePath(): void
    {
        $path = $this->createTestImage(1280, 1280);

        $cachedPath = $this->service->resize(400, $path);

        $this->assertNotNull($cachedPath);
        $this->assertStringContainsString('/media/414/', $cachedPath);
    }

    public function testResizeReturnsCachedFileIfExists(): void
    {
        $path = $this->createTestImage(1280, 1280);

        $firstResult = $this->service->resize(430, $path);
        $this->assertNotNull($firstResult);

        $mtime = filemtime($firstResult);
        $this->assertNotFalse($mtime);

        usleep(10000);

        $secondResult = $this->service->resize(430, $path);
        $this->assertSame($firstResult, $secondResult);
        $this->assertSame($mtime, filemtime($secondResult));
    }

    // --- Remote resize tests ---

    public function testRemoteResizeReturnsExistingResizedFromS3(): void
    {
        $storageMock = $this->createStub(MediaStorageInterface::class);
        $storageMock->method('isLocal')->willReturn(false);

        $tempFile = $this->createTempImageFile(430, 430);

        $objectStorageMock = $this->createMock(ObjectStorageInterface::class);
        $objectStorageMock->expects($this->once())
            ->method('exists')
            ->with(
                $this->equalTo('my-bucket'),
                $this->equalTo('public/media/430/game/202602/test.webp'),
            )
            ->willReturn(true);

        $objectStorageMock->expects($this->once())
            ->method('download')
            ->with(
                $this->equalTo('my-bucket'),
                $this->equalTo('public/media/430/game/202602/test.webp'),
                $this->anything(),
                $this->equalTo('test.webp'),
            )
            ->willReturn($tempFile);

        $service = new ImageResizeService(
            storage: $storageMock,
            objectStorage: $objectStorageMock,
            mediaBucket: 'my-bucket',
            publicDir: $this->tempDir,
            outputFormat: 'webp',
        );

        $result = $service->resize(430, 'game/202602/test.webp');

        $this->assertSame($tempFile, $result);
    }

    public function testRemoteResizeDownloadsOriginalAndResizesAndUploads(): void
    {
        $storageMock = $this->createStub(MediaStorageInterface::class);
        $storageMock->method('isLocal')->willReturn(false);

        $originalTempFile = $this->createTempImageFile(1280, 1280);

        $objectStorageMock = $this->createMock(ObjectStorageInterface::class);
        $objectStorageMock->expects($this->once())
            ->method('exists')
            ->with(
                $this->equalTo('my-bucket'),
                $this->equalTo('public/media/430/game/202602/test.webp'),
            )
            ->willReturn(false);

        $objectStorageMock->expects($this->once())
            ->method('download')
            ->with(
                $this->equalTo('my-bucket'),
                $this->equalTo('public/uploads/game/202602/test.webp'),
                $this->anything(),
                $this->equalTo('test.webp'),
            )
            ->willReturn($originalTempFile);

        $objectStorageMock->expects($this->once())
            ->method('upload')
            ->with(
                $this->anything(),
                $this->equalTo('my-bucket'),
                $this->equalTo('public/media/430/game/202602/test.webp'),
            );

        $service = new ImageResizeService(
            storage: $storageMock,
            objectStorage: $objectStorageMock,
            mediaBucket: 'my-bucket',
            publicDir: $this->tempDir,
            outputFormat: 'webp',
        );

        $result = $service->resize(430, 'game/202602/test.webp');

        $this->assertNotNull($result);
        $this->assertFileExists($result);

        $resized = new \Imagick($result);
        $this->assertSame(430, $resized->getImageWidth());
        $resized->destroy();
    }

    public function testRemoteResizeReturnsNullWhenOriginalNotFoundOnS3(): void
    {
        $storageMock = $this->createStub(MediaStorageInterface::class);
        $storageMock->method('isLocal')->willReturn(false);

        $objectStorageMock = $this->createMock(ObjectStorageInterface::class);
        $objectStorageMock->method('exists')->willReturn(false);

        $objectStorageMock->expects($this->once())
            ->method('download')
            ->willThrowException(new \Exception('Object not found'));

        $service = new ImageResizeService(
            storage: $storageMock,
            objectStorage: $objectStorageMock,
            mediaBucket: 'my-bucket',
            publicDir: $this->tempDir,
            outputFormat: 'webp',
        );

        $result = $service->resize(430, 'game/202602/test.webp');

        $this->assertNull($result);
    }

    // --- deleteResized tests ---

    public function testDeleteResizedRemovesAllLocalCachedVersions(): void
    {
        $path = $this->createTestImage(1280, 1280);

        $this->service->resize(320, $path);
        $this->service->resize(430, $path);

        $this->assertFileExists($this->tempDir.'/media/320/'.$path);
        $this->assertFileExists($this->tempDir.'/media/430/'.$path);

        $this->service->deleteResized($path);

        $this->assertFileDoesNotExist($this->tempDir.'/media/320/'.$path);
        $this->assertFileDoesNotExist($this->tempDir.'/media/430/'.$path);
    }

    public function testDeleteResizedRemoteDeletesToAllBucketsOnS3(): void
    {
        $storageMock = $this->createStub(MediaStorageInterface::class);
        $storageMock->method('isLocal')->willReturn(false);

        $objectStorageMock = $this->createMock(ObjectStorageInterface::class);

        $expectedKeys = [];
        foreach ([320, 375, 414, 430, 600, 860] as $width) {
            $expectedKeys[] = 'public/media/'.$width.'/game/202602/test.webp';
        }

        $objectStorageMock->expects($this->exactly(6))
            ->method('delete')
            ->with(
                $this->equalTo('my-bucket'),
                $this->callback(fn (string $key) => in_array($key, $expectedKeys, true)),
            );

        $service = new ImageResizeService(
            storage: $storageMock,
            objectStorage: $objectStorageMock,
            mediaBucket: 'my-bucket',
            publicDir: $this->tempDir,
            outputFormat: 'webp',
        );

        $service->deleteResized('game/202602/test.webp');
    }

    // --- Helper ---

    private function createTempImageFile(int $width, int $height): string
    {
        $tempFile = sys_get_temp_dir().'/resize_test_'.uniqid().'.webp';
        $imagick = new \Imagick();
        $imagick->newImage($width, $height, new \ImagickPixel('red'));
        $imagick->setImageFormat('webp');
        $imagick->writeImage($tempFile);
        $imagick->destroy();

        return $tempFile;
    }
}
