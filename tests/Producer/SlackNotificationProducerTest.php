<?php

namespace Khalil1608\LibBundle\Tests\Producer;

use Khalil1608\LibBundle\Producer\SlackNotificationProducer;

class SlackNotificationProducerTest extends AbstractProducerCase
{
    public function testSendNotificationWithoutRetryNumber()
    {
        $params = ['some' => 'params'];

        $messageBody = json_encode(array_merge($params, [
            'retryNumber' => 0
        ]));

        $this->producerMock->expects($this->once())
            ->method('publish')
            ->with(
                $this->equalTo($messageBody),
                $this->equalTo(''),
                $this->equalTo([]),
                $this->equalTo([])
            );

        $slackNotificationProducer = new SlackNotificationProducer($this->producerMock);

        $slackNotificationProducer->sendSlackNotification($params);
    }

    public function testSendNotificationWithOneRetryNumber()
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

        $slackNotificationProducer = new SlackNotificationProducer($this->producerMock);

        $slackNotificationProducer->sendSlackNotification(['some' => 'options', 'retryNumber' => 0], 1);
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

        $slackNotificationProducer = new SlackNotificationProducer($this->producerMock);

        $slackNotificationProducer->sendSlackNotification(['some' => 'options', 'retryNumber' => 1], 2);
    }
}
