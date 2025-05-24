<?php


namespace Khalil1608\LibBundle\Tests\Producer;

use Khalil1608\LibBundle\Producer\CompressPdfProducer;

class CompressPdfProducerTest extends AbstractProducerCase
{
    public function testCompressPdfWithoutRetryNumber(): void
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

        $compressPdfProducer = new CompressPdfProducer($this->producerMock);

        $compressPdfProducer->sendCompressPdf(['some' => 'options']);
    }

    public function testSendEmailWithOneRetryNumber(): void
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

        $compressPdfProducer = new CompressPdfProducer($this->producerMock);

        $compressPdfProducer->sendCompressPdf(['some' => 'options', 'retryNumber' => 0], 1);
    }

    public function testSendEmailWithTwoRetryNumber(): void
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

        $compressPdfProducer = new CompressPdfProducer($this->producerMock);

        $compressPdfProducer->sendCompressPdf(['some' => 'options', 'retryNumber' => 1], 2);
    }
}
