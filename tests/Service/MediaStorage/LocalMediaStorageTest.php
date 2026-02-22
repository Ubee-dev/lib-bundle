<?php

namespace UbeeDev\LibBundle\Tests\Service\MediaStorage;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use UbeeDev\LibBundle\Entity\Media;
use UbeeDev\LibBundle\Service\MediaStorage\LocalMediaStorage;

class LocalMediaStorageTest extends TestCase
{
    private LocalMediaStorage $storage;
    private string $projectDir;
    private Filesystem $fileSystem;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/local-media-storage-test-' . uniqid();
        $this->fileSystem = new Filesystem();
        $this->fileSystem->mkdir($this->projectDir);

        $parameterBag = $this->createStub(ParameterBagInterface::class);
        $parameterBag->method('get')
            ->willReturnMap([
                ['kernel.project_dir', $this->projectDir],
            ]);

        $this->storage = new LocalMediaStorage($parameterBag);
    }

    protected function tearDown(): void
    {
        $this->fileSystem->remove($this->projectDir);
    }

    public function testStorePublicFile(): void
    {
        $sourceFile = $this->createTempFile('hello world');
        $media = $this->createMediaStub('uploads/avatars/202603/abc123.pdf', false);

        $this->storage->store($sourceFile, $media);

        $expectedPath = $this->projectDir . '/public/uploads/avatars/202603/abc123.pdf';
        $this->assertFileExists($expectedPath);
        $this->assertEquals('hello world', file_get_contents($expectedPath));
    }

    public function testStorePrivateFile(): void
    {
        $sourceFile = $this->createTempFile('secret content');
        $media = $this->createMediaStub('uploads/invoices/202603/invoice.pdf', true);

        $this->storage->store($sourceFile, $media);

        $expectedPath = $this->projectDir . '/private/uploads/invoices/202603/invoice.pdf';
        $this->assertFileExists($expectedPath);
        $this->assertEquals('secret content', file_get_contents($expectedPath));
    }

    public function testDeletePublicFile(): void
    {
        $filePath = $this->projectDir . '/public/uploads/avatars/202603/abc123.pdf';
        $this->fileSystem->mkdir(dirname($filePath));
        $this->fileSystem->dumpFile($filePath, 'content');

        $media = $this->createMediaStub('uploads/avatars/202603/abc123.pdf', false);

        $this->storage->delete($media);

        $this->assertFileDoesNotExist($filePath);
    }

    public function testDeletePrivateFile(): void
    {
        $filePath = $this->projectDir . '/private/uploads/invoices/202603/invoice.pdf';
        $this->fileSystem->mkdir(dirname($filePath));
        $this->fileSystem->dumpFile($filePath, 'content');

        $media = $this->createMediaStub('uploads/invoices/202603/invoice.pdf', true);

        $this->storage->delete($media);

        $this->assertFileDoesNotExist($filePath);
    }

    public function testGetUrlReturnsWebPathForPublicMedia(): void
    {
        $media = $this->createMediaStub('uploads/avatars/202603/abc123.webp', false);

        $url = $this->storage->getUrl($media);

        $this->assertEquals('/uploads/avatars/202603/abc123.webp', $url);
    }

    public function testGetUrlThrowsForPrivateMedia(): void
    {
        $media = $this->createMediaStub('uploads/invoices/202603/invoice.pdf', true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot get web path for private media');
        $this->storage->getUrl($media);
    }

    public function testGetAbsolutePathForPublicMedia(): void
    {
        $media = $this->createMediaStub('uploads/avatars/202603/abc123.webp', false);

        $path = $this->storage->getAbsolutePath($media);

        $this->assertEquals($this->projectDir . '/public/uploads/avatars/202603/abc123.webp', $path);
    }

    public function testGetAbsolutePathForPrivateMedia(): void
    {
        $media = $this->createMediaStub('uploads/invoices/202603/invoice.pdf', true);

        $path = $this->storage->getAbsolutePath($media);

        $this->assertEquals($this->projectDir . '/private/uploads/invoices/202603/invoice.pdf', $path);
    }

    public function testGetLocalPathReturnsSameAsAbsolutePath(): void
    {
        $media = $this->createMediaStub('uploads/avatars/202603/abc123.webp', false);

        $this->assertEquals(
            $this->storage->getAbsolutePath($media),
            $this->storage->getLocalPath($media)
        );
    }

    public function testIsLocalReturnsTrue(): void
    {
        $this->assertTrue($this->storage->isLocal());
    }

    private function createMediaStub(string $storagePath, bool $isPrivate): Media
    {
        $media = $this->createStub(Media::class);
        $media->method('getStoragePath')->willReturn($storagePath);
        $media->method('isPrivate')->willReturn($isPrivate);

        return $media;
    }

    private function createTempFile(string $content): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'media-test-');
        file_put_contents($tempFile, $content);

        return $tempFile;
    }
}
