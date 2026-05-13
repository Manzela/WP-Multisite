<?php
/**
 * SEO Meta Hardening (mu-plugin)
 * 
 * Adds missing SEO meta tags using DYNAMIC data from store_settings.
 * Built with complete awareness of existing hooks to prevent duplicates.
 * 
 * CONFLICT MAP (existing sources — DO NOT duplicate):
 * ┌──────────────────────────────────┬────────────────────────────────┬─────────────────────────┐
 * │ Source                           │ What it outputs                │ When                    │
 * ├──────────────────────────────────┼────────────────────────────────┼─────────────────────────┤
 * │ ProductSeoServiceProvider        │ og:site_name, og:type/url/     │ is_singular('product')  │
 * │                                  │ title/desc/image, product:price│                         │
 * │ store-description.blade.php      │ <meta name="description">     │ archive pages (Blade)   │
 * │ app.blade.php                    │ geo.placename, geo.position,   │ when lat/lng exist      │
 * │                                  │ ICBM                           │                         │
 * │ WC_Structured_Data (enriched by  │ Product JSON-LD (via           │ product pages           │
 * │ ProductSeoServiceProvider)       │ woocommerce_structured_data)   │                         │
 * │ SchemaServiceProvider            │ LocalBusiness, BreadcrumbList  │ product + store pages   │
 * └──────────────────────────────────┴────────────────────────────────┴─────────────────────────┘
 * 
 * THIS PLUGIN ADDS (filling gaps only):
 * 1. hreflang (pt-PT + x-default) — ALL pages (no existing source)
 * 2. Meta description — MAIN SITE HOMEPAGE ONLY (stores have Blade template)
 * 3. OG + Twitter Cards — NON-PRODUCT pages (ProductSeoServiceProvider handles products)
 * 4. Schema enforcement — blocks future WC schema leaks, protects existing pipeline
 * 
 * DATA SOURCE: 100% dynamic from store_settings (seo.*, store_info.*, store_logo).
 * SAFETY: Purely additive. `rm seo-meta-hardening.php` removes all effects.
 * 
 * @since 2026-02-13
 */

if (!defined('ABSPATH')) {
    exit;
}

// ====================================================================
// HELPER: Centralized store data accessor (loaded once, cached)
// Pulls from: store_settings → seo.*, store_info.*, store_logo, social[]
// ====================================================================
function _seo_mh_get_store_data() {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $s = get_option('store_settings');
    $data = [
        'store_name'  => '',
        'description' => '',
        'address'     => '',
        'city'        => '',
        'phone'       => '',
        'email'       => '',
        'latitude'    => '',
        'longitude'   => '',
        'gmb_link'    => '',
        'gmb_name'    => '',
        'ecommerce'   => '',
        'logo_url'    => '',
        'social'      => [],
        'mall'        => '',
        'neighborhood'=> '',
    ];

    if (!is_array($s)) {
        $data['store_name'] = get_bloginfo('name');
        $cache = $data;
        return $cache;
    }

    // SEO tab fields (store_settings.seo.*)
    $data['store_name']   = $s['seo']['store_name'] ?? '';
    $data['gmb_link']     = $s['seo']['gmb_link'] ?? '';
    $data['gmb_name']     = $s['seo']['gmb_name'] ?? '';
    $data['mall']         = $s['seo']['mall'] ?? '';
    $data['neighborhood'] = $s['seo']['neighborhood'] ?? '';

    // Store info fields (store_settings.store_info.*)
    $info = $s['store_info'] ?? [];
    $data['description'] = $info['description'] ?? '';
    $data['address']     = $info['address'] ?? '';
    $data['city']        = $info['city'] ?? '';
    $data['phone']       = $info['phone'] ?? '';
    $data['email']       = $info['email'] ?? '';
    $data['latitude']    = $info['latitude'] ?? '';
    $data['longitude']   = $info['longitude'] ?? '';
    $data['ecommerce']   = $info['ecommerce_link'] ?? '';

    // Logo: resolve from attachment ID first, then fallback chain
    $logo_id = $s['store_logo'] ?? 0;
    if ($logo_id && is_numeric($logo_id)) {
        $url = wp_get_attachment_url((int) $logo_id);
        if ($url) {
            $data['logo_url'] = $url;
        }
    }
    if (!$data['logo_url'] && !empty($info['logo_url'])) {
        $data['logo_url'] = $info['logo_url'];
    }
    if (!$data['logo_url']) {
        $data['logo_url'] = get_site_icon_url(512);
    }

    // Fallback store name: store_info.name → bloginfo
    if (!$data['store_name']) {
        $data['store_name'] = $info['name'] ?? get_bloginfo('name');
    }

    // Social links array
    $data['social'] = $s['social'] ?? [];

    $cache = $data;
    return $cache;
}

