<?php

declare(strict_types=1);

require __DIR__ . '/../src/NmailException.php';
require __DIR__ . '/../src/NmailClient.php';

use Nythral\Nmail\NmailClient;
use Nythral\Nmail\NmailException;

$client = new NmailClient('nmail_live_test');
$error = new NmailException('Failed', 403, 'ses_domain_required');

if (!$client instanceof NmailClient || $error->errorCode !== 'ses_domain_required') {
    throw new RuntimeException('Nmail PHP SDK smoke test failed');
}

echo "ok\n";
