<?php

namespace UbeeDev\LibBundle\Producer;

use UbeeDev\LibBundle\Service\Slack\SlackSnippetInterface;

class SlackNotificationProducer extends AbstractProducer
{
    public function sendNotification(array $options, int $retryNumber = 0): void
    {
        $this->producer->publish(
            json_encode(array_merge($options, ['retryNumber' => $retryNumber])),
            '',
            [],
            $this->getXDelayForRetryNumber($retryNumber)
        );
    }

    public function publish(string $channel, string $title, SlackSnippetInterface $snippet, ?string $threadTs = null): void
    {
        $this->sendNotification([
            'channel' => $channel,
            'title' => $title,
            'snippet' => $snippet->jsonSerialize(),
            'threadTs' => $threadTs,
        ]);
    }
}
