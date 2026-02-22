<?php

declare(strict_types=1);

namespace UbeeDev\LibBundle\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use UbeeDev\LibBundle\Service\ImageResizeService;

class ImageResizeController
{
    #[Route('/{width}/{path}', name: 'ubee_dev_media_resize', requirements: ['width' => '[1-9]\d*', 'path' => '.+'], methods: ['GET'])]
    public function __invoke(int $width, string $path, ImageResizeService $imageResizeService): Response
    {
        $cachedPath = $imageResizeService->resize($width, $path);
        if (null === $cachedPath) {
            throw new NotFoundHttpException();
        }

        $contentType = $this->resolveContentType($cachedPath);

        return new BinaryFileResponse($cachedPath, headers: [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=2592000, immutable',
        ]);
    }

    private function resolveContentType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'avif' => 'image/avif',
            default => 'application/octet-stream',
        };
    }
}