/**
 * HELPER: Build meta description from dynamic store data.
 * Priority: store_info.description → constructed from fields → bloginfo
 */
function _seo_mh_build_description() {
    $d = _seo_mh_get_store_data();

    // Primary: use store's own description (strip all HTML)
    if (!empty($d['description'])) {
        $clean = wp_strip_all_tags($d['description']);
        $clean = preg_replace('/\s+/', ' ', trim($clean));
        if (mb_strlen($clean) > 160) {
            $clean = mb_substr($clean, 0, 157) . '...';
        }
        return $clean;
    }

    // Fallback: build dynamically from available fields
    $parts = [];
    if ($d['store_name']) {
        $parts[] = $d['store_name'];
    }
    if ($d['address']) {
        $parts[] = $d['address'];
    }
    if ($d['city']) {
        $parts[] = $d['city'];
    }

    return implode(' - ', $parts);
}

// ====================================================================
// 1. HREFLANG TAGS - ALL pages
// No existing source for hreflang anywhere in the stack.
// Single-language site (pt-PT). x-default = same URL.
// ====================================================================
add_action('wp_head', function () {
    $canonical = '';
    
    if (is_singular()) {
        $canonical = get_permalink();
    } elseif (is_front_page() || is_home()) {
        $canonical = home_url('/');
    } elseif (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term)) {
            $canonical = get_term_link($term);
            if (is_wp_error($canonical)) {
                $canonical = home_url('/');
            }
        }
    } else {
        $canonical = home_url('/');
    }

    if ($canonical) {
        $canonical = esc_url($canonical);
        echo "\n<!-- SEO Meta Hardening: hreflang -->\n";
        echo '<link rel="alternate" hreflang="pt-PT" href="' . $canonical . '" />' . "\n";
        echo '<link rel="alternate" hreflang="x-default" href="' . $canonical . '" />' . "\n";
    }
}, 99);

// ====================================================================
// 2. META DESCRIPTION - MAIN SITE HOMEPAGE ONLY
// 
// Why NOT on store pages? → store-description.blade.php already outputs it
// Why NOT on products?    → ProductSeoServiceProvider already handles it
// The ONLY gap is the main site homepage.
// ====================================================================
add_action('wp_head', function () {
    if (!is_main_site() || !(is_front_page() || is_home())) {
        return;
    }

    // Dynamic: use site tagline from Settings > General
    $desc = get_bloginfo('description');
    if (!$desc) {
        // Fallback: dynamic store count
        $site_count = get_sites([
            'count'    => true,
            'public'   => 1,
            'archived' => 0,
            'deleted'  => 0,
        ]);
        $store_count = max(0, (int) $site_count - 1);
        $desc = get_bloginfo('name') . ' - ' . $store_count . ' lojas';
    }

    echo "\n<!-- SEO Meta Hardening: meta description (main homepage) -->\n";
    echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($desc)) . '" />' . "\n";
}, 99);

