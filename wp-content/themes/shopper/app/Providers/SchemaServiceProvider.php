<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SchemaServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        add_action('wp_head', [$this, 'renderSchema']);

        // START [Schema-Dedup] Suppress WooCommerce default structured data
        // Our SchemaServiceProvider is the single source of truth for all schema types.
        // WooCommerce outputs duplicate BreadcrumbList + WebSite via WC_Structured_Data.
        add_filter('woocommerce_structured_data_breadcrumblist', '__return_empty_array');
        add_filter('woocommerce_structured_data_website', '__return_empty_array');
        // END [Schema-Dedup]
    }

    // ═══════════════════════════════════════════════
    //  ROUTING
    // ═══════════════════════════════════════════════

    public function renderSchema()
    {
        $req_uri = $_SERVER['REQUEST_URI'] ?? '';
        // CROSS-SITE DETECTION: If on main site but URL has /pd/, /pl/, /st/ patterns,
        // resolve the subsite and render its schema instead of main-site schema.
        if (is_main_site() && preg_match('#/(?:tiendas|stores|%D7%97%D7%A0%D7%95%D7%99%D7%95%D7%AA)/(pd|pl|st)/([^/]+)(?:/([^/?]+))?#', $req_uri, $m)) {
            $this->renderCrossSiteSchema($m[1], $m[2], $m[3] ?? '');
            return;
        }

        if (is_main_site()) {
            $this->renderMainSiteSchema();
            return;
        }

        // Subsites (when sunrise correctly resolved the blog)
        if (is_front_page()) {
            $this->renderStoreSiteSchema();
        } elseif (function_exists('is_product_category') && is_product_category()) {
            $this->renderCategorySchema();
        } elseif (function_exists('is_product') && is_product()) {
            global $product;
            if ($product) {
                $this->renderProductSchema($product);
            }
        }
    }

    /**
     * Handle schema rendering for cross-site /pd/, /pl/, /st/ URLs
     * that arrive on the main site because sunrise couldn't resolve the blog.
     */
    private function renderCrossSiteSchema(string $mode, string $megaSlug, string $extraSlug): void
    {
        // Extract store slug from megaslug (strip brand prefix and hash suffix)
        $parts = explode('-', $megaSlug);
        if (count($parts) < 3) {
            $this->renderMainSiteSchema();
            return;
        }
        $storeSlugParts = array_slice($parts, 1, -1);
        $storeSlug = implode('-', $storeSlugParts);

        // LIKE-based blog lookup
        global $wpdb;
        $blogId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT blog_id FROM {$wpdb->blogs}
             WHERE path LIKE %s AND archived = '0' AND deleted = '0'
             ORDER BY LENGTH(path) DESC LIMIT 1",
            '%' . $wpdb->esc_like($storeSlug) . '%'
        ));

        if (!$blogId || $blogId === get_main_site_id()) {
            $this->renderMainSiteSchema();
            return;
        }

        // Switch to the subsite context for schema generation
        switch_to_blog($blogId);

        try {
            if ($mode === 'pd' && $extraSlug) {
                // Product page schema — renderProductSchema handles both simple & variable
                $productPost = get_page_by_path($extraSlug, OBJECT, 'product');
                if ($productPost && function_exists('wc_get_product')) {
                    $product = wc_get_product($productPost->ID);
                    if ($product) {
                        $this->renderProductSchema($product);
                        restore_current_blog();
                        return;
                    }
                }
            } elseif ($mode === 'pl' && $extraSlug) {
                $this->renderCategorySchema();
                restore_current_blog();
                return;
            } elseif ($mode === 'st') {
                $this->renderStoreSiteSchema();
                restore_current_blog();
                return;
            }
        } catch (\Throwable $e) {
            error_log('SCHEMA_CROSSSITE_ERROR: ' . $e->getMessage());
        }

        restore_current_blog();
        $this->renderMainSiteSchema();
    }

    // ═══════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════

    /** Remove null/empty-string values recursively. Keeps 0, false. */
    private function filterSchema(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $v = $this->filterSchema($v);
                if (!empty($v))
                    $out[$k] = $v;
            } elseif (!is_null($v) && $v !== '') {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    /** Shared store/org/policy data for all subsite schemas. */
    private function getStoreContext(): array
    {
        $ss = get_option('store_settings', []);
        $info = $ss['store_info'] ?? [];
        $seo = $ss['seo'] ?? [];
        $url = get_site_url();
        $mid = get_main_site_id();
        $murl = rtrim(get_site_url($mid), '/');
        $cur = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'EUR';
        $ctry = !empty($info['country']) ? $info['country'] : (function_exists('WC') ? WC()->countries->get_base_country() : 'PT');

        // Logo (store → network fallback)
        $ns = get_site_option('network_store_settings', []);
        $lid = $ss['store_logo'] ?? 0;
        $logo = $lid ? wp_get_attachment_url($lid) : null;
        if (!$logo) {
            $nlid = $ns['network_store_logo'] ?? 0;
            $logo = $nlid ? wp_get_attachment_url($nlid) : null;
        }

        // Social
        $social = array_values(array_filter(array_map(fn($s) => $s['url'] ?? '', $ss['social'] ?? [])));
        if (!empty($seo['gmb_link']))
            $social[] = $seo['gmb_link'];

        // Address
        $addr = [
            'streetAddress' => $info['address'] ?? get_option('woocommerce_store_address', ''),
            'addressLocality' => $info['city'] ?? get_option('woocommerce_store_city', ''),
            'postalCode' => $info['postcode'] ?? get_option('woocommerce_store_postcode', ''),
            'addressCountry' => $ctry,
        ];

        // Geo
        $lat = $info['latitude'] ?? null;
        $lng = $info['longitude'] ?? null;
        $geo = ($lat && $lng) ? ['@type' => 'GeoCoordinates', 'latitude' => (float) $lat, 'longitude' => (float) $lng] : null;

        // Parent Org (from main site)
        switch_to_blog($mid);
        $go = get_option('global_options', []);
        $netName = html_entity_decode(get_bloginfo('name'), ENT_QUOTES, 'UTF-8');
        restore_current_blog();

        // Organization URL: prefer store_info[ecommerce_link], fallback to global_options, then main site
        $orgUrl = $info['ecommerce_link'] ?? $go['ecommerce_link'] ?? $murl;
        // Organization name = Network Title (Network Settings > Network Title)
        $orgName = get_site_option('site_name') ?: $netName;

        // Return Policy
        $rs = $ns['network_ecom_refund_return'] ?? [];
        $rd = isset($rs['merchant_return_days']) && $rs['merchant_return_days'] !== '' ? (int) $rs['merchant_return_days'] : 30;
        $rf = !empty($rs['return_fees']) ? $rs['return_fees'] : 'https://schema.org/FreeReturn';

        return [
            'siteUrl' => $url,
            'mainSiteUrl' => $murl,
            'storeName' => $seo['store_name'] ?? html_entity_decode(get_bloginfo('name'), ENT_QUOTES, 'UTF-8'),
            'storeDesc' => !empty($info['description']) ? $info['description'] : html_entity_decode(get_bloginfo('description'), ENT_QUOTES, 'UTF-8'),
            'storeLogo' => $logo,
            'storeInfo' => $info,
            'socialLinks' => $social,
            'address' => $addr,
            'geo' => $geo,
            'hours' => $this->parseHours($info['hours'] ?? []),
            'phone' => $info['phone'] ?? null,
            'email' => $info['email'] ?? null,
            'priceRange' => substr($info['price_range'] ?? '$$', 0, 99),
            'paymentAccepted' => $info['payment_accepted'] ?? 'Cash, Credit Card',
            'currency' => $cur,
            'country' => $ctry,
            'hasMap' => $this->buildMapUrl($addr),
            'bizType' => $info['business_type'] ?? null,
            'mall' => $seo['mall'] ?? null,
            'lang' => get_bloginfo('language'),
            'orgId' => $orgUrl . '/#organization',
            'orgName' => $orgName,
            'orgUrl' => $orgUrl,
            'orgLogo' => $go['parent_org_logo'] ?? $logo,
            'orgSameAs' => array_values(array_filter([
                $orgUrl,
                $go['social_facebook'] ?? null,
                $go['social_instagram'] ?? null,
                $go['social_twitter'] ?? null,
                $go['social_youtube'] ?? null,
                $go['social_gmb'] ?? null,
            ])),
            'returnPolicy' => [
                '@type' => 'MerchantReturnPolicy',
                'applicableCountry' => $ctry,
                'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                'merchantReturnDays' => $rd,
                'returnMethod' => 'https://schema.org/ReturnByMail',
                'returnFees' => $rf,
                'refundType' => 'https://schema.org/FullRefund',
            ],
            'deliveryRules' => $ss['delivery_rules'] ?? [],
        ];
    }

    private function parseHours(array $hours): array
    {
        $map = [
            'sunday' => 'Sunday',
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday'
        ];
        $g = [];
        foreach ($hours as $dk => $s) {
            if (!empty($s['closed']))
                continue;
            $dayName = $map[$dk] ?? $dk;

            // Support nested periods structure (GMB sync & admin form)
            // as well as flat open/close (backward compatibility)
            if (!empty($s['periods']) && is_array($s['periods'])) {
                foreach ($s['periods'] as $period) {
                    $tk = ($period['open'] ?? '09:00') . '-' . ($period['close'] ?? '17:00');
                    $g[$tk][] = $dayName;
                }
            } else {
                // Flat structure fallback
                $tk = ($s['open'] ?? '09:00') . '-' . ($s['close'] ?? '17:00');
                $g[$tk][] = $dayName;
            }
        }
        $out = [];
        foreach ($g as $tk => $days) {
            [$o, $c] = explode('-', $tk);
            $out[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => count($days) === 1 ? $days[0] : $days, 'opens' => $o, 'closes' => $c];
        }
        return $out;
    }

    private function buildMapUrl(array $a): ?string
    {
        $p = array_filter([$a['streetAddress'] ?? '', $a['addressLocality'] ?? '', $a['postalCode'] ?? '', $a['addressCountry'] ?? '']);
        return empty($p) ? null : 'https://www.google.com/maps/search/?api=1&query=' . urlencode(implode(', ', $p));
    }

    private function getShipping(array $rules, string $cur, string $ctry): array
    {
        $d = [];
        foreach ($rules as $r) {
            if (empty($r['active']))
                continue;
            $d[] = [
                '@type' => 'OfferShippingDetails',
                'shippingRate' => ['@type' => 'MonetaryAmount', 'value' => (string) ($r['shipping_cost'] ?? 0), 'currency' => $cur],
                'shippingDestination' => ['@type' => 'DefinedRegion', 'addressCountry' => $ctry]
            ];
        }
        if (empty($d)) {
            $d[] = [
                '@type' => 'OfferShippingDetails',
                'shippingRate' => ['@type' => 'MonetaryAmount', 'value' => '0', 'currency' => $cur],
                'shippingDestination' => ['@type' => 'DefinedRegion', 'addressCountry' => $ctry]
            ];
        }
        return $d;
    }

    private function productImage($product): ?string
    {
        if ($product->get_image_id()) {
            $u = wp_get_attachment_url($product->get_image_id());
            if ($u)
                return $u;
        }
        return get_post_meta($product->get_id(), '_external_image_url', true) ?: null;
    }

    /** Build LocalBusiness schema array from store context. */
    private function buildLocalBusiness(array $c): array
    {
        // Multi-typed: Store + LocalBusiness + Schema.org types from store_info[business_type][]
        // Loads from shared BusinessTypes.php — single source of truth
        $businessTypeData = include get_template_directory() . '/app/Data/BusinessTypes.php';
        $schemaTypeMap = array_map(fn($v) => $v[0], $businessTypeData);

        $rawBiz = is_array($c['bizType']) ? $c['bizType'] : [$c['bizType']];
        $mappedTypes = [];
        foreach ($rawBiz as $bt) {
            if (isset($schemaTypeMap[$bt])) {
                $mappedTypes[] = $schemaTypeMap[$bt];
            }
        }
        $types = array_values(array_unique(array_filter(array_merge(['Store', 'LocalBusiness'], $mappedTypes))));

        $lb = $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => count($types) > 1 ? $types : 'LocalBusiness',
            '@id' => $c['siteUrl'] . '/#store',
            'parentOrganization' => $this->filterSchema([
                '@type' => 'Organization',
                '@id' => $c['orgId'],
                'name' => $c['orgName'],
                'url' => $c['orgUrl'],
                'logo' => $c['orgLogo'],
                'sameAs' => $c['orgSameAs'],
            ]),
            'name' => $c['storeName'],
            'description' => $c['storeDesc'],
            'url' => $c['siteUrl'],
            'logo' => $c['storeLogo'],
            'image' => $c['storeLogo'],
            'telephone' => $c['phone'],
            'email' => $c['email'],
            'priceRange' => $c['priceRange'],
            'paymentAccepted' => $c['paymentAccepted'],
            'currenciesAccepted' => $c['currency'],
            'address' => array_merge(['@type' => 'PostalAddress'], $c['address']),
            'geo' => $c['geo'],
            'openingHoursSpecification' => !empty($c['hours']) ? $c['hours'] : null,
            'hasMap' => $c['hasMap'],
            'sameAs' => $c['socialLinks'],
            'brand' => $this->filterSchema([
                '@type' => 'Brand',
                'name' => $c['orgName'],
                'url' => $c['orgUrl'],
                'logo' => $c['orgLogo'],
            ]),
            'areaServed' => !empty($c['address']['addressLocality']) ? [
                ['@type' => 'City', 'name' => $c['address']['addressLocality']],
            ] : null,
            'hasMerchantReturnPolicy' => $c['returnPolicy'],
            'containedInPlace' => !empty($c['mall']) ? $this->filterSchema([
                '@type' => 'ShoppingCenter',
                'name' => $c['mall'],
            ]) : null,
        ]);
        // AggregateRating (only if data exists)
        $ri = $c['storeInfo'];
        if (!empty($ri['rating_value'])) {
            $lb['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $ri['rating_value'], 'reviewCount' => $ri['review_count'] ?? 1, 'bestRating' => '5', 'worstRating' => '1'];
        }

        // GMB Reviews (top-5 star reviews, available on all pages)
        $reviews = get_option('gmb_reviews', false);
        if ($reviews === false) {
            $reviews = get_transient('gmb_place_reviews') ?: get_transient('gmb_reviews');
        }
        if (is_array($reviews) && !empty($reviews)) {
            $rNodes = [];
            foreach (array_slice(array_filter($reviews, fn($r) => ($r['rating'] ?? 0) >= 4), 0, 5) as $rev) {
                $txt = is_array($rev['text'] ?? '') ? ($rev['text']['text'] ?? '') : ($rev['text'] ?? '');
                $aut = $rev['authorAttribution']['displayName'] ?? ($rev['author_name'] ?? 'Google User');
                $rn = [
                    '@type' => 'Review',
                    'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $rev['rating'] ?? 5, 'bestRating' => '5'],
                    'author' => ['@type' => 'Person', 'name' => $aut],
                    'reviewBody' => strip_tags($txt)
                ];
                $pt = $rev['publishTime'] ?? ($rev['time'] ?? null);
                if ($pt)
                    $rn['datePublished'] = is_numeric($pt) ? date('Y-m-d', $pt) : $pt;
                $rNodes[] = $rn;
            }
            if (!empty($rNodes))
                $lb['review'] = $rNodes;
        }

        return $lb;
    }

    /** Build Organization schema array from store context. */
    private function buildOrganization(array $c): array
    {
        return $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $c['orgId'],
            'name' => $c['orgName'],
            'url' => $c['orgUrl'],
            'logo' => $c['orgLogo'],
            'sameAs' => $c['orgSameAs'],
        ]);
    }

    // ═══════════════════════════════════════════════
    //  RENDERERS
    // ═══════════════════════════════════════════════

    // ── MAIN SITE (already working — keep as-is) ──

    public function renderMainSiteSchema()
    {
        $site_url = get_site_url(get_main_site_id());
        $site_name = html_entity_decode(get_bloginfo('name'), ENT_QUOTES, 'UTF-8');
        $site_desc = html_entity_decode(get_bloginfo('description'), ENT_QUOTES, 'UTF-8');
        $site_lang = get_bloginfo('language');

        // Context: Main Site & Global Options
        $mid = get_main_site_id();
        $go = get_option('global_options', []);

        // 1. Publisher / Organization URL Refinement
        if (get_current_blog_id() !== $mid) {
            switch_to_blog($mid);
            $ss = get_option('store_settings', []);
            restore_current_blog();
        } else {
            $ss = get_option('store_settings', []);
        }

        // Ensure we don't pick up empty strings
        $storeEcomLink = $ss['store_info']['ecommerce_link'] ?? '';
        $globalEcomLink = $go['ecommerce_link'] ?? '';

        $ecom = !empty($storeEcomLink) ? $storeEcomLink : (!empty($globalEcomLink) ? $globalEcomLink : $site_url);

        $orgName = get_site_option('site_name') ?: $site_name;
        $orgLogo = $go['parent_org_logo'] ?? '';

        // 2. Return Policy URL Refinement
        $ns = get_site_option('network_store_settings', []);
        $rs = $ns['network_ecom_refund_return'] ?? [];
        $rpUrl = !empty($rs['url']) ? $rs['url'] : ($go['return_policy_url'] ?? ($ecom . '/return-policy'));

        // Social links: Merge Store Info (Main Site) + Global Options
        $storeSocial = array_values(array_filter(array_map(fn($s) => $s['url'] ?? '', $ss['social'] ?? [])));
        $globalSocial = array_values(array_filter([
            $go['social_facebook'] ?? null,
            $go['social_instagram'] ?? null,
            $go['social_twitter'] ?? null,
            $go['social_youtube'] ?? null,
            $go['social_gmb'] ?? null,
        ]));
        $socialLinks = array_values(array_unique(array_merge($storeSocial, $globalSocial)));

        // Country for return policy
        $ctry = !empty($rs['country']) ? $rs['country'] : (function_exists('WC') ? WC()->countries->get_base_country() : 'PT');
        $mainTitle = $orgName;

        // START [Schema-Refactor] Two-Organization Architecture

        // 1a. Parent Organization (Client Brand) — lives at ecommerce_link
        $parentOrgId = $ecom . '/#organization';

        $parentOrganizationSchema = $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $parentOrgId,
            'name' => $orgName,
            'url' => $ecom,
            'logo' => $orgLogo,
            'sameAs' => $socialLinks,
            'hasMerchantReturnPolicy' => [
                '@type' => 'MerchantReturnPolicy',
                'applicableCountry' => $ctry,
                'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                'merchantReturnDays' => isset($rs['merchant_return_days']) && $rs['merchant_return_days'] !== '' ? (int) $rs['merchant_return_days'] : 30,
                'returnMethod' => 'https://schema.org/ReturnByMail',
                'returnFees' => !empty($rs['return_fees']) ? $rs['return_fees'] : 'https://schema.org/FreeReturn',
                'refundType' => 'https://schema.org/FullRefund',
                'merchantReturnLink' => $rpUrl,
            ],
        ]);

        // 1b. Multisite Organization (Store Locator) — lives at site_url
        $multisiteOrgId = $site_url . '/#organization';

        $organizationSchema = $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $multisiteOrgId,
            'name' => $site_name,
            'url' => $site_url,
            'logo' => $orgLogo,
            'parentOrganization' => ['@id' => $parentOrgId],
            'sameAs' => array_values(array_unique(array_filter([$ecom, $ecom . '/stores']))),
        ]);

        // END [Schema-Refactor] Two-Organization Architecture

        // 2. WebSite
        $websiteSchema = $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $site_url . '/#website',
            'name' => $site_name,
            'url' => $site_url,
            'inLanguage' => $site_lang,
            'publisher' => ['@id' => $parentOrgId],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $site_url . '/?s={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ]);

        // 3. WebPage
        $webPageSchema = $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => $site_url . '/#webpage',
            'name' => $mainTitle,
            'url' => $site_url,
            'description' => $site_desc,
            'inLanguage' => $site_lang,
            'isPartOf' => ['@id' => $site_url . '/#website'],
            'about' => ['@id' => $multisiteOrgId],
            'publisher' => ['@id' => $parentOrgId],
            'speakable' => [
                '@type' => 'SpeakableSpecification',
                'cssSelector' => ['.page-title', '.store-count', '.location-list'],
            ],
        ]);

        // 4. ItemList — each ListItem uses ONLY 'item' (not top-level name+url)
        //    Enrich LocalBusiness with address, telephone, priceRange, image
        $sites = get_sites(['public' => 1, 'archived' => 0, 'spam' => 0, 'deleted' => 0, 'number' => 100]);
        $listItems = [];
        $pos = 1;

        // Parent Org Reference to re-use (points to Client Brand)
        $parentOrgRef = ['@id' => $parentOrgId];

        foreach ($sites as $s) {
            if ((int) $s->blog_id === (int) get_main_site_id())
                continue;
            switch_to_blog($s->blog_id);

            $d = get_blog_details($s->blog_id);
            $su = get_site_url($s->blog_id);
            $sn = html_entity_decode($d->blogname, ENT_QUOTES, 'UTF-8');
            $ssStore = get_option('store_settings', []);
            $si = $ssStore['store_info'] ?? [];

            // Build enriched Store/LocalBusiness for this store
            $lb = ['@type' => ['Store', 'LocalBusiness'], 'name' => $sn, 'url' => $su];

            // Address
            $addr = array_filter([
                'streetAddress' => $si['address'] ?? '',
                'addressLocality' => $si['city'] ?? '',
                'postalCode' => $si['postcode'] ?? '',
                'addressCountry' => $si['country'] ?? $ctry,
            ]);
            if (!empty($addr))
                $lb['address'] = array_merge(['@type' => 'PostalAddress'], $addr);

            // Geo
            $la = $si['latitude'] ?? ($si['lat'] ?? null);
            $lo = $si['longitude'] ?? ($si['lng'] ?? null);
            if ($la && $lo)
                $lb['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $la, 'longitude' => (float) $lo];

            // Telephone
            if (!empty($si['phone']))
                $lb['telephone'] = $si['phone'];

            // PriceRange
            if (!empty($si['price_range']))
                $lb['priceRange'] = $si['price_range'];

            // Opening Hours
            if (!empty($si['hours'])) {
                $lb['openingHoursSpecification'] = $this->parseHours($si['hours']);
            }

            // Image/Logo
            $lid = $ssStore['store_logo'] ?? 0;
            $slogo = $lid ? wp_get_attachment_url($lid) : null;
            if ($slogo) {
                $lb['image'] = $slogo;
                $lb['logo'] = $slogo;
            }

            // AggregateRating for carousel item
            if (!empty($si['rating_value'])) {
                $lb['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => $si['rating_value'],
                    'reviewCount' => $si['review_count'] ?? 1,
                    'bestRating' => '5',
                    'worstRating' => '1',
                ];
            }

            // GMB Reviews (Top 5)
            $reviews = get_transient('gmb_place_reviews') ?: get_transient('gmb_reviews');
            if (is_array($reviews) && !empty($reviews)) {
                $rNodes = [];
                foreach (array_slice(array_filter($reviews, fn($r) => ($r['rating'] ?? 0) >= 4), 0, 5) as $rev) {
                    $txt = is_array($rev['text'] ?? '') ? ($rev['text']['text'] ?? '') : ($rev['text'] ?? '');
                    $aut = $rev['authorAttribution']['displayName'] ?? ($rev['author_name'] ?? 'Google User');
                    $rn = [
                        '@type' => 'Review',
                        'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $rev['rating'] ?? 5, 'bestRating' => '5'],
                        'author' => ['@type' => 'Person', 'name' => $aut],
                        'reviewBody' => strip_tags($txt)
                    ];
                    $pt = $rev['publishTime'] ?? ($rev['time'] ?? null);
                    if ($pt)
                        $rn['datePublished'] = is_numeric($pt) ? date('Y-m-d', $pt) : $pt;
                    $rNodes[] = $rn;
                }
                if (!empty($rNodes))
                    $lb['review'] = $rNodes;
            }

            // START [Schema-Refactor] Store-level sameAs: social + ecommerce + GMB
            $storeSameAs = array_values(array_filter(
                array_map(fn($sLink) => $sLink['url'] ?? '', $ssStore['social'] ?? [])
            ));
            // Add ecommerce main site URL
            if (!empty($ecom)) {
                $storeSameAs[] = $ecom;
            }
            // Add GMB link from store info
            $storeGmb = $si['gmb_link'] ?? ($si['google_maps_link'] ?? '');
            if (!empty($storeGmb)) {
                $storeSameAs[] = $storeGmb;
            }
            $storeSameAs = array_values(array_unique($storeSameAs));
            if (!empty($storeSameAs)) {
                $lb['sameAs'] = $storeSameAs;
            }
            // END [Schema-Refactor] Store-level sameAs

            // Parent org reference (linked via ID to Client Brand)
            $lb['parentOrganization'] = $parentOrgRef;

            restore_current_blog();

            // ListItem uses ONLY 'item' — no top-level name/url to avoid mutually exclusive error
            $listItems[] = ['@type' => 'ListItem', 'position' => $pos, 'item' => $this->filterSchema($lb)];
            $pos++;
        }

        $itemListSchema = $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $mainTitle . ' - ' . __('Official Local Directory', 'shopper'),
            'numberOfItems' => count($listItems),
            'itemListElement' => $listItems,
        ]);

        // 5. BreadcrumbList
        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $site_url]],
        ];

        echo view('schema.main-site', compact(
            'parentOrganizationSchema',
            'organizationSchema',
            'websiteSchema',
            'webPageSchema',
            'itemListSchema',
            'breadcrumbSchema'
        ));
    }

    // ── STORE SITE ──

    protected function renderStoreSiteSchema()
    {
        $c = $this->getStoreContext();

        // 1. LocalBusiness
        $lb = $this->buildLocalBusiness($c);

        // GMB Reviews now loaded inside buildLocalBusiness() — available on all pages

        // hasOfferCatalog — top-level product categories
        $cats = get_terms(['taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'number' => 10]);
        if (!empty($cats) && !is_wp_error($cats)) {
            $catItems = array_values(array_map(fn($t) => [
                '@type' => 'OfferCatalog',
                'name' => $t->name,
                'url' => get_term_link($t),
            ], $cats));
            $lb['hasOfferCatalog'] = $this->filterSchema([
                '@type' => 'OfferCatalog',
                'name' => 'Products at ' . $c['storeName'],
                'url' => $c['siteUrl'],
                'itemListElement' => $catItems,
            ]);
        }

        // 2. WebSite with SearchAction
        $ws = $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $c['siteUrl'] . '/#website',
            'url' => $c['siteUrl'],
            'name' => $c['storeName'],
            'inLanguage' => $c['lang'],
            'publisher' => ['@id' => $c['orgId']],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $c['siteUrl'] . '/?s={search_term_string}'],
                'query-input' => 'required name=search_term_string'
            ],
        ]);

        // 3. BreadcrumbList
        $bc = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $c['siteUrl']]]
        ];

        // 4. WebPage
        $wp = $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => $c['siteUrl'] . '/#webpage',
            'url' => $c['siteUrl'],
            'name' => $c['storeName'] . ' | Store Hours, Location & Products',
            'description' => $c['storeDesc'],
            'inLanguage' => $c['lang'],
            'isPartOf' => ['@id' => $c['siteUrl'] . '/#website'],
            'about' => ['@id' => $c['siteUrl'] . '/#store'],
            'publisher' => ['@id' => $c['orgId']],
        ]);

        echo view('schema.store-site', [
            'localBusinessSchema' => $lb,
            'websiteSchema' => $ws,
            'breadcrumbSchema' => $bc,
            'webPageSchema' => $wp,
        ]);
    }

    // ── CATEGORY ──

    protected function renderCategorySchema()
    {
        $c = $this->getStoreContext();
        $cat = get_queried_object();
        if (!$cat)
            return;

        $catUrl = get_term_link($cat);
        $catName = $cat->name ?? '';
        $catDesc = $cat->description ?? '';

        // Products in category
        $items = [];
        $pos = 1;
        $q = new \WP_Query([
            'post_type' => 'product',
            'tax_query' => [['taxonomy' => $cat->taxonomy, 'field' => 'term_id', 'terms' => $cat->term_id]],
            'posts_per_page' => 50,
            'post_status' => 'publish'
        ]);
        while ($q->have_posts()) {
            $q->the_post();
            $p = wc_get_product(get_the_ID());
            if (!$p)
                continue;
            $img = $this->productImage($p);
            $it = [
                '@type' => 'ListItem',
                'position' => $pos,
                'item' => $this->filterSchema([
                    '@type' => 'Product',
                    'name' => $p->get_name(),
                    'url' => get_permalink($p->get_id()),
                    'image' => $img,
                    'brand' => ['@type' => 'Brand', 'name' => $c['orgName']],
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => $p->get_price(),
                        'priceCurrency' => $c['currency'],
                        'availability' => $p->is_in_stock()
                            ? 'https://schema.org/InStock'
                            : 'https://schema.org/OutOfStock',
                        'seller' => ['@id' => $c['orgId']],
                        'availableAtOrFrom' => ['@id' => $c['siteUrl'] . '/#store'],
                    ],
                ]),
            ];
            $items[] = $it;
            $pos++;
        }
        wp_reset_postdata();

        $locality = $c['address']['addressLocality'] ?? '';
        $richName = $locality ? ($catName . ' in ' . $locality . ' | ' . $c['storeName']) : ($catName . ' | ' . $c['storeName']);

        // 1. CollectionPage
        $cp = $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => $catUrl . '#collectionpage',
            'url' => $catUrl,
            'name' => $richName,
            'description' => $catDesc,
            'inLanguage' => $c['lang'],
            'isPartOf' => ['@id' => $c['siteUrl'] . '/#website'],
            'about' => ['@id' => $c['siteUrl'] . '/#store'],
            'publisher' => ['@id' => $c['orgId']],
            'mainEntity' => [
                '@type' => 'ItemList',
                '@id' => $catUrl . '#itemlist',
                'name' => $catName . ' at ' . $c['storeName'],
                'numberOfItems' => count($items),
                'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
                'itemListElement' => $items,
            ],
            'potentialAction' => [
                [
                    '@type' => 'SearchAction',
                    'name' => 'Search ' . $catName,
                    'target' => $catUrl . '?s={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
                [
                    '@type' => 'ViewAction',
                    'name' => 'Browse All ' . $catName,
                    'target' => $catUrl,
                ],
            ],
        ]);

        // 2. LocalBusiness (with nested parentOrganization)
        $lb = $this->buildLocalBusiness($c);

        // 3. BreadcrumbList (Home → Store → Category)
        $bc = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $c['siteUrl']],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $c['storeName'], 'item' => $c['siteUrl']],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $catName, 'item' => $catUrl],
            ]
        ];

        // 4. OfferCatalog (standalone)
        $oc = $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => 'OfferCatalog',
            '@id' => $catUrl . '#catalog',
            'name' => $catName . ' at ' . $c['storeName'],
            'url' => $catUrl,
            'numberOfItems' => count($items),
            'offeredBy' => ['@id' => $c['orgId']],
            'availableAtOrFrom' => ['@id' => $c['siteUrl'] . '/#store'],
        ]);

        // 5. WebSite
        $ws = $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $c['siteUrl'] . '/#website',
            'url' => $c['siteUrl'],
            'name' => $c['storeName'],
            'publisher' => ['@id' => $c['orgId']],
        ]);

        echo view('schema.category', [
            'collectionPageSchema' => $cp,
            'localBusinessSchema' => $lb,
            'breadcrumbSchema' => $bc,
            'offerCatalogSchema' => $oc,
            'websiteSchema' => $ws,
        ]);
    }

    // ── PRODUCT (simple + variable) ──

    protected function renderProductSchema($product)
    {
        if (!$product)
            return;
        $c = $this->getStoreContext();

        // Shared: LocalBusiness
        $lb = $this->buildLocalBusiness($c);
        // Remove @context from LB (it's a reference only on product pages)
        unset(
            $lb['description'],
            $lb['email'],
            $lb['paymentAccepted'],
            $lb['currenciesAccepted'],
            $lb['hasMap'],
            $lb['sameAs'],
            $lb['hasMerchantReturnPolicy']
        );

        // Shared: BreadcrumbList
        $cats = get_the_terms($product->get_id(), 'product_cat');
        $cat = $cats ? $cats[0] : null;
        $bcItems = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $c['siteUrl']]];
        if ($cat) {
            $bcItems[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $cat->name, 'item' => get_term_link($cat)];
            $bcItems[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $product->get_name(), 'item' => get_permalink($product->get_id())];
        } else {
            $bcItems[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $product->get_name(), 'item' => get_permalink($product->get_id())];
        }
        $bc = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $bcItems];

        $shipping = $this->getShipping($c['deliveryRules'], $c['currency'], $c['country']);
        $storeId = $c['siteUrl'] . '/#store';

        if ($product->is_type('variable')) {
            $this->renderVariableProduct($product, $c, $lb, $bc, $shipping, $storeId);
        } else {
            $this->renderSimpleProduct($product, $c, $lb, $bc, $shipping, $storeId);
        }
    }

    private function renderSimpleProduct($product, array $c, array $lb, array $bc, array $shipping, string $storeId)
    {
        $img = $this->productImage($product);
        $brand = $product->get_attribute('brand') ?: $product->get_attribute('pa_brand');

        $ps = $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => get_permalink($product->get_id()) . '#product',
            'name' => $product->get_name(),
            'description' => wp_strip_all_tags($product->get_description()) ?: $product->get_name(),
            'sku' => $product->get_sku() ?: (string) $product->get_id(),
            'url' => get_permalink($product->get_id()),
            'image' => $img ? ['@type' => 'ImageObject', 'url' => $img, 'contentUrl' => $img] : null,
            'brand' => $brand ? ['@type' => 'Brand', 'name' => $brand] : null,
            'category' => strip_tags(wc_get_product_category_list($product->get_id(), ', ')),
            'offers' => [
                '@type' => 'Offer',
                'price' => $product->get_price() ?: '0',
                'priceCurrency' => $c['currency'],
                'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
                'url' => get_permalink($product->get_id()),
                'seller' => ['@id' => $storeId],
                'availableAtOrFrom' => ['@id' => $storeId],
                'shippingDetails' => $shipping,
                'hasMerchantReturnPolicy' => $c['returnPolicy'],
            ],
        ]);
        if ($product->get_review_count() > 0) {
            $ps['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $product->get_average_rating(), 'reviewCount' => $product->get_review_count()];
        }
        $revs = $this->getProductReviews($product);
        if (!empty($revs))
            $ps['review'] = $revs;

        echo view('schema.product', ['product' => $product, 'productSchema' => $ps, 'localBusinessSchema' => $lb, 'breadcrumbSchema' => $bc]);
    }

    private function renderVariableProduct($product, array $c, array $lb, array $bc, array $shipping, string $storeId)
    {
        $brand = $product->get_attribute('brand') ?: $product->get_attribute('pa_brand');
        $variants = [];

        foreach ($product->get_available_variations('objects') as $var) {
            $attrs = $var->get_variation_attributes();
            $vName = $product->get_name();
            foreach ($attrs as $an => $av) {
                $tax = str_replace('attribute_', '', $an);
                $term = get_term_by('slug', $av, $tax);
                $vName .= ' - ' . ($term ? $term->name : $av);
            }
            $vImg = $this->productImage($var) ?: $this->productImage($product);
            $variants[] = $this->filterSchema([
                '@type' => 'Product',
                'name' => $vName,
                'sku' => $var->get_sku() ?: (string) $var->get_id(),
                'url' => $var->get_permalink(),
                'image' => $vImg,
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $var->get_price() ?: '0',
                    'priceCurrency' => $c['currency'],
                    'availability' => $var->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'itemCondition' => 'https://schema.org/NewCondition',
                    'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
                    'seller' => ['@id' => $storeId],
                    'shippingDetails' => $shipping,
                    'hasMerchantReturnPolicy' => $c['returnPolicy'],
                ],
            ]);
        }

        $pg = $this->filterSchema([
            '@context' => 'https://schema.org',
            '@type' => 'ProductGroup',
            'name' => $product->get_name(),
            'url' => get_permalink($product->get_id()),
            'description' => wp_strip_all_tags($product->get_description()) ?: $product->get_name(),
            'brand' => $brand ? ['@type' => 'Brand', 'name' => $brand] : null,
            'hasVariant' => $variants,
        ]);
        if ($product->get_review_count() > 0) {
            $pg['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $product->get_average_rating(), 'reviewCount' => $product->get_review_count()];
        }
        $revs = $this->getProductReviews($product);
        if (!empty($revs))
            $pg['review'] = $revs;

        echo view('schema.variable-product', ['product' => $product, 'productGroupSchema' => $pg, 'localBusinessSchema' => $lb, 'breadcrumbSchema' => $bc]);
    }

    private function getProductReviews($product): array
    {
        if ($product->get_review_count() < 1)
            return [];
        $comments = get_comments(['post_id' => $product->get_id(), 'status' => 'approve', 'type' => 'review', 'number' => 5]);
        return array_map(function ($c) {
            return [
                '@type' => 'Review',
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => get_comment_meta($c->comment_ID, 'rating', true), 'bestRating' => '5'],
                'author' => ['@type' => 'Person', 'name' => $c->comment_author]
            ];
        }, $comments);
    }
}