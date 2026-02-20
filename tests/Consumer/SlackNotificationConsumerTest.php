<?php

namespace UbeeDev\LibBundle\Tests\Consumer;

use UbeeDev\LibBundle\Consumer\SlackNotificationConsumer;
use UbeeDev\LibBundle\Producer\SlackNotificationProducer;
use UbeeDev\LibBundle\Service\Slack\TextSnippet;
use UbeeDev\LibBundle\Service\SlackManager;
use PHPUnit\Framework\MockObject\MockObject;

class SlackNotificationConsumerTest extends AbstractConsumerCase
{
    private SlackNotificationConsumer $slackConsumer;

    /** @var MockObject|SlackManager */
    private MockObject|SlackManager $slackManagerMock;

    /** @var MockObject|SlackNotificationProducer */
    private MockObject|SlackNotificationProducer $slackProducerMock;
    private array $parameters;

    public function setUp(): void
    {
        parent::setUp();
        $this->slackManagerMock = $this->getMockedClass(SlackManager::class);
        $this->slackProducerMock = $this->getMockedClass(SlackNotificationProducer::class);
        $this->slackConsumer = $this->initConsumer();
        $this->parameters = [
            'channel' => 'dev',
            'title' => 'Test notification',
            'snippet' => (new TextSnippet('some content'))->jsonSerialize(),
            'threadTs' => null,
            'retryNumber' => 0,
        ];
    }

    public function testSendSlackNotificationSuccessfully(): void
    {
        $this->createAMPMessage($this->parameters);

        $this->slackManagerMock->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->equalTo('dev'),
                $this->equalTo('Test notification'),
                $this->isInstanceOf(TextSnippet::class),
                $this->isNull()
            );

        $this->slackConsumer->execute($this->message);
    }

    public function testSendSlackNotificationRetriesOnFirstFail(): void
    {
        $this->createAMPMessage($this->parameters);

        $this->slackManagerMock->expects($this->once())
            ->method('sendNotification')
            ->willThrowException(new \Exception('some error'));

        $this->slackProducerMock->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->callback(function (array $options) {
                    return $options['retryNumber'] === 1
                        && $options['channel'] === 'dev';
                })
            );

        $this->slackConsumer->execute($this->message);
    }

    public function testSendEmailRetriesOnSecondFail(): void
    {
        $this->parameters['retryNumber'] = 1;
        $this->createAMPMessage($this->parameters);

        $this->slackManagerMock->expects($this->once())
            ->method('sendNotification')
            ->willThrowException(new \Exception('some error'));

        $this->slackProducerMock->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->callback(function (array $options) {
                    return $options['retryNumber'] === 2;
                })
            );

        $this->slackConsumer->execute($this->message);
    }

    public function testSendEmailNotifyErrorOnThirdFail(): void
    {
        $this->parameters['retryNumber'] = 2;
        $this->createAMPMessage($this->parameters);

        $this->slackManagerMock->expects($this->once())
            ->method('sendNotification')
            ->willThrowException(new \Exception('some error'));

        $this->slackProducerMock->expects($this->never())
            ->method('sendNotification');

        $this->errorProducerMock->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->equalTo(SlackNotificationConsumer::class),
                $this->equalTo('sendNotification'),
                $this->equalTo($this->parameters),
                $this->equalTo('some error')
            );

        $this->slackConsumer->execute($this->message);
    }

    private function initConsumer(): SlackNotificationConsumer
    {
        return new SlackNotificationConsumer(
            $this->slackManagerMock,
            $this->errorProducerMock,
            $this->slackProducerMock
        );
    }
}