// ====================================================================
// 3. OG + TWITTER CARDS - NON-PRODUCT pages only
// 
// Products: SKIP (ProductSeoServiceProvider outputs og:title, og:desc,
//   og:url, og:type, og:image, og:site_name, product:price:amount/currency)
// Store homepage / categories / main site: ADD OG + Twitter from store_settings
// ====================================================================
add_action('wp_head', function () {
    // GUARD: Skip product pages entirely
    if (is_singular('product')) {
        return;
    }

    $d = _seo_mh_get_store_data();

    $og = [
        'title'       => '',
        'description' => '',
        'url'         => '',
        'image'       => $d['logo_url'],
        'type'        => 'website',
        'locale'      => 'pt_PT',
    ];

    if (is_front_page() || is_home()) {
        if (is_main_site()) {
            // Main site: dynamic from bloginfo
            $og['title'] = get_bloginfo('name');
            $tagline = get_bloginfo('description');
            if ($tagline) {
                $og['title'] .= ' - ' . $tagline;
                $og['description'] = $tagline;
            } else {
                // Fallback: dynamic store count (mirrors meta description logic)
                $site_count = get_sites(['count' => true, 'public' => 1, 'archived' => 0, 'deleted' => 0]);
                $store_count = max(0, (int) $site_count - 1);
                $og['description'] = get_bloginfo('name') . ' - ' . $store_count . ' lojas';
            }
            $og['type'] = 'website';
        } else {
            // Store homepage: 100% from store_settings
            $og['title']       = $d['store_name'];
            $og['description'] = _seo_mh_build_description();
            $og['type']        = 'business.business';
        }
        $og['url'] = home_url('/');

    } elseif (is_tax('product_cat')) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term)) {
            $og['title'] = $term->name . ' - ' . $d['store_name'];
            $og['description'] = $term->description
                ? wp_strip_all_tags($term->description)
                : $term->name . ' - ' . $d['store_name'];
            $og['url'] = get_term_link($term);
            if (is_wp_error($og['url'])) {
                $og['url'] = home_url('/');
            }
        }

    } elseif (is_singular()) {
        // Non-product singular pages
        $og['title'] = get_the_title() . ' - ' . $d['store_name'];
        $excerpt = get_the_excerpt();
        $og['description'] = $excerpt ? wp_strip_all_tags($excerpt) : $og['title'];
        $og['url'] = get_permalink();
    }

    // Guard: need at least a title to output anything meaningful
    if (empty($og['title'])) {
        return;
    }

    echo "\n<!-- SEO Meta Hardening: OG + Twitter -->\n";

    // OpenGraph
    echo '<meta property="og:title" content="' . esc_attr($og['title']) . '" />' . "\n";
    if ($og['description']) {
        echo '<meta property="og:description" content="' . esc_attr($og['description']) . '" />' . "\n";
    }
    if ($og['url']) {
        echo '<meta property="og:url" content="' . esc_url($og['url']) . '" />' . "\n";
    }
    echo '<meta property="og:type" content="' . esc_attr($og['type']) . '" />' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr($og['locale']) . '" />' . "\n";
    if ($og['image']) {
        echo '<meta property="og:image" content="' . esc_url($og['image']) . '" />' . "\n";
    }

    // Twitter Card (mirrors OG — no existing source for these on non-product pages)
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($og['title']) . '" />' . "\n";
    if ($og['description']) {
        echo '<meta name="twitter:description" content="' . esc_attr($og['description']) . '" />' . "\n";
    }
    if ($og['image']) {
        echo '<meta name="twitter:image" content="' . esc_url($og['image']) . '" />' . "\n";
    }
}, 99);

// ====================================================================
// 4. SCHEMA ENFORCEMENT (protect existing pipeline)
//
// Current schema architecture:
//   - SchemaServiceProvider: renders Product, LocalBusiness, BreadcrumbList JSON-LD
//   - ProductSeoServiceProvider: enriches WC's product schema via
//     woocommerce_structured_data_product filter
//   - SchemaServiceProvider already blocks: woocommerce_structured_data_breadcrumblist,
//     woocommerce_structured_data_website → __return_empty_array
//
// We reinforce by:
//   a) Ensuring WC's Review/Order schemas don't leak (we don't use those)
//   b) NOT touching WC product schema output (enrichment pipeline depends on it)
// ====================================================================
add_filter('woocommerce_structured_data_review', '__return_empty_array', 999);
add_filter('woocommerce_structured_data_order', '__return_empty_array', 999);

// Reinforce: if SchemaServiceProvider's filters are removed (e.g., by theme update),
// these act as a safety net to prevent WC schema duplication
add_filter('woocommerce_structured_data_breadcrumblist', '__return_empty_array', 999);
add_filter('woocommerce_structured_data_website', '__return_empty_array', 999);
