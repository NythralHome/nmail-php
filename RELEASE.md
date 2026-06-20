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
- Repository visibility must be public before submitting to public Packagist.
- Required GitHub secrets: `PACKAGIST_USERNAME`, `PACKAGIST_TOKEN`.
- `PACKAGIST_TOKEN` must be a Packagist **Main API Token** for the first package creation. A Safe API Token is enough for later package updates.
- First release: run the `Publish` workflow with `packagist_action=create` and `confirm_publish=publish-nmail-php`.
- Later releases: run the `Publish` workflow with `packagist_action=update` and `confirm_publish=publish-nmail-php`.
- Alternative first release: submit `https://github.com/NythralHome/nmail-php` in the Packagist UI, then use the workflow for updates.

## Creating Packagist credentials

1. Sign in to `https://packagist.org`.
2. Open your profile settings and create a **Main API Token** named `nmail-publish`.
3. Add `PACKAGIST_USERNAME` and `PACKAGIST_TOKEN` to the GitHub repository secrets for `NythralHome/nmail-php`.
4. Do not paste the token into chat, commits, issue comments, or workflow logs.

## Post-Release

- Verify package page is public.
- Install in a temporary project with `composer require nythral/nmail`.
- Run a non-secret mocked test first.
- Ask Owen or Iris for one approved live Nmail API smoke send if needed.
