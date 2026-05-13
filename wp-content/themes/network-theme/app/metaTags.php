<?php

/**
 * Meta Tags functionality
 */

namespace App;

/**
 * Generate product title early in the WordPress lifecycle
 */
function generate_product_title($product_id = null)
{
    if (!$product_id) {
        $product_id = get_the_ID();
    }

    // Ensure we have a valid product ID
    if (!$product_id || !is_numeric($product_id)) {
        return false;
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        return false;
    }

    // Ensure proper UTF-8 encoding for Hebrew text
    $product_name = wp_strip_all_tags($product->get_name());
    $product_name = html_entity_decode($product_name, ENT_QUOTES, 'UTF-8');

    $store_settings = get_option('store_settings', []);
    $store_name = $store_settings['seo']['store_name'] ?? get_bloginfo('name');
    $store_name = html_entity_decode($store_name, ENT_QUOTES, 'UTF-8');

    // Get focus keywords and select one randomly
    $focus_keywords = get_post_meta($product->get_id(), '_network_focus_keywords', true);
    $random_keyword = '';

    if (!empty($focus_keywords)) {
        $keywords_array = array_map('trim', explode(',', $focus_keywords));
        $random_keyword = $keywords_array[array_rand($keywords_array)];
    }

    // Get price information - handle both simple and variable products
    $regular_price = 0;
    $sale_price = 0;
    $current_price = 0;
    $discount_percentage = 0;

    if ($product->is_type('variable')) {
        // For variable products, check if any variation is on sale
        $variations = $product->get_available_variations();
        $has_sale_variation = false;
        $min_sale_price = PHP_FLOAT_MAX;
        $min_regular_price = PHP_FLOAT_MAX;

        foreach ($variations as $variation) {
            $variation_obj = wc_get_product($variation['variation_id']);
            if ($variation_obj) {
                $var_regular_price = (float) $variation_obj->get_regular_price();
                $var_sale_price = (float) $variation_obj->get_sale_price();

                if ($var_regular_price > 0 && $var_sale_price > 0 && $var_regular_price > $var_sale_price) {
                    $has_sale_variation = true;
                    $min_sale_price = min($min_sale_price, $var_sale_price);
                    $min_regular_price = min($min_regular_price, $var_regular_price);
                }
            }
        }

        if ($has_sale_variation) {
            $regular_price = $min_regular_price;
            $sale_price = $min_sale_price;
            $current_price = $min_sale_price;
            $discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
        } else {
            // No sale variations, get the minimum price
            $current_price = $product->get_variation_price('min');
            $regular_price = $product->get_variation_regular_price('min');
        }
    } else {
        // Simple product
        $regular_price = (float) $product->get_regular_price();
        $sale_price = (float) $product->get_sale_price();
        $current_price = (float) $product->get_price();

        // Calculate discount percentage if on sale
        if ($regular_price > 0 && $sale_price > 0 && $regular_price > $sale_price) {
            $discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
        }
    }

    // Build the title with price information
    $price_prefix = '';
    $price_suffix = '';

    // Updated to focus on Local Availability and Informational Utility (SQEG 4.5.3)
    if ($product->is_in_stock()) {
        $title_variations = [
            // Informational / Navigation variations
            [
                'prefix' => '',
                'suffix' => sprintf(__('Availability at', 'sage'))
            ],
            [
                'prefix' => '',
                'suffix' => sprintf(__('Available at', 'sage'))
            ],
            [
                'prefix' => '',
                'suffix' => sprintf(__('Find at', 'sage'))
            ],
            [
                'prefix' => '',
                'suffix' => sprintf(__('Check Stock at', 'sage'))
            ]
        ];

        // Select random variation
        $selected_variation = $title_variations[array_rand($title_variations)];
        $price_prefix = $selected_variation['prefix']; // Keep empty or use if needed
        $price_suffix = $selected_variation['suffix'];
    }

    // Build the title parts with character optimization
    $new_title_parts = [];

    // Add price prefix if available
    if (!empty($price_prefix)) {
        $new_title_parts[] = $price_prefix;
    }

    // Add product name (truncate if needed to stay within 50-60 chars)
    if (!empty($product_name)) {
        $new_title_parts[] = $product_name;
    }

    // Add price suffix if available
    if (!empty($price_suffix)) {
        $new_title_parts[] = $price_suffix;
    }

    // Add store name
    if (!empty($store_name)) {
        $new_title_parts[] = $store_name;
    }

    // Join with appropriate separators - use " | " before store name
    $title_without_store = [];
    if (!empty($price_prefix)) {
        $title_without_store[] = $price_prefix;
    }
    if (!empty($product_name)) {
        $title_without_store[] = $product_name;
    }
    if (!empty($price_suffix)) {
        $title_without_store[] = $price_suffix;
    }

    $new_title = implode(' - ', array_filter($title_without_store));
    if (!empty($store_name)) {
        $new_title .= ' | ' . $store_name;
    }

    // Optimize title length to stay within 50-60 characters
    // Use mb_strlen for proper Hebrew character counting
    $max_length = 60;
    if (mb_strlen($new_title, 'UTF-8') > $max_length) {
        // Calculate available space for product name
        $prefix_length = !empty($price_prefix) ? mb_strlen($price_prefix, 'UTF-8') + 3 : 0; // +3 for " - "
        $suffix_length = !empty($price_suffix) ? mb_strlen($price_suffix, 'UTF-8') + 3 : 0; // +3 for " - "
        $store_length = !empty($store_name) ? mb_strlen($store_name, 'UTF-8') + 3 : 0; // +3 for " | "

        $available_for_product = $max_length - $prefix_length - $suffix_length - $store_length - 3; // -3 for separators

        if ($available_for_product > 10) { // Ensure minimum product name length
            $truncated_product = mb_substr($product_name, 0, $available_for_product - 3, 'UTF-8') . '...';

            // Rebuild title with truncated product name
            $title_without_store = [];
            if (!empty($price_prefix)) {
                $title_without_store[] = $price_prefix;
            }
            $title_without_store[] = $truncated_product;
            if (!empty($price_suffix)) {
                $title_without_store[] = $price_suffix;
            }

            $new_title = implode(' - ', array_filter($title_without_store));
            if (!empty($store_name)) {
                $new_title .= ' | ' . $store_name;
            }
        }
    }

    // Ensure proper UTF-8 encoding for the final title
    $new_title = mb_convert_encoding($new_title, 'UTF-8', 'UTF-8');

    return $new_title;
}

