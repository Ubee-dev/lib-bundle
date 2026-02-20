<?php


namespace UbeeDev\LibBundle\Tests\Producer;

use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use OldSound\RabbitMqBundle\RabbitMq\Producer as RabbitProducer;

class AbstractProducerCase extends AbstractWebTestCase
{
    /** @var RabbitProducer */
    protected $producerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->producerMock = $this->getMockedClass(RabbitProducer::class);
    }
}
