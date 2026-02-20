<?php

namespace UbeeDev\LibBundle\Twig;

use UbeeDev\LibBundle\Service\AntiRobot\AntiRobotVerifierFactory;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AntiRobotExtension extends AbstractExtension
{
    public function __construct(
        private readonly AntiRobotVerifierFactory $verifierFactory
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('anti_robot_data', $this->getAntiRobotData(...)),
            new TwigFunction('anti_robot_verifier', $this->getVerifierName(...)),
        ];
    }

    public function getAntiRobotData(?string $verifierName = null): array
    {
        return $this->verifierFactory->getVerifier($verifierName)->getTemplateData();
    }

    public function getVerifierName(?string $verifierName = null): string
    {
        return $this->verifierFactory->getVerifier($verifierName)->getName();
    }
}