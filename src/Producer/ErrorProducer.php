<?php

namespace UbeeDev\LibBundle\Producer;

class ErrorProducer extends AbstractProducer
{
    public function sendNotification(string $component, string $function, array $params = [], string $exception = ''): void
    {
        $data = [
            'component' => $component,
            'function' => $function,
            'params' => $params,
            'exception' => $exception
        ];

        $messageBody = \json_encode($data);
        $this->producer->publish($messageBody);
    }
}
