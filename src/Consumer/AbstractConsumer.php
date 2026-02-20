<?php


namespace UbeeDev\LibBundle\Consumer;

use UbeeDev\LibBundle\Traits\EntityManagerTrait;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

abstract class AbstractConsumer implements ConsumerInterface
{
    use EntityManagerTrait;
}