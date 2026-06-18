# nythral/nmail

Server-side PHP client for the Nmail transactional email API.

Keep `NMAIL_API_KEY` in server environment variables. Do not expose the key to browser JavaScript.

## PHP

```php
use Nythral\Nmail\NmailClient;

$nmail = new NmailClient($_ENV['NMAIL_API_KEY']);

$nmail->sendEmail([
    'from' => 'app@yourdomain.com',
    'to' => 'customer@example.com',
    'subject' => 'Order confirmed',
    'text' => 'Your order was confirmed.',
]);
```

## Laravel

```php
$nmail = new \Nythral\Nmail\NmailClient(config('services.nmail.key'));

$nmail->sendEmail([
    'from' => 'app@yourdomain.com',
    'to' => $user->email,
    'subject' => 'Your login code',
    'text' => 'Your login code is ' . $code,
]);
```

## Errors

Failed requests throw `NmailException` with `status`, `errorCode`, and optional `details`.

## TLS certificates

If PHP cannot open HTTPS URLs because `openssl.cafile` points to a missing file, configure PHP with the system CA bundle:

```bash
php -d openssl.cafile=/etc/ssl/certs/ca-certificates.crt your-script.php
```

On production hosts, fix this in `php.ini` instead of passing it per command.
