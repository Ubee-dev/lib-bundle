<?php

namespace UbeeDev\LibBundle\Service\ObjectStorage;

class S3ObjectStorage extends AbstractS3ObjectStorage
{
    public function __construct(string $key, string $secret, string $region)
    {
        parent::__construct($key, $secret, $region);
    }
}
