# nythral/nmail

Server-side PHP client for the Nmail transactional email API.

Keep `NMAIL_API_KEY` in server environment variables. Do not expose the key to browser JavaScript.

## Install

```bash
composer require nythral/nmail
```

## PHP

```php
use Nythral\Nmail\NmailClient;

$nmail = new NmailClient(
    apiKey: $_ENV['NMAIL_API_KEY'],
    options: ['timeoutSeconds' => 10],
);

$nmail->sendEmail([
    'from' => 'app@yourdomain.com',
    'to' => 'customer@example.com',
    'subject' => 'Order confirmed',
    'text' => 'Your order was confirmed.',
]);
```

## Laravel

```php
$nmail = new \Nythral\Nmail\NmailClient(
    apiKey: config('services.nmail.key'),
);

$nmail->sendEmail([
    'from' => 'app@yourdomain.com',
    'to' => $user->email,
    'subject' => 'Your login code',
    'text' => 'Your login code is ' . $code,
]);
```

Add the key to `config/services.php`:

```php
'nmail' => [
    'key' => env('NMAIL_API_KEY'),
],
```

## OTP example

```php
function sendLoginCode(string $email, string $code): array
{
    $nmail = new \Nythral\Nmail\NmailClient($_ENV['NMAIL_API_KEY']);

    return $nmail->sendEmail([
        'from' => 'app@yourdomain.com',
        'to' => $email,
        'subject' => 'Your login code',
        'text' => "Your login code is {$code}. It expires in 10 minutes.",
    ]);
}
```

## Order confirmation example

```php
$nmail->sendEmail([
    'from' => 'orders@yourdomain.com',
    'to' => $customer->email,
    'subject' => "Order {$order->number} confirmed",
    'html' => '<p>Your order was confirmed.</p>',
]);
```

## Errors

Validation failures throw `NmailValidationException` before any network request.

Failed API requests throw `NmailException` with:

- `status`: HTTP status code.
- `errorCode`: Nmail error code such as `invalid_api_key`, `ses_domain_required`, `daily_limit_exceeded`, `recipient_limit_exceeded`, or `account_suspended`.
- `details`: optional structured metadata.
- `retryable()`: true for transient upstream errors (`502`, `503`, `504`).

```php
use Nythral\Nmail\NmailException;
use Nythral\Nmail\NmailValidationException;

try {
    $nmail->sendEmail($message);
} catch (NmailValidationException $error) {
    logger()->warning('Invalid Nmail message', ['field' => $error->field]);
} catch (NmailException $error) {
    logger()->warning('Nmail API rejected email', [
        'status' => $error->status,
        'code' => $error->errorCode,
    ]);
}
```

## Retries

Automatic retries are disabled by default to avoid duplicate transactional email. You can opt in for transient transport/upstream failures:

```php
$nmail = new NmailClient(
    apiKey: $_ENV['NMAIL_API_KEY'],
    options: [
        'maxRetries' => 1,
        'retryDelayMs' => 250,
    ],
);
```

## TLS certificates

If PHP cannot open HTTPS URLs because `openssl.cafile` points to a missing file, configure PHP with the system CA bundle:

```bash
php -d openssl.cafile=/etc/ssl/certs/ca-certificates.crt your-script.php
```

On production hosts, fix this in `php.ini` instead of passing it per command.
