<?php

declare(strict_types=1);

namespace Nythral\Nmail;

final class NmailClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://nmail.nythral.com',
    ) {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('Nmail API key is required');
        }
    }

    /**
     * @param array{
     *   from: string,
     *   to: string|array<int,string>,
     *   subject: string,
     *   text?: string,
     *   html?: string,
     *   replyTo?: string
     * } $message
     * @return array<string,mixed>
     */
    public function sendEmail(array $message): array
    {
        $payload = json_encode($message, JSON_THROW_ON_ERROR);
        $url = rtrim($this->baseUrl, '/') . '/api/nmail/v1/send';

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Type: application/json',
                ],
                'content' => $payload,
                'ignore_errors' => true,
            ],
        ]);

        $body = file_get_contents($url, false, $context);
        $status = $this->statusCode($http_response_header ?? []);
        $data = json_decode($body === false ? '{}' : $body, true) ?: [];

        if ($status < 200 || $status >= 300) {
            $error = is_array($data['error'] ?? null) ? $data['error'] : [];
            throw new NmailException(
                (string)($error['message'] ?? 'Nmail email request failed'),
                $status,
                (string)($error['code'] ?? 'request_failed'),
                $error['details'] ?? null,
            );
        }

        return $data;
    }

    /**
     * @param array<int,string> $headers
     */
    private function statusCode(array $headers): int
    {
        if (!isset($headers[0]) || !preg_match('/\s(\d{3})\s/', $headers[0], $matches)) {
            return 0;
        }
        return (int)$matches[1];
    }
}
