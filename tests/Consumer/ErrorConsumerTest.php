<?php

namespace Khalil1608\LibBundle\Tests\Consumer;

use Khalil1608\LibBundle\Consumer\ErrorConsumer;
use Khalil1608\LibBundle\Producer\EmailProducer;
use Khalil1608\LibBundle\Service\OpsAlertManager;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;

class ErrorConsumerTest extends AbstractWebTestCase
{
    /** @var AMQPMessage */
    private $message;
    private $paramsBody;

    /** @var OpsAlertManager|MockObject */
    private $opsAlertManagerMock;

    /** @var MockObject|EmailProducer */
    private $emailProducerMock;

    /** @var MockObject|ErrorConsumer */
    private $errorConsumer;

    public function setUp(): void
    {
        parent::setUp();
        $this->opsAlertManagerMock = $this->getMockedClass(OpsAlertManager::class);
        $this->emailProducerMock = $this->getMockedClass(EmailProducer::class);

        $this->paramsBody = [
            'component' => 'SomeConsumer',
            'function' => 'some function',
            'params' => ['some-params'],
            'exception' => 'some-exception'
        ];

        $this->message = new AMQPMessage(json_encode($this->paramsBody));
        $this->errorConsumer = new ErrorConsumer($this->opsAlertManagerMock, $this->emailProducerMock);
    }

    public function testSendSlackNotificationSuccessfully()
    {
        $this->opsAlertManagerMock->expects($this->once())
            ->method('sendSlackNotification')
            ->with(
                $this->equalTo([
                    'parameters' => $this->paramsBody,
                    'initialComment' => 'Erreur provenant de : '.$this->paramsBody['component'].'->'.$this->paramsBody['function'],
                    'channel' => 'dev'
                ])
            );

        $this->errorConsumer->execute($this->message);
    }

    public function testSendSlackNotificationShouldSendAMailIfSlackFails()
    {
        $this->opsAlertManagerMock->expects($this->once())
            ->method('sendSlackNotification')
            ->with(
                $this->equalTo([
                    'parameters' => $this->paramsBody,
                    'initialComment' => $subject = 'Erreur provenant de : '.$this->paramsBody['component'].'->'.$this->paramsBody['function'],
                    'channel' => 'dev'
                ])
            )->willThrowException(new \Exception('Some error'));

        $this->emailProducerMock->expects($this->once())
            ->method('sendMail')
            ->with(
                $this->equalTo('khalil1608@gmail.com'),
                $this->equalTo(['khalil1608@gmail.com']),
                $this->equalTo($this->message->getBody()),
                $this->equalTo($subject)
            );

        $this->errorConsumer->execute($this->message);
    }
}
