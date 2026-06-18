<?php

declare(strict_types=1);

namespace Nythral\Nmail;

final class NmailClient
{
    private mixed $transport;
    private int $timeoutSeconds;
    private int $maxRetries;
    private int $retryDelayMs;

    /**
     * @param array{
     *   timeoutSeconds?: int,
     *   maxRetries?: int,
     *   retryDelayMs?: int,
     *   transport?: callable(string,array<string,mixed>):array{status:int,body:string}
     * } $options
     */
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://nmail.nythral.com',
        array $options = [],
    ) {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('Nmail API key is required');
        }
        $this->timeoutSeconds = max(1, (int)($options['timeoutSeconds'] ?? 10));
        $this->maxRetries = max(0, min(3, (int)($options['maxRetries'] ?? 0)));
        $this->retryDelayMs = max(0, (int)($options['retryDelayMs'] ?? 250));
        $this->transport = $options['transport'] ?? null;
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
        $this->validateMessage($message);
        return $this->request('/api/nmail/v1/send', $message);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function request(string $path, array $payload): array
    {
        $attempt = 0;

        while ($attempt <= $this->maxRetries) {
            try {
                return $this->requestOnce($path, $payload);
            } catch (NmailException $error) {
                if (!$error->retryable() || $attempt >= $this->maxRetries) {
                    throw $error;
                }
                $this->sleepBeforeRetry($attempt);
            } catch (\RuntimeException $error) {
                if ($attempt >= $this->maxRetries) {
                    throw $error;
                }
                $this->sleepBeforeRetry($attempt);
            }
            $attempt++;
        }

        throw new \RuntimeException('Nmail request failed');
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function requestOnce(string $path, array $payload): array
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $url = rtrim($this->baseUrl, '/') . $path;

        if (is_callable($this->transport)) {
            $result = ($this->transport)($url, $this->requestOptions($body));
            $status = (int)($result['status'] ?? 0);
            $responseBody = (string)($result['body'] ?? '{}');
        } else {
            $context = stream_context_create($this->requestOptions($body));
            $responseBody = file_get_contents($url, false, $context);
            if ($responseBody === false) {
                throw new \RuntimeException('Nmail transport request failed');
            }
            $status = $this->statusCode($http_response_header ?? []);
        }

        $data = json_decode($responseBody, true) ?: [];

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
     * @return array<string,mixed>
     */
    private function requestOptions(string $body): array
    {
        return [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Type: application/json',
                    'User-Agent: nythral/nmail',
                ],
                'content' => $body,
                'ignore_errors' => true,
                'timeout' => $this->timeoutSeconds,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $message
     */
    private function validateMessage(array $message): void
    {
        $this->assertEmail($message['from'] ?? null, 'from');
        $to = $message['to'] ?? null;
        $recipients = is_array($to) ? $to : [$to];
        if ($recipients === [] || array_filter($recipients, fn (mixed $email): bool => !$this->validEmail($email)) !== []) {
            throw new NmailValidationException('Use one or more valid recipient email addresses', 'to');
        }
        if (!is_string($message['subject'] ?? null) || trim((string)$message['subject']) === '') {
            throw new NmailValidationException('Email subject is required', 'subject');
        }
        if (empty($message['text']) && empty($message['html'])) {
            throw new NmailValidationException('Provide text or html content', 'content');
        }
        if (array_key_exists('replyTo', $message)) {
            $this->assertEmail($message['replyTo'], 'replyTo');
        }
    }

    private function assertEmail(mixed $value, string $field): void
    {
        if (!$this->validEmail($value)) {
            throw new NmailValidationException("Use a valid {$field} email address", $field);
        }
    }

    private function validEmail(mixed $value): bool
    {
        return is_string($value) && filter_var(trim($value), FILTER_VALIDATE_EMAIL) !== false;
    }

    private function sleepBeforeRetry(int $attempt): void
    {
        if ($this->retryDelayMs > 0) {
            usleep($this->retryDelayMs * ($attempt + 1) * 1000);
        }
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
