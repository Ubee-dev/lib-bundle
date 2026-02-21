<?php

namespace UbeeDev\LibBundle\Tests\Helper;

use OldSound\RabbitMqBundle\RabbitMq\Producer as RabbitProducer;

class RabbitMQStub extends RabbitProducer
{
    public function __construct()
    {
    }

    public function publish($msgBody, $routingKey = '', $additionalProperties = array(), ?array $headers = null): void
    {
    }
}