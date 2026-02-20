<?php

namespace UbeeDev\LibBundle\Service;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client as amazonS3Client;
use Symfony\Component\Filesystem\Filesystem;

class S3Client
{
    private amazonS3Client $s3Client;

    public function __construct(string $key, string $secret, string $region, string $version)
    {
        $this->s3Client =  new amazonS3Client([
            'region' => $region,
            'version' => $version,
            'credentials' => [
                'key'    => $key,
                'secret' => $secret,
            ],
        ]);
    }

    public function upload(string $localFilePath, string $bucket, string $s3FilePath): string
    {
        $result = $this->s3Client->putObject([
            'Bucket'     => $bucket,
            'Key'        => $s3FilePath,
            'SourceFile' => $localFilePath,
        ]);

        return $result->get("ObjectURL");
    }

    public function get(string $bucket, string $keyName): string
    {
        $object = $this->s3Client->getObject([
            'Bucket'     => $bucket,
            'Key'        => $keyName
        ]);

        return $object['@metadata']['effectiveUri'];
    }

    public function download(string $bucket, string $keyName, string $tmpDumpFolderPath, string $tmpDumpFileName): string
    {
        $tmpDumpFilePath = $tmpDumpFolderPath.'/'.$tmpDumpFileName;
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($tmpDumpFolderPath);

        $this->s3Client->getObject([
            'Bucket'     => $bucket,
            'Key'        => $keyName,
            'SaveAs'     =>  $tmpDumpFilePath
        ]);

        return $tmpDumpFilePath;
    }



    public function delete(string $bucket, string $keyName): bool
    {
        $this->s3Client->deleteObject([
            'Bucket'     => $bucket,
            'Key'        => $keyName
        ]);

        return true;
    }

    public function list(array $options): array
    {
        $objects = $this->s3Client->getIterator('ListObjects', $options);
        $list = [];
        foreach ($objects as $object) {
            $list[] = $object['Key'];
        }
        return $list;
    }

}
