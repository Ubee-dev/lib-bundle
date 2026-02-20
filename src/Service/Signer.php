<?php

namespace UbeeDev\LibBundle\Service;


class Signer
{
    public function sign(array $data, string $secret): string
    {
        return hash('sha256', implode('', array_values($data)).$secret);
    }
}

