<?php

namespace Khalil1608\LibBundle\Producer;

class EmailProducer extends AbstractProducer
{
    public function sendMail(
        string|array $from,
        array $to,
        string $text,
        string $subject,
        array $attachments = [],
        int $retryNumber = 0
    ): void
    {
        $this->producer->publish(
            serialize(['from' => $from, 'to' => $to, 'text' => $text, 'subject' => $subject, 'attachments' => $attachments, 'retryNumber' => $retryNumber]),
            '',
            [],
            $this->getXDelayForRetryNumber($retryNumber)
        );
    }
}
