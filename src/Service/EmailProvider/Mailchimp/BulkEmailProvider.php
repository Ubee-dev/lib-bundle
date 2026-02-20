<?php

namespace UbeeDev\LibBundle\Service\EmailProvider\Mailchimp;

use UbeeDev\LibBundle\Service\EmailProvider\BulkEmailProviderInterface;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class BulkEmailProvider extends AbstractEmailProvider implements BulkEmailProviderInterface
{
    /**
     * @param array $options
     * @return array
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function send(array $options): array
    {
        $expectedProps = [
            'html',
            'subject',
            'fromEmail',
            'fromName',
            'recipients',
            'replyTo',
            'tags',
            'extraVars',
            'attachments',
        ];
        $receivedProps = array_keys($options);
        $diffProps = array_diff($expectedProps, $receivedProps);
        if (count($diffProps)) {
            throw new Exception('BulkEmailProvider expects options [' . implode(', ', $expectedProps) . '], but received [' . implode(', ', $receivedProps) . '].');
        }

        $recipients = $options['recipients'];
        if (!is_array($recipients)) {
            throw new Exception("BulkEmailProvider expects 'recipients' to be an array, none given.");
        }

        if (count($recipients) === 0) {
            // Noop.
            return [];
        }

        // Transform $options['recipients'] to 'to' and 'merge_vars'
        $to = array_map(function ($recipient) {
            return [
                'email' => $recipient['email'],
                'name' => $recipient['firstName'] . ' ' . $recipient['lastName'],
                'type' => 'to'
            ];
        }, $recipients);

        $extraVars = $options['extraVars'] ?? [];

        $mergeVars = array_map(function ($recipient) use ($extraVars){
            $extraValues = array_map(function ($key) use ($recipient){
                return [
                    'name' => $key,
                    'content' => $recipient[$key]
                ];
            }, $extraVars);

            $vars = array_merge([
                [
                    'name' => 'firstName',
                    'content' => $recipient['firstName']
                ],
                [
                    'name' => 'lastName',
                    'content' => $recipient['lastName']
                ],
            ], $extraValues);

            return [
                'rcpt' => $recipient['email'],
                'vars' => $vars
            ];
        }, $recipients);

        $message = [
            'html' => $options['html'],
            'subject' => $options['subject'],
            'from_email' => $options['fromEmail'],
            'from_name' => $options['fromName'],
            'to' => $to,
            'headers' => [
                'Reply-To' => $options['replyTo']
            ],
            'auto_text' => true,
            'auto_html' => true,
            'preserve_recipients' => false,
            'merge' => true,
            'merge_language' => 'mailchimp',
            'merge_vars' => $mergeVars,
            'tags' => $options['tags'],
            'attachments' => $this->formatAttachments($options['attachments'] ?? []),
        ];

        return $this->sendMessage($message, async: true);
    }
}

