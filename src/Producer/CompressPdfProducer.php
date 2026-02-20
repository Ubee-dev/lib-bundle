<?php

namespace UbeeDev\LibBundle\Producer;

class CompressPdfProducer extends AbstractProducer
{
    public function sendCompressPdf(array $options, int $retryNumber = 0): void
    {
        $this->producer->publish(
            json_encode(array_merge($options, ['retryNumber' => $retryNumber])),
            '',
            [],
            $this->getXDelayForRetryNumber($retryNumber)
        );
    }
}