/**
 * More robust product detection and title generation
 */
add_filter('document_title_parts', function ($title_parts) {
    // Check multiple conditions for product pages
    $is_product_page = false;
    $product_id = null;

    // Method 1: Check if it's a product page
    if (is_product()) {
        $is_product_page = true;
        $product_id = get_the_ID();
    }

    // Method 2: Check post type
    if (!$is_product_page && get_post_type() === 'product') {
        $is_product_page = true;
        $product_id = get_the_ID();
    }

    // Method 3: Check queried object
    $qo = get_queried_object();
    if (!$is_product_page && $qo && $qo instanceof \WP_Post && $qo->post_type === 'product') {
        $is_product_page = true;
        $product_id = get_queried_object_id();
    }

    // Method 4: Check if we're in the main query and it's a product
    if (!$is_product_page && is_main_query() && get_post_type() === 'product') {
        $is_product_page = true;
        $product_id = get_the_ID();
    }

    if ($is_product_page && $product_id) {
        // Try to get the product object
        $product = wc_get_product($product_id);

        if ($product) {
            // Generate the full title
            $new_title = generate_product_title($product_id);

            if ($new_title) {
                return ['title' => $new_title];
            }
        } else {
            // Fallback: use basic product data
            $product_name = get_the_title($product_id);
            $product_name = html_entity_decode($product_name, ENT_QUOTES, 'UTF-8');
            $store_settings = get_option('store_settings', []);
            $store_name = $store_settings['seo']['store_name'] ?? get_bloginfo('name');
            $store_name = html_entity_decode($store_name, ENT_QUOTES, 'UTF-8');

            $fallback_title = $product_name;
            if (!empty($store_name)) {
                $fallback_title .= ' | ' . $store_name;
            }

            // Ensure proper UTF-8 encoding
            $fallback_title = mb_convert_encoding($fallback_title, 'UTF-8', 'UTF-8');

            return ['title' => $fallback_title];
        }
    }

    return $title_parts;
}, 10); // Lower priority to run earlier

/**
 * Alternative approach: Set title even earlier in the WordPress lifecycle
 */
add_action('template_redirect', function () {
    if (is_product() || (get_post_type() === 'product' && is_single())) {
        // Ensure WooCommerce is loaded
        if (!function_exists('wc_get_product')) {
            return;
        }

        $product_id = get_the_ID();
        if ($product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                // Store the generated title in a transient for this request
                $title = generate_product_title($product_id);
                if ($title) {
                    set_transient('product_title_' . $product_id, $title, 60); // 1 minute cache
                }
            }
        }
    }
}, 5);

/**
 * Use the cached title if available
 */
add_filter('document_title_parts', function ($title_parts) {
    // Check multiple conditions for product pages
    $is_product_page = false;
    $product_id = null;

    if (is_product()) {
        $is_product_page = true;
        $product_id = get_the_ID();
    } elseif (get_post_type() === 'product') {
        $is_product_page = true;
        $product_id = get_the_ID();
    }

    if ($is_product_page && $product_id) {
        $cached_title = get_transient('product_title_' . $product_id);
        if ($cached_title) {
            return ['title' => $cached_title];
        }
    }

    return $title_parts;
}, 5); // Very early priority

/**
 * Debug function to help identify issues (remove in production)
 */
add_action('wp_footer', function () {
    if (is_product() && current_user_can('administrator')) {
        echo '<!-- Debug: is_product() = ' . (is_product() ? 'true' : 'false') . ' -->';
        echo '<!-- Debug: post_type = ' . get_post_type() . ' -->';
        echo '<!-- Debug: product_id = ' . get_the_ID() . ' -->';
        echo '<!-- Debug: wc_get_product exists = ' . (function_exists('wc_get_product') ? 'true' : 'false') . ' -->';
        if (function_exists('wc_get_product')) {
            $product = wc_get_product(get_the_ID());
            echo '<!-- Debug: product object = ' . ($product ? 'exists' : 'null') . ' -->';
            if ($product) {
                echo '<!-- Debug: product name = ' . esc_html($product->get_name()) . ' -->';
                echo '<!-- Debug: product name raw = ' . esc_html(wp_strip_all_tags($product->get_name())) . ' -->';
            }
        }
    }
});

/**
 * Add meta tags for single product pages
 */
// Consolidated SEO logic moved to ProductSeoServiceProvider.php