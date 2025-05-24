<?php

namespace Khalil1608\LibBundle\Tests\Service;

use Khalil1608\LibBundle\Service\OpsAlertManager;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpsAlertManagerTest extends AbstractWebTestCase
{
    
    private array $expectedHeaders;
    private OpsAlertManager $opsAlertManager;
    private HttpClientInterface|MockObject $clientMock;
    
    private string $slackToken;

    public function setUp(): void
    {
        parent::setUp();
        $this->expectedHeaders = ['content-type' => 'application/json;charset=UTF-8', 'Accept' => 'application/json'];
        $this->clientMock = $this->getMockedClass(HttpClientInterface::class);
        $this->slackToken = 'some-token';
        $this->opsAlertManager = $this->initManager();
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendSlackNotificationWithoutEncodeParameterSuccessfully(): void
    {
        $payload = [
            'channels' => 'some-channel',
            'content' => json_encode(['some-params']),
            'filetype' => 'javascript',
            'initial_comment' => 'some comment',
            'title'   => '[test] - Params',
        ];

        $responseMock = $this->jsonResponseMock(["ok" => true]);

        $this->clientMock->expects($this->once())
            ->method('request')
            ->with($this->equalTo('POST'),
                $this->equalTo('https://slack.com/api/files.upload'),
                $this->equalTo([
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->slackToken
                    ],
                    'body' => $payload,
                ]))
            ->willReturn($responseMock);

        $this->opsAlertManager->sendSlackNotification([
            'initialComment' => 'some comment',
            'parameters' => ['some-params'],
            'channel' => 'some-channel'
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendSlackNotificationWithTextSuccessfully(): void
    {
        $payload = [
            'channel' => 'some-channel',
            'text' => 'Some text',
            'as_user' => true
        ];

        $responseMock = $this->jsonResponseMock(["ok" => true]);

        $this->clientMock->expects($this->once())
            ->method('request')
            ->with($this->equalTo('POST'),
                $this->equalTo('https://slack.com/api/chat.postMessage'),
                $this->equalTo([
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->slackToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                ]))
            ->willReturn($responseMock);

        $this->opsAlertManager->sendSlackNotification([
            'text' => 'Some text',
            'channel' => 'some-channel',
            'contentType' => 'text'
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendSlackNotificationWithEncodeParametersSuccessfully(): void
    {
        $payload = [
            'channels' => 'some-channel',
            'content'  => ['some-params'],
            'filetype' => 'javascript',
            'initial_comment' => 'some comment',
            'title'   => '[test] - Params',
        ];

        $responseMock = $this->jsonResponseMock(["ok" => true]);

        $this->clientMock->expects($this->once())
            ->method('request')
            ->with($this->equalTo('POST'),
                $this->equalTo('https://slack.com/api/files.upload'),
                $this->equalTo([
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->slackToken,
                    ],
                    'body' => $payload,
                ]))
            ->willReturn($responseMock);

        $this->opsAlertManager->sendSlackNotification([
            'initialComment' => 'some comment',
            'parameters' => ['some-params'],
            'channel' => 'some-channel',
            'encodeParameters' => false
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendSlackNotificationWithFileAndThreadTsSuccessfully(): void
    {
        $expectedPayload = [
            'channels' => 'some-channel',
            'file' => $file = fopen($filePath = $this->getAsset('document.pdf'), 'rb'),
            'filetype' => 'jpg',
            'thread_ts' => '123456789.123456',
            'initial_comment' => 'some comment',
            'title'   => '[test] - Params',
        ];

        $responseMock = $this->jsonResponseMock(["ok" => true]);

        $this->clientMock->expects($this->once())
            ->method('request')
            ->with($this->equalTo('POST'),
                $this->equalTo('https://slack.com/api/files.upload'),
                $this->callback(function ($payload) use ($expectedPayload) {
                    return $payload['headers'] ===  [
                        'Authorization' => 'Bearer ' . $this->slackToken,
                    ] && $payload['body']['channels'] === $expectedPayload['channels']
                    && $payload['body']['filetype'] === $expectedPayload['filetype']
                    && $payload['body']['thread_ts'] === $expectedPayload['thread_ts']
                    && $payload['body']['initial_comment'] === $expectedPayload['initial_comment']
                    && $payload['body']['title'] === $expectedPayload['title']
                    && stream_get_contents($payload['body']['file']) === stream_get_contents($expectedPayload['file'])
                        ;
                }))
            ->willReturn($responseMock);

        $this->opsAlertManager->sendSlackNotification([
            'initialComment' => 'some comment',
            'parameters' => ['some-params'],
            'channel' => 'some-channel',
            'encodeParameters' => false,
            'threadTs' => '123456789.123456',
            'file' => $filePath,
            'filetype' => 'jpg',
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendSlackNotificationWhenSlackReturnsAnError(): void
    {
        $payload = [
            'channels' => 'some-channel',
            'content'  => null,
            'filetype' => 'javascript',
            'initial_comment' => '',
            'title'   => '[test] - Params',
        ];

        $responseMock = $this->jsonResponseMock(["ok" => false, "error" => "some-error"]);

        $this->clientMock->expects($this->once())
            ->method('request')
            ->with($this->equalTo('POST'),
                $this->equalTo('https://slack.com/api/files.upload'),
                $this->equalTo([
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->slackToken
                    ],
                    'body' => $payload,
                ]))
            ->willReturn($responseMock);

        $this->expectException(\RuntimeException::class);
        $this->opsAlertManager->sendSlackNotification(['channel' => 'some-channel']);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendSlackNotificationWhenSlackHandleAnErrorError(): void
    {
        $payload = [
            'channels' => 'some-channel',
            'content'  => null,
            'filetype' => 'javascript',
            'initial_comment' => '',
            'title'   => '[test] - Params',
        ];

        $responseMock = $this->jsonResponseMock(['some' => 'error']);

        $this->clientMock->expects($this->once())
            ->method('request')
            ->with($this->equalTo('POST'),
                $this->equalTo('https://slack.com/api/files.upload'),
                $this->equalTo([
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->slackToken
                    ],
                    'body' => $payload,
                ]))
            ->willReturn($responseMock);

        $this->expectException(\RuntimeException::class);
        $this->opsAlertManager->sendSlackNotification(['channel' => 'some-channel']);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testNoSlackIsSentIfSlackAlertAreMuted(): void
    {
        $this->clientMock->expects($this->never())
            ->method('request');

        $this->opsAlertManager = new OpsAlertManager(
            client: $this->clientMock,
            slackToken: $this->slackToken,
            currentEnv: 'test',
            muteOpsAlerts: true
        );

        $response = $this->opsAlertManager->sendSlackNotification([]);
        $this->assertEquals(['notSent' => 'Ops alerts are muted in this environment'], $response);
    }

    private function initManager(): OpsAlertManager
    {
        return new OpsAlertManager($this->clientMock, $this->slackToken, 'test');
    }
}
