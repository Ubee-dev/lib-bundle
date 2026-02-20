<?php

namespace UbeeDev\LibBundle\Service;

use UbeeDev\LibBundle\Entity\DateTime;
use UbeeDev\LibBundle\Entity\Media;
use UbeeDev\LibBundle\Exception\InvalidArgumentException;
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
        private readonly Validator $validator
    )
    {
        $this->fileSystem = new Filesystem();
        $this->uploadDir = $this->parameterBag->get('upload_dir');
        $this->mediaClassName = $this->parameterBag->get('mediaClassName');
    }

    public function deleteAsset(Media $media): void
    {
        $this->fileSystem->remove($this->getRelativePath($media));
    }

    public function delete(Media $media): void
    {
        $this->entityManager->remove($media);
        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
    public function getWebPath(Media $media): string
    {
        if($media->isPrivate()) {
            throw new \RuntimeException('Cannot get web path for private media');
        }
        $createdAt = $media->getCreatedAt();
        return $this->getContextPath($media).'/'.$createdAt->format('Ym').'/'.$media->getFilename();
    }

    public function getRelativePath(Media $media): string
    {
        $createdAt = $media->getCreatedAt();
        return $this->parameterBag->get('kernel.project_dir').'/'.($media->isPrivate() ? $this->getPrivatePath() : $this->getPublicPath()).$this->getContextPath($media).'/'.$createdAt->format('Ym').'/'.$media->getFilename();
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

        $media =  $this->buildMedia(
            fileName: $newFilename,
            fileSize: $size,
            context: $context,
            contentType: $mimeType,
            isPrivate: $private,
        );

        $uploadDirectory = $this->getUploadDirectoryForContext($context, $private).'/'.$media->getCreatedAt()->format('Ym');
        $this->fileSystem->mkdir($uploadDirectory);
        $uploadedFile->move(
            $uploadDirectory,
            $newFilename
        );

        $filePath = $uploadDirectory.'/'.$newFilename;

        // Convertir en WebP si c'est une image convertible (JPEG/PNG)
        if ($this->isConvertibleToWebp($mimeType)) {
            $webpPath = $this->convertToWebp($filePath);
            if ($webpPath !== null) {
                // Supprimer l'original et mettre à jour le média
                $this->fileSystem->remove($filePath);
                $newFilename = pathinfo($newFilename, PATHINFO_FILENAME) . '.webp';
                $media->setFilename($newFilename);
                $media->setContentType('image/webp');
                $media->setContentSize(filesize($webpPath));
                $filePath = $webpPath;
            }
        }

        // Extraire les dimensions si c'est une image
        $this->extractImageDimensions($media, $filePath);

        if($andFlush) {
            $this->entityManager->persist($media);

            try {
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $this->deleteAsset($media);
                throw $e;
            }

        }

        return $media;
    }

    /**
     * Met à jour les dimensions d'un média existant
     */
    public function updateImageDimensions(Media $media): bool
    {
        if (!$media->isImage()) {
            return false;
        }

        $filePath = $this->getRelativePath($media);
        if (!file_exists($filePath)) {
            return false;
        }

        return $this->extractImageDimensions($media, $filePath);
    }

    /**
     * Extrait et définit les dimensions d'une image
     */
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
            // Log l'erreur si nécessaire, mais ne pas faire échouer l'upload
            error_log("Failed to extract image dimensions for {$filePath}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Vérifie si le type MIME peut être converti en WebP
     */
    private function isConvertibleToWebp(string $mimeType): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/png', 'image/jpg'], true);
    }

    /**
     * Convertit une image en WebP avec Imagick
     * @return string|null Le chemin du fichier WebP ou null si échec
     */
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

            // Optimisations WebP
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

    /**
     * Convertit un média existant en WebP (pour migration)
     * @return bool True si la conversion a réussi
     */
    public function convertMediaToWebp(Media $media): bool
    {
        if (!$this->isConvertibleToWebp($media->getContentType())) {
            return false;
        }

        $originalPath = $this->getRelativePath($media);
        if (!file_exists($originalPath)) {
            return false;
        }

        $webpPath = $this->convertToWebp($originalPath);
        if ($webpPath === null) {
            return false;
        }

        // Supprimer l'original
        $this->fileSystem->remove($originalPath);

        // Mettre à jour le média
        $newFilename = preg_replace('/\.(jpe?g|png)$/i', '.webp', $media->getFilename());
        $media->setFilename($newFilename);
        $media->setContentType('image/webp');
        $media->setContentSize(filesize($webpPath));

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
        ;
        // Initialize Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true)
            ->set('isPhpEnabled', true)
            ->set('isRemoteEnabled', true)
        ;

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait')
            ->render();

        $uploadDirectory =  $this->getUploadDirectoryForContext($context, $private).'/'.$media->getCreatedAt()->format('Ym').'/';
        $filePath = $uploadDirectory.$fileName;


        $this->fileSystem->mkdir($uploadDirectory);
        // Save the generated PDF
        if (file_put_contents($filePath, $dompdf->output()) === false) {
            exit('Failed to write the PDF to file.');
        }

        $media->setContentSize(filesize($filePath));

        $this->validator
            ->setMessage('Invalid media input')
            ->addValidation($media)
            ->validate();

        $this->entityManager->persist($media);
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->deleteAsset($media);
            throw $e;
        }

        return $media;
    }

    /**
     * @return string
     */
    private function getPublicPath(): string
    {
        return 'public';
    }

    private function getPrivatePath(): string
    {
        return 'private';
    }

    private function getUploadDirectoryForContext(string $context, bool $private): string
    {
        return $this->parameterBag->get('kernel.project_dir').'/'.($private ? $this->getPrivatePath() : $this->getPublicPath()).$this->uploadDir.'/'.$context;
    }

    /**
     * @param Media $media
     * @return string
     */
    private function getContextPath(Media $media): string
    {
        return $this->uploadDir.'/'.$media->getContext();
    }

    private function generateNameForFile(File $file): string
    {
        return sha1_file($file).uniqid().'.'.$file->guessExtension();
    }

    private function generateNameForContent(string $content, string $extension): string
    {
        return sha1($content).uniqid().'.'.$extension;
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