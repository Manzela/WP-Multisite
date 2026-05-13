<?php
/**
 * Single Product Template Proxy
 * 
 * This file is used by PermalinkServiceProvider to force the loading of the Single Product Blade template
 * when a cross-site product request is detected.
 */

if (!function_exists('view')) {
    return;
}

try {
    global $post, $product;
    if ($post && function_exists('wc_setup_product_data')) {
        wc_setup_product_data($post); // Setup global $product
    }

    if (!$product && $post) {
        $product = wc_get_product($post->ID);
    }

    // Pass product explicitly to ensure view composers and includes have context
    echo \Roots\view('single-product', ['product' => $product])->render();

} catch (\Throwable $e) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo "<h1>View Render Error: " . $e->getMessage() . "</h1>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } else {
        error_log("Single Product Proxy Error: " . $e->getMessage());
        wp_die('Error rendering product template.');
    }
}