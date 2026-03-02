<?php

namespace UbeeDev\LibBundle\Service;

interface ObjectStorageInterface
{
    public function upload(string $localFilePath, string $bucket, string $remotePath, bool $private = false): string;

    public function download(string $bucket, string $key, string $localFolder, string $localFileName): string;

    public function get(string $bucket, string $key): string;

    public function delete(string $bucket, string $key): bool;

    public function list(string $bucket, ?string $prefix = null): array;

    public function getPresignedUrl(string $bucket, string $key, int $expiry = 3600): string;

    public function exists(string $bucket, string $key): bool;
}
