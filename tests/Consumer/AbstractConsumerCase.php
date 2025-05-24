<?php


namespace Khalil1608\LibBundle\Tests\Consumer;

use Khalil1608\LibBundle\Producer\EmailProducer;
use Khalil1608\LibBundle\Producer\ErrorProducer;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;

class AbstractConsumerCase extends AbstractWebTestCase
{
    /** @var errorProducer|MockObject */
    protected $errorProducerMock;

    /** @var AMQPMessage */
    protected $message;

    /** @var EmailProducer|MockObject */
    protected $emailProducerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->errorProducerMock = $this->getMockedClass(ErrorProducer::class);
        $this->emailProducerMock = $this->getMockedClass(EmailProducer::class);
    }

    protected function createAMPMessage($params, bool $useSerialization = false): void
    {
        $this->message = new AMQPMessage($useSerialization ? serialize($params) : json_encode($params));
    }
}
