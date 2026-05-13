<?php
/**
 * Canonical URL handling for WooCommerce
 * 
 * This file handles various canonical URL scenarios:
 * 1. Product variant pages
 * 2. Pagination issues
 * 3. Filter and sorting parameters
 * 4. Tracking parameters
 * 5. Multiple path issues
 */

/**
 * Clean URL by removing trailing hyphen and number and ensuring trailing slash
 * 
 * @param string $url The URL to clean
 * @return string The cleaned URL
 */
function clean_url_trailing_number($url) {
    // Remove trailing slash if exists
    $url = rtrim($url, '/');
    
    // Remove trailing hyphen and number
    $url = preg_replace('/-\d+$/', '', $url);
    
    // Add trailing slash
    $url = $url . '/';
    
    return $url;
}

// Handle canonical URLs for products and product categories
add_filter('woocommerce_get_canonical_url', function($canonical_url, $product = null) {
    // Handle product pages
    if (is_product()) {
        // If no product is provided, try to get it from the current post
        if (!$product) {
            global $post;
            if ($post) {
                $product = wc_get_product($post);
            }
        }
        
        if ($product) {
            // Get the base product URL without any parameters
            $base_url = $product->get_permalink();
            
            // Remove any query parameters
            $base_url = strtok($base_url, '?');
            
            // For variable products, we want to canonicalize to the parent product
            if ($product->is_type('variation')) {
                $parent_id = $product->get_parent_id();
                $parent_product = wc_get_product($parent_id);
                if ($parent_product) {
                    $base_url = $parent_product->get_permalink();
                    $base_url = strtok($base_url, '?');
                }
            }
            
            // Clean trailing number and ensure trailing slash
            $base_url = clean_url_trailing_number($base_url);
            
            return $base_url;
        }
    }
    
    // Handle product category pages
    if (is_product_category()) {
        // Get the current category
        $category = get_queried_object();
        if ($category && is_a($category, 'WP_Term')) {
            // Get the base category URL
            $base_url = get_term_link($category, 'product_cat');
            if (!is_wp_error($base_url)) {
                // Remove any query parameters and page numbers
                $base_url = preg_replace('/\/page\/\d+\/?$/', '', $base_url);
                $base_url = strtok($base_url, '?');
                
                // Clean trailing number and ensure trailing slash
                $base_url = clean_url_trailing_number($base_url);
                
                // Check if we have important filters that should be preserved
                $important_filters = get_important_filters();
                if (empty($important_filters)) {
                    return $base_url;
                }
            }
        }
    }
    
    // Handle shop page
    if (is_shop()) {
        // Get the shop page URL
        $shop_page_url = get_permalink(wc_get_page_id('shop'));
        if ($shop_page_url) {
            // Remove any query parameters and page numbers
            $base_url = preg_replace('/\/page\/\d+\/?$/', '', $shop_page_url);
            $base_url = strtok($base_url, '?');
            
            // Clean trailing number and ensure trailing slash
            $base_url = clean_url_trailing_number($base_url);
            
            // Check if we have important filters that should be preserved
            $important_filters = get_important_filters();
            if (empty($important_filters)) {
                return $base_url;
            }
        }
    }
    
    // Clean the final canonical URL and ensure trailing slash
    return clean_url_trailing_number($canonical_url);
}, 10, 2);

// Override WordPress canonical URL for product pages
add_filter('get_canonical_url', function($canonical_url) {
    if (is_product() || is_product_category() || is_shop()) {
        return apply_filters('woocommerce_get_canonical_url', $canonical_url);
    }
    return clean_url_trailing_number($canonical_url);
}, 10, 1);

// Add canonical link to head
add_action('wp_head', function() {
    // Skip if we're not on a product or category page
    if (!is_product() && !is_product_category() && !is_shop()) {
        return;
    }
    
    // Get the canonical URL
    $canonical_url = apply_filters('woocommerce_get_canonical_url', '');
    
    // Output the canonical link
    if (!empty($canonical_url)) {
        echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
    }
    
    // Add robots meta tag for paginated pages
    $paged = get_query_var('paged');
    if ($paged > 1) {
        echo '<meta name="robots" content="noindex, follow" />' . "\n";
    }
}, 1);

/**
 * Get important filters that should be preserved in canonical URLs
 * 
 * @return array Array of important filter keys
 */
function get_important_filters() {
    $important_filters = [];
    
    // Check for important category filters
    if (isset($_GET['filter_cat']) && !empty($_GET['filter_cat'])) {
        $important_filters['filter_cat'] = $_GET['filter_cat'];
    }
    
    // Check for important attribute filters
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    foreach ($attribute_taxonomies as $tax) {
        $tax_name = 'filter_' . $tax->attribute_name;
        if (isset($_GET[$tax_name]) && !empty($_GET[$tax_name])) {
            $important_filters[$tax_name] = $_GET[$tax_name];
        }
    }
    
    return $important_filters;
}

/**
 * Get the current URL without tracking parameters
 * 
 * @return string The current URL without tracking parameters
 */
function get_current_url_without_tracking_params() {
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    // Parse the URL
    $url_parts = parse_url($current_url);
    
    // If there's no query string, return the URL as is
    if (!isset($url_parts['query'])) {
        return $current_url;
    }
    
    // Parse the query string
    parse_str($url_parts['query'], $query_params);
    
    // Remove tracking parameters
    $tracking_params = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'ref', 'source', 'affiliate'];
    foreach ($tracking_params as $param) {
        if (isset($query_params[$param])) {
            unset($query_params[$param]);
        }
    }
    
    // Remove sorting parameters unless they're important
    if (isset($query_params['orderby']) && !in_array($query_params['orderby'], ['menu_order', 'date', 'price', 'popularity', 'rating'])) {
        unset($query_params['orderby']);
    }
    
    // Rebuild the query string
    $new_query = http_build_query($query_params);
    
    // Rebuild the URL
    $new_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
    if (!empty($new_query)) {
        $new_url .= '?' . $new_query;
    }
    
    // Ensure trailing slash
    $new_url = rtrim($new_url, '/') . '/';
    
    return $new_url;
}
