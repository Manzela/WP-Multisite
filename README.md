# WP-Multisite — production WordPress multisite blueprint for AI-search-aware retail networks

**Sanitized public blueprint of a production WordPress multisite system architected by [Daniel Manzela](https://manzela.github.io/Manzela/) at TNG Shopper.** The production version runs across 234 managed sites for 11 enterprise retailers in 5 countries, integrated with an autonomous content pipeline that ships ~10.5M product detail pages per cycle at $0.0006/page (see [pipeline-observatory](https://manzela.github.io/pipeline-observatory/) for the full architecture).

This repo is the architecture, code patterns, and full functional volume — sanitized so client names, API keys, OAuth tokens, GMB place IDs, customer emails, internal Slack handles, and other PII have been replaced with `example-tenant-*` and `example-network` placeholders. **Business logic, infrastructure design, service-provider architecture, and the full functional volume are preserved exactly as in production.**

---

## What this proves

This is not a sample, a tutorial, or a stripped-down example. It is the production codebase with secrets removed, kept verbatim so the *sheer functional volume* is visible:

- **~7,000 lines of custom theme code** (Sage 10 / Roots / Acorn on PHP 8.1+)
- **10 service providers** doing the heavy lifting (Schema, Sitemap, Permalink, GoogleBusiness, GrowthDashboard, NetworkFields, ProductSeo, StoreFields, DeliveryRules, Theme)
- **5 controllers**
- **93 Blade templates** (`resources/views/`)
- **5 mu-plugins** (network core, security hardening, SEO meta hardening, debug context trace, debug links)
- **2 custom plugins** (event tracker with GCS-backed analytics + GDPR consent + cookie registry; merchantor)
- **Custom `sunrise.php`** — "NUCLEAR SUNRISE" dynamic global blog resolution via longest-path-prefix match against `wp_blogs`, with 3-mode routing
- **Custom `htaccess` template** for subdirectory multisite routing
- All wired together as a self-healing, programmatically-spun-up multisite network

---

## The headline capabilities (what the production system does that off-the-shelf WP doesn't)

### 1. AI-search-aware infrastructure (uniquely on-axis for AI hiring)
- **Custom `llms.txt` generator** (`app/Providers/SitemapServiceProvider.php`) emits llms.txt-spec-compliant Markdown with category links — explicit AI-agent indexing surface.
- **AI-bot-aware `robots.txt`** (`app/Providers/ProductSeoServiceProvider.php`) — explicit ALLOW list for `OAI-SearchBot, ChatGPT-User, PerplexityBot, ClaudeBot, claude-web, Applebot, Amazonbot, DuckAssistBot, Schema-Markup-Validator`; explicit BLOCK list for training crawlers `GPTBot, Google-Extended, anthropic-ai, Applebot-Extended, CCBot, Bytespider, FacebookBot, Diffbot, cohere-ai`.
- **JSON-LD schema generator** (`app/Providers/SchemaServiceProvider.php`, ~1,000 lines): emits multi-typed graphs — `LocalBusiness + Store + Schema.org business_type[]`, `Organization`, `WebSite` with `SearchAction`, `WebPage` with **`SpeakableSpecification`** (direct AI-voice / AEO surface), `OfferShippingDetails`, `MerchantReturnPolicy`, `AggregateRating + Review`, `OpeningHoursSpecification`, `GeoCoordinates`. Disables WC's stock breadcrumb/website schema to avoid duplication.

### 2. Dynamic multisite blog resolution (`sunrise.php`)
WordPress's native subsite lookup is too rigid for the routing patterns this network needs. The custom `sunrise.php`:
- Bypasses native WP blog lookup entirely.
- Queries `wp_blogs` for **longest-path-prefix match** to resolve any subsite from any virtual URL.
- Supports a 3-mode routing scheme: `/st/` (store home), `/pl/` (category — also `/pl/` and `/br/`), `/pd/` (product, intentionally kept on Main Site for cross-blog `switch_to_blog` template inclusion).
- Multilingual base support: `/tienda/`, `/tiendas/`, `/store/`, `/stores/`, `/חנויות/`.

### 3. Programmatic site spin-up + self-healing
`wp-content/mu-plugins/shopper-network-core.php` (~835 lines) wires the `wp_initialize_site` hook to:
- Force `switch_theme('shopper')` on every new subsite.
- Inherit 8 network-level fields from the main site (brand colors, banner, logo, site icon, Google Places API key reference, buy-externally flag, product image style).
- Trigger Google Business sync via the GoogleBusinessServiceProvider.
- Bypass WooCommerce setup wizard.
- Construct virtual paths and update `wp_blogs.path`, `siteurl`, `home`.
- **Multilingual mega-slug single-source-of-truth**: language map (es, en, pt, he); `shopper_get_clean_mega_slug()` produces `{brand}-{core-slug}-{md5(blog_id)[0:6]}` deterministic SEO-friendly URLs.
- **Self-healing slug enforcement**: `shopper_enforce_mega_slug()` runs on `admin_init` and reconstructs the slug from blog title, writes back to `wp_blogs`, `siteurl`, `home`, `store_mega_slug` if mismatched.
- **NS Cloner integration**: `ns_cloner_process_finish` hook restores blogname after the cloner ordering bug, regenerates mega-slug from target title, fixes path/siteurl/home, **resets 11 location-specific store fields** (`address`, `latitude`, `longitude`, `phone`, `gmb_link`, `gmb_name`, etc.) to prevent inheriting source location's GMB profile.

### 4. Permalink rewriting at network scale
`Shopper_Permalink_Manager` (in `shopper-network-core.php`):
- Rewrites `/st/` → `/pd/` for products, `/st/` → `/pl/` for taxonomies.
- Prevents canonical-redirect loops.
- `app/Providers/PermalinkServiceProvider.php` builds full network URLs: `generate_network_url($lang, $mode, $mega_slug, $cat_slug, $prod_slug)`.

### 5. Asset URL rewriting for multilingual paths
`Shopper_Asset_Manager` strips virtual prefixes from `/wp-content/`, `/wp-includes/` URLs to prevent 404s under multilingual paths.

### 6. Network-aware analytics + GDPR
`wp-content/plugins/event-tracker-plugin/` — GCS-backed analytics plugin with cookie registry, GDPR consent flow, growth-dashboard client (sanitized customer list shown as `example-tenant-*.shop`).

### 7. Theme stack
- **Sage 10** (Roots/Acorn — Laravel-style WordPress) on **PHP 8.1+**
- **Tailwind 3.4** + **Alpine.js 3.14**, built with **bud.js 6.24**
- **93 Blade templates** in `resources/views/`
- **2 routes** (`routes/web.php`: 4 policy pages + `/about/` + `/category`; `routes/api.php`: 4 REST endpoints under `custom/v1` — `/products`, `/products/update`, `/validate`, `/stores`, all gated by `current_user_can('edit_products')`)
- **5 controllers**, **10 service providers**

---

## Production context

The production version of this system runs:
- 234 managed websites
- 11 enterprise retail clients
- 5 countries (currently live: Spain, Portugal, Israel; historical: United States, Mexico)
- ~10.5M product detail pages per cycle (via the [autonomous content pipeline](https://manzela.github.io/pipeline-observatory/) that uses this WP infra as the publishing surface)
- Cloudflare CDN in front
- Bing IndexNow integration for direct search-engine push
- Redis Object Cache for `wp_options` flushes
- Microsoft Clarity for behavioral analytics
- Multi-locale (es / en / pt / he)

Live observability: `https://manzela.github.io/pipeline-observatory/`
Architecture diagram: `https://manzela.github.io/pipeline-observatory/architecture.html`
Case studies: `https://manzela.github.io/pipeline-observatory/case-studies.html`

---

## Team & attribution

- **Daniel Manzela** — Lead architect, network core, sunrise.php, schema generator, llms.txt + AI-bot-aware robots.txt design, programmatic spin-up, theme architecture. Author tag: `Antigravity` (Daniel's brand) appears in mu-plugin headers.
- **Sharon Chen** — co-engineer, attributed in `event-tracker-plugin` and `GoogleBusinessServiceProvider` headers.
- One additional engineer rounds out the team-of-3 that shipped this in production.

---

## What's been sanitized vs preserved

| Sanitized (replaced with placeholders) | Preserved (verbatim) |
|---|---|
| Client codenames (10 retailers) → `example-tenant-{a..i}` | Service-provider architecture |
| Customer site domains → `example-tenant-{aa..hh}.shop` | Hook registrations + WP filter wiring |
| `tng-network.com` → `example-network.com` | All Blade template logic |
| `info@tngshopper.com` → `info@example-network.com` | All JSON-LD schema generation |
| `Adidas TLV Mall` demo branch → `Example TLV Demo` | All multilingual mega-slug logic |
| `PrimaPrix Directory` site_name → `Network Directory` | All sunrise.php routing logic |
| Vendor build artifacts (`vendor/`, `node_modules/`, `composer.lock`, `*.bak.*`) | All mu-plugin functionality |
| Storage caches, log files, language files | All custom plugin functionality |
| Production debug scripts (`debug-*.php`, `fix_*.php`, `migration_*.php`) | All controllers |
| WordPress core (not committed; bring your own) | All 93 Blade templates (sanitized of brand strings) |
| Admin credentials, API keys, OAuth tokens | All theme configuration |
| GMB Place IDs, customer addresses, phone numbers | The 1,056-line JSON-LD schema generator |

API keys, OAuth tokens, customer credentials, and database connection strings have been removed. Where the code references an external service (Google Places, Microsoft Clarity, IndexNow, Bing Webmaster Tools, Cloudflare), the integration is preserved but the credential is not — bring your own.

---

## Use as a template

This is published as a reference and a portfolio artefact. To run it:

1. Stand up a vanilla WordPress multisite installation.
2. Drop `wp-content/themes/shopper/` into your `wp-content/themes/`.
3. Drop `wp-content/mu-plugins/*.php` into your `wp-content/mu-plugins/`.
4. Drop `wp-content/plugins/event-tracker-plugin/` and `wp-content/plugins/merchantor-plugin/` into your `wp-content/plugins/`.
5. Drop `wp-content/sunrise.php` into your `wp-content/` and add `define('SUNRISE', 'on');` to your `wp-config.php`.
6. Use `htaccess.multisite-template` as the basis for your `.htaccess`.
7. Run `composer install` inside `themes/shopper/` (Sage 10 dependencies — vendor/ is not committed).
8. Run `npm install && npm run build` inside `themes/shopper/` (build dependencies — node_modules/ and `public/build/` are not committed).
9. Configure your own API credentials for Google Places, Microsoft Clarity, IndexNow, Bing Webmaster Tools, Cloudflare.
10. Replace `example-tenant-*` placeholders with your own tenant codenames.

---

## License

MIT. See [LICENSE](LICENSE).

---

## Related work

- [Antigravity-OS](https://github.com/Manzela/Antigravity-OS) — open-source governance kernel for AI agents (PyPI: `ag-os`); 9-rule Constitution (Rules 00–08); MCP server; Dreaming Module.
- [agent-dag-pipeline](https://github.com/Manzela/agent-dag-pipeline) — open-source 7-node multi-agent DAG with Google ADK + Vertex AI + O-R-A-V evaluation + RLAIF/DPO data flywheel.
- [gemma4-vllm-deployment](https://github.com/Manzela/gemma4-vllm-deployment) — forensic runbook of 20 distinct failure modes deploying Gemma 4 26B-A4B-it MoE on Vertex AI/vLLM.
- [pipeline-observatory](https://github.com/Manzela/pipeline-observatory) — live observability dashboard for the autonomous content pipeline that publishes to the WordPress multisite this repo describes.
