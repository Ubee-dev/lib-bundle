<?php

namespace UbeeDev\LibBundle\Service\ObjectStorage;

use Aws\S3\S3Client as AmazonS3Client;
use Symfony\Component\Filesystem\Filesystem;
use UbeeDev\LibBundle\Service\ObjectStorageInterface;

abstract class AbstractS3ObjectStorage implements ObjectStorageInterface
{
    protected AmazonS3Client $s3Client;

    public function __construct(string $key, string $secret, string $region, ?string $endpoint = null)
    {
        $config = [
            'region' => $region,
            'version' => 'latest',
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ],
        ];

        if ($endpoint !== null) {
            $config['endpoint'] = $endpoint;
            $config['use_path_style_endpoint'] = true;
        }

        $this->s3Client = new AmazonS3Client($config);
    }

    public function upload(string $localFilePath, string $bucket, string $remotePath): string
    {
        $result = $this->s3Client->putObject([
            'Bucket' => $bucket,
            'Key' => $remotePath,
            'SourceFile' => $localFilePath,
        ]);

        return $result->get('ObjectURL');
    }

    public function get(string $bucket, string $key): string
    {
        $object = $this->s3Client->getObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);

        return $object['@metadata']['effectiveUri'];
    }

    public function download(string $bucket, string $key, string $localFolder, string $localFileName): string
    {
        $localFilePath = $localFolder.'/'.$localFileName;
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($localFolder);

        $this->s3Client->getObject([
            'Bucket' => $bucket,
            'Key' => $key,
            'SaveAs' => $localFilePath,
        ]);

        return $localFilePath;
    }

    public function delete(string $bucket, string $key): bool
    {
        $this->s3Client->deleteObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);

        return true;
    }

    public function list(string $bucket, ?string $prefix = null): array
    {
        $options = ['Bucket' => $bucket];

        if ($prefix !== null) {
            $options['Prefix'] = $prefix;
        }

        $objects = $this->s3Client->getIterator('ListObjects', $options);
        $list = [];
        foreach ($objects as $object) {
            $list[] = $object['Key'];
        }

        return $list;
    }
}
