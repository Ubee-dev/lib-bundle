<?php

namespace UbeeDev\LibBundle\Tests\Service;

use UbeeDev\LibBundle\Service\Slack\FileSnippet;
use UbeeDev\LibBundle\Service\Slack\JsonSnippet;
use UbeeDev\LibBundle\Service\Slack\ShellSnippet;
use UbeeDev\LibBundle\Service\Slack\SlackSnippetInterface;
use UbeeDev\LibBundle\Service\Slack\TextSnippet;
use UbeeDev\LibBundle\Service\SlackManager;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SlackManagerTest extends AbstractWebTestCase
{
    private SlackManager $slackManager;
    private HttpClientInterface|MockObject $clientMock;

    private string $slackToken;

    public function setUp(): void
    {
        parent::setUp();
        $this->clientMock = $this->getMockedClass(HttpClientInterface::class);
        $this->slackToken = 'some-token';
        $this->slackManager = $this->initManager();
    }

    /**
     * Test sending text message with TextSnippet - should use chat.postMessage with blocks
     */
    public function testSendNotificationWithTextSnippetSuccessfully(): void
    {
        $expectedBlocks = $this->buildExpectedTextBlocks(
            'Error detected',
            'Connection timeout after 30 seconds'
        );

        $this->expectPostMessageRequest('some-channel', $expectedBlocks);

        $textSnippet = new TextSnippet('Connection timeout after 30 seconds');
        $this->slackManager->sendNotification('some-channel', 'Error detected', $textSnippet);
    }

    /**
     * Test sending text message with TextSnippet - should use chat.postMessage with blocks
     */
    public function testSendNotificationWithLongTextSnippetSuccessfully(): void
    {
        $expectedBlocks = [
            $this->buildSectionBlock('Error detected'),
            $this->buildSectionBlock("Lorem ipsum dolor sit amet, consectetur."),
            $this->buildSectionBlock("Pellentesque tristique aliquet tortor ut"),
            $this->buildSectionBlock("laoreet. Vestibulum ac sem sed tortor laoreet port"),
            $this->buildSectionBlock("a."),
            $this->buildContextBlock()
        ];

        $this->expectPostMessageRequest('some-channel', $expectedBlocks);

        $textSnippet = new TextSnippet("Lorem ipsum dolor sit amet, consectetur.\nPellentesque tristique aliquet tortor ut\nlaoreet. Vestibulum ac sem sed tortor laoreet porta.");
        $this->slackManager->sendNotification('some-channel', 'Error detected', $textSnippet);
    }

    /**
     * Test sending text message with TextSnippet and thread
     */
    public function testSendNotificationWithTextSnippetAndThreadTsSuccessfully(): void
    {
        $expectedBlocks = $this->buildExpectedTextBlocks(
            'Update message',
            'Problem has been resolved'
        );

        $this->expectPostMessageRequest('some-channel', $expectedBlocks, '123456789.123456');

        $textSnippet = new TextSnippet('Problem has been resolved');
        $this->slackManager->sendNotification('some-channel', 'Update message', $textSnippet, '123456789.123456');
    }

    /**
     * Test sending JSON snippet - should use file upload API
     */
    public function testSendNotificationWithJsonSnippetSuccessfully(): void
    {
        $jsonData = ['error' => 'ValidationException', 'details' => ['id' => 123]];
        $jsonSnippet = new JsonSnippet($jsonData);

        $this->setupFileUploadMocks(
            'C09EV13PZS8',
            $jsonSnippet,
            'Contract creation error'
        );

        $this->slackManager->sendNotification('C09EV13PZS8', 'Contract creation error', $jsonSnippet);
    }

    /**
     * Test sending JSON snippet with thread - should use file upload API with thread_ts
     */
    public function testSendNotificationWithJsonSnippetAndThreadTsSuccessfully(): void
    {
        $jsonData = ['status' => 'resolved', 'resolution_time' => '2 minutes'];
        $jsonSnippet = new JsonSnippet($jsonData);
        $threadTs = '123456789.123456';

        $this->setupFileUploadMocks(
            'C09EV13PZS8',
            $jsonSnippet,
            'Resolution details',
            $threadTs
        );

        $this->slackManager->sendNotification('C09EV13PZS8', 'Resolution details', $jsonSnippet, $threadTs);
    }

    /**
     * Test sending Shell snippet - should use file upload API
     */
    public function testSendNotificationWithShellSnippetSuccessfully(): void
    {
        $shellOutput = "Connecting to database...\nConnection established\nQuery executed successfully";
        $shellSnippet = new ShellSnippet($shellOutput);

        $this->setupFileUploadMocks(
            'C09EV13PZS8',
            $shellSnippet,
            'Database operation log'
        );

        $this->slackManager->sendNotification('C09EV13PZS8', 'Database operation log', $shellSnippet);
    }

    /**
     * Test sending File snippet - should use file upload API
     */
    public function testSendNotificationWithFileSnippetSuccessfully(): void
    {
        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        $fileContent = "Error log contents\nLine 1: Connection refused\nLine 2: Retry attempt failed";
        file_put_contents($tempFile, $fileContent);

        try {
            $fileSnippet = new FileSnippet($tempFile, 'error.log', 'text');

            $this->setupFileUploadMocks(
                'C09EV13PZS8',
                $fileSnippet,
                'Error log file attached'
            );

            $this->slackManager->sendNotification('C09EV13PZS8', 'Error log file attached', $fileSnippet);
        } finally {
            // Clean up the temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Test sending multiple notifications with all snippet types
     * A parent message is posted first, then all snippets are sent as thread replies
     */
    public function testSendNotificationsWithAllSnippetTypes(): void
    {
        // Create temp file for FileSnippet
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, "Log file content");

        try {
            $snippets = [
                new TextSnippet('Text error message'),
                new JsonSnippet(['error' => 'json_error', 'code' => 500]),
                new ShellSnippet("$ command output\nLine 1\nLine 2"),
                new FileSnippet($tempFile, 'error.log', 'text')
            ];

            $threadTs = '1234567890.123456';

            // First call: postThreadParent - creates the parent message
            $parentResponse = $this->jsonResponseMock([
                'ok' => true,
                'ts' => $threadTs,
                'channel' => 'C09EV13PZS8'
            ]);

            // Second snippet (TextSnippet): 1 call in thread
            $textResponse = $this->jsonResponseMock([
                'ok' => true,
                'ts' => '1234567890.123457',
                'channel' => 'C09EV13PZS8'
            ]);

            // Third snippet (JsonSnippet): 3 calls in thread (getUploadURL, upload, complete)
            $jsonUploadUrlResponse = $this->jsonResponseMock([
                'ok' => true,
                'upload_url' => 'https://upload.url/json',
                'file_id' => 'F_JSON'
            ]);
            $jsonUploadResponse = $this->createStub(ResponseInterface::class);
            $jsonUploadResponse->method('getStatusCode')->willReturn(200);
            $jsonCompleteResponse = $this->jsonResponseMock([
                'ok' => true,
                'files' => [
                    [
                        'id' => 'F_JSON',
                        'permalink' => 'https://workspace.slack.com/files/USER/F_JSON/data',
                        'shares' => []
                    ]
                ]
            ]);

            // Fourth snippet (ShellSnippet): 3 calls in thread (getUploadURL, upload, complete)
            $shellUploadUrlResponse = $this->jsonResponseMock([
                'ok' => true,
                'upload_url' => 'https://upload.url/shell',
                'file_id' => 'F_SHELL'
            ]);
            $shellUploadResponse = $this->createStub(ResponseInterface::class);
            $shellUploadResponse->method('getStatusCode')->willReturn(200);
            $shellCompleteResponse = $this->jsonResponseMock([
                'ok' => true,
                'files' => [
                    [
                        'id' => 'F_SHELL',
                        'permalink' => 'https://workspace.slack.com/files/USER/F_SHELL/data',
                        'shares' => []
                    ]
                ]
            ]);

            // Fifth snippet (FileSnippet): 3 calls in thread (getUploadURL, upload, complete)
            $fileUploadUrlResponse = $this->jsonResponseMock([
                'ok' => true,
                'upload_url' => 'https://upload.url/file',
                'file_id' => 'F_FILE'
            ]);
            $fileUploadResponse = $this->createStub(ResponseInterface::class);
            $fileUploadResponse->method('getStatusCode')->willReturn(200);
            $fileCompleteResponse = $this->jsonResponseMock([
                'ok' => true,
                'files' => [
                    [
                        'id' => 'F_FILE',
                        'permalink' => 'https://workspace.slack.com/files/USER/F_FILE/data',
                        'shares' => []
                    ]
                ]
            ]);

            // Total: 1 (parent) + 1 (text) + 3 (json) + 3 (shell) + 3 (file) = 11 calls
            $this->clientMock->expects($this->exactly(11))
                ->method('request')
                ->willReturnOnConsecutiveCalls(
                    $parentResponse,
                    $textResponse,
                    $jsonUploadUrlResponse,
                    $jsonUploadResponse,
                    $jsonCompleteResponse,
                    $shellUploadUrlResponse,
                    $shellUploadResponse,
                    $shellCompleteResponse,
                    $fileUploadUrlResponse,
                    $fileUploadResponse,
                    $fileCompleteResponse
                );

            $this->slackManager->sendNotifications('C09EV13PZS8', 'Multiple errors detected', $snippets);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Test error when sending notification fails
     */
    public function testSendNotificationWithError(): void
    {
        $responseMock = $this->jsonResponseMock(['ok' => false, 'error' => 'invalid_channel']);

        $this->clientMock->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error sending notification');

        $textSnippet = new TextSnippet('Some error message');
        $this->slackManager->sendNotification('invalid-channel', 'Error title', $textSnippet);
    }

    /**
     * Test error when file upload fails
     */
    public function testSendNotificationWithFileUploadError(): void
    {
        $jsonData = ['error' => 'test'];
        $uploadUrlResponse = $this->jsonResponseMock(['ok' => false, 'error' => 'invalid_auth']);

        $this->clientMock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://slack.com/api/files.getUploadURLExternal',
                $this->anything()
            )
            ->willReturn($uploadUrlResponse);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error getting upload URL');

        $jsonSnippet = new JsonSnippet($jsonData);
        $this->slackManager->sendNotification('some-channel', 'Test error', $jsonSnippet);
    }

    /**
     * Test no Slack message is sent if alerts are muted
     */
    public function testNoSlackIsSentIfSlackAlertsAreMuted(): void
    {
        $this->clientMock->expects($this->never())
            ->method('request');

        $mutedSlackManager = new SlackManager(
            client: $this->clientMock,
            slackToken: $this->slackToken,
            currentEnv: 'test',
            logger: $this->createStub(LoggerInterface::class),
            muteOpsAlerts: true
        );

        $textSnippet = new TextSnippet('Some message');
        $response = $mutedSlackManager->sendNotification('some-channel', 'Test title', $textSnippet);

        $this->assertEquals(['notSent' => 'Ops alerts are muted in this environment'], $response);
    }

    /**
     * Test no Slack messages are sent if alerts are muted (plural version)
     */
    public function testNoSlackMessagesAreSentIfSlackAlertsAreMuted(): void
    {
        $this->clientMock->expects($this->never())
            ->method('request');

        $mutedSlackManager = new SlackManager(
            client: $this->clientMock,
            slackToken: $this->slackToken,
            currentEnv: 'test',
            logger: $this->createStub(LoggerInterface::class),
            muteOpsAlerts: true
        );

        $snippets = [
            new TextSnippet('Message 1'),
            new TextSnippet('Message 2')
        ];

        $response = $mutedSlackManager->sendNotifications('some-channel', 'Test title', $snippets, null);

        $this->assertEquals(['notSent' => 'Ops alerts are muted in this environment'], $response);
    }

    /**
     * Test invalid channel format throws exception
     */
    public function testSendNotificationWithFindChannelNameError(): void
    {
        $jsonData = ['error' => 'ValidationException', 'details' => ['id' => 123]];
        $jsonSnippet = new JsonSnippet($jsonData);
        $channelId = 'C09EV13PZS8';
        $channelName = 'general';

        $uploadUrlResponse = $this->jsonResponseMock([
            'ok' => true,
            'upload_url' => 'https://upload.url',
            'file_id' => 'F123456'
        ]);

        $uploadResponse = $this->createStub(ResponseInterface::class);
        $uploadResponse->method('getStatusCode')->willReturn(200);

        $completeResponse = $this->jsonResponseMock([
            'ok' => true,
            'file' => [
                'shares' => [
                    'private' => [
                        $channelId => [
                            ['ts' => '123.456']
                        ]
                    ]
                ]
            ]
        ]);

        $environmentResponse = $this->jsonResponseMock(['ok' => true]);

        $this->clientMock->expects($this->exactly(3))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $uploadUrlResponse,
                $uploadResponse,
                $this->throwException(new \RuntimeException('HTTP 500 on conversations.list')),
                $completeResponse,
                $environmentResponse
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid channel format');

        $this->slackManager->sendNotification($channelName, 'Contract creation error', $jsonSnippet);
    }

    // ========== HELPER METHODS ==========

    /**
     * Build expected blocks for a simple text notification
     */
    private function buildExpectedTextBlocks(string $title, string $text): array
    {
        return [
            $this->buildSectionBlock($title),
            $this->buildSectionBlock($text),
            $this->buildContextBlock()
        ];
    }

    /**
     * Build a section block
     */
    private function buildSectionBlock(string $text): array
    {
        return [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => $text
            ]
        ];
    }

    /**
     * Build a context block with environment information
     */
    private function buildContextBlock(): array
    {
        return [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'mrkdwn',
                    'text' => ':gear: Environment: test'
                ]
            ]
        ];
    }

    /**
     * Expect a chat.postMessage request with specific blocks
     */
    private function expectPostMessageRequest(
        string $channel,
        array $blocks,
        ?string $threadTs = null
    ): void {
        $expectedPayload = [
            'channel' => $channel,
            'blocks' => $blocks
        ];

        if ($threadTs) {
            $expectedPayload['thread_ts'] = $threadTs;
        }

        $responseMock = $this->jsonResponseMock(['ok' => true]);

        $this->clientMock->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('https://slack.com/api/chat.postMessage'),
                $this->equalTo([
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->slackToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $expectedPayload,
                ])
            )
            ->willReturn($responseMock);
    }

    /**
     * Setup mocks for the 3-step file upload process
     */
    private function setupFileUploadMocks(
        string $channelId,
        SlackSnippetInterface $snippet,
        string $initialComment,
        ?string $threadTs = null
    ): void {
        $fileSize = strlen($snippet->getContent());

        // Step 1: Get upload URL
        $uploadUrlResponse = $this->jsonResponseMock([
            'ok' => true,
            'upload_url' => 'https://upload.url',
            'file_id' => 'F123456'
        ]);

        // Step 2: Upload file content
        $uploadResponse = $this->createStub(ResponseInterface::class);
        $uploadResponse->method('getStatusCode')->willReturn(200);

        // Step 3: Complete upload
        $completeResponse = $this->jsonResponseMock([
            'ok' => true,
            'file' => [
                'shares' => [
                    'private' => [
                        $channelId => [
                            ['ts' => '123.456']
                        ]
                    ]
                ]
            ]
        ]);

        $completeUploadPayload = [
            'files' => [
                [
                    'id' => 'F123456',
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
                            'text' => ":gear: Environment: *test*"
                        ]
                    ]
                ]
            ]
        ];

        if ($threadTs) {
            $completeUploadPayload['thread_ts'] = $threadTs;
        }

        $this->clientMock->expects($this->exactly(3))
            ->method('request')
            ->with(...self::withConsecutive(
                [
                    $this->equalTo('POST'),
                    $this->equalTo('https://slack.com/api/files.getUploadURLExternal'),
                    $this->equalTo([
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->slackToken,
                            'Content-Type' => 'application/x-www-form-urlencoded',
                        ],
                        'body' => http_build_query([
                            'filename' => $snippet->getFileName(),
                            'length' => $fileSize,
                            'snippet_type' => $snippet->getSnippetType()
                        ]),
                    ])
                ],
                [
                    $this->equalTo('POST'),
                    $this->equalTo('https://upload.url'),
                    $this->equalTo([
                        'headers' => [
                            'Content-Type' => 'application/octet-stream',
                        ],
                        'body' => $snippet->getContent(),
                    ])
                ],
                [
                    $this->equalTo('POST'),
                    $this->equalTo('https://slack.com/api/files.completeUploadExternal'),
                    $this->equalTo([
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->slackToken,
                            'Content-Type' => 'application/json',
                        ],
                        'json' => $completeUploadPayload,
                    ])
                ],
            ))
            ->willReturnOnConsecutiveCalls(
                $uploadUrlResponse,
                $uploadResponse,
                $completeResponse
            );
    }

    private function initManager(): SlackManager
    {
        return new SlackManager(
            client: $this->clientMock,
            slackToken: $this->slackToken,
            currentEnv: 'test',
            logger: $this->createStub(LoggerInterface::class),
            maxBlockTextLength: 50
        );
    }
}