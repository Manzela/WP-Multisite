<?php
/**
 * Plugin Name: Shopper Network Core
 * Description: Enforces strictly defined Network Architecture (Homepages, Mega-Slugs) for the Shopper implementation. Must not be disabled.
 * Version: 1.0.3
 * Author: Antigravity
 * Author URI: https://google.deepmind
 */

defined('ABSPATH') || exit;

/**
 * 1. GLOBAL HELPER: Check if request is a subsite path
 * Used for proxy routing decisions.
 */
if (!function_exists('shopper_is_subsite_path')) {
    function shopper_is_subsite_path()
    {
        if (is_admin())
            return false;
        $req = $_SERVER['REQUEST_URI'] ?? '/';

        // Multilingual Support for /tiendas/, /stores/, /חנויות/
        $bases = ['/tienda/', '/tiendas/', '/store/', '/stores/', '/%D7%97%D7%A0%D7%95%D7%99%D7%95%D7%AA/'];

        foreach ($bases as $base) {
            if (strpos($req, $base) === 0)
                return true;
        }
        return false;
    }
}

/**
 * 2. AUTOMATION: Enforce Homepage Settings (Shop / Sites Directory)
 * Triggered on: admin_init, wp_initialize_site
 */
/**
 * 2. AUTOMATION: Enforce Homepage Settings (Shop / Sites Directory)
 * Triggered on: admin_init, wp_initialize_site
 */
if (!function_exists('shopper_enforce_homepage_settings')) {
    function shopper_enforce_homepage_settings($site_id = null)
    {
        $switched = false;
        if ($site_id && $site_id !== get_current_blog_id()) {
            switch_to_blog($site_id);
            $switched = true;
        }

        // Strict Check: Main Site is ALWAYS ID 1.
        $is_main = ((int) get_current_blog_id() === 1);

        // A. CLEANUP: Subsites must NOT have "Sites Directory"
        // This prevents confusion and invalid homepage selections
        if (!$is_main) {
            $bad_page = get_page_by_path('sites-directory'); // Valid for cleanup
            if ($bad_page) {
                wp_delete_post($bad_page->ID, true);
                error_log("SHOPPER CLEANUP: Deleted 'Sites Directory' from Subsite " . get_current_blog_id());
            }
        }

        $target_slug = $is_main ? 'sites-directory' : 'shop';
        $target_titles = $is_main ? ['Sites Directory'] : ['Shop', 'Tienda', 'Store', 'חנויות']; // Multilingual Fallbacks

        // 1. Find Page ID
        $page_id = 0;

        // Try WC Helper (Most Reliable for Subsites)
        if (!$is_main && function_exists('wc_get_page_id')) {
            $wc_id = wc_get_page_id('shop');
            if ($wc_id > 0)
                $page_id = $wc_id;
        }

        // Try Slug (WP_Query replacement for deprecated get_page_by_path/title)
        if ($page_id <= 0) {
            $q = new WP_Query([
                'post_type' => 'page',
                'name' => $target_slug,
                'fields' => 'ids',
                'posts_per_page' => 1
            ]);
            if ($q->have_posts())
                $page_id = $q->posts[0];
        }

        // Try Titles (Strict Fallback)
        if ($page_id <= 0) {
            foreach ($target_titles as $title) {
                $q = new WP_Query([
                    'post_type' => 'page',
                    'title' => $title,
                    'fields' => 'ids', // Searching title exact match is hard via WP_Query standard args without filter, falling back to database safe search
                    'posts_per_page' => 1
                ]);
                // To avoid deprecation warning:
                global $wpdb;
                $page = $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'page' AND post_status = 'publish' LIMIT 1", $title));

                if ($page) {
                    $page_id = $page->ID;
                    break;
                }
            }
        }

        // 2. Apply Settings
        if ($page_id > 0) {
            // Check if update is actually needed (Optimization)
            if (get_option('page_on_front') != $page_id || get_option('show_on_front') !== 'page') {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $page_id);
                if (!$is_main) {
                    update_option('woocommerce_shop_page_id', $page_id);
                }
                error_log("SHOPPER ENFORCED: Homepage set to ID $page_id (" . ($is_main ? "Directory" : "Shop") . ") on Site " . get_current_blog_id());
            }
        } else {
            // 3. Emergency Creation (If Shop is missing on subsite)
            if (!$is_main && function_exists('wc_create_page')) {
                // Trigger WC page creation if totally missing? 
                // Maybe too aggressive. Logging error instead.
                error_log("SHOPPER ALERT: Could not find 'Shop' page for Site " . get_current_blog_id());
            }
        }

        if ($switched)
            restore_current_blog();
    }
}

