<?php


namespace UbeeDev\LibBundle\Tests\Producer;

use UbeeDev\LibBundle\Producer\BulkEmailProducer;
use UbeeDev\LibBundle\Producer\EmailProducer;

class BulkEmailProducerTest extends AbstractProducerCase
{

    public function testSendEmailWithoutRetryNumber()
    {
        $messageBody = json_encode([
            'some' => 'options',
            'retryNumber' => 0
        ]);

        $this->producerMock->expects($this->once())
            ->method('publish')
            ->with(
                $this->equalTo($messageBody),
                $this->equalTo(''),
                $this->equalTo([]),
                $this->equalTo([])
            );

        $bulkEmailProducer = new BulkEmailProducer($this->producerMock);

        $bulkEmailProducer->sendBulkEmail(['some' => 'options']);
    }

    public function testSendEmailWithOneRetryNumber()
    {
        $messageBody = json_encode([
            'some' => 'options',
            'retryNumber' => 1
        ]);

        $this->producerMock->expects($this->once())
            ->method('publish')
            ->with(
                $this->equalTo($messageBody),
                $this->equalTo(''),
                $this->equalTo([]),
                $this->equalTo(['x-delay' => 5 * 60 * 1000])
            );

        $emailProducer = new EmailProducer($this->producerMock);

        $bulkEmailProducer = new BulkEmailProducer($this->producerMock);

        $bulkEmailProducer->sendBulkEmail(['some' => 'options', 'retryNumber' => 0], 1);
    }

    public function testSendEmailWithTwoRetryNumber()
    {
        $messageBody = json_encode([
            'some' => 'options',
            'retryNumber' => 2
        ]);

        $this->producerMock->expects($this->once())
            ->method('publish')
            ->with(
                $this->equalTo($messageBody),
                $this->equalTo(''),
                $this->equalTo([]),
                $this->equalTo(['x-delay' => 15 * 60 * 1000])
            );

        $bulkEmailProducer = new BulkEmailProducer($this->producerMock);

        $bulkEmailProducer->sendBulkEmail(['some' => 'options', 'retryNumber' => 1], 2);
    }
}
