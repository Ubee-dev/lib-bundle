<?php

namespace UbeeDev\LibBundle\Service\EmailProvider;

use UbeeDev\LibBundle\Service\Mailer;

interface EmailProviderInterface
{
    public function sendMail(
        string|array  $from,
        array   $to,
        string  $body,
        string  $subject,
        ?string $replyTo = null,
        string  $contentType = Mailer::HTML_CONTENT_TYPE,
        array   $attachments = []
    ): array;
}