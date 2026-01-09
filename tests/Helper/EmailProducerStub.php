<?php

namespace Khalil1608\LibBundle\Tests\Helper;

use Khalil1608\LibBundle\Model\Type\Email;
use Khalil1608\LibBundle\Producer\EmailProducer;
use OldSound\RabbitMqBundle\RabbitMq\Producer as RabbitProducer;

class EmailProducerStub extends EmailProducer
{
    public function __construct(
        private readonly FakeEmailProvider $fakeEmailProvider,
        RabbitProducer $producer,
        ?string $currentEnv = null,
    )
    {
        parent::__construct($producer, $currentEnv);
    }

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
        $this->fakeEmailProvider->sendMail(
            from: $from,
            to: $to,
            body: $text,
            subject: $subject,
            attachments: $attachments
        );
    }
}