<?php

namespace Khalil1608\LibBundle\Service\EmailProvider\Mailchimp;

use Khalil1608\LibBundle\Service\EmailProvider\BulkEmailProviderInterface;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractEmailProvider
{
    const API_URL = 'https://mandrillapp.com/api/1.0';

    public function __construct(
        private readonly string $apiKey,
        private readonly HttpClientInterface $client
    )
    {
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    protected function sendMessage(array $message, bool $async = false): array
    {
        $response = $this->client->request('POST', self::API_URL . '/messages/send', [
            'body' => [
                'key' => $this->apiKey,
                'message' => $message,
                'async' => $async,
            ],
        ]);

        return json_decode($response->getContent(), true);
    }

    protected function formatAttachments(array $attachments): array
    {
        $formattedAttachments = [];
        /** @var UploadedFile $attachment */
        foreach ($attachments as $attachment) {

            if($attachment instanceof UploadedFile) {
                $attachmentFileContent = file_get_contents($attachment->getFileInfo()->getPathname());
                $type = $attachment->getMimeType();
                $name = $attachment->getClientOriginalName();
            } else {
                $attachmentFileContent = $attachment['content'];
                $type = $attachment['type'];
                $name = $attachment['name'];
            }

            $attachmentEncoded = base64_encode($attachmentFileContent);

            $formattedAttachments[] = [
                'content' => $attachmentEncoded,
                'type' => $type,
                'name' => $name,
            ];
        }

        return $formattedAttachments;
    }
}