<?php

namespace UbeeDev\LibBundle\Service\MediaStorage;

use UbeeDev\LibBundle\Entity\Media;
use UbeeDev\LibBundle\Service\ObjectStorageInterface;

class ObjectStorageMediaStorage implements MediaStorageInterface
{
    public function __construct(
        private readonly ObjectStorageInterface $objectStorage,
        private readonly string $bucket,
        private readonly ?string $cdnBaseUrl = null,
        private readonly int $presignedUrlExpiry = 3600,
    ) {
    }

    public function store(string $localFilePath, Media $media): void
    {
        $this->objectStorage->upload($localFilePath, $this->bucket, $this->buildRemoteKey($media));
    }

    public function delete(Media $media): void
    {
        $this->objectStorage->delete($this->bucket, $this->buildRemoteKey($media));
    }

    public function getUrl(Media $media): string
    {
        if ($media->isPrivate()) {
            return $this->objectStorage->getPresignedUrl(
                $this->bucket,
                $this->buildRemoteKey($media),
                $this->presignedUrlExpiry
            );
        }

        $remoteKey = $this->buildRemoteKey($media);

        if ($this->cdnBaseUrl) {
            return $this->cdnBaseUrl . '/' . $remoteKey;
        }

        return $this->objectStorage->get($this->bucket, $remoteKey);
    }

    public function getAbsolutePath(Media $media): string
    {
        throw new \RuntimeException('Cannot get absolute path for object storage media');
    }

    public function getLocalPath(Media $media): string
    {
        return $this->objectStorage->download(
            $this->bucket,
            $this->buildRemoteKey($media),
            sys_get_temp_dir(),
            basename($media->getStoragePath())
        );
    }

    public function isLocal(): bool
    {
        return false;
    }

    private function buildRemoteKey(Media $media): string
    {
        $prefix = $media->isPrivate() ? 'private' : 'public';

        return $prefix . '/' . $media->getStoragePath();
    }
}
