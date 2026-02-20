<?php

namespace UbeeDev\LibBundle\EventListener;

use UbeeDev\LibBundle\Entity\Date;
use UbeeDev\LibBundle\Entity\DateTime;
use UbeeDev\LibBundle\Tests\Helper\DateMock;
use UbeeDev\LibBundle\Tests\Helper\DateTimeMock;
use SlopeIt\ClockMock\ClockMock;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[When(env: 'test')]
#[AsEventListener(event: RequestEvent::class, method: 'onKernelRequest')]
class MockTimeListener
{
    public function __construct(
        protected readonly ParameterBagInterface $parameterBag
    )
    {
    }

    /**
     * @throws \Exception
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            // don't do anything if it's not the main request
            return;
        }

        $fileSystem = new Filesystem();
        $mockTimeFilePath = $this->parameterBag->get('kernel.project_dir').'/tests/assets/mockTime'.getenv('TEST_TOKEN').'.txt';
        if($fileSystem->exists($mockTimeFilePath)) {
            ClockMock::freeze(new DateTime(file_get_contents($mockTimeFilePath)));
            uopz_set_mock(DateTime::class, DateTimeMock::class);
            uopz_set_mock(Date::class, DateMock::class);
        }
    }
}