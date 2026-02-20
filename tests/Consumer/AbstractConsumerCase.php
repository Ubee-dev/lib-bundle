<?php


namespace UbeeDev\LibBundle\Tests\Consumer;

use UbeeDev\LibBundle\Producer\EmailProducer;
use UbeeDev\LibBundle\Producer\ErrorProducer;
use UbeeDev\LibBundle\Service\Mailer;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;

class AbstractConsumerCase extends AbstractWebTestCase
{
    /** @var ErrorProducer|MockObject */
    protected $errorProducerMock;

    /** @var AMQPMessage */
    protected $message;

    /** @var EmailProducer|MockObject */
    protected $emailProducerMock;

    /** @var Mailer|MockObject */
    protected $mailerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->errorProducerMock = $this->getMockedClass(ErrorProducer::class);
        $this->emailProducerMock = $this->getMockedClass(EmailProducer::class);
        $this->mailerMock = $this->getMockedClass(Mailer::class);
    }

    protected function createAMPMessage($params, bool $useSerialization = false): void
    {
        $this->message = new AMQPMessage($useSerialization ? serialize($params) : json_encode($params));
    }
}
