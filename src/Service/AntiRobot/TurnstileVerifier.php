<?php

namespace Khalil1608\LibBundle\Service\AntiRobot;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

readonly class TurnstileVerifier implements AntiRobotVerifierInterface
{
    private const string VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $secretKey,
        private string $siteKey,
        private ?LoggerInterface $logger = null
    ) {
    }

    public function verify(Request $request, array $parameters): bool
    {
        $token = $parameters['cf-turnstile-response'] ?? '';

        if (empty($token)) {
            $this->logger?->warning('Turnstile token is missing');
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => [
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $request->getClientIp(),
                ],
            ]);

            $data = $response->toArray();

            $success = $data['success'] ?? false;

            if (!$success && isset($data['error-codes'])) {
                $this->logger?->warning('Turnstile verification failed', [
                    'error_codes' => $data['error-codes']
                ]);
            }

            return $success;

        } catch (\Exception $e) {
            $this->logger?->error('Turnstile verification error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getName(): string
    {
        return 'turnstile';
    }

    public function getTemplateData(): array
    {
        return [
            'site_key' => $this->siteKey,
            'script_url' => 'https://challenges.cloudflare.com/turnstile/v0/api.js'
        ];
    }
}