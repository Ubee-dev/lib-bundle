<?php

namespace UbeeDev\LibBundle\Service\EmailProvider;


interface BulkEmailProviderInterface
{
    public function send(array $options);
}