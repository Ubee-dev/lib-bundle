<?php

namespace Khalil1608\LibBundle\Tests\Helper;


class RabbitMQStub
{
    public function publish($msgBody, $routingKey = '', $additionalProperties = array(), ?array $headers = null){}
}