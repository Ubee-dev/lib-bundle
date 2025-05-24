<?php

namespace Khalil1608\LibBundle\Service\EmailProvider;


interface BulkEmailProviderInterface
{
    public function send(array $options);
}