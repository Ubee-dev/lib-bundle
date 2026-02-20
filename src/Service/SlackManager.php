<?php

namespace UbeeDev\LibBundle\Service;

use UbeeDev\LibBundle\Service\Slack\SlackSnippetInterface;
use UbeeDev\LibBundle\Service\Slack\TextSnippet;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Unified Slack Manager with single sendNotification method
 */
readonly class SlackManager
{
    public function __construct(
        private HttpClientInterface $client,
        private string              $slackToken,
        private string              $currentEnv,
        private LoggerInterface     $logger,
        private bool                $muteOpsAlerts = false,
        private int                 $maxBlockTextLength = 3000
    ) {
    }

    /**
     * Universal notification method
     * - If snippet type is 'text': sends blocks message with chat.postMessage
     * - For all other snippet types: uploads as file
     */
    public function sendNotification(
        string $channel,
        string $title,
        SlackSnippetInterface $snippet,
        ?string $threadTs = null
    ): array {
        if ($this->muteOpsAlerts) {
            return ['notSent' => 'Ops alerts are muted in this environment'];
        }

        // If snippet type is 'text', send as blocks message with multiple blocks if needed
        if ($snippet instanceof TextSnippet) {
            return $this->sendTextAsMultipleBlocks($channel, $title, $snippet->getContent(), $threadTs);
        }

        // For all other snippet types, upload as file
        return $this->sendWithSnippet($channel, $title, $snippet, $threadTs);
    }

    /**
     * Send text message using multiple blocks to handle long content
     */
    private function sendTextAsMultipleBlocks(string $channel, string $title, string $content, ?string $threadTs = null): array
    {
        $blocks = [];

        $blocks[] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => $title
            ]
        ];

        // Divide content into chunks if necessary
        $contentChunks = $this->splitContentIntoChunks($content, $this->maxBlockTextLength);

        foreach ($contentChunks as $chunk) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $chunk
                ]
            ];
        }

        // Footer with environment info
        $blocks[] = [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'mrkdwn',
                    'text' => ":gear: Environment: $this->currentEnv"
                ]
            ]
        ];

        // Check total payload size
        $payload = [
            'channel' => $channel,
            'blocks' => $blocks
        ];

        if ($threadTs) {
            $payload['thread_ts'] = $threadTs;
        }

        return $this->sendMessage($payload);
    }

    /**
     * Split content into chunks respecting character limits
     */
    private function splitContentIntoChunks(string $content, int $maxChunkSize): array
    {
        $chunks = [];
        $lines = explode("\n", $content);
        $currentChunk = '';

        foreach ($lines as $line) {
            // If adding this line exceeds the limit
            if (strlen($currentChunk . "\n" . $line) > $maxChunkSize) {
                // If the current chunk is not empty, add it to chunks
                if (!empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = '';
                }

                // If the line itself is too long, split it
                if (strlen($line) > $maxChunkSize) {
                    $subChunks = str_split($line, $maxChunkSize);
                    foreach ($subChunks as $subChunk) {
                        $chunks[] = $subChunk;
                    }
                } else {
                    $currentChunk = $line;
                }
            } else {
                // Add the line to the current chunk
                if (!empty($currentChunk)) {
                    $currentChunk .= "\n" . $line;
                } else {
                    $currentChunk = $line;
                }
            }
        }

        // Add the last chunk if not empty
        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    /**
     * Send multiple notifications as a thread
     * First snippet creates the main message, subsequent snippets are replies in the thread
     *
     * @param string $channel Channel ID or name
     * @param string $title Title for the notification
     * @param SlackSnippetInterface[] $snippets Array of snippets to send
     * @return array Response from the first message with thread_ts
     */
    public function sendNotifications(
        string $channel,
        string $title,
        array $snippets,
    ): array
    {
        if ($this->muteOpsAlerts) {
            return ['notSent' => 'Ops alerts are muted in this environment'];
        }

        if (empty($snippets)) {
            throw new InvalidArgumentException('Snippets array cannot be empty');
        }

        $parentResp = $this->postThreadParent($channel, $title);
        $threadTs = $parentResp['ts'];

        foreach ($snippets as $snippet) {
            $this->sendNotification($channel, $title, $snippet, $threadTs);
        }

        return $parentResp;
    }

    /**
     * Send notification with snippet attachment using file upload
     */
    private function sendWithSnippet(
        string $channel,
        string $title,
        SlackSnippetInterface $snippet,
        ?string $threadTs = null
    ): array {
        $fileContent = $snippet->getContent();
        $filename = $snippet->getFileName();
        $filetype = $snippet->getSnippetType();
        $fileSize = strlen($fileContent);

        // Step 1: Get upload URL
        $uploadUrlResponse = $this->getUploadURL($filename, $fileSize, $filetype);

        if (!$uploadUrlResponse['ok']) {
            throw new RuntimeException('Error getting upload URL: ' . json_encode($uploadUrlResponse));
        }

        $uploadUrl = $uploadUrlResponse['upload_url'];
        $fileId = $uploadUrlResponse['file_id'];

        // Step 2: Upload file content to the URL
        $this->uploadFileContent($uploadUrl, $fileContent);

        // Step 3: Complete the upload and share to channel
        $completeResponse = $this->postCompleteUploadToChannel($fileId, $channel, $title, $threadTs);

        if (!array_key_exists('ok', $completeResponse) || $completeResponse['ok'] === false) {
            throw new RuntimeException('Error completing file upload: ' . json_encode($completeResponse));
        }

        return $completeResponse;
    }

    // ===== PRIVATE UPLOAD METHODS =====

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function getUploadURL(string $filename, int $length, ?string $filetype): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->slackToken,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $body = [
            'filename' => $filename,
            'length' => $length,
        ];

        if ($filetype) {
            $body['snippet_type'] = $filetype;
        }

        $body = http_build_query($body);

        $response = $this->client->request('POST', 'https://slack.com/api/files.getUploadURLExternal', [
            'headers' => $headers,
            'body' => $body,
        ]);

        return json_decode($response->getContent(), true);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function uploadFileContent(string $uploadUrl, string $fileContent): void
    {
        $response = $this->client->request('POST', $uploadUrl, [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $fileContent,
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new RuntimeException('Failed to upload file content. HTTP Status: ' . $statusCode);
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function postCompleteUploadToChannel(
        string $fileId,
        string $channel,
        string $initialComment,
        ?string $threadTs = null
    ): array {
        $channelId = $this->validateAndConvertChannel($channel);

        $payload = [
            'files' => [
                [
                    'id' => $fileId,
                ]
            ],
            'channel_id' => $channelId,
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $initialComment
                    ]
                ],
                [
                    'type' => 'context',
                    'elements' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => ":gear: Environment: *$this->currentEnv*"
                        ]
                    ]
                ]
            ]
        ];

        if ($threadTs) {
            $payload['thread_ts'] = $threadTs;
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->slackToken,
            'Content-Type' => 'application/json',
        ];

        $response = $this->client->request('POST', 'https://slack.com/api/files.completeUploadExternal', [
            'headers' => $headers,
            'json' => $payload,
        ]);

        return json_decode($response->getContent(), true);
    }

    private function validateAndConvertChannel(string $channel): string
    {
        if (preg_match('/^[CGDZ][A-Z0-9]{8,}$/', $channel)) {
            return $channel;
        }

        $channelName = ltrim($channel, '#');

        try {
            $channelId = $this->getChannelIdByName($channelName);
            if ($channelId) {
                return $channelId;
            }
        } catch (Exception $e) {
            $this->logger->error("Error converting channel name '$channelName' to ID: " . $e->getMessage());
        }

        throw new RuntimeException(
            "Invalid channel format: '$channel'. " .
            "Could not find or access channel. Please ensure:\n" .
            "1. The channel exists, it is private and accessible\n" .
            "2. The bot is invited to the channel\n" .
            "3. You have the correct permissions (channels:read scope)\n" .
            "4. Or use the channel ID directly (format: C1234567890)\n" .
            "Channel ID must match pattern: ^[CGDZ][A-Z0-9]{8,}$"
        );
    }

    private function getChannelIdByName(string $channelName): ?string
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->slackToken,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $response = $this->client->request('GET', 'https://slack.com/api/conversations.list?' . http_build_query([
                'types' => 'private_channel',
                'limit' => 1000
            ]), [
            'headers' => $headers,
        ]);

        $data = json_decode($response->getContent(), true);

        if (!$data['ok']) {
            error_log("conversations.list API error: " . json_encode($data));
            return null;
        }

        foreach ($data['channels'] as $channel) {
            if ($channel['name'] === $channelName) {
                return $channel['id'];
            }
        }

        return null;
    }

    private function sendMessage(array $payload): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->slackToken,
            'Content-Type' => 'application/json',
        ];

        $response = $this->client->request('POST', 'https://slack.com/api/chat.postMessage', [
            'headers' => $headers,
            'json' => $payload,
        ]);

        $formattedResponse = json_decode($response->getContent(), true);

        if (!array_key_exists('ok', $formattedResponse) || $formattedResponse['ok'] === false) {
            throw new RuntimeException('Error sending notification with payload: ' . json_encode($payload) . '. Response: ' . json_encode($formattedResponse));
        }

        return $formattedResponse;
    }

    private function postThreadParent(string $channel, string $title): array
    {
        $payload = [
            'channel' => $channel,
            'text' => $title, // fallback simple
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $title
                    ]
                ],
                [
                    'type' => 'context',
                    'elements' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => ":gear: Environment: *$this->currentEnv*"
                        ]
                    ]
                ]
            ],
        ];

        return $this->sendMessage($payload); // chat.postMessage
    }
}