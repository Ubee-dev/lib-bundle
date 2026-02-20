<?php

namespace UbeeDev\LibBundle\Tests\Consumer;

use UbeeDev\LibBundle\Consumer\ErrorConsumer;
use UbeeDev\LibBundle\Producer\EmailProducer;
use UbeeDev\LibBundle\Service\Slack\JsonSnippet;
use UbeeDev\LibBundle\Service\SlackManager;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

class ErrorConsumerTest extends AbstractWebTestCase
{
    private AMQPMessage $message;
    private array $paramsBody;
    private MockObject|SlackManager $slackManagerMock;
    private EmailProducer|MockObject|Stub $emailProducerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->slackManagerMock = $this->getMockedClass(SlackManager::class);
        $this->emailProducerMock = $this->createStub(EmailProducer::class);

        $this->paramsBody = [
            'component' => 'SomeConsumer',
            'function' => 'some function',
            'params' => ['some-params'],
            'exception' => 'some-exception'
        ];

        $this->message = new AMQPMessage(json_encode($this->paramsBody));
    }

    public function testDoNothingWhenNoChannelConfigured(): void
    {
        $consumer = new ErrorConsumer($this->slackManagerMock, $this->emailProducerMock);

        $this->slackManagerMock->expects($this->never())
            ->method('sendNotification');

        $consumer->execute($this->message);
    }

    public function testSendSlackNotificationSuccessfully(): void
    {
        $consumer = new ErrorConsumer($this->slackManagerMock, $this->emailProducerMock, 'dev');

        $this->slackManagerMock->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->equalTo('dev'),
                $this->equalTo('Error from: '.$this->paramsBody['component'].'->'.$this->paramsBody['function']),
                $this->equalTo(new JsonSnippet($this->paramsBody))
            );

        $consumer->execute($this->message);
    }

    public function testSendSlackNotificationShouldSendAMailIfSlackFails(): void
    {
        $this->emailProducerMock = $this->getMockedClass(EmailProducer::class);
        $consumer = new ErrorConsumer($this->slackManagerMock, $this->emailProducerMock, 'dev', 'error@example.com');

        $this->slackManagerMock->expects($this->once())
            ->method('sendNotification')
            ->willThrowException(new \Exception('Some error'));

        $this->emailProducerMock->expects($this->once())
            ->method('sendMail')
            ->with(
                $this->equalTo('error@example.com'),
                $this->equalTo(['error@example.com']),
                $this->equalTo($this->message->getBody()),
                $this->equalTo('Error from: '.$this->paramsBody['component'].'->'.$this->paramsBody['function'])
            );

        $consumer->execute($this->message);
    }

    public function testSendSlackNotificationShouldNotSendMailIfNoEmailConfigured(): void
    {
        $this->emailProducerMock = $this->getMockedClass(EmailProducer::class);
        $consumer = new ErrorConsumer($this->slackManagerMock, $this->emailProducerMock, 'dev');

        $this->slackManagerMock->expects($this->once())
            ->method('sendNotification')
            ->willThrowException(new \Exception('Some error'));

        $this->emailProducerMock->expects($this->never())
            ->method('sendMail');

        $consumer->execute($this->message);
    }
}
