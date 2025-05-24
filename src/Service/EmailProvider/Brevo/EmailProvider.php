<?php

namespace Khalil1608\LibBundle\Service\EmailProvider\Brevo;

use Brevo\Client\Model\SendSmtpEmail;
use Khalil1608\LibBundle\Service\EmailProvider\EmailProviderInterface;
use Khalil1608\LibBundle\Service\Mailer;

class EmailProvider extends AbstractBrevoEmailProvider implements EmailProviderInterface
{
    public function sendMail(
        string|array  $from,
        array   $to,
        string  $body,
        string  $subject,
        ?string $replyTo = null,
        string  $contentType = Mailer::HTML_CONTENT_TYPE,
        array   $attachments = []
    ): array {
        $fromData = $this->parseEmail($from);
        $toData = array_map([$this, 'parseEmail'], $to);

        $email = new SendSmtpEmail();
        $email->setSender([
            'email' => $fromData['email'],
            'name' => $fromData['name'],
        ]);
        $email->setTo(array_map(fn ($t) => [
            'email' => $t['email'],
            'name' => $t['name'],
        ], $toData));
        $email->setSubject($subject);

        if ($contentType === Mailer::HTML_CONTENT_TYPE) {
            $email->setHtmlContent($body);
        } else {
            $email->setTextContent($body);
        }

        if ($replyTo) {
            $replyToData = $this->parseEmail($replyTo);
            $email->setReplyTo([
                'email' => $replyToData['email'],
                'name' => $replyToData['name']
            ]);
        }

        $formattedAttachments = $this->formatAttachments($attachments);
        if (!empty($formattedAttachments)) {
            $email->setAttachment($formattedAttachments);
        }

        try {
            $response = $this->getClient()->sendTransacEmail($email);
            dd($response);
            return ['success' => true, 'messageId' => $response->getMessageId()];
        } catch (\Exception $e) {
            dd($e);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
