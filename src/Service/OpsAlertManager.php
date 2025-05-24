<?php


namespace Khalil1608\LibBundle\Service;

use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpsAlertManager
{
    public const DEFAULT_CHANNEL = 'ops-notifications';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $slackToken,
        private readonly string $currentEnv,
        private readonly bool $muteOpsAlerts = false
    )
    {
    }

    /**
     * @param $options
     * @return mixed
     * @throws TransportExceptionInterface
     */
    public function sendSlackNotification($options)
    {
        if ($this->muteOpsAlerts) {
            return ['notSent' => 'Ops alerts are muted in this environment'];
        }

        $payload = $this->formatAsBlockKit($options);

        $headers = [
            'Authorization' => 'Bearer ' . $this->slackToken,
            'Content-Type' => 'application/json',
        ];

        $response = $this->client->request('POST', 'https://slack.com/api/chat.postMessage', [
            'headers' => $headers,
            'json' => $payload,
        ]);

        $formattedResponse = json_decode($response->getContent(), true);

        if (!array_key_exists('ok', $formattedResponse) || $formattedResponse['ok'] === false) {
            throw new \RuntimeException('Error sending Slack notification. Response: ' . json_encode($formattedResponse));
        }

        return $formattedResponse;
    }

    private function formatAsBlockKit(array $options): array
    {
        $params = $options['parameters'] ?? [];
        $initialComment = $options['initialComment'] ?? '[Notification Ops]';
        $channel = $options['channel'] ?? self::DEFAULT_CHANNEL;

        $fields = [];
        foreach ($params as $key => $value) {
            $stringValue = is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $fields[] = [
                'type' => 'mrkdwn',
                'text' => "*{$key}:*\n{$stringValue}"
            ];
        }

        return [
            'channel' => $channel,
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '📡 ' . $initialComment,
                        'emoji' => true
                    ]
                ],
                [
                    'type' => 'section',
                    'fields' => $fields
                ]
            ]
        ];
    }
}
