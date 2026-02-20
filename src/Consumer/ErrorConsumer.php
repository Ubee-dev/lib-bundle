<?php

namespace UbeeDev\LibBundle\Consumer;

use UbeeDev\LibBundle\Producer\EmailProducer;
use UbeeDev\LibBundle\Service\Slack\JsonSnippet;
use UbeeDev\LibBundle\Service\SlackManager;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

readonly class ErrorConsumer
{
    public function __construct(
        private SlackManager  $slackManager,
        private EmailProducer $emailProducer,
        private ?string       $errorChannel = null,
        private ?string       $errorEmail = null,
    )
    {
    }

    /**
     * @throws \Exception
     */
    public function execute(AMQPMessage $message): int|bool
    {
        if (!$this->errorChannel) {
            return ConsumerInterface::MSG_ACK;
        }

        $messageBody = json_decode($message->getBody(), true);
        $subject = 'Error from: '.$messageBody['component'].'->'.$messageBody['function'];

        try {
            $this->slackManager->sendNotification(
                $this->errorChannel,
                $subject,
                new JsonSnippet($messageBody)
            );
            return ConsumerInterface::MSG_ACK;
        } catch (\Exception) {
            // Fallback to email if Slack fails
            if ($this->errorEmail) {
                $this->emailProducer->sendMail($this->errorEmail, [$this->errorEmail], $message->getBody(), $subject);
            }
        }


        return ConsumerInterface::MSG_ACK;
    }
}