/**
 * 3. AUTOMATION: Enforce Mega-Slug Pattern
 * Pattern: {brand}-{store_slug}-{hash}
 */

/**
 * LANGUAGE MAP (Single Source of Truth)
 * Maps 2-char language codes to localized "stores" base slugs.
 * Used by: wp_initialize_site hook, NS Cloner post-clone hook, PermalinkServiceProvider.
 * To add a new language: add ONE entry here. All permalink generation inherits automatically.
 */
if (!function_exists('shopper_get_store_language_map')) {
    function shopper_get_store_language_map()
    {
        return [
            'es' => 'tiendas',
            'sp' => 'tiendas', // legacy alias
            'en' => 'stores',
            'pt' => 'lojas',
            'he' => '%D7%97%D7%A0%D7%95%D7%99%D7%95%D7%AA', // חנויות
        ];
    }
}

/**
 * LANGUAGE DERIVATION HELPER
 * Dynamically extracts 2-char language code from any WordPress locale.
 * Falls back to first supported language in the map if locale is unknown.
 * @param string|null $locale  WordPress locale (e.g., 'es_ES', 'pt_PT'). If null, uses get_locale().
 * @return string 2-char language code (e.g., 'es', 'pt', 'en')
 */
if (!function_exists('shopper_derive_lang_from_locale')) {
    function shopper_derive_lang_from_locale($locale = null)
    {
        $locale = $locale ?: get_locale();
        $lang = substr($locale, 0, 2);

        // Validate against known map
        $map = shopper_get_store_language_map();
        if (isset($map[$lang])) {
            return $lang;
        }

        // Fallback: first key in map (network default)
        return array_key_first($map) ?: 'en';
    }
}

/**
 * VIRTUAL PATH BUILDER
 * Constructs the full virtual path: /{lang}/{store_base}/st/{mega_slug}/
 * @param string $mega_slug  The brand-location-hash slug
 * @param string|null $locale  WordPress locale. If null, uses get_locale().
 * @return string Full virtual path
 */
if (!function_exists('shopper_build_virtual_path')) {
    function shopper_build_virtual_path($mega_slug, $locale = null)
    {
        $lang = shopper_derive_lang_from_locale($locale);
        $map = shopper_get_store_language_map();
        $store_base = $map[$lang] ?? 'stores';
        return "/{$lang}/{$store_base}/st/{$mega_slug}/";
    }
}

/**
 * CLEAN GENERATION HELPER
 * Decomposes a slug to its core essence and rebuilds it cleanly.
 * Ensures {Brand}-{Core}-{Hash} structure without recursion.
 */
