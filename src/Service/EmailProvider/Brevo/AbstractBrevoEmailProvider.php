<?php

namespace Khalil1608\LibBundle\Service\EmailProvider\Brevo;

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client as HttpClient;

abstract class AbstractBrevoEmailProvider
{
    private TransactionalEmailsApi $client;

    public function __construct(
        private readonly string $brevoApiKey,
        protected readonly string $currentEnv,
    )
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', $brevoApiKey);

        $this->client = new TransactionalEmailsApi(new HttpClient(), $config);
    }

    protected function getClient(): TransactionalEmailsApi
    {
        return $this->client;
    }

    protected function formatAttachments(array $attachments): array
    {
        $formattedAttachments = [];

        foreach ($attachments as $attachment) {
            if (isset($attachment['content'], $attachment['name'])) {
                $formattedAttachments[] = [
                    'content' => base64_encode($attachment['content']),
                    'name'    => $attachment['name']
                    // Brevo n'utilise pas 'type' dans son API
                ];
            }
        }

        return $formattedAttachments;
    }

    protected function parseEmail(string|array $email): array
    {
        if(is_string($email)) {
            return ['name' => trim($email), 'email' => trim($email)];
        }
        return $email;
    }
}
