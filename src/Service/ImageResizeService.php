<?php

declare(strict_types=1);

namespace UbeeDev\LibBundle\Service;

use UbeeDev\LibBundle\Service\MediaStorage\MediaStorageInterface;

class ImageResizeService
{
    private const int QUALITY = 100;
    private const array WIDTH_BUCKETS = [320, 375, 414, 430, 600, 860, 1290];

    /** @var int[] */
    private readonly array $widthBuckets;

    /**
     * @param int[] $widthBuckets Overrides the default width buckets when provided
     */
    public function __construct(
        private readonly MediaStorageInterface $storage,
        private readonly ObjectStorageInterface $objectStorage,
        private readonly string $mediaBucket,
        private readonly string $publicDir,
        private readonly string $outputFormat = 'webp',
        array $widthBuckets = [],
    ) {
        $buckets = [] !== $widthBuckets ? $widthBuckets : self::WIDTH_BUCKETS;
        sort($buckets);
        $this->widthBuckets = $buckets;
    }

    public function bucketWidth(int $width): int
    {
        foreach ($this->widthBuckets as $bucket) {
            if ($width <= $bucket) {
                return $bucket;
            }
        }

        return $this->widthBuckets[array_key_last($this->widthBuckets)];
    }

    public function resize(int $width, string $relativePath): ?string
    {
        $width = $this->bucketWidth($width);

        if ($this->storage->isLocal()) {
            return $this->resizeLocal($width, $relativePath);
        }

        return $this->resizeRemote($width, $relativePath);
    }

    public function deleteResized(string $relativePath): void
    {
        if ($this->storage->isLocal()) {
            $this->deleteResizedLocal($relativePath);

            return;
        }

        $this->deleteResizedRemote($relativePath);
    }

    private function resizeLocal(int $width, string $relativePath): ?string
    {
        $originalPath = $this->resolveOriginalPath($relativePath);
        if (null === $originalPath) {
            return null;
        }

        $cachePath = $this->publicDir.'/media/'.$width.'/'.$relativePath;

        if (file_exists($cachePath)) {
            return $cachePath;
        }

        return $this->processResize($originalPath, $cachePath, $width);
    }

    private function resizeRemote(int $width, string $relativePath): ?string
    {
        $resizedKey = 'public/media/'.$width.'/'.$relativePath;

        if ($this->objectStorage->exists($this->mediaBucket, $resizedKey)) {
            return $this->objectStorage->download(
                $this->mediaBucket,
                $resizedKey,
                sys_get_temp_dir(),
                basename($relativePath),
            );
        }

        $originalKey = 'public/uploads/'.$relativePath;

        try {
            $originalTempPath = $this->objectStorage->download(
                $this->mediaBucket,
                $originalKey,
                sys_get_temp_dir(),
                basename($relativePath),
            );
        } catch (\Exception) {
            return null;
        }

        $resizedTempPath = sys_get_temp_dir().'/resized_'.uniqid().'_'.basename($relativePath);
        $result = $this->processResize($originalTempPath, $resizedTempPath, $width);

        if (null === $result) {
            return null;
        }

        $this->objectStorage->upload($resizedTempPath, $this->mediaBucket, $resizedKey);

        return $resizedTempPath;
    }

    private function processResize(string $sourcePath, string $destPath, int $width): ?string
    {
        $cacheDir = dirname($destPath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $imagick = new \Imagick($sourcePath);
        $origWidth = $imagick->getImageWidth();

        if ($width >= $origWidth) {
            copy($sourcePath, $destPath);
            $imagick->clear();
            $imagick->destroy();

            return $destPath;
        }

        $imagick->resizeImage($width, 0, \Imagick::FILTER_LANCZOS, 1);
        $imagick->setImageFormat($this->outputFormat);
        $imagick->setImageCompressionQuality(self::QUALITY);
        $imagick->writeImage($destPath);
        $imagick->clear();
        $imagick->destroy();

        return $destPath;
    }

    private function resolveOriginalPath(string $relativePath): ?string
    {
        $uploadsDir = realpath($this->publicDir.'/uploads');
        if (false === $uploadsDir) {
            return null;
        }

        $originalPath = realpath($this->publicDir.'/uploads/'.$relativePath);
        if (false === $originalPath) {
            return null;
        }

        if (!str_starts_with($originalPath, $uploadsDir.'/')) {
            return null;
        }

        return $originalPath;
    }

    private function deleteResizedLocal(string $relativePath): void
    {
        foreach ($this->widthBuckets as $width) {
            $cachePath = $this->publicDir.'/media/'.$width.'/'.$relativePath;
            if (file_exists($cachePath)) {
                unlink($cachePath);
            }
        }
    }

    private function deleteResizedRemote(string $relativePath): void
    {
        foreach ($this->widthBuckets as $width) {
            $this->objectStorage->delete(
                $this->mediaBucket,
                'public/media/'.$width.'/'.$relativePath,
            );
        }
    }
}
