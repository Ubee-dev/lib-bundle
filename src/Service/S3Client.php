<?php

namespace Khalil1608\LibBundle\Service;

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

    public function upload($localFilePath, $bucket, $s3FilePath): ?string
    {
        try {
            $result = $this->s3Client->putObject([
                'Bucket'     => $bucket,
                'Key'        => $s3FilePath,
                'SourceFile' => $localFilePath,
            ]);

            return $result->get("ObjectURL");

        } catch (S3Exception $e) {
            return null;
        }
    }

    public function get($bucket, $keyName)
    {
        try {

            $object = $this->s3Client->getObject([
                'Bucket'     => $bucket,
                'Key'        => $keyName
            ]);

            return $object['@metadata']['effectiveUri'];

        } catch (S3Exception $e) {
            return null;
        }
    }

    public function download($bucket, $keyName, $tmpDumpFolderPath, $tmpDumpFileName)
    {
        try {
            $tmpDumpFilePath = $tmpDumpFolderPath.'/'.$tmpDumpFileName;
            $fileSystem = new Filesystem();
            $fileSystem->mkdir($tmpDumpFolderPath);

            $result = $this->s3Client->getObject([
                'Bucket'     => $bucket,
                'Key'        => $keyName,
                'SaveAs'     =>  $tmpDumpFilePath
            ]);

            return $tmpDumpFilePath;

        } catch (S3Exception $e) {

            return null;
        }
    }



    public function delete($bucket, $keyName)
    {
        try {

            $object = $this->s3Client->deleteObject([
                'Bucket'     => $bucket,
                'Key'        => $keyName
            ]);

            return true;

        } catch (S3Exception $e) {
            return null;
        }
    }

    public function list($options)
    {
        $objects = $this->s3Client->getIterator('ListObjects', $options);
        $list = [];
        foreach ($objects as $object) {
            $list[] = $object['Key'];
        }
        return $list;
    }

}
