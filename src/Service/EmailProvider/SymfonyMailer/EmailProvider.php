<?php

declare(strict_types=1);

namespace UbeeDev\LibBundle\Service\EmailProvider\SymfonyMailer;

use UbeeDev\LibBundle\Service\EmailProvider\EmailProviderInterface;
use UbeeDev\LibBundle\Service\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EmailProvider implements EmailProviderInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * @param string|array{email: string, name?: string}  $from
     * @param array<string>                               $to
     * @param array<array{content: string, name: string}> $attachments
     *
     * @return array{success: bool, error?: string}
     */
    public function sendMail(
        string|array $from,
        array $to,
        string $body,
        string $subject,
        ?string $replyTo = null,
        string $contentType = Mailer::HTML_CONTENT_TYPE,
        array $attachments = [],
    ): array {
        $email = (new Email())
            ->from($this->parseFrom($from))
            ->to(...$to)
            ->subject($subject);

        if (Mailer::HTML_CONTENT_TYPE === $contentType) {
            $email->html($body);
        } else {
            $email->text($body);
        }

        if (null !== $replyTo) {
            $email->replyTo($replyTo);
        }

        foreach ($attachments as $attachment) {
            $email->attach($attachment['content'], $attachment['name']);
        }

        try {
            $this->mailer->send($email);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param string|array{email: string, name?: string} $from
     */
    private function parseFrom(string|array $from): Address
    {
        if (is_string($from)) {
            return new Address($from);
        }

        return new Address($from['email'], $from['name'] ?? '');
    }
}
