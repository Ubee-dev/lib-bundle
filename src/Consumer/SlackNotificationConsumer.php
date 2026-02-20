<?php

namespace UbeeDev\LibBundle\Consumer;

use UbeeDev\LibBundle\Producer\ErrorProducer;
use UbeeDev\LibBundle\Producer\SlackNotificationProducer;
use UbeeDev\LibBundle\Service\Slack\SlackSnippetInterface;
use UbeeDev\LibBundle\Service\SlackManager;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqplib\Message\AMQPMessage;

class SlackNotificationConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly SlackManager $slackManager,
        private readonly ErrorProducer $errorProducer,
        private readonly SlackNotificationProducer $slackNotificationProducer
    )
    {
    }

    public function execute(AMQPMessage $msg): int|bool
    {
        $options = json_decode($msg->body, true);

        try {
            $channel = $options['channel'];
            $title = $options['title'];
            $snippet = $this->deserializeSnippet($options['snippet']);
            $threadTs = $options['threadTs'] ?? null;

            $this->slackManager->sendNotification($channel, $title, $snippet, $threadTs);
        } catch (\Exception $exception) {
            $this->retryOrNotify($options, $exception);
        }
        return ConsumerInterface::MSG_ACK;
    }

    private function deserializeSnippet(array $snippetData): SlackSnippetInterface
    {
        $class = $snippetData['class'];
        return new $class($snippetData['content']);
    }

    private function retryOrNotify(array $options, \Exception $exception): void
    {
        $retryNumber = $options['retryNumber'] ?? 0;

        if ($retryNumber >= 2) {
            $this->errorProducer->sendNotification(
                SlackNotificationConsumer::class,
                'sendNotification',
                $options,
                $exception->getMessage()
            );
            return;
        }

        $options['retryNumber'] = $retryNumber + 1;
        $this->slackNotificationProducer->sendNotification($options);
    }
}
