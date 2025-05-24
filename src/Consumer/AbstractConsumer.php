<?php


namespace Khalil1608\LibBundle\Consumer;

use Khalil1608\LibBundle\Traits\EntityManagerTrait;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

abstract class AbstractConsumer implements ConsumerInterface
{
    use EntityManagerTrait;
}