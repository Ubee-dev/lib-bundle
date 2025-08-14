<?php

namespace Khalil1608\LibBundle\Service\EmailProvider\Gmail;

use Khalil1608\LibBundle\Service\EmailProvider\EmailProviderInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

readonly class EmailProvider implements EmailProviderInterface
{
    const HTML_CONTENT_TYPE = 'html';
    const TEXT_CONTENT_TYPE = 'text';
    
    public function __construct(private MailerInterface $mailer)
    {
    }

    /**
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
            'to' => array_map(static function ($t) use ($to) {
                return [
                    'email' => $t,
                    'name' => $t,
                    'type' => 'to'
                ];
            }, $to),
        ];

        if ($replyTo) {
            $message['headers'] = [
                "Reply-To" => $replyTo
            ];
        }

        return $this->sendMessage($message, $attachments);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendMessage(array $message, array $attachments = []): array
    {
        try {
            $email = (new Email())
                ->from(new Address($message['from_email']))
                ->subject($message['subject']);

            // Ajout des destinataires
            foreach ($message['to'] as $recipient) {
                $email->addTo(new Address($recipient['email'], $recipient['name']));
            }

            // Reply-To
            if (isset($message['headers']['Reply-To'])) {
                $email->replyTo($message['headers']['Reply-To']);
            }

            // Contenu
            if (isset($message['html'])) {
                $email->html($message['html']);
            } elseif (isset($message['text'])) {
                $email->text($message['text']);
            }

            // Pièces jointes
            foreach ($attachments as $attachment) {
                if (isset($attachment['path'])) {
                    $email->attachFromPath($attachment['path'], $attachment['name'] ?? null, $attachment['mime'] ?? null);
                } elseif (isset($attachment['data'])) {
                    $email->attach($attachment['data'], $attachment['name'] ?? null, $attachment['mime'] ?? null);
                }
            }

            // Envoi
            $this->mailer->send($email);

            $messageId = null;
            if ($email->getHeaders()->has('Message-ID')) {
                $messageId = $email->getHeaders()->get('Message-ID')->getBodyAsString();
            }

            return [
                'status' => 'sent',
                'message_id' => $messageId ?: uniqid('email_', true),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}