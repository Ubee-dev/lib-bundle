<?php

namespace Khalil1608\LibBundle\Tests\Helper;

use Khalil1608\LibBundle\Exception\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class DummyController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route("/tests/event-listener/invalid-argument-exception",
        name: "invalid_argument_exception",
    )]
    public function invalidArgumentException()
    {
        throw new InvalidArgumentException('Some message', [
            'key' => 'value'
        ], [
            'original' => 'data'
        ]);
    }
}