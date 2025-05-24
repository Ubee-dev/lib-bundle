<?php

namespace Khalil1608\LibBundle\Tests\Producer;

use Khalil1608\LibBundle\Producer\ErrorProducer;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
use OldSound\RabbitMqBundle\RabbitMq\Producer as RabbitProducer;

class ErrorProducerTest extends AbstractWebTestCase
{
    private $producerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->producerMock = $this->getMockedClass(RabbitProducer::class);
    }

    public function testSendNotification()
    {
        $messageBody = json_encode([
            'component' => 'SomeConsumer',
            'function' => 'some function',
            'params' => ['some-params'],
            'exception' => 'some-exception'
        ]);

        $this->producerMock->expects($this->once())
            ->method('publish')
            ->with($this->equalTo($messageBody));

        $errorProducer = new ErrorProducer($this->producerMock);

        $errorProducer->sendNotification('SomeConsumer', 'some function', ['some-params'], 'some-exception');
    }
}
