<?php

namespace Khalil1608\LibBundle\Service\EmailProvider;

use Khalil1608\LibBundle\Service\Mailer;

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