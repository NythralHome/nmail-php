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