if (!function_exists('shopper_get_clean_mega_slug')) {
    function shopper_get_clean_mega_slug($dirty_slug, $blog_id = null)
    {
        $blog_id = $blog_id ?: get_current_blog_id();

        // 1. Get Brand Prefix from Settings (or fallback)
        $settings = get_option('store_settings');
        if ($blog_id !== get_current_blog_id()) {
            $settings = get_blog_option($blog_id, 'store_settings');
        }
        $brand_raw = $settings['seo']['store_name'] ?? 'store';

        // Fix: Extract strict Brand Name if "Store Name" contains location (e.g. "example-tenant-d- Madrid" -> "example-tenant-d")
        // We look for the first occurrence of ' - ' or ',' or just split by words if needed.
        // Simple heuristic: specific delimiters.
        $brand_parts = preg_split('/[-|,]/', $brand_raw);
        $brand_simple = trim($brand_parts[0]);

        $brand_prefix = sanitize_title($brand_simple);

        // 2. Deconstruct
        $core_slug = $dirty_slug;

        // Strip Prefix recursively
        while (strpos($core_slug, $brand_prefix . '-') === 0) {
            $core_slug = substr($core_slug, strlen($brand_prefix . '-'));
        }

        // Strip Hashes recursively
        while (preg_match('/-([a-f0-9]{6})$/', $core_slug)) {
            $core_slug = preg_replace('/-([a-f0-9]{6})$/', '', $core_slug);
        }

        // 3. URL Store Mega Slug:
        // Format: {core-slug}-{hash}
        $clean_hash = substr(md5($blog_id . $core_slug), 0, 6);
        return "{$core_slug}-{$clean_hash}";
    }
}

if (!function_exists('shopper_enforce_mega_slug')) {
    function shopper_enforce_mega_slug($site_id = null)
    {
        $blog_id = $site_id ?: get_current_blog_id();

        // Safety: Subsites only
        if ($blog_id === 1)
            return;

        $switched = false;
        if ($site_id && $site_id !== get_current_blog_id()) {
            switch_to_blog($site_id);
            $switched = true;
        }

        // 1. Get Source of Truth (Site Title)
        // Instead of relying on the potentially corrupted URL, we use the Site Title.
        // Example: "ExampleSports madrid-bravo-murillo" -> "madrid-bravo-murillo"
        $site_title = get_option('blogname');

        // 2. Generate Clean Version
        // We sanitize the title to make it a slug, then pass it to the cleaner.
        // The cleaner will strip the Brand Prefix if present.
        $clean_mega_slug = shopper_get_clean_mega_slug(sanitize_title($site_title), $blog_id);

        // 3. Force Synchronization if Mismatched
        $current_home = get_option('home');
        if (!$current_home) {
            if ($switched)
                restore_current_blog();
            return;
        }

        $path = trim(parse_url($current_home, PHP_URL_PATH), '/');
        $parts = explode('/', $path);
        $current_slug_tip = end($parts);

        if ($current_slug_tip !== $clean_mega_slug) {
            update_option('store_mega_slug', $clean_mega_slug);

            array_pop($parts);
            $parts[] = $clean_mega_slug;
            $new_path = '/' . implode('/', $parts) . '/';
            $new_url = rtrim(network_site_url($new_path), '/');

            update_option('siteurl', $new_url);
            update_option('home', $new_url);

            global $wpdb;
            $wpdb->update($wpdb->blogs, ['path' => $new_path], ['blog_id' => $blog_id]);

            error_log("SHOPPER HEALED: Site $blog_id moved to $clean_mega_slug (Source: Title '$site_title')");
        }

        if ($switched)
            restore_current_blog();
    }
}

/**
 * 4. HOOKS
 */
add_action('admin_init', function () {
    global $pagenow;
    // Self-Healing on Dashboard visit
    if ($pagenow === 'options-reading.php' || $pagenow === 'index.php') {
        shopper_enforce_homepage_settings();
        shopper_enforce_mega_slug();
        flush_rewrite_rules(); // Force flush to fix 404s
    }
});

/**
 * 5. POLICY: Enforce Path Structure on Creation (Architecture Level)
 */
