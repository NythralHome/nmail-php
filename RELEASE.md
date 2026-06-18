# Release Checklist

Use this checklist before running the `Publish` workflow.

## Package

- `composer.json` metadata is current.
- `CHANGELOG.md` has an entry for the release.
- `README.md` examples match the live Nmail API and dashboard docs.
- No API keys, tokens, customer emails, or test recipient lists are present.
- `composer validate --strict` passes in CI.
- PHP syntax checks and `php test/syntax.php` pass locally and in GitHub Actions.

## Packagist

- Package name: `nythral/nmail`.
- Required GitHub secrets: `PACKAGIST_USERNAME`, `PACKAGIST_TOKEN`.
- Publish workflow input `confirm_publish` must be exactly `publish-nmail-php`.

## Post-Release

- Verify package page is public.
- Install in a temporary project with `composer require nythral/nmail`.
- Run a non-secret mocked test first.
- Ask Owen or Iris for one approved live Nmail API smoke send if needed.
