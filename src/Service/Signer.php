<?php

namespace Khalil1608\LibBundle\Service;


class Signer
{
    public function sign(array $data, $secret)
    {
        return hash('sha256', implode('', array_values($data)).$secret);
    }
}

