<?php

namespace Khalil1608\LibBundle\Consumer;

use Khalil1608\LibBundle\Producer\ErrorProducer;
use Khalil1608\LibBundle\Producer\SlackNotificationProducer;
use Khalil1608\LibBundle\Service\OpsAlertManager;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqplib\Message\AMQPMessage;

class SlackNotificationConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly OpsAlertManager $opsAlertManager, 
        private readonly ErrorProducer $errorProducer, 
        private readonly SlackNotificationProducer $slackNotificationProducer
    )
    {
    }

    public function execute(AMQPMessage $msg): int|bool
    {
        $options = json_decode($msg->body, true);

       
        try {
            $slackParameters = array_diff_key($options, ['retryNumber' => '']);
            $this->opsAlertManager->sendSlackNotification($slackParameters);
        } catch (\Exception $exception) {
            $this->retryOrNotify($options, $exception);
        }
        return ConsumerInterface::MSG_ACK;
    }

    private function retryOrNotify($options, $exception)
    {
        if($options['retryNumber'] >= 2) {
            ConsumerInterface::MSG_ACK;

            $this->errorProducer->sendNotification(
                SlackNotificationConsumer::class,
                'sendSlackNotification',
                $options,
                $exception->getMessage()
            );

            return;
        }
        $options['retryNumber']++;
        $this->slackNotificationProducer->sendSlackNotification($options, $options['retryNumber']);
    }
}
