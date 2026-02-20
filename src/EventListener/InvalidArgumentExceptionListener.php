<?php

namespace UbeeDev\LibBundle\EventListener;

use UbeeDev\LibBundle\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class InvalidArgumentExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // if exception not invalidArgumentException or is not json request
        if(!$exception instanceof InvalidArgumentException) {
            return;
        }

        $response = new JsonResponse(['message' => $exception->getMessage(), 'errors' => $exception->getErrors(), 'data' => $exception->getData()], 401);

        $event->setResponse($response);
    }
}