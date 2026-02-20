<?php

namespace UbeeDev\LibBundle\Service;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client as amazonS3Client;
use Symfony\Component\Filesystem\Filesystem;

class S3Client
{
    private amazonS3Client $s3Client;

    public function __construct($key, $secret, $region, $version)
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

    public function upload($localFilePath, $bucket, $s3FilePath): string
    {
        $result = $this->s3Client->putObject([
            'Bucket'     => $bucket,
            'Key'        => $s3FilePath,
            'SourceFile' => $localFilePath,
        ]);

        return $result->get("ObjectURL");
    }

    public function get($bucket, $keyName): string
    {
        $object = $this->s3Client->getObject([
            'Bucket'     => $bucket,
            'Key'        => $keyName
        ]);

        return $object['@metadata']['effectiveUri'];
    }

    public function download($bucket, $keyName, $tmpDumpFolderPath, $tmpDumpFileName): string
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



    public function delete($bucket, $keyName): bool
    {
        $this->s3Client->deleteObject([
            'Bucket'     => $bucket,
            'Key'        => $keyName
        ]);

        return true;
    }

    public function list($options): array
    {
        $objects = $this->s3Client->getIterator('ListObjects', $options);
        $list = [];
        foreach ($objects as $object) {
            $list[] = $object['Key'];
        }
        return $list;
    }

}
