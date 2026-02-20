<?php

namespace UbeeDev\LibBundle\Consumer;

use UbeeDev\LibBundle\Producer\EmailProducer;
use UbeeDev\LibBundle\Producer\ErrorProducer;
use UbeeDev\LibBundle\Service\Mailer;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqplib\Message\AMQPMessage;

class EmailConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly Mailer $mailer, 
        private readonly ErrorProducer $errorProducer, 
        private readonly EmailProducer $emailProducer
    )
    {
    }

    public function execute(AMQPMessage $msg): int|bool
    {
        $mailData = unserialize($msg->body);

        try {
            $from = $mailData['from'] ?? null;
            $to = $mailData['to'] ?? null;

            if(!$from or !$to) {
                throw new \Exception('Invalid arguments');
            }
            
            $this->mailer->sendMail(
                from: $mailData['from'],
                to: $mailData['to'],
                body: $mailData['text'],
                subject: $mailData['subject'],
                replyTo: $mailData['replyTo'] ?? null,
                contentType: Mailer::HTML_CONTENT_TYPE,
                attachments: $mailData['attachments'] ?? []
            );
            
        } catch (\Exception $exception) {
            $this->retryOrNotify($mailData, $exception);
        }
        return ConsumerInterface::MSG_ACK;
    }

    private function retryOrNotify(array $mailData, \Exception $exception): void
    {
        if($mailData['retryNumber'] >= 2) {
            if(isset($mailData['text'])) {
                unset($mailData['text']);
            }

            $this->errorProducer->sendNotification(
                EmailConsumer::class,
                'sendMail',
                $mailData,
                $exception->getMessage()
            );

            return;
        }
        $mailData['retryNumber']++;
        $this->emailProducer->sendMail($mailData['from'], $mailData['to'], $mailData['text'], $mailData['subject'], $mailData['attachments'], $mailData['retryNumber']);
    }
}
