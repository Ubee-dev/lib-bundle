<?php

namespace UbeeDev\LibBundle\Service\ObjectStorage;

class OvhObjectStorage extends AbstractS3ObjectStorage
{
    public function __construct(string $key, string $secret, string $region, string $endpoint)
    {
        parent::__construct($key, $secret, $region, $endpoint);
    }
}
