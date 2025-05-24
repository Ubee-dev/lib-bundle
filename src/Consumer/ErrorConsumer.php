<?php

namespace Khalil1608\LibBundle\Consumer;

use Khalil1608\LibBundle\Producer\EmailProducer;
use Khalil1608\LibBundle\Service\OpsAlertManager;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class ErrorConsumer
{
    public function __construct(
        private readonly OpsAlertManager $opsAlertManager, 
        private readonly EmailProducer $emailProducer
    )
    {
    }

    /**
     * @throws \Exception
     */
    public function execute(AMQPMessage $message): int|bool
    {
        $messageBody = json_decode($message->getBody(), true);
        $subject = 'Erreur provenant de : '.$messageBody['component'].'->'.$messageBody['function'];
        try {
            $this->opsAlertManager->sendSlackNotification([
                'parameters' => $messageBody,
                'initialComment' => $subject,
                'channel' => 'dev',
            ]);
        } catch (\Exception $exception) {
            $this->emailProducer->sendMail('khalil1608@gmail.com', ['khalil1608@gmail.com'], $message->getBody(), $subject);
        }
        return ConsumerInterface::MSG_ACK;
    }
}
