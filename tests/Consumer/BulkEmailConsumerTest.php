<?php


namespace  Khalil1608\LibBundle\Tests\Consumer;

use Khalil1608\LibBundle\Consumer\BulkEmailConsumer;
use Khalil1608\LibBundle\Producer\BulkEmailProducer;
use Khalil1608\LibBundle\Service\EmailProvider\BulkEmailProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;

class BulkEmailConsumerTest extends AbstractConsumerCase
{
    private BulkEmailConsumer $bulkEmailConsumer;

    private BulkEmailProducer|MockObject $bulkEmailProducerMock;

    private BulkEmailProviderInterface|MockObject $bulkEmailProviderMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->bulkEmailProviderMock = $this->getMockedClass(BulkEmailProviderInterface::class);
        $this->bulkEmailProducerMock = $this->getMockedClass(BulkEmailProducer::class);
        $this->bulkEmailConsumer = $this->initConsumer();
    }

    public function testSendEmailSuccessfully()
    {
        $this->createAMPMessage([
            'some' => 'options',
            'retryNumber' => 0
        ]);

        $this->bulkEmailProviderMock->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo(['some' => 'options'])
            );

        $this->bulkEmailConsumer->execute($this->message);
    }

    public function testSendEmailRetriesOnFirstFail()
    {
        $this->createAMPMessage($parameters = [
            'some' => 'options',
            'retryNumber' => 0
        ]);

        $this->bulkEmailProviderMock->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo(['some' => 'options'])
            )->willThrowException(new \Exception('some error'));

        $this->bulkEmailProducerMock->expects($this->once())
            ->method('sendBulkEmail')
            ->with(
                $this->equalTo([
                    'some' => 'options',
                    'retryNumber' => 1
                ]),
                $this->equalTo(1)
            );

        $this->bulkEmailConsumer->execute($this->message);
    }

    public function testSendEmailRetriesOnSecondFail()
    {
        $this->createAMPMessage($parameters = [
            'some' => 'options',
            'retryNumber' => 1
        ]);

        $this->bulkEmailProviderMock->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo(['some' => 'options'])
            )->willThrowException(new \Exception('some error'));

        $this->bulkEmailProducerMock->expects($this->once())
            ->method('sendBulkEmail')
            ->with(
                $this->equalTo([
                    'some' => 'options',
                    'retryNumber' => 2
                ]),
                $this->equalTo(2)
            );

        $this->bulkEmailConsumer->execute($this->message);
    }

    public function testSendEmailNotifyErrorOnThirdFail()
    {
        $this->createAMPMessage($parameters = [
            'some' => 'options',
            'retryNumber' => 2
        ]);

        $this->bulkEmailProviderMock->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo(['some' => 'options'])
            )->willThrowException(new \Exception('some error'));

        $this->bulkEmailProducerMock->expects($this->never())
            ->method('sendBulkEmail');

        $this->errorProducerMock->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->equalTo(BulkEmailConsumer::class),
                $this->equalTo('sendBulkEmail'),
                $this->equalTo($parameters),
                $this->equalTo('some error')
            );

        $this->bulkEmailConsumer->execute($this->message);
    }

    private function initConsumer()
    {
        return new BulkEmailConsumer(
            $this->bulkEmailProviderMock,
            $this->errorProducerMock,
            $this->bulkEmailProducerMock
        );
    }
}
