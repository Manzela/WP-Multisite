# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability in this repository — whether in the WordPress multisite blueprint code, a dependency, or any documentation that accidentally exposes a secret or PII — please report it privately rather than opening a public issue.

**Preferred channel:** Open a private security advisory via GitHub:
[github.com/Manzela/WP-Multisite/security/advisories/new](https://github.com/Manzela/WP-Multisite/security/advisories/new)

Alternatively, email the maintainer directly (see [Daniel Manzela's profile](https://manzela.github.io/Manzela/) for contact).

## What to include

- A clear description of the issue and where it lives in the code (file path + line number where possible).
- Steps to reproduce, including the WordPress version, PHP version, and any relevant plugin versions.
- The impact — what could an attacker do with this issue.
- Your proposed fix, if you have one.

## Specific concerns this blueprint cares about

Because this is a public blueprint of a system that runs in production for real enterprise tenants, the maintainers take **sanitization regressions** as security issues. If you find any of the following in the public repo, please report privately:

- A real tenant codename (anything other than `example-tenant-*`).
- A real customer site domain.
- A real API key, OAuth token, secret, database connection string, or password — even one in a backup file or vendor lock-file.
- A real GMB Place ID, customer street address, customer phone number, or customer email.
- A specific city or jurisdiction reference in inline code comments that could narrow down the production deployment.
- Any other identifier that wasn't supposed to leave the production environment.

## What to expect

- Acknowledgment within **5 business days**.
- A first-pass impact assessment within **10 business days**.
- For confirmed issues, a fix or written remediation plan within **30 days** for medium-severity, **7 days** for critical-severity.
- Public credit (if you want it) once a fix is published.

## Out of scope

- Vulnerabilities in third-party plugins (`woocommerce`, `redis-cache`, `ns-cloner-site-copier`, etc.) that are vendored or imported by this blueprint — please report those upstream to their respective maintainers.
- Vulnerabilities in WordPress core itself — report to the [WordPress security team](https://wordpress.org/about/security/).
