<?php

namespace Khalil1608\LibBundle\Consumer;

use Khalil1608\LibBundle\Producer\CompressPdfProducer;
use Khalil1608\LibBundle\Producer\ErrorProducer;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class CompressPdfConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly ErrorProducer $errorProducer,
        private readonly CompressPdfProducer $compressPdfProducer,
    ) {
    }

    public function execute(AMQPMessage $msg): int|bool
    {

        $data = json_decode($msg->body, true);
        $filePath = $data['filePath'];
        // output only errors
        $error = shell_exec('gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -sOutputFile='.$filePath.'.tmp '.$filePath.' 2>&1 > /dev/null');

        if($error) {
            $this->errorProducer->sendNotification(
                CompressPdfConsumer::class,
                'sendCompressPdf',
                $data,
                $error
            );
            shell_exec('rm -f '.$filePath.'.tmp');
            return ConsumerInterface::MSG_ACK;
        }

        shell_exec('mv '.$filePath.'.tmp '.$filePath);
        return ConsumerInterface::MSG_ACK;
    }
}