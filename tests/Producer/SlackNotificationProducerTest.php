<?php

namespace UbeeDev\LibBundle\Tests\Producer;

use UbeeDev\LibBundle\Producer\SlackNotificationProducer;
use UbeeDev\LibBundle\Service\Slack\TextSnippet;

class SlackNotificationProducerTest extends AbstractProducerCase
{
    public function testSendNotificationWithoutRetryNumber(): void
    {
        $snippet = new TextSnippet('some content');
        $messageBody = json_encode([
            'channel' => 'dev',
            'title' => 'Test title',
            'snippet' => $snippet->jsonSerialize(),
            'threadTs' => null,
            'retryNumber' => 0,
        ]);

        $this->producerMock->expects($this->once())
            ->method('publish')
            ->with(
                $this->equalTo($messageBody),
                $this->equalTo(''),
                $this->equalTo([]),
                $this->equalTo([])
            );

        $producer = new SlackNotificationProducer($this->producerMock);
        $producer->publish('dev', 'Test title', $snippet);
    }

    public function testSendNotificationWithOneRetryNumber(): void
    {
        $options = [
            'channel' => 'dev',
            'title' => 'Test title',
            'snippet' => (new TextSnippet('content'))->jsonSerialize(),
            'retryNumber' => 0,
        ];

        $messageBody = json_encode(array_merge($options, ['retryNumber' => 1]));

        $this->producerMock->expects($this->once())
            ->method('publish')
            ->with(
                $this->equalTo($messageBody),
                $this->equalTo(''),
                $this->equalTo([]),
                $this->equalTo(['x-delay' => 5 * 60 * 1000])
            );

        $producer = new SlackNotificationProducer($this->producerMock);
        $producer->sendNotification($options, 1);
    }

    public function testSendNotificationWithTwoRetryNumber(): void
    {
        $options = [
            'channel' => 'dev',
            'title' => 'Test title',
            'snippet' => (new TextSnippet('content'))->jsonSerialize(),
            'retryNumber' => 1,
        ];

        $messageBody = json_encode(array_merge($options, ['retryNumber' => 2]));

        $this->producerMock->expects($this->once())
            ->method('publish')
            ->with(
                $this->equalTo($messageBody),
                $this->equalTo(''),
                $this->equalTo([]),
                $this->equalTo(['x-delay' => 15 * 60 * 1000])
            );

        $producer = new SlackNotificationProducer($this->producerMock);
        $producer->sendNotification($options, 2);
    }
}
