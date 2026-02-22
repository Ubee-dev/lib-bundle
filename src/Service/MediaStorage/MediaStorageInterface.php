<?php

namespace UbeeDev\LibBundle\Service\MediaStorage;

use UbeeDev\LibBundle\Entity\Media;

interface MediaStorageInterface
{
    public function store(string $localFilePath, Media $media): void;

    public function delete(Media $media): void;

    public function getUrl(Media $media): string;

    public function getAbsolutePath(Media $media): string;

    public function getLocalPath(Media $media): string;

    public function isLocal(): bool;
}