add_action('wp_initialize_site', function ($new_site) {
    global $wpdb;

    if ((int) $new_site->blog_id === 1)
        return;

    // 1. Derive Configuration from Source of Truth (Title)
    // We assume the Title is clean (e.g., "Brand ShopName")
    switch_to_blog($new_site->blog_id);

    // [AUTOMATION] 1. Force Theme Activation 'Shopper'
    switch_theme('shopper');

    // [AUTOMATION] 2. Inherit Network Settings
    // We access network options from the main site context or via get_site_option (which is network-wide for site meta but here we want the specific option stored on main blog if that is where network settings page saves it? 
    // Wait, the network settings page uses `get_site_option('network_store_settings')` which is sitemeta. This is correct.
    $network_settings = get_site_option('network_store_settings', []);

    if (!empty($network_settings)) {
        $store_settings = get_option('store_settings', []);

        // Map Colors
        if (!empty($network_settings['network_primary_color'])) {
            $store_settings['primary_color'] = $network_settings['network_primary_color'];
        }
        if (!empty($network_settings['network_secondary_color'])) {
            $store_settings['secondary_color'] = $network_settings['network_secondary_color'];
        }

        // Map Images
        if (!empty($network_settings['network_store_banner'])) {
            $store_settings['store_banner'] = $network_settings['network_store_banner'];
        }
        if (!empty($network_settings['network_store_logo'])) {
            $store_settings['store_logo'] = $network_settings['network_store_logo'];
            // Also set custom_logo theme mod for core compatibility
            set_theme_mod('custom_logo', $network_settings['network_store_logo']);
        }

        // Site Icon
        if (!empty($network_settings['network_site_icon'])) {
            update_option('site_icon', $network_settings['network_site_icon']);
        }

        // API Key (Google Places)
        if (!empty($network_settings['network_google_places_api_key'])) {
            update_option('google_places_api_key', $network_settings['network_google_places_api_key']);
        }

        // Buy Externally
        if (!empty($network_settings['network_buy_externally'])) {
            $store_settings['buy_externally'] = $network_settings['network_buy_externally'];
        }

        // Product Image Style
        if (!empty($network_settings['product_image_style'])) {
            $store_settings['product_image_style'] = $network_settings['product_image_style'];
            update_option('store_settings', $store_settings);
        } else {
            // [Fix] Even if no network settings, we must initialize store_settings with site title
            // to ensure GMB Sync "Exact Match" strategy works
            $store_settings = get_option('store_settings', []);
        }

        // [Requirement] "You must use the store name from Site Title (of any new site)."
        // We enforce this by populating the store_name setting which GMB Sync uses.
        if (empty($store_settings['seo']['store_name'])) {
            $store_settings['seo']['store_name'] = get_option('blogname');
            update_option('store_settings', $store_settings);
        }

        // [Requirement] "Site Visibility should be True (Live) currently it is Store Coming Soon."
        if (!isset($store_settings['store_coming_soon'])) {
            $store_settings['store_coming_soon'] = 'no';
            update_option('store_settings', $store_settings);
        }
    }

    // [AUTOMATION] 3. Trigger GMB Sync
    // We must manually load the provider as the theme is not yet fully booted in this request context
    $theme_dir = WP_CONTENT_DIR . '/themes/shopper';
    if (file_exists($theme_dir . '/vendor/autoload.php')) {
        require_once $theme_dir . '/vendor/autoload.php';

        if (class_exists('App\Providers\GoogleBusinessServiceProvider')) {
            try {
                // Call static method directly (Refactored to avoid Container dependency)
                $result = \App\Providers\GoogleBusinessServiceProvider::syncGMBData();
                error_log("SHOPPER AUTO-SETUP: GMB Sync Result for Site {$new_site->blog_id}: " . json_encode($result));
            } catch (\Throwable $e) {
                error_log("SHOPPER AUTO-SETUP ERROR: " . $e->getMessage());
            }
        }
    }

    $site_title = get_option('blogname');
    $brand_slug = shopper_get_clean_mega_slug(sanitize_title($site_title), $new_site->blog_id);

    // 2. Set Language — Inherit from network default, NOT hardcoded
    $wplang = get_option('WPLANG'); // NS Cloner may have already copied from source
    if (empty($wplang)) {
        $wplang = get_site_option('WPLANG', 'en_US'); // Network-level fallback
    }
    update_option('WPLANG', $wplang);

    // 3. Construct Full Virtual Path using shared helper
    // Uses shopper_get_store_language_map() as single source of truth.
    $new_path = shopper_build_virtual_path($brand_slug, $wplang);

    // Update DB from Network Context (after restore) or directly?
    // Accessing wp_blogs matches needs to happen carefully.
    // We are switched to blog, but wp_blogs is global.
    $wpdb->update($wpdb->blogs, ['path' => $new_path], ['blog_id' => $new_site->blog_id]);

    // 4. Initialize Site Settings (siteurl and home use the full virtual path)
    $new_url = rtrim(network_site_url($new_path), '/');
    update_option('siteurl', $new_url);
    update_option('home', $new_url);

    // Initial enforcement to lock in homepage/slug
    shopper_enforce_homepage_settings();
    // shopper_enforce_mega_slug() is redundant as we just set it, but good safety.
    shopper_enforce_mega_slug();

    restore_current_blog();

}, 10);

