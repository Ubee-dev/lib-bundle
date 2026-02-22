<?php

namespace UbeeDev\LibBundle\Service;

use UbeeDev\LibBundle\Entity\Media;
use UbeeDev\LibBundle\Exception\InvalidArgumentException;
use UbeeDev\LibBundle\Service\MediaStorage\MediaStorageInterface;
use UbeeDev\LibBundle\Validator\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

class MediaManager
{
    private Filesystem $fileSystem;
    private string $uploadDir;
    private ?string $mediaClassName;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
        private readonly MediaStorageInterface $storage,
    )
    {
        $this->fileSystem = new Filesystem();
        $this->uploadDir = $this->parameterBag->get('upload_dir');
        $this->mediaClassName = $this->parameterBag->get('mediaClassName');
    }

    public function deleteAsset(Media $media): void
    {
        $this->storage->delete($media);
    }

    public function delete(Media $media): void
    {
        $this->entityManager->remove($media);
        $this->entityManager->flush();
    }

    public function getWebPath(Media $media): string
    {
        return $this->storage->getUrl($media);
    }

    public function getRelativePath(Media $media): string
    {
        return $this->storage->getAbsolutePath($media);
    }

    /**
     * @throws Exception
     */
    public function upload(
        File $uploadedFile,
        string $context,
        bool $private = false,
        bool $andFlush = true
    ): Media
    {
        $newFilename = $this->generateNameForFile($uploadedFile);
        $mimeType = $uploadedFile->getMimeType();
        $size = $uploadedFile->getSize();

        $media = $this->buildMedia(
            fileName: $newFilename,
            fileSize: $size,
            context: $context,
            contentType: $mimeType,
            isPrivate: $private,
        );

        $tempDir = sys_get_temp_dir() . '/media-upload-' . uniqid();
        $this->fileSystem->mkdir($tempDir);
        $uploadedFile->move($tempDir, $newFilename);
        $filePath = $tempDir . '/' . $newFilename;

        if ($this->isConvertibleToWebp($mimeType)) {
            $webpPath = $this->convertToWebp($filePath);
            if ($webpPath !== null) {
                $this->fileSystem->remove($filePath);
                $newFilename = pathinfo($newFilename, PATHINFO_FILENAME) . '.webp';
                $media->setFilename($newFilename);
                $media->setContentType('image/webp');
                $media->setContentSize(filesize($webpPath));
                $filePath = $webpPath;
            }
        }

        $this->extractImageDimensions($media, $filePath);

        $media->setStoragePath($this->buildStoragePath($media));
        $this->storage->store($filePath, $media);
        $this->fileSystem->remove($tempDir);

        if ($andFlush) {
            $this->entityManager->persist($media);

            try {
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $this->storage->delete($media);
                throw $e;
            }
        }

        return $media;
    }

    public function updateImageDimensions(Media $media): bool
    {
        if (!$media->isImage()) {
            return false;
        }

        $localPath = $this->storage->getLocalPath($media);
        if (!file_exists($localPath)) {
            return false;
        }

        $result = $this->extractImageDimensions($media, $localPath);

        if (!$this->storage->isLocal()) {
            $this->fileSystem->remove($localPath);
        }

        return $result;
    }

    private function extractImageDimensions(Media $media, string $filePath): bool
    {
        if (!$media->isImage() || !file_exists($filePath)) {
            return false;
        }

        try {
            $imageInfo = getimagesize($filePath);
            if ($imageInfo !== false && isset($imageInfo[0], $imageInfo[1])) {
                $media->setWidth($imageInfo[0]);
                $media->setHeight($imageInfo[1]);
                return true;
            }
        } catch (\Exception $e) {
            error_log("Failed to extract image dimensions for {$filePath}: " . $e->getMessage());
        }

        return false;
    }

    private function isConvertibleToWebp(string $mimeType): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/png', 'image/jpg'], true);
    }

    private function convertToWebp(string $sourcePath, int $quality = 85): ?string
    {
        if (!class_exists(\Imagick::class)) {
            error_log('Imagick extension not available for WebP conversion');
            return null;
        }

        try {
            $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $sourcePath);

            $imagick = new \Imagick($sourcePath);
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality($quality);

            $imagick->setOption('webp:lossless', 'false');
            $imagick->setOption('webp:method', '6');

            $imagick->writeImage($webpPath);
            $imagick->destroy();

            return $webpPath;
        } catch (\Exception $e) {
            error_log("Failed to convert image to WebP: " . $e->getMessage());
            return null;
        }
    }

    public function convertMediaToWebp(Media $media): bool
    {
        if (!$this->isConvertibleToWebp($media->getContentType())) {
            return false;
        }

        $localPath = $this->storage->getLocalPath($media);
        if (!file_exists($localPath)) {
            return false;
        }

        $webpPath = $this->convertToWebp($localPath);
        if ($webpPath === null) {
            return false;
        }

        $this->storage->delete($media);

        $newFilename = preg_replace('/\.(jpe?g|png)$/i', '.webp', $media->getFilename());
        $media->setFilename($newFilename);
        $media->setContentType('image/webp');
        $media->setContentSize(filesize($webpPath));
        $media->setStoragePath($this->buildStoragePath($media));

        $this->storage->store($webpPath, $media);

        if (!$this->storage->isLocal()) {
            $this->fileSystem->remove($localPath);
            $this->fileSystem->remove($webpPath);
        }

        return true;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function createPdfFromHtml(string $htmlContent, string $context, bool $private = false): Media
    {
        $fileName = $this->generateNameForContent($htmlContent, 'pdf');

        $media = new $this->mediaClassName();
        $media->setFilename($fileName)
            ->setContext($context)
            ->setContentType('application/pdf')
            ->setPrivate($private);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true)
            ->set('isPhpEnabled', true)
            ->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait')
            ->render();

        $tempDir = sys_get_temp_dir() . '/media-pdf-' . uniqid();
        $this->fileSystem->mkdir($tempDir);
        $filePath = $tempDir . '/' . $fileName;

        if (file_put_contents($filePath, $dompdf->output()) === false) {
            exit('Failed to write the PDF to file.');
        }

        $media->setContentSize(filesize($filePath));

        $this->validator
            ->setMessage('Invalid media input')
            ->addValidation($media)
            ->validate();

        $media->setStoragePath($this->buildStoragePath($media));
        $this->storage->store($filePath, $media);
        $this->fileSystem->remove($tempDir);

        $this->entityManager->persist($media);
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->storage->delete($media);
            throw $e;
        }

        return $media;
    }

    public function getStorage(): MediaStorageInterface
    {
        return $this->storage;
    }

    private function buildStoragePath(Media $media): string
    {
        $uploadDir = ltrim($this->uploadDir, '/');

        return $uploadDir . '/' . $media->getContext() . '/' . $media->getCreatedAt()->format('Ym') . '/' . $media->getFilename();
    }

    private function generateNameForFile(File $file): string
    {
        return sha1_file($file) . uniqid() . '.' . $file->guessExtension();
    }

    private function generateNameForContent(string $content, string $extension): string
    {
        return sha1($content) . uniqid() . '.' . $extension;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function buildMedia(
        string $fileName,
        string $fileSize,
        string $context,
        string $contentType,
        bool $isPrivate,
    ): Media
    {
        $media = new $this->mediaClassName();
        $media->setFilename($fileName);
        $media->setContentSize($fileSize);
        $media->setContext($context);
        $media->setContentType($contentType);
        $media->setPrivate($isPrivate);

        $this->validator
            ->setMessage('Invalid media input')
            ->addValidation($media)
            ->validate();

        return $media;
    }
}
