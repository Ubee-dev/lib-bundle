<?php

namespace UbeeDev\LibBundle\Consumer;

use UbeeDev\LibBundle\Producer\BulkEmailProducer;
use UbeeDev\LibBundle\Producer\ErrorProducer;
use UbeeDev\LibBundle\Service\EmailProvider\BulkEmailProviderInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqplib\Message\AMQPMessage;

class BulkEmailConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly BulkEmailProviderInterface $bulkEmailProvider,
        private readonly ErrorProducer              $errorProducer,
        private readonly BulkEmailProducer          $bulkEmailProducer
    )
    {
    }

    public function execute(AMQPMessage $msg): bool|int
    {
        $options = json_decode($msg->body, true);

        try {
            $mailParams = array_diff_key($options, ['retryNumber' => '']);
            $this->bulkEmailProvider->send($mailParams);
        } catch (\Exception $exception) {
            $this->retryOrNotify($options, $exception);
        }
        return ConsumerInterface::MSG_ACK;
    }

    private function retryOrNotify($options, $exception): void
    {
        if($options['retryNumber'] >= 2) {
            ConsumerInterface::MSG_ACK;

            $this->errorProducer->sendNotification(
                BulkEmailConsumer::class,
                'sendBulkEmail',
                $options,
                $exception->getMessage()
            );
            return;
        }
        $options['retryNumber']++;
        $this->bulkEmailProducer->sendBulkEmail($options, $options['retryNumber']);
    }
}
