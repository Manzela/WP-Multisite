<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ProductSeoServiceProvider extends ServiceProvider
{
    public function register()
    {
        // 1. Admin Interface
        add_action('add_meta_boxes', [$this, 'addProductSeoMetaBox']);
        add_action('save_post_product', [$this, 'saveProductSeoFields']);

        // 2. Indexing Rules (Modern WP Filter)
        add_filter('wp_robots', [$this, 'handleRobotsFilter']);
        add_filter('robots_txt', [$this, 'handleRobotsTxt'], 999, 2); // Master Robots.txt Override (Force Authority)
        // [FIX] Force wp_robots output if removed by theme/plugins
        if (!has_action('wp_head', 'wp_robots')) {
            add_action('wp_head', 'wp_robots', 1);
        }


        // 3. MASTER CANONICAL LOGIC
        // Priority 0: Remove ALL existing canonicals (Ghost Tag Fix)
        add_action('wp_head', [$this, 'removeGhostCanonicals'], 0);
        // Priority 5: Add OUR canonical
        add_action('wp_head', [$this, 'addCanonicalTag'], 5);

        // 3.1 Feed Canonicalization
        add_action('template_redirect', [$this, 'addFeedCanonicalHeader']);

        // 4. Open Graph
        add_action('wp_head', [$this, 'addOgSiteName']);
        add_action('wp_head', [$this, 'outputOpenGraphTags'], 1);

        // 5. Schema
        add_filter('woocommerce_structured_data_product', [$this, 'enrichProductSchema'], 10, 2);

        // 6. 404 Redirects (Priority 1 = Run Early)
        add_action('template_redirect', [$this, 'customRedirects'], 1);

        add_filter('pre_get_document_title', [$this, 'customDocumentTitle'], 99);
    }

    /**
     * [FIX] Ghost Tag Silencer
     * Removes WP default, WooCommerce default, and common plugin canonicals.
     */
    public function removeGhostCanonicals()
    {
        remove_action('wp_head', 'rel_canonical'); // WP Default
        remove_action('wp_head', 'wc_rel_canonical'); // WooCommerce Not-Default (unlikely but safe)
    }

    /**
     * [FIX] Strict Canonical Logic
     */
    public function addCanonicalTag()
    {
        $canonical_url = '';
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;

        if (is_404()) {
            return; // 404s redirect, no tag needed
        } elseif (is_search()) {
            // Search results should not have self-referencing canonical if noindex
            return;
        } elseif (is_singular()) {
            global $post;
            $custom = get_post_meta($post->ID, '_shopper_canonical_url', true);
            $canonical_url = !empty($custom) ? $custom : get_permalink($post->ID);
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            $link = get_term_link($term);
            if (!is_wp_error($link))
                $canonical_url = $link;
        } elseif (is_post_type_archive()) {
            $canonical_url = get_post_type_archive_link(get_query_var('post_type'));
        } elseif (is_front_page() || is_home()) {
            $canonical_url = home_url('/');
        } elseif (is_shop()) {
            $canonical_url = get_permalink(wc_get_page_id('shop'));
        }

        // Pagination Logic: /page/2/ points to /page/2/
        if ($canonical_url && $paged > 1) {
            global $wp_rewrite;
            if ($wp_rewrite->using_permalinks()) {
                $canonical_url = user_trailingslashit(trailingslashit($canonical_url) . $wp_rewrite->pagination_base . '/' . $paged);
            } else {
                $canonical_url = add_query_arg('paged', $paged, $canonical_url);
            }
        }

        if ($canonical_url) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
        }
    }

    /**
     * [FIX] Early 404 Redirect
     */
    public function customRedirects()
    {
        if (is_404()) {
            wp_redirect(home_url('/'), 301);
            exit;
        }
    }

    /**
     * [FIX] Robots Filter Implementation
     */
    public function handleRobotsFilter($robots)
    {
        // Enforce safe defaults
        $robots['max-snippet'] = '-1';
        $robots['max-image-preview'] = 'large';
        $robots['max-video-preview'] = '-1';

        // Check filtered/faceted URLs via query params (e.g. ?color=red)
        // If query string exists and is not just paged or s (standard WP params), noindex it
        $allowed_params = ['paged', 's', 'product_cat', 'post_type'];
        $has_filters = false;
        if (!empty($_GET)) {
            $current_params = array_keys($_GET);
            $diff = array_diff($current_params, $allowed_params);
            if (!empty($diff)) {
                $has_filters = true;
            }
        }

        if (is_search()) {
            $robots['noindex'] = true;
            $robots['follow'] = true;       // "Noindex, Follow"
            unset($robots['nofollow']);
        } elseif (is_404()) {
            $robots['noindex'] = true;
            $robots['follow'] = true;     // "Noindex, Follow" (Standard)
            unset($robots['nofollow']);
        } elseif ($has_filters) {
            $robots['noindex'] = true;
            $robots['follow'] = true;      // "Noindex, Follow" (Faceted Nav)
            unset($robots['nofollow']);
        } elseif (is_admin() || is_preview()) {
            $robots['noindex'] = true;
            $robots['follow'] = false;    // "Noindex, Nofollow" (Private)
        } else {
            $robots['index'] = true;
            $robots['follow'] = true;     // "Index, Follow" (Default)
            unset($robots['noindex']);
            unset($robots['nofollow']);
        }

        return $robots;
    }

    /**
     * [FIX] Final Master Robots.txt Injection
     * Overwrites default WP robots.txt with the approved AI-Safe, Scalable strategy.
     */
    public function handleRobotsTxt($output, $public)
    {
        $site_url = home_url(); // Dynamic Site URL

        $master_robots = <<<ROBOTS
# --- Shopper Network AI Protocols (2026) ---
# [cite_start]See Google Documentation on AI User Agents [cite: 4431]

# Group 1: AI Search & Citation (ALLOW)
User-agent: OAI-SearchBot
User-agent: ChatGPT-User
User-agent: PerplexityBot
User-agent: ClaudeBot
User-agent: claude-web
User-agent: Bingbot
User-agent: Googlebot
User-agent: Applebot
User-agent: Amazonbot
User-agent: DuckAssistBot
User-agent: Google-InspectionTool
User-agent: Schema-Markup-Validator
Allow: /

# Group 2: AI Model Training & Scraping (BLOCK)
User-agent: GPTBot
User-agent: Google-Extended
User-agent: anthropic-ai
User-agent: Applebot-Extended
User-agent: CCBot
User-agent: Bytespider
User-agent: FacebookBot
User-agent: Diffbot
User-agent: omgili
User-agent: cohere-ai
Disallow: /

# Group 3: General Crawler Directives
User-agent: *

# 1. ESSENTIAL ALLOWS
Allow: /wp-content/uploads/
Allow: /wp-admin/admin-ajax.php
Allow: */feed/ 
Allow: */page/*

# 2. SYSTEM BLOCKS
Disallow: /wp-admin/
Disallow: /wp-includes/
Disallow: /account/
Disallow: /my-account/
Disallow: /cart/
Disallow: /checkout/
Disallow: /session/

# 3. CRAWL BUDGET OPTIMIZATION (Dynamic Filters)
Disallow: /*?*filter=
Disallow: /*?*sort=
Disallow: /*?*orderby=
Disallow: /product-tag/

# 4. SEARCH RESULTS
Disallow: /search
Disallow: /?s=

# Sitemaps
Sitemap: {$site_url}/sitemap.xml
ROBOTS;

        return $master_robots;
    }

    public function addProductSeoMetaBox()
    {
        add_meta_box('shopper_product_seo', 'Shopper SEO', [$this, 'renderProductSeoMetaBox'], 'product', 'normal', 'high');
    }

    private function processHebrewUrl($url)
    {
        return empty($url) ? '' : $url;
    }

    public function renderProductSeoMetaBox($post)
    {
        wp_nonce_field('shopper_product_seo_nonce', 'shopper_product_seo_nonce');
        echo view('partials.admin.product-seo', [
            'meta_title' => get_post_meta($post->ID, '_shopper_meta_title', true),
            'meta_description' => get_post_meta($post->ID, '_shopper_meta_description', true),
            'focus_keywords' => get_post_meta($post->ID, '_shopper_focus_keywords', true),
            'canonical_url' => get_post_meta($post->ID, '_shopper_canonical_url', true),
            'redirect_to' => get_post_meta($post->ID, '_shopper_redirect_to', true),
            'redirect_type' => get_post_meta($post->ID, '_shopper_redirect_type', true),
            'image_alt' => get_post_meta($post->ID, '_shopper_image_alt', true),
            'source_url' => $this->processHebrewUrl(get_post_meta($post->ID, '_shopper_source_url', true)),
            'display_rank' => get_post_meta($post->ID, '_shopper_display_rank', true) ?: 1
        ]);
    }

    public function saveProductSeoFields($post_id)
    {
        if (!isset($_POST['shopper_product_seo_nonce']) || !wp_verify_nonce($_POST['shopper_product_seo_nonce'], 'shopper_product_seo_nonce'))
            return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (!current_user_can('edit_post', $post_id))
            return;

        $fields = ['_shopper_meta_title', '_shopper_meta_description', '_shopper_focus_keywords', '_shopper_canonical_url', '_shopper_redirect_to', '_shopper_redirect_type', '_shopper_image_alt', '_shopper_source_url', '_shopper_display_rank'];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                if ($field === '_shopper_display_rank') {
                    $value = max(1, min(10, absint($value)));
                } elseif ($field !== '_shopper_source_url') {
                    $value = sanitize_text_field($value);
                }
                update_post_meta($post_id, $field, $value);
            }
        }
    }

    public function addFeedCanonicalHeader()
    {
        if (is_feed()) {
            $canonical = home_url('/');
            if (!headers_sent()) {
                header("Link: <$canonical>; rel=\"canonical\"");
            }
        }
    }

    public function outputOpenGraphTags()
    {
        if (!is_singular('product'))
            return;

        global $post;
        $product = function_exists('wc_get_product') ? wc_get_product($post->ID) : null;

        $s_title = get_post_meta($post->ID, '_shopper_meta_title', true) ?: get_the_title();
        $s_desc = get_post_meta($post->ID, '_shopper_meta_description', true) ?: get_the_excerpt();
        $s_img_alt = get_post_meta($post->ID, '_shopper_image_alt', true);
        $thumb_id = get_post_thumbnail_id($post->ID);
        $img_url = $thumb_id ? wp_get_attachment_url($thumb_id) : '';

        echo "\n\n";
        echo '<meta property="og:type" content="business.business" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($s_title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(strip_tags($s_desc)) . '" />' . "\n";

        if ($img_url) {
            echo '<meta property="og:image" content="' . esc_url($img_url) . '" />' . "\n";
            if ($s_img_alt) {
                echo '<meta property="og:image:alt" content="' . esc_attr($s_img_alt) . '" />' . "\n";
            }
        }

        if ($product) {
            echo '<meta property="product:price:amount" content="' . esc_attr($product->get_price()) . '" />' . "\n";
            echo '<meta property="product:price:currency" content="' . esc_attr(get_woocommerce_currency()) . '" />' . "\n";
            echo '<meta property="product:availability" content="' . ($product->is_in_stock() ? 'in stock' : 'out of stock') . '" />' . "\n";
        }
    }

    public function addOgSiteName()
    {
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
    }

    public function customDocumentTitle($title)
    {
        return $title;
    }

    public function enrichProductSchema($data, $product)
    {
        // 1. CONTEXT: FETCH NETWORK BRAND IDENTITY (SELLER)
        $main_blog_id = 1;
        switch_to_blog($main_blog_id);

        $network_settings = get_option('store_settings');
        $global_options = get_site_option('network_store_settings');

        $brand_name = $network_settings['seo']['store_name'] ?? get_bloginfo('name');
        $parent_ecommerce_link = $network_settings['store_info']['ecommerce_link'] ?? '';
        $brand_url = !empty($parent_ecommerce_link) ? $parent_ecommerce_link : get_site_url($main_blog_id);

        $logo_id = $global_options['network_store_logo'] ?? 0;
        $logo_src = $logo_id ? wp_get_attachment_url($logo_id) : '';
        // [FIX] Ensure logo is a valid string url or empty, never boolean false
        $brand_logo = ($logo_src && is_string($logo_src)) ? $logo_src : '';

        $brand_socials = [];
        if (!empty($network_settings['social'])) {
            foreach ($network_settings['social'] as $social) {
                if (!empty($social['url']))
                    $brand_socials[] = $social['url'];
            }
        }
        restore_current_blog();

        // 2. CONTEXT: FETCH LOCAL STORE IDENTITY
        $local_settings = get_option('store_settings');
        $store_info = $local_settings['store_info'] ?? [];

        // 3. CONSTRUCT NODES (Defensive Coding)

        // [FIX] Address Node: Only create if we have required fields
        $address_node = null;
        if (!empty($store_info['address']) && !empty($store_info['city'])) {
            $address_node = [
                '@type' => 'PostalAddress',
                'streetAddress' => $store_info['address'],
                'addressLocality' => $store_info['city'],
                'postalCode' => $store_info['postcode'] ?? '',
                'addressCountry' => $store_info['country'] ?? 'ES'
            ];
        }

        $seller_node = [
            '@type' => 'Organization',
            '@id' => $brand_url . '/#organization',
            'name' => $brand_name,
            'url' => $brand_url,
            'sameAs' => array_values(array_filter($brand_socials))
        ];

        // Only inject logo if valid
        if ($brand_logo) {
            $seller_node['logo'] = $brand_logo;
            $seller_node['image'] = $brand_logo;
        }
        // Only inject address if valid
        if ($address_node) {
            $seller_node['address'] = $address_node;
        }

        $availability_node = [
            '@type' => 'Store', // [FIX] Align with merchant listings expected type
            '@id' => get_site_url() . '/#store',
            'name' => $local_settings['seo']['store_name'] ?? get_bloginfo('name'),
            'telephone' => $store_info['phone'] ?? '',
            'priceRange' => $local_settings['seo']['price_range'] ?? '$$',
        ];

        if ($brand_logo) {
            $availability_node['image'] = $brand_logo;
        }

        // 4. INJECT DESCRIPTION AND IMAGE INTO MAIN PRODUCT DATA
        if (empty($data['image'])) {
            $data['image'] = wp_get_attachment_url($product->get_image_id()) ?: $brand_logo;
        }
        if (empty($data['description'])) {
            $data['description'] = wp_strip_all_tags($product->get_short_description() ?: $product->get_description());
        }

        // 5. INJECT INTO OFFERS
        if (isset($data['offers']) && is_array($data['offers']) && isset($data['offers'][0])) {
            foreach ($data['offers'] as $key => $offer) {
                $data['offers'][$key]['seller'] = $seller_node;
                $data['offers'][$key]['availableAtOrFrom'] = $availability_node;
            }
        } else {
            $data['offers']['seller'] = $seller_node;
            $data['offers']['availableAtOrFrom'] = $availability_node;
        }

        return $data;
    }
}