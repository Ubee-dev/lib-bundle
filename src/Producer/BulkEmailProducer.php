<?php

namespace UbeeDev\LibBundle\Producer;

class BulkEmailProducer extends AbstractProducer
{
    public function sendBulkEmail(array $options, int $retryNumber = 0): void
    {
        $this->producer->publish(
            json_encode(array_merge($options, ['retryNumber' => $retryNumber])),
            '',
            [],
            $this->getXDelayForRetryNumber($retryNumber)
        );
    }
}
