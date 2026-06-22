<?php

declare(strict_types=1);

require __DIR__ . '/../src/NmailException.php';
require __DIR__ . '/../src/NmailValidationException.php';
require __DIR__ . '/../src/NmailClient.php';

use Nythral\Nmail\NmailClient;
use Nythral\Nmail\NmailException;
use Nythral\Nmail\NmailValidationException;

$calls = 0;
$client = new NmailClient('nmail_live_test', options: [
    'transport' => function (string $url, array $options) use (&$calls): array {
        $calls++;
        $body = json_decode((string)($options['http']['content'] ?? '{}'), true, flags: JSON_THROW_ON_ERROR);
        if ($url !== 'https://nmail.nythral.com/api/nmail/v1/send' || ($body['subject'] ?? '') !== 'Hello') {
            throw new RuntimeException('Unexpected SDK request');
        }

        return ['status' => 202, 'body' => '{"id":"msg_1","status":"queued"}'];
    },
]);
$result = $client->sendEmail([
    'from' => 'app@example.com',
    'to' => 'user@example.com',
    'subject' => 'Hello',
    'text' => 'Welcome',
]);
$error = new NmailException('Failed', 403, 'ses_domain_required');

if (!$client instanceof NmailClient || $error->errorCode !== 'ses_domain_required' || $result['status'] !== 'queued' || $calls !== 1) {
    throw new RuntimeException('Nmail PHP SDK smoke test failed');
}

$retryCalls = 0;
$retryClient = new NmailClient('nmail_live_test', options: [
    'maxRetries' => 1,
    'retryDelayMs' => 1,
    'transport' => function () use (&$retryCalls): array {
        $retryCalls++;
        if ($retryCalls === 1) {
            return ['status' => 503, 'body' => '{"error":{"code":"service_unavailable","message":"Try again"}}'];
        }

        return ['status' => 202, 'body' => '{"id":"msg_2","status":"queued"}'];
    },
]);
$retryResult = $retryClient->sendEmail([
    'from' => 'app@example.com',
    'to' => 'user@example.com',
    'subject' => 'Hello',
    'text' => 'Welcome',
]);

if ($retryResult['id'] !== 'msg_2' || $retryCalls !== 2) {
    throw new RuntimeException('Nmail PHP SDK retry test failed');
}

$invoiceCalls = 0;
$invoiceClient = new NmailClient('nmail_live_test', options: [
    'transport' => function (string $url, array $options) use (&$invoiceCalls): array {
        $invoiceCalls++;
        $body = json_decode((string)($options['http']['content'] ?? '{}'), true, flags: JSON_THROW_ON_ERROR);
        if (
            $url !== 'https://nmail.nythral.com/api/nmail/v1/send'
            || ($body['cc'][0] ?? '') !== 'owner@example.com'
            || ($body['bcc'][0] ?? '') !== 'audit@example.com'
            || ($body['stream'] ?? '') !== 'billing'
            || ($body['idempotencyKey'] ?? '') !== 'invoice:INV-001'
            || ($body['attachments'][0]['filename'] ?? '') !== 'INV-001.pdf'
        ) {
            throw new RuntimeException('Unexpected invoice SDK request');
        }

        return ['status' => 202, 'body' => '{"id":"msg_invoice","status":"queued"}'];
    },
]);
$invoiceClient->sendEmail([
    'from' => 'invoices@example.com',
    'to' => ['billing@example.com'],
    'cc' => ['owner@example.com'],
    'bcc' => ['audit@example.com'],
    'subject' => 'Invoice INV-001',
    'text' => 'Invoice attached.',
    'stream' => 'billing',
    'idempotencyKey' => 'invoice:INV-001',
    'attachments' => [[
        'filename' => 'INV-001.pdf',
        'contentType' => 'application/pdf',
        'contentBase64' => 'JVBERi0xLjQK',
    ]],
]);

if ($invoiceCalls !== 1) {
    throw new RuntimeException('Nmail PHP SDK invoice test failed');
}

try {
    $client->sendEmail([
        'from' => 'app@example.com',
        'to' => 'user@example.com',
        'subject' => 'Hello',
    ]);
    throw new RuntimeException('Validation did not fail');
} catch (NmailValidationException $validationError) {
    if ($validationError->field !== 'content') {
        throw new RuntimeException('Unexpected validation field');
    }
}

echo "ok\n";