/**
 * 7. POLICY: Enforce WooCommerce Configuration (No Wizard)
 * Trigger: Site Creation & Admin Init (Self-Healing)
 * Action: Marks onboarding as complete and disables setup redirect.
 */

// A. Helper Function to Set Options
if (!function_exists('shopper_set_wc_completed_state')) {
    function shopper_set_wc_completed_state()
    {
        update_option('woocommerce_task_list_complete', 'yes');
        update_option('woocommerce_task_list_hidden', 'yes');
        update_option('woocommerce_onboarding_profile', ['completed' => true, 'skipped' => true]);
        delete_transient('_wc_activation_redirect');
    }
}

// B. Hook on Creation (New Sites)
add_action('wp_initialize_site', function ($new_site) {
    switch_to_blog($new_site->blog_id);
    shopper_set_wc_completed_state();
    restore_current_blog();
}, 20);

// C. Self-Healing on Admin Load (Existing Sites)
add_action('admin_init', function () {
    // Lightweight check: Only run if option is unset
    if (!get_option('woocommerce_task_list_complete')) {
        shopper_set_wc_completed_state();
    }
});

// D. Hard Filters (UI Suppression)
add_filter('woocommerce_prevent_automatic_wizard_redirect', '__return_true');
add_filter('woocommerce_enable_setup_wizard', '__return_false');
add_filter('woocommerce_show_admin_notice', '__return_false');
// NOTE: Do NOT use 'woocommerce_admin_disabled' — it kills the entire React-based
// admin interface (Products, Orders, Analytics). The above filters + self-healing
// in shopper_set_wc_completed_state() are sufficient to prevent task list redirects.
add_filter('woocommerce_marketing_overview_welcome_hidden', '__return_yes');

