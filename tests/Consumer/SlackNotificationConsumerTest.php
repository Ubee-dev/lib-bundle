<?php


namespace  Khalil1608\LibBundle\Tests\Consumer;

use Khalil1608\LibBundle\Consumer\SlackNotificationConsumer;
use Khalil1608\LibBundle\Producer\SlackNotificationProducer;
use Khalil1608\LibBundle\Service\OpsAlertManager;
use PHPUnit\Framework\MockObject\MockObject;

class SlackNotificationConsumerTest extends AbstractConsumerCase
{
    /** @var SlackNotificationConsumer */
    private $slackConsumer;
    
    /** @var MockObject|OpsAlertManager */
    private $opsAlertManagerMock;
    
    /** @var MockObject|SlackNotificationProducer */
    private $slackProducerMock;
    private $parameters;

    public function setUp(): void
    {
        parent::setUp();
        $this->opsAlertManagerMock = $this->getMockedClass(OpsAlertManager::class);
        $this->slackProducerMock = $this->getMockedClass(SlackNotificationProducer::class);
        $this->slackConsumer = $this->initConsumer();
        $this->parameters =  [
            'some' => 'parameters',
            'retryNumber' => 0
        ];
    }

    public function testSendSlackNotificationSuccessfully()
    {
        $this->createAMPMessage($this->parameters);

        $this->opsAlertManagerMock->expects($this->once())
            ->method('sendSlackNotification')
            ->with($this->equalTo(['some' => 'parameters']));

        $this->slackConsumer->execute($this->message);
    }

    public function testSendSlackNotificationSuccessfullyWithoutEncodeParameter()
    {
        unset($this->parameters['encodeParameters']);
        $this->createAMPMessage($this->parameters);

        $this->opsAlertManagerMock->expects($this->once())
            ->method('sendSlackNotification')
            ->with($this->equalTo(['some' => 'parameters']));

        $this->slackConsumer->execute($this->message);
    }

    public function testSendSlackNotificationRetriesOnFirstFail()
    {
        $this->createAMPMessage($this->parameters);

        $this->opsAlertManagerMock->expects($this->once())
            ->method('sendSlackNotification')
            ->with($this->equalTo(['some' => 'parameters']))
            ->willThrowException(new \Exception('some error'));

        $this->parameters['retryNumber'] = 1 ;
        $this->slackProducerMock->expects($this->once())
            ->method('sendSlackNotification')
            ->with(
                $this->equalTo($this->parameters),
                $this->equalTo(1)
            );

        $this->slackConsumer->execute($this->message);
    }

    public function testSendEmailRetriesOnSecondFail()
    {
        $this->parameters['retryNumber'] = 1;
        $this->createAMPMessage($this->parameters);

        $this->opsAlertManagerMock->expects($this->once())
            ->method('sendSlackNotification')
            ->with($this->equalTo(['some' => 'parameters']))
            ->willThrowException(new \Exception('some error'));

        $this->parameters['retryNumber'] = 2;
        $this->slackProducerMock->expects($this->once())
            ->method('sendSlackNotification')
            ->with(
                $this->equalTo($this->parameters),
                $this->equalTo(2)
            );

        $this->slackConsumer->execute($this->message);
    }

    public function testSendEmailNotifyErrorOnThirdFail()
    {
        $this->parameters['retryNumber'] = 2;
        $this->createAMPMessage($this->parameters);

        $this->opsAlertManagerMock->expects($this->once())
            ->method('sendSlackNotification')
            ->with($this->equalTo(['some' => 'parameters']))
            ->willThrowException(new \Exception('some error'));

        $this->slackProducerMock->expects($this->never())
            ->method('sendSlackNotification');

        $this->errorProducerMock->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->equalTo(SlackNotificationConsumer::class),
                $this->equalTo('sendSlackNotification'),
                $this->equalTo($this->parameters),
                $this->equalTo('some error')
            );

        $this->slackConsumer->execute($this->message);
    }

    private function initConsumer()
    {
        return new SlackNotificationConsumer(
            $this->opsAlertManagerMock,
            $this->errorProducerMock,
            $this->slackProducerMock
        );
    }
}
