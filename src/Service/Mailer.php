<?php

namespace Khalil1608\LibBundle\Service;

use Khalil1608\LibBundle\Service\EmailProvider\EmailProviderInterface;

class Mailer
{
    const HTML_CONTENT_TYPE = 'html';
    const TEXT_CONTENT_TYPE = 'text';

    public function __construct(
        private readonly EmailProviderInterface $emailProvider
    )
    {
    }

    public function sendMail(
        string|array $from,
        array        $to,
        string       $body,
        string       $subject,
        ?string      $replyTo = null,
        ?string      $contentType = self::HTML_CONTENT_TYPE,
        array        $attachments = []): array
    {
        return $this->emailProvider->sendMail(
            from: $from,
            to: $to,
            body: $body,
            subject: $subject,
            replyTo: $replyTo,
            contentType: $contentType,
            attachments: $attachments
        );
    }
}
