<?php

namespace UbeeDev\LibBundle\Service\EmailProvider\Mailchimp;

use UbeeDev\LibBundle\Service\EmailProvider\EmailProviderInterface;
use Exception;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class EmailProvider extends AbstractEmailProvider implements EmailProviderInterface
{
    const HTML_CONTENT_TYPE = 'html';
    const TEXT_CONTENT_TYPE = 'text';

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function sendMail(
        string|array  $from,
        array   $to,
        string  $body,
        string  $subject,
        ?string $replyTo = null,
        string  $contentType = self::HTML_CONTENT_TYPE,
        array   $attachments = []): array
    {
        $message = [
            $contentType => $body,
            'subject' => $subject,
            'from_email' => is_string($from) ? $from : $from['email'],
            'to' => array_map(function ($t) use ($to) {
                return [
                    'email' => $t,
                    'name' => $t,
                    'type' => 'to'
                ];
            }, $to),
            'attachments' => $this->formatAttachments($attachments)
        ];

        if ($replyTo) {
            $message['headers'] = [
                "Reply-To" => $replyTo
            ];
        }

        return $this->sendMessage($message);
    }
}