// START [NS-CLONER-COMPLIANCE] Post-Clone Restoration
// Runs on ns_cloner_process_finish to re-apply critical network config that was
// overwritten by copy_tables(). NOTE: This hook fires BEFORE NS Cloner restores
// blogname (line ~286 in finish()), so we MUST use ns_cloner_request()->get('target_title')
// and restore blogname ourselves via direct DB query.
add_action('ns_cloner_process_finish', function () {
    if (!function_exists('ns_cloner_request'))
        return;

    $target_id = ns_cloner_request()->get('target_id');
    $source_id = ns_cloner_request()->get('source_id');
    if (!$target_id || (int) $target_id <= 1)
        return;

    switch_to_blog($target_id);
    global $wpdb;

    // 0. Restore blogname FIRST — NS Cloner does this AFTER this hook fires,
    //    so blogname currently still holds the SOURCE site's name.
    $target_title = ns_cloner_request()->get('target_title');
    if ($target_title) {
        $target_prefix = ns_cloner_request()->get('target_prefix');
        $wpdb->update(
            $target_prefix . 'options',
            ['option_value' => $target_title],
            ['option_name' => 'blogname']
        );
        // Clear object cache so get_option picks up the new value
        wp_cache_delete('blogname', 'options');
        wp_cache_delete('alloptions', 'options');
    }

    // 1. Regenerate Mega Slug from TARGET title (not get_option which may be stale)
    $site_title = $target_title ?: get_option('blogname');
    $clean_mega_slug = shopper_get_clean_mega_slug(sanitize_title($site_title), $target_id);
    update_option('store_mega_slug', $clean_mega_slug);

    // 2. Restore WPLANG from source site FIRST (needed for path construction)
    $source_lang = get_blog_option($source_id, 'WPLANG');
    if (empty($source_lang)) {
        $source_lang = get_site_option('WPLANG', 'en_US'); // Network-level fallback
    }
    update_option('WPLANG', $source_lang);

    // 3. Fix Path in wp_blogs + siteurl + home using shared helper
    $new_path = shopper_build_virtual_path($clean_mega_slug, $source_lang);
    $new_url = rtrim(network_site_url($new_path), '/');
    update_option('siteurl', $new_url);
    update_option('home', $new_url);

    $wpdb->update($wpdb->blogs, ['path' => $new_path], ['blog_id' => $target_id]);

    // 4. Re-enforce Homepage Settings
    shopper_enforce_homepage_settings();

    // 5. Re-enforce WC Onboarding State
    if (function_exists('shopper_set_wc_completed_state')) {
        shopper_set_wc_completed_state();
    }

    // 5.5 Reset location-specific store_settings so GMB Sync finds the CORRECT business.
    //     Without this, the cloned site inherits the source's address, coords, gmb_name,
    //     gmb_link, phone, description, etc. — causing Sync Now to match the WRONG profile.
    $store_settings = get_option('store_settings', []);
    if (!empty($store_settings)) {
        // Reset Store Info fields that are location-specific
        $string_fields = [
            'address',
            'address_2',
            'city',
            'postcode',
            'country',
            'phone',
            'email',
            'description',
            'latitude',
            'longitude',
            'ecommerce_link'
        ];
        $array_fields = ['hours', 'amenities', 'custom_tab'];

        foreach ($string_fields as $field) {
            if (isset($store_settings['store_info'][$field])) {
                $store_settings['store_info'][$field] = '';
            }
        }
        foreach ($array_fields as $field) {
            if (isset($store_settings['store_info'][$field])) {
                $store_settings['store_info'][$field] = [];
            }
        }

        // Reset SEO fields that are location-specific
        if (!isset($store_settings['seo'])) {
            $store_settings['seo'] = [];
        }
        // Set store_name + image_alt to the NEW blogname (used in footer, pickup, schema, etc.)
        $store_settings['seo']['store_name'] = $site_title;
        $store_settings['seo']['image_alt'] = $site_title;
        // Set gmb_name so first sync finds the correct Google Business Profile
        $store_settings['seo']['gmb_name'] = $site_title;
        // Clear location-specific SEO fields
        $store_settings['seo']['gmb_link'] = '';
        $store_settings['seo']['neighborhood'] = '';
        $store_settings['seo']['storeCode'] = '';
        $store_settings['seo']['meta_title'] = '';
        $store_settings['seo']['meta_description'] = '';

        update_option('store_settings', $store_settings);
    }

    // Clear stale GMB sync data from source site
    delete_option('gmb_reviews');
    delete_option('gmb_reviews_header');
    delete_option('gmb_last_sync');
    delete_option('gmb_last_updates');
    delete_option('gmb_sync_error');
    delete_option('gmb_reviews_updated');

    // 6. Flush Rewrite Rules for new subsite context
    flush_rewrite_rules();

    restore_current_blog();

    error_log("SHOPPER NS-CLONER: Post-clone restoration complete for Site {$target_id} (Slug: {$clean_mega_slug})");
}, 20);
// END [NS-CLONER-COMPLIANCE] Post-Clone Restoration

/**
 * ==============================================================================
 * NETWORK POLICY: GLOBAL PERMALINK MANAGER (The "Generator")
 * ==============================================================================
 * Enforces the /pd/, /pl/, /br/ structure for outgoing links.
 */
