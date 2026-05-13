# Changelog

All notable changes to this public blueprint are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project loosely follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

— No pending changes.

## [1.1.0] — 2026-05-13

### Changed
- **Full sanitization pass.** Renamed the production theme codename throughout the codebase (theme directory, `style.css` Theme Name, mu-plugin file name and Plugin Name, all function prefixes, all class prefixes, all post-meta key prefixes, all CSS class prefixes, all REST endpoint namespaces, all WordPress text-domain references). The blueprint is now fully neutral with respect to the production product name.
- **Removed all client-, customer-, and jurisdiction-specific identifiers.** Tenant codenames replaced with `example-tenant-{a..i}`. Customer site domains replaced with `example-tenant-{aa..hh}.shop`. Cities reference list (`app/Data/cities.php`) replaced with a generic 15-entry placeholder list. Inline city / mall / address references in code comments replaced with `Example City` / `[REGISTERED COMPANY ADDRESS]`.
- **Removed jurisdiction-specific legal text.** Hebrew-language return-policy clauses in the Returns blade template replaced with English placeholder structure that demonstrates the conditional-locale render pattern without leaking the original content. Hebrew UI strings in delivery-rules, store-fields, weekday names, and product custom-tabs translated to English.
- **README rewritten** to reference the production system in generic terms (no original product name, no specific country names, no specific brand-mention table).

### Added
- `CONTRIBUTING.md` — contribution guidelines including a sanitization checklist for any PR that touches code.
- `SECURITY.md` — private-disclosure policy, with explicit treatment of sanitization regressions as security issues.
- `CODE_OF_CONDUCT.md` — Contributor Covenant v2.1 (link-referenced).
- `.editorconfig` — consistent editor settings across contributors.
- `CHANGELOG.md` — this file.
- "Project hygiene" section in README pointing to all of the above.

### Verified
- Comprehensive grep sweep over the entire repo: zero hits on any of the original tenant codenames, customer domains, jurisdiction names, original product name, original company-name variants, or any non-placeholder Hebrew runs.

## [1.0.0] — 2026-05-13

### Added
- Initial public release of the sanitized WordPress multisite blueprint.
- ~7,000 lines of custom theme code (Sage 10 / Roots / Acorn on PHP 8.1+).
- 10 service providers (Schema, Sitemap, Permalink, GoogleBusiness, GrowthDashboard, NetworkFields, ProductSeo, StoreFields, DeliveryRules, Theme).
- 5 controllers, 93 Blade templates.
- 5 mu-plugins (network core, security hardening, SEO meta hardening, debug context trace, debug links).
- 2 custom plugins (event tracker with GCS-backed analytics + GDPR consent + cookie registry; merchantor).
- Custom `sunrise.php` for dynamic global blog resolution via longest-path-prefix match.
- Custom `htaccess.multisite-template` for subdirectory multisite routing.
- 1,056-line JSON-LD schema generator with `LocalBusiness`, `Store`, `Organization`, `WebSite + SearchAction`, `WebPage + SpeakableSpecification`, `OfferShippingDetails`, `MerchantReturnPolicy`, `AggregateRating + Review`, `OpeningHoursSpecification`, `GeoCoordinates`.
- `llms.txt` generator (llms.txt-spec-compliant Markdown with category links).
- AI-bot-aware `robots.txt` (ALLOW: OAI-SearchBot, ChatGPT-User, PerplexityBot, ClaudeBot, claude-web, Applebot, Amazonbot, DuckAssistBot, Schema-Markup-Validator; BLOCK: GPTBot, Google-Extended, anthropic-ai, Applebot-Extended, CCBot, Bytespider, FacebookBot, Diffbot, cohere-ai).
- Programmatic site spin-up via `wp_initialize_site` hook with self-healing slug enforcement and NS Cloner integration.
- Multilingual mega-slug single-source-of-truth (configurable language map).
- Permalink rewriter and asset URL rewriter for multilingual paths.

### Removed (sanitization at initial release — superseded by v1.1.0)
- All vendor build artefacts (`vendor/`, `node_modules/`, `composer.lock`, `package-lock.json`, `yarn.lock`, `*.bak.*`, storage caches, log files, language files).
- WordPress core (bring your own).
- Production debug / migration / fix utility scripts (`debug-*.php`, `fix_*.php`, `migration_*.php`).
- Admin credentials, API keys, OAuth tokens.
- GMB Place IDs, customer addresses, customer phone numbers, customer emails.
- Note: v1.0.0 sanitization was incomplete; v1.1.0 closes the remaining gaps. Use v1.1.0 or later.

[Unreleased]: https://github.com/Manzela/WP-Multisite/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/Manzela/WP-Multisite/releases/tag/v1.1.0
[1.0.0]: https://github.com/Manzela/WP-Multisite/releases/tag/v1.0.0
