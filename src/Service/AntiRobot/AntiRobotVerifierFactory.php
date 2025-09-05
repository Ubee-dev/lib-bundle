<?php

namespace Khalil1608\LibBundle\Service\AntiRobot;

use InvalidArgumentException;

class AntiRobotVerifierFactory
{
    private array $verifiers = [];

    public function __construct(
        private readonly string $defaultVerifier = 'honeypot',
        iterable                $verifiers = []
    )
    {
        foreach ($verifiers as $verifier) {
            if ($verifier instanceof AntiRobotVerifierInterface) {
                $this->addVerifier($verifier);
            }
        }
    }

    public function addVerifier(AntiRobotVerifierInterface $verifier): void
    {
        $this->verifiers[$verifier->getName()] = $verifier;
    }

    public function getVerifier(?string $name = null): AntiRobotVerifierInterface
    {
        $verifierName = $name ?? $this->defaultVerifier;

        if (!isset($this->verifiers[$verifierName])) {
            throw new InvalidArgumentException(
                sprintf('Anti-robot verifier "%s" not found. Available: %s',
                    $verifierName,
                    implode(', ', array_keys($this->verifiers))
                )
            );
        }

        return $this->verifiers[$verifierName];
    }

    public function getAvailableVerifiers(): array
    {
        return array_keys($this->verifiers);
    }
}