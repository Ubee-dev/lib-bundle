<?php


namespace UbeeDev\LibBundle\Producer;


use UbeeDev\LibBundle\Tests\Helper\RabbitMQStub;
use OldSound\RabbitMqBundle\RabbitMq\Producer as RabbitProducer;

abstract class AbstractProducer
{
    protected RabbitProducer $producer;
    protected ?string $currentEnv;

    public function __construct(RabbitProducer $producer, ?string $currentEnv = null)
    {
        $this->producer = $currentEnv === 'test' ? new RabbitMQStub() : $producer;
        $this->currentEnv = $currentEnv;
    }

    public function getXDelayForRetryNumber(int $retryNumber): array
    {
        $xDelay = [];

        if($retryNumber === 1) {
            // 5 minutes in milliseconds
            $xDelay = ['x-delay' => 5 * 60 * 1000];
        }
        elseif($retryNumber > 1) {
            // 15 minutes in milliseconds
            $xDelay = ['x-delay' => 15 * 60 * 1000];
        }

        return $xDelay;
    }
}
