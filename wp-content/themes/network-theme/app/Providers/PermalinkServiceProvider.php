<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PermalinkServiceProvider extends ServiceProvider
{
    private $is_cross_site = false;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // 1. MAIN SITE: Handle Proxy Routing (Product Detail only)
        // Use MU-Plugin helper 'network_is_subsite_path' if available
        $is_subsite = function_exists('network_is_subsite_path') ? network_is_subsite_path() : false;

        if (is_main_site() && !$is_subsite) {
            add_action('init', [$this, 'register_main_site_rewrites']);
            // Priority 1: Run BEFORE redirect_canonical and other routers
            add_action('template_redirect', [$this, 'resolve_cross_site_request'], 1);
        }

        // 2. SUBSITES: Handle virtual URL context (/pl/ category pages)
        // Sunrise resolves /pl/ to subsite but REQUEST_URI stays as /pl/.
        // We need to detect this and set up the category query.
        if (!is_main_site()) {
            add_action('template_redirect', [$this, 'resolve_subsite_virtual_context'], 0);
        }

        // 3. SUBSITES: Generate Links (Universal Registration)
        // We register these everywhere so they work during switch_to_blog() contexts
        add_filter('post_type_link', [$this, 'filter_product_link'], 20, 2);
        add_filter('term_link', [$this, 'filter_term_link'], 20, 3);

        add_action('template_redirect', [$this, 'redirect_native_subsite_urls']);
        add_filter('home_url', [$this, 'filter_home_url'], 20, 2);
    }

    private function log($message)
    {
        // error_log("[PermalinkDebug] " . $message);
        // file_put_contents(WP_CONTENT_DIR . '/debug_redirects.log', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
    }

    /**
     * MAIN SITE: Register rewrite rules (Deep Hierarchy).
     */
    public function register_main_site_rewrites()
    {
        add_rewrite_tag('%cross_site_mega_slug%', '([^&]+)');
        add_rewrite_tag('%cross_site_product_slug%', '([^&]+)');
        add_rewrite_tag('%cross_site_category_slug%', '([^&]+)');
        add_rewrite_tag('%cross_site_mode%', '([^&]+)');
        add_rewrite_tag('%lang%', '([^&]+)');

        // Map: Lang => Base
        $maps = $this->get_language_map();

        foreach ($maps as $lang => $base) {
            // 1. PRODUCT DETAIL (/pd/)
            // ^{lang}/{base}/pd/{mega-slug}/{product-slug}/?
            add_rewrite_rule(
                "^({$lang})/{$base}/pd/([^/]+)/([^/]+)/?$",
                'index.php?lang=$matches[1]&cross_site_mode=pd&cross_site_mega_slug=$matches[2]&cross_site_product_slug=$matches[3]',
                'top'
            );

            // 2. CATEGORY LIST (/pl/)
            // ^{lang}/{base}/pl/{mega-slug}/{category-slug}/?
            add_rewrite_rule(
                "^({$lang})/{$base}/pl/([^/]+)/([^/]+)/?$",
                'index.php?lang=$matches[1]&cross_site_mode=pl&cross_site_mega_slug=$matches[2]&cross_site_category_slug=$matches[3]',
                'top'
            );

            // 3. STORE HOME (/st/)
            // ^{lang}/{base}/st/{mega-slug}/?
            add_rewrite_rule(
                "^({$lang})/{$base}/st/([^/]+)/?$",
                'index.php?lang=$matches[1]&cross_site_mode=st&cross_site_mega_slug=$matches[2]',
                'top'
            );

            // 4. SITEMAPS (Virtual Context)
            // ^{lang}/{base}/st/{mega-slug}/sitemap\.xml
            add_rewrite_rule(
                "^({$lang})/{$base}/st/([^/]+)/sitemap\.xml$",
                'index.php?lang=$matches[1]&cross_site_mode=st&cross_site_mega_slug=$matches[2]&sitemap=main',
                'top'
            );

            // ^{lang}/{base}/st/{mega-slug}/{type}-sitemap\.xml
            add_rewrite_rule(
                "^({$lang})/{$base}/st/([^/]+)/([^/]+)-sitemap\.xml$",
                'index.php?lang=$matches[1]&cross_site_mode=st&cross_site_mega_slug=$matches[2]&sitemap=$matches[3]',
                'top'
            );
        }
    }

    /**
     * MAIN SITE: Resolve request, Validate SEO, and Switch Context.
     */
    public function resolve_cross_site_request()
    {
        $mega_slug = get_query_var('cross_site_mega_slug');
        if (!$mega_slug) {
            $this->log("Resolve: No mega slug. Bail.");
            return;
        }

        // MARKER: We are processing a Virtual Network Request
        $this->is_cross_site = true;

        // DISABLE Canonical Redirects for these virtual routes
        remove_action('template_redirect', 'redirect_canonical');

        // 1. Resolve Blog ID directly from Mega Slug
        // Logic: The DB path is now updated to strictly match the mega slug (e.g. /seminario-e6d0bec6/)
        global $wpdb;

        // Try strict match first (fastest/safest)
        $blog_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT blog_id FROM {$wpdb->blogs} 
             WHERE path = %s AND archived = '0' AND deleted = '0' 
             LIMIT 1",
            '/' . $mega_slug . '/'
        ));

        // Fallback: Try regex-like match (if there are lingering issues, but strict should work now)
        if (!$blog_id) {
            $blog_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT blog_id FROM {$wpdb->blogs}
                 WHERE path LIKE %s AND archived = '0' AND deleted = '0'
                 ORDER BY LENGTH(path) DESC LIMIT 1",
                '%' . $wpdb->esc_like($mega_slug) . '%'
            ));
        }

        if (!$blog_id || $blog_id === 0) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        // 3. Switch Context
        switch_to_blog($blog_id);

        // 4. Validate URL Match (Canonical Check)
        $real_mega_slug = get_option('store_mega_slug');
        $lang = get_query_var('lang', function_exists('network_derive_lang_from_locale') ? network_derive_lang_from_locale() : 'en'); // Dynamic default from locale
        $mode = get_query_var('cross_site_mode');

        // If wrong Mega Slug, Redirect
        if ($mega_slug !== $real_mega_slug) {
            $new_url = $this->generate_network_url($lang, $mode, $real_mega_slug, get_query_var('cross_site_category_slug'), get_query_var('cross_site_product_slug'));
            restore_current_blog();
            wp_safe_redirect($new_url, 301);
            exit;
        }

        // 5. Re-run Query in New Context
        global $wp_query, $wp, $post;

        $product_slug = get_query_var('cross_site_product_slug');
        $category_slug = get_query_var('cross_site_category_slug');

        $wp_query->init();
        $wp_query->is_404 = false;
        status_header(200);

        // DEBUG
        // die("DEBUG: Mode: $mode Mega: $mega_slug Prod: $product_slug Cat: $category_slug Brand: " . get_query_var('cross_site_brand_slug'));

        if ($mode === 'pd' && $product_slug) {
            // SINGLE PRODUCT QUERY
            $wp_query->query([
                'post_type' => 'product',
                'name' => $product_slug,
                'posts_per_page' => 1,
            ]);
            $wp_query->is_singular = true;
            $wp_query->is_single = true;
            $wp_query->is_home = false;
        } elseif ($mode === 'pl' && $category_slug) {
            // CATEGORY ARCHIVE QUERY
            $wp_query->query([
                'product_cat' => $category_slug,
                'post_type' => 'product',
                'paged' => get_query_var('paged') ?: 1,
            ]);
            $wp_query->is_archive = true;
            $wp_query->is_tax = true;
            $wp_query->queried_object = get_term_by('slug', $category_slug, 'product_cat');
            $wp_query->is_home = false;
        } elseif ($mode === 'st') {
            // SHOP ARCHIVE QUERY -> Store Home
            $wp_query->query([
                'post_type' => 'product',
                'paged' => get_query_var('paged') ?: 1,
            ]);
            $wp_query->is_archive = true;
            $wp_query->is_post_type_archive = true;
            $wp_query->is_home = false;
        }

        if ($wp_query->have_posts()) {
            $wp_query->the_post();
            $post = $wp_query->post;
            rewind_posts();
        }

        // 6. Register Shutdown Handler
        add_action('shutdown', function () {
            if (ms_is_switched()) {
                restore_current_blog();
            }
        });
    }


    public function generate_network_url($lang, $mode, $mega_slug, $cat_slug = '', $prod_slug = '')
    {
        // Map Lang to Base
        $bases = $this->get_language_map();
        $base_slug = $bases[$lang] ?? 'lojas'; // Default to LOJAS

        $base_url = network_home_url("{$lang}/{$base_slug}/");

        switch ($mode) {
            case 'pd':
                return "{$base_url}pd/{$mega_slug}/{$prod_slug}/";
            case 'pl':
                return "{$base_url}pl/{$mega_slug}/{$cat_slug}/";
            case 'st':
            default:
                return "{$base_url}st/{$mega_slug}/";
        }
    }

    /**
     * SUBSITE: Detect virtual /pl/ context resolved by sunrise.
     * Sets up $wp_query for category archive display.
     */
    public function resolve_subsite_virtual_context()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        // Only handle /pl/ (category) URLs on subsites
        if (strpos($uri, '/pl/') === false) {
            return;
        }

        // Parse: /{lang}/lojas/pl/{mega-slug}/{category-slug}/
        if (!preg_match('#/pl/[^/]+/([^/]+)/?#', $uri, $matches)) {
            return;
        }

        $category_slug = sanitize_title($matches[1]);
        if (empty($category_slug)) {
            return;
        }

        $term = get_term_by('slug', $category_slug, 'product_cat');
        if (!$term) {
            return;
        }

        // Override wp_query to display this category's products
        global $wp_query;
        $wp_query->query([
            'product_cat' => $category_slug,
            'post_type' => 'product',
            'paged' => get_query_var('paged') ?: 1,
        ]);
        $wp_query->is_archive = true;
        $wp_query->is_tax = true;
        $wp_query->queried_object = $term;
        $wp_query->is_home = false;
        $wp_query->is_404 = false;
    }

    /**
     * SUBSITE: Link Generation
     */
    public function filter_product_link($permalink, $post)
    {
        if ($post->post_type !== 'product')
            return $permalink;

        $segments = $this->get_url_segments();
        // /{lang}/tiendas/pd/{mega-slug}/{product-slug}
        $url = $this->generate_network_url($segments['lang'], 'pd', $segments['mega_slug'], '', $post->post_name);

        return $url;
    }

    public function filter_shop_link($link, $post_type = '')
    {
        if ($post_type !== 'product')
            return $link;
        $segments = $this->get_url_segments();
        return $this->generate_network_url($segments['lang'], 'st', $segments['mega_slug']);
    }

    public function filter_term_link($termlink, $term, $taxonomy)
    {
        if ($taxonomy === 'product_cat') {
            $segments = $this->get_url_segments();
            return $this->generate_network_url($segments['lang'], 'pl', $segments['mega_slug'], $term->slug);
        }
        if ($taxonomy === 'product_brand') {
            $segments = $this->get_url_segments();
            return $this->generate_network_url($segments['lang'], 'br', $segments['mega_slug'], $term->slug);
        }
        return $termlink;
    }

    private function get_language_map()
    {
        // Delegate to global single source of truth (defined in network-core.php mu-plugin)
        if (function_exists('network_get_store_language_map')) {
            return network_get_store_language_map();
        }
        // Fallback if mu-plugin not loaded (should never happen in production)
        return [
            'es' => 'tiendas',
            'sp' => 'tiendas',
            'en' => 'stores',
            'pt' => 'lojas',
            'he' => '%D7%97%D7%A0%D7%95%D7%99%D7%95%D7%AA',
        ];
    }

    public function redirect_native_subsite_urls()
    {
        // Ignore if we are in admin
        if (is_admin())
            return;

        $req_uri = $_SERVER['REQUEST_URI'];
        $segments = $this->get_url_segments();

        // 0. GUARD: If this is a Cross-Site Request (Virtual URL), do NOT redirect.
        // We only want to redirect NATIVE subsite URLs (e.g. /camacha-shopping/produto/...)
        if ($this->is_cross_site) {
            $this->log("RedirectCheck: Cross site guarded. Return.");
            return;
        }

        // 0b. GUARD: If the request URI already contains a virtual network path,
        // we are already on the correct URL (resolved by sunrise). Do NOT redirect.
        // This prevents loops when sunrise resolves /pl/ or /st/ to the subsite.
        if (preg_match('#/(pt|es|en)/(lojas|tiendas|stores)/(pd|pl|st|br)/#', $req_uri)) {
            return;
        }

        // FIX: Catch-all redirect for native product/category pages to Network URL
        if (is_singular('product')) {
            $url = $this->generate_network_url($segments['lang'], 'pd', $segments['mega_slug'], '', get_post()->post_name);

            // Compare PATHS only to avoid looping (Absolute vs Relative mismatch)
            $url_path = trim(parse_url($url, PHP_URL_PATH), '/');
            $req_path = trim($req_uri, '/');

            // if ($this->is_debug_mode('st') && isset($_GET['debug_redirect'])) {
            //     // Debug output if needed, but not logging to file
            // }

            if ($url && $url_path !== $req_path) {
                wp_safe_redirect($url, 301);
                exit;
            }
        }

        if (is_tax('product_cat')) {
            $term = get_queried_object();
            if ($term) {
                $url = $this->generate_network_url($segments['lang'], 'pl', $segments['mega_slug'], $term->slug);
                if ($url && $url !== $req_uri) {
                    wp_safe_redirect($url, 301);
                    exit;
                }
            }
        }

        // 0. FIX root redirects -> Network Home
        // Prevent access to /tiendas/ directly or singular variants
        $maps = $this->get_language_map();
        foreach ($maps as $base) {
            if ($req_uri === "/{$base}/" || $req_uri === "/{$base}") {
                wp_safe_redirect(network_home_url('/'), 301);
                exit;
            }
        }
        if (in_array($req_uri, ['/tienda/', '/tiendas/', '/store/', '/stores/', '/loja/', '/lojas/'])) {
            wp_safe_redirect(network_home_url('/'), 301);
            exit;
        }

        $store_slug = $segments['store_slug'];

        // 1. PHYSICAL ROOT REDIRECT
        // If the request matches the Physical Path (e.g. /seminario-e6d0bec6/)
        // We must redirect to the Virtual Network URL /pt/lojas/st/seminario-e6d0bec6/
        // To avoid loops, we ensure this logic only runs when we are NOT in the virtual context 
        // (which is handled by resolve_cross_site_request on main site).
        // On a subsite, we correspond to the physical path.
        $home_path = trim(parse_url(home_url(), PHP_URL_PATH) ?? '', '/');
        $current_request_path = trim($req_uri, '/');

        // If we are strictly at the root of the subsite
        if ($home_path && $current_request_path === $home_path) {
            $new_url = $this->generate_network_url($segments['lang'], 'st', $segments['mega_slug']);
            wp_safe_redirect($new_url, 301);
            exit;
        }

        // Iterate all languages to verify if we are hitting a "native" base URL 
        // that needs redirecting to the Network URL (e.g. /loja/myshop -> /pt/lojas/st/myshop)
        foreach ($maps as $lang_code => $base) {
            // Heuristic: Check both singular (base minus 's') and distinct plural
            // For 'tiendas' -> 'tienda'
            // For 'lojas' -> 'loja'
            // For 'stores' -> 'store'

            $native_base_plural = "/{$base}/{$store_slug}";
            $native_base_singular = "/" . rtrim($base, 's') . "/{$store_slug}";

            // If request starts with native base
            if (strpos($req_uri, $native_base_singular) === 0 || strpos($req_uri, $native_base_plural) === 0) {
                // Determine Mode & Context
                $mode = 'st';
                $cat = '';
                $prod = '';

                if (is_singular('product')) {
                    // Product Deep Link
                    $mode = 'pd';
                    global $post;
                    if ($post) {
                        $prod = $post->post_name;
                    }
                } elseif (strpos($req_uri, '/category/') !== false) {
                    // Fix: Manually detect category slug if WP returns 404 or is_tax fails
                    // Structure: .../category/{slug}/
                    $mode = 'pl';
                    $parts = explode('/category/', $req_uri);
                    if (isset($parts[1])) {
                        $cat_parts = explode('/', trim($parts[1], '/'));
                        $cat = $cat_parts[0] ?? '';
                    }
                } elseif (is_tax('product_cat')) {
                    // Standard Category Deep Link (if works)
                    $mode = 'pl';
                    $term = get_queried_object();
                    if ($term) {
                        $cat = $term->slug;
                    }
                }

                $new_url = $this->generate_network_url($segments['lang'], $mode, $segments['mega_slug'], $cat, $prod);

                wp_safe_redirect($new_url, 301);
                exit;
            }
        }
    }


    public function get_url_segments()
    {
        // Store slug from the subsite's local path.
        // Primarily used in link-generation filters on subsites.

        // Avoid lookup loop
        $path = trim(parse_url(get_option('home'), PHP_URL_PATH) ?? '', '/');
        $parts = explode('/', $path);

        $store_slug = end($parts);
        if ($store_slug === 'store' || !$store_slug) {
            $store_slug = 'global-store';
        }

        // Determine language from request URI or fall back to locale.

        $req_uri = $_SERVER['REQUEST_URI'] ?? '';
        $req_parts = explode('/', trim($req_uri, '/'));

        // Dynamic Default Language based on Site Locale
        // Dynamic Default Language based on Site Locale
        // Uses shared helper if available, otherwise derive from locale dynamically
        if (function_exists('network_derive_lang_from_locale')) {
            $lang = network_derive_lang_from_locale();
        } else {
            $locale = get_locale();
            $lang = substr($locale, 0, 2);
            $known = array_keys($this->get_language_map());
            if (!in_array($lang, $known)) {
                $lang = 'en'; // Safe fallback
            }
        }

        // Check if URL overrides language
        $known_langs = array_keys($this->get_language_map());
        if (!empty($req_parts[0]) && in_array($req_parts[0], $known_langs)) {
            $lang = $req_parts[0];
        }

        return [
            'store_slug' => $store_slug,
            'mega_slug' => get_option('store_mega_slug', function_exists('network_get_clean_mega_slug')
                ? network_get_clean_mega_slug($store_slug)
                : $store_slug . '-' . substr(md5(get_current_blog_id() . $store_slug), 0, 8)),
            'lang' => $lang,
        ];
    }

    public function filter_home_url($url, $path)
    {
        // 0. GUARD: Only apply on front-end, not admin/login
        if (is_admin() || strpos($url, 'wp-login.php') !== false || strpos($url, 'wp-admin') !== false) {
            return $url;
        }

        // 1. GUARD: Only apply if NOT main site
        if (is_main_site()) {
            return $url;
        }

        // 2. GUARD: Only apply if we have a valid Mega Slug
        $mega_slug = get_option('store_mega_slug');
        if (!$mega_slug) {
            return $url;
        }

        // Force virtual URL using the language derived from get_url_segments().
        // Use consistent detector
        $segments = $this->get_url_segments();
        $lang = $segments['lang'];

        // Use our consistent generator
        // If $path is provided (e.g. /checkout/), append it?
        // generate_network_url returns "..../st/{mega_slug}/"
        // If $path is relative, we might need to append it.

        $base_url = $this->generate_network_url($lang, 'st', $mega_slug);

        // If path is root '/', just return base
        if (!$path || $path === '/') {
            return $base_url;
        }

        // Non-root paths are not remapped; return the store base URL.

        return $base_url;
    }
}
