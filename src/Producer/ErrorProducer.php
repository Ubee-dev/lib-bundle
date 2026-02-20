<?php

namespace UbeeDev\LibBundle\Producer;

class ErrorProducer extends AbstractProducer
{
    /**
     * @param string $component
     * @param string $function
     * @param array $params
     * @param string $exception
     */
    public function sendNotification($component, $function, $params = [], $exception = '')
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