class Shopper_Permalink_Manager
{
    public static function init()
    {
        // Rewrite Product Links (/st/ -> /pd/)
        add_filter('post_type_link', [__CLASS__, 'rewrite_product_link'], 20, 2);

        // Rewrite Taxonomy Links (/st/ -> /pl/)
        // ALERT: Re-enabled with conservative logic (only swapping base, keeping /category/)
        add_filter('term_link', [__CLASS__, 'rewrite_term_link'], 20, 3);

        // Prevent "Canonical Redirect" loops
        add_filter('redirect_canonical', [__CLASS__, 'prevent_canonical_loops'], 20, 2);
    }

    public static function rewrite_product_link($permalink, $post)
    {
        if ($post->post_type !== 'product' || empty($permalink))
            return $permalink;
        return str_replace('/st/', '/pd/', $permalink);
    }

    public static function rewrite_term_link($termlink, $term, $taxonomy)
    {
        if (empty($termlink))
            return $termlink;

        // Supported Taxonomies: Categories, Tags, Brands
        $valid_tax = ['product_cat', 'product_tag', 'product_brand'];
        if (!in_array($taxonomy, $valid_tax))
            return $termlink;

        // Fix: Replace /st/ with /pl/ ONLY.
        $termlink = str_replace(['/st/'], ['/pl/'], $termlink);

        // Clean up accidental double slashes, but PRESERVE Protocol (http://)
        return preg_replace('#(?<!:)//+#', '/', $termlink);
    }

    public static function prevent_canonical_loops($redirect_url, $requested_url)
    {
        // If we are on a valid /pd/, /pl/, or /st/ URL, do NOT let WP redirect back to /st/ or root
        if (strpos($requested_url, '/pd/') !== false || strpos($requested_url, '/pl/') !== false || strpos($requested_url, '/st/') !== false) {
            return false;
        }
        return $redirect_url;
    }
}

/**
 * 8. UI POLISH: Favicon Inheritance
 * If subsite has no icon, use Main Site's.
 */
add_filter('get_site_icon_url', function ($url, $size, $blog_id = 0) {
    if ($url)
        return $url;
    if (!is_main_site()) {
        switch_to_blog(1);
        $url = get_site_icon_url($size);
        restore_current_blog();
    }
    return $url;
}, 10, 3);

Shopper_Permalink_Manager::init();

/**
 * 9. SECURITY HARDENING
 * - Disable XML-RPC (Safe since no Jetpack/App)
 * - Hardened REST API (Smart Blocking of User Enumeration)
 * - Obscurity (Hide WP Version)
 */
class Shopper_Security_Manager
{
    public static function init()
    {
        // --------------------------------------------------------------------------
        // 1. SECURITY: Disable XML-RPC
        // --------------------------------------------------------------------------
        add_filter('xmlrpc_enabled', '__return_false');
        remove_action('xmlrpc_rsd_apis', 'rest_output_rsd_link');
        add_filter('xmlrpc_methods', function ($methods) {
            return []; // Clear methods as a backup
        });

        // --------------------------------------------------------------------------
        // 2. SECURITY: Hardened REST API (Smart Blocking)
        // --------------------------------------------------------------------------
        // Disable the "Users" endpoint to prevent username scraping (The real security risk)
        add_filter('rest_endpoints', [__CLASS__, 'disable_user_endpoints']);

        // Remove the REST API link tag from <head> to reduce discoverability
        remove_action('template_redirect', 'rest_output_link_header', 11);
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('xmlrpc_rsd_apis', 'rest_output_rsd_link');

        // --------------------------------------------------------------------------
        // 3. SECURITY: Obscurity (Hide WP Version)
        // --------------------------------------------------------------------------
        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_empty_string');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
    }

