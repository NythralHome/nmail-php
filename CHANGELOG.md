# Changelog

## 0.1.0 - 2026-06-18

- Initial PHP/Laravel SDK for Nmail transactional email.
- Added `NmailClient::sendEmail()`.
- Added `NmailException` with `status`, `errorCode`, `details`, and `retryable()`.
- Added `NmailValidationException` for local validation before network requests.
- Added timeout support and opt-in retry for transient failures.
- Added PHP, Laravel, OTP, and order confirmation examples.
