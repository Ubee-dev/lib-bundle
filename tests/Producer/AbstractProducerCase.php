<?php


namespace Khalil1608\LibBundle\Tests\Producer;

use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
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