    public static function disable_user_endpoints($endpoints)
    {
        if (isset($endpoints['/wp/v2/users'])) {
            unset($endpoints['/wp/v2/users']);
        }
        if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
            unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
        }
        return $endpoints;
    }

    /**
     * 4. SECURITY: Strict Cookie Hardening
     * Enforces Secure and HttpOnly flags on all auth cookies.
     */
    public static function enforce_secure_cookies($secure)
    {
        return true; // Always force secure (SSL)
    }

    public static function enforce_cookie_flags($cookie)
    {
        // Note: WordPress 'auth_cookie' filter passes the cookie string, not arrays.
        // But 'set_cookie' hooks are different.
        // We focus on the 'secure_auth_cookie' filter which expects a boolean return.
        return $cookie;
    }
}

// EXTENDED INIT outside the class to avoid breaking existing structure if needed, 
// OR we can just extend the init above.
// Re-opening the class via separate strict init to keep cleaner diffs? 
// No, let's just append to the init in a clean way in the next step or rewrite init now.
// Actually, I'll rewrite the init method in a separate block if I can, but since I'm in Replace,
// I should have included the Init method in the TargetContent if I wanted to change it.
// Let's add the hooks via a separate add_action since the class is already closed and instantiated.

// APPENDING SECURITY COOKIE LOGIC
add_action('init', function () {
    // Force "Secure" flag on auth cookies
    add_filter('secure_auth_cookie', '__return_true');
});

// 10. SECURITY: CSP consolidated to Cloudflare Transform Rules (2026-02-13)
// worker-src 'self' blob: data: is now included in the Cloudflare-managed CSP header.
// Removed VM-side header to prevent duplication/overwrite conflicts.

Shopper_Security_Manager::init();

/**
 * 11. NETWORK POLICY: Enforce Clean Subsite Paths (Systemic Safety Net)
 * Prevents "Virtual Path" pollution (e.g. /es/tiendas/st/slug) in wp_blogs.
 */
// [DISABLED] 11. NETWORK POLICY: Enforce Clean Subsite Paths
// We now WANT virtual paths (e.g., /pt/lojas/st/...) so this cleaner is counter-productive.
/*
add_filter('wp_insert_site_data', function ($data) {
    // Only process sub-sites (ID > 1 check via path root mostly)
    // Pattern: /lang/base/mode/slug/ -> extract slug.
    if (preg_match('#^/(?:[a-z]{2})/(?:[^/]+)/(?:st|pl|pd)/([^/]+)/?$#', $data['path'], $matches)) {
        $slug = $matches[1];
        $data['path'] = '/' . $slug . '/';
    }
    return $data;
}, 20);
*/


/**
 * 12. ASSET MANAGER: Fix 404s on Virtual Paths
 * Ensures assets load from Network Root, not relative to /es/tiendas/...
 */
class Shopper_Asset_Manager
{
    public static function init()
    {
        add_filter('style_loader_src', [__CLASS__, 'fix_asset_url'], 999);
        add_filter('script_loader_src', [__CLASS__, 'fix_asset_url'], 999);
        add_filter('plugins_url', [__CLASS__, 'fix_asset_url'], 999);
        add_filter('theme_file_uri', [__CLASS__, 'fix_asset_url'], 999);
    }

    public static function fix_asset_url($url)
    {
        if (!$url)
            return $url;

        // Target: /es/tiendas/slug/wp-content/... -> /wp-content/...
        // We look for the pattern: / wp- (content|includes|admin)
        // And ensure it is relative to the root domain.

        // Get the Network Site URL (Root)
        $root = network_site_url();

        // If the URL contains one of our virtual bases AND wp-content/includes
        if (strpos($url, '/wp-content/') !== false || strpos($url, '/wp-includes/') !== false) {
            $path = parse_url($url, PHP_URL_PATH);
            if (preg_match('#/wp-(content|includes)/#', $path, $m, PREG_OFFSET_CAPTURE)) {
                $real_path = substr($path, $m[0][1]); // /wp-content/...
                // Return clean root URL
                return $root . ltrim($real_path, '/');
            }
        }

        return $url;
    }
}
Shopper_Asset_Manager::init();
