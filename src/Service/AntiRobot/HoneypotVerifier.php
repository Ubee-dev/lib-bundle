<?php

namespace UbeeDev\LibBundle\Service\AntiRobot;

use UbeeDev\LibBundle\Service\FormManager;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

readonly class HoneypotVerifier implements AntiRobotVerifierInterface
{
    public function __construct(
        private FormManager      $formManager,
        private ?LoggerInterface $logger = null
    ) {
    }

    public function verify(Request $request, array $parameters): bool
    {
        // Utilise la logique existante du FormManager
        $isRobot = $this->formManager->wasFilledByARobot(
            $request,
            firstNameField: 'name', // Adapté au formulaire de contact
            lastNameField: 'name',  // Même champ car on n'a qu'un champ nom
            emailField: 'email',
            compareFirstAndLastName: false // Désactivé car on n'a qu'un champ nom
        );

        if ($isRobot) {
            $this->logger?->warning('FormManager detected robot activity', [
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent')
            ]);
        }

        return !$isRobot;
    }

    public function getName(): string
    {
        return 'honeypot';
    }

    public function getTemplateData(): array
    {
        return [
            'requires_javascript' => true,
            'script_path' => '/assets/js/anti-spam.js',
        ];
    }
}