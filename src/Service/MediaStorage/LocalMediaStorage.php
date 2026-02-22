<?php

namespace UbeeDev\LibBundle\Service\MediaStorage;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use UbeeDev\LibBundle\Entity\Media;

class LocalMediaStorage implements MediaStorageInterface
{
    private Filesystem $fileSystem;
    private string $projectDir;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->fileSystem = new Filesystem();
        $this->projectDir = $parameterBag->get('kernel.project_dir');
    }

    public function store(string $localFilePath, Media $media): void
    {
        $targetPath = $this->getAbsolutePath($media);
        $this->fileSystem->mkdir(dirname($targetPath));
        $this->fileSystem->copy($localFilePath, $targetPath, overwriteNewerFiles: true);
    }

    public function delete(Media $media): void
    {
        $this->fileSystem->remove($this->getAbsolutePath($media));
    }

    public function getUrl(Media $media): string
    {
        if ($media->isPrivate()) {
            throw new \RuntimeException('Cannot get web path for private media');
        }

        return '/' . $media->getStoragePath();
    }

    public function getAbsolutePath(Media $media): string
    {
        $prefix = $media->isPrivate() ? 'private' : 'public';

        return $this->projectDir . '/' . $prefix . '/' . $media->getStoragePath();
    }

    public function getLocalPath(Media $media): string
    {
        return $this->getAbsolutePath($media);
    }

    public function isLocal(): bool
    {
        return true;
    }
}
