<?php

namespace Khalil1608\LibBundle\Producer;

class SlackNotificationProducer extends AbstractProducer
{
    /**
     * @param array $options
     * @param int $retryNumber
     */
    public function sendSlackNotification(array $options, $retryNumber = 0)
    {
        $this->producer->publish(
            json_encode(array_merge($options, ['retryNumber' => $retryNumber])),
            '',
            [],
            $this->getXDelayForRetryNumber($retryNumber)
        );
    }
}
