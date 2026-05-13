<?php

/**
 * Set up product permalinks
 */
function custom_setup_product_permalinks()
{
    // Set permalink structure to post name
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure('/%postname%/');

    // Update WooCommerce permalinks
    $permalinks = get_option('woocommerce_permalinks');
    if (!is_array($permalinks)) {
        $permalinks = array();
    }

    $permalinks['category_base'] = 'category';
    $permalinks['product_base'] = ''; // Remove product base
    update_option('woocommerce_permalinks', $permalinks);

    // Force flush rewrite rules
    // flush_rewrite_rules(); // DISABLED: Architecture Violation - Global Policy Handles Rules
}
// add_action('init', 'custom_setup_product_permalinks', 1); // DISABLED: Global Policy Enforces /pd/

function custom_remove_product_slug($post_link, $post)
{
    if ($post->post_type === 'product' && $post->post_status === 'publish') {
        $post_link = home_url('/' . $post->post_name . '/');
    }
    return $post_link;
}
// add_filter('post_type_link', 'custom_remove_product_slug', 10, 2); // DISABLED: Conflicts with Shopper_Permalink_Manager

function custom_product_query_vars($query)
{
    if (!is_admin() && $query->is_main_query() && $query->is_single()) {
        $query->set('post_type', array('product', 'post'));
    }
}
add_action('pre_get_posts', 'custom_product_query_vars');

/**
 * Ensure product categories are always shown as 'category'
 */
function custom_category_base($termlink, $term, $taxonomy)
{
    if ($taxonomy === 'product_cat') {
        $termlink = home_url('/category/' . $term->slug . '/');
    }
    return $termlink;
}
// add_filter('term_link', 'custom_category_base', 10, 3); // DISABLED: Conflict with Global Policy

/**
 * Add rewrite rules for category URLs
 */
function custom_category_rewrite_rules()
{
    add_rewrite_rule(
        '^category/([^/]+)/?$',
        'index.php?product_cat=$matches[1]',
        'top'
    );

    add_rewrite_rule(
        '^category/([^/]+)/page/([0-9]+)/?$',
        'index.php?product_cat=$matches[1]&paged=$matches[2]',
        'top'
    );
}
// add_action('init', 'custom_category_rewrite_rules'); // DISABLED: Conflict with Global Policy

/**
 * Add rewrite rules for product URLs
 */
function custom_product_rewrite_rules()
{
    // Add rewrite rule for products without the product base
    add_rewrite_rule(
        '^product/([^/]+)/?$',
        'index.php?product=$matches[1]',
        'top'
    );
}
add_action('init', 'custom_product_rewrite_rules');

/**
 * Handle product URLs in query vars
 */
function custom_product_query_vars_handler($query_vars)
{
    $query_vars[] = 'product';
    return $query_vars;
}
add_filter('query_vars', 'custom_product_query_vars_handler');

/**
 * Ensure proper product handling in main query
 */
function custom_product_query($query)
{
    if (!is_admin() && $query->is_main_query()) {
        // Only handle product URLs when explicitly set
        if (isset($query->query['product'])) {
            $query->set('post_type', 'product');
            $query->set('name', $query->query['product']);
        }
    }
}
add_action('pre_get_posts', 'custom_product_query');

/**
 * Handle 301 redirects from old product URLs to new format
 */
function custom_product_redirect()
{
    if (!is_admin()) {
        global $wp;
        $current_url = home_url($wp->request);

        // Check if URL contains /product/ or /products/ in the path
        if (strpos($current_url, '/product/') !== false || strpos($current_url, '/products/') !== false) {
            // Get the product slug from the URL
            $product_slug = basename(parse_url($current_url, PHP_URL_PATH));

            // Build the new URL without /product/ or /products/
            $new_url = home_url('/' . $product_slug . '/');

            // Perform 301 redirect
            wp_redirect($new_url, 301);
            exit;
        }
    }
}
add_action('template_redirect', 'custom_product_redirect');

/**
 * Add rewrite rules for old product URLs
 */
function custom_old_product_rewrite_rules()
{
    add_rewrite_rule(
        '^product/([^/]+)/?$',
        'index.php?product=$matches[1]',
        'top'
    );
}
add_action('init', 'custom_old_product_rewrite_rules');

/**
 * Flush rewrite rules when needed
 */
function custom_flush_rewrite_rules()
{
    if (get_option('custom_rewrite_rules_flushed') !== 'yes') {
        flush_rewrite_rules();
        update_option('custom_rewrite_rules_flushed', 'yes');
    }
}
add_action('init', 'custom_flush_rewrite_rules', 20);


