<?php

namespace UbeeDev\LibBundle\Producer;

class BulkEmailProducer extends AbstractProducer
{
    /**
     * @param string $from
     * @param array|string $to
     * @param string $text
     * @param string $subject
     * @param int $retryNumber
     */
    public function sendBulkEmail(array $options, $retryNumber = 0)
    {
        $this->producer->publish(
            json_encode(array_merge($options, ['retryNumber' => $retryNumber])),
            '',
            [],
            $this->getXDelayForRetryNumber($retryNumber)
        );
    }
}
