<?php

namespace UbeeDev\LibBundle\Producer;

use UbeeDev\LibBundle\Model\Type\Email;

class EmailProducer extends AbstractProducer
{
    public function sendMail(
        string|array $from,
        array $to,
        string $text,
        string $subject,
        array $attachments = [],
        int $retryNumber = 0,
        ?Email $replyTo = null
    ): void
    {
        $this->producer->publish(
            serialize([
                'from' => $from,
                'to' => $to,
                'text' => $text,
                'subject' => $subject,
                'attachments' => $attachments,
                'retryNumber' => $retryNumber,
                'replyTo' => $replyTo
            ]),
            '',
            [],
            $this->getXDelayForRetryNumber($retryNumber)
        );
    }
}