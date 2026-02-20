<?php

namespace UbeeDev\LibBundle\Tests\Helper;

use UbeeDev\LibBundle\Model\Type\Email;
use UbeeDev\LibBundle\Producer\EmailProducer;
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