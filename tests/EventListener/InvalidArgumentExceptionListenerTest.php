<?php

namespace Khalil1608\LibBundle\Tests\EventListener;

use Khalil1608\LibBundle\Tests\AbstractWebTestCase;

class InvalidArgumentExceptionListenerTest extends AbstractWebTestCase
{
    protected bool $initClient = true;

    public function testInvalidArgumentExceptionShouldBeCatchAndJsonSerialized()
    {
        $this->client->request(
            'GET',
            '/tests/event-listener/invalid-argument-exception',
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json']
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Some message', $response['message']);
        $this->assertEquals(['key' => 'value'], $response['errors']);
        $this->assertEquals(['original' => 'data'], $response['data']);
    }
}