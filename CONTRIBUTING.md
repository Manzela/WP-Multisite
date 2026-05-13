# Contributing to WP-Multisite

Thanks for your interest. This repo is published primarily as a **portfolio artefact and an architectural reference** rather than as an actively maintained product. Pull requests are welcome but please read this short note first.

## Scope of contributions we accept

- **Sanitization improvements** — if you find a string, comment, or code path that still leaks a client name, customer domain, geographic identifier, secret, or other PII that the maintainers missed, please open an issue or PR. We treat sanitization as a hard correctness requirement.
- **Generic functional improvements** — bug fixes, security hardening, or pattern improvements that apply to any WordPress multisite using this blueprint.
- **Documentation improvements** — clarifications, additional examples, or corrections to the README or inline comments.

## Out of scope

- New features tied to a specific business model, vendor, or jurisdiction.
- Re-introducing tenant-, customer-, or location-specific logic.
- Build-process changes that require unsanitized vendor lock-files.

## Workflow

1. Open an issue first for anything beyond a typo. Briefly describe the change and why it's generic enough to fit this blueprint.
2. Fork → branch → PR. Use a descriptive branch name (`fix/...`, `feat/...`, `docs/...`).
3. Sign your commits with a verifiable email.
4. Squash to a small number of focused commits before requesting review.

## Coding style

- PHP: follow the existing project's style (Sage 10 / Roots / Acorn conventions). Run `phpcs` before submitting.
- JS / Blade: match the surrounding code.
- Tabs vs spaces, line endings, and trailing-newline rules are codified in [`.editorconfig`](.editorconfig). Most editors honour it automatically.

## Sanitization checklist for any PR that touches code

Before opening a PR, confirm that your diff does NOT introduce:

- [ ] Real client / tenant codenames (use `example-tenant-*` placeholders).
- [ ] Real customer site domains (use `example-tenant-*.shop` or `example-network.shop`).
- [ ] Real API keys, OAuth tokens, secrets (load from `wp-config.php` constants or env).
- [ ] Real GMB Place IDs, addresses, phone numbers, emails.
- [ ] Specific city, country, or jurisdiction strings in *inline code comments* (configuration data tables are fine).
- [ ] Hebrew, Arabic, or other locale-specific UI text that identifies a particular customer market (multilingual *capability* is fine; specific *content* is not).

If your PR touches the `app/Data/cities.php` placeholder file, please keep it as a generic example list. Do not add real city names from any specific country.

## Reporting a security issue

Please do not open a public issue for security-sensitive findings. Follow the policy in [SECURITY.md](SECURITY.md).

## Code of Conduct

This project follows the [Contributor Covenant v2.1](CODE_OF_CONDUCT.md). By participating, you agree to its terms.
