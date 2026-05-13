<?php

/**
 * Modify product image attributes
 */
add_filter('wp_get_attachment_image_attributes', function($attr, $attachment, $size) {
    // Check if this is a product image
    $post_parent = get_post_field('post_parent', $attachment->ID);
    if (!$post_parent || get_post_type($post_parent) !== 'product') {
        return $attr;
    }

    // Get product name from cache if possible
    $product_name = wp_cache_get('product_name_' . $post_parent);
    if (false === $product_name) {
        $product = wc_get_product($post_parent);
        if ($product) {
            $product_name = $product->get_name();
            wp_cache_set('product_name_' . $post_parent, $product_name);
        }
    }

    if ($product_name) {
        $attr['alt'] = $attr['title'] = $product_name;
    }
    
    return $attr;
}, 10, 3);

/**
 * Modify product gallery image attributes
 */
add_filter('woocommerce_gallery_image_html_attachment_image_params', function($params, $attachment_id, $image_size, $main_image) {
    $post_parent = get_post_field('post_parent', $attachment_id);
    if (!$post_parent) {
        return $params;
    }

    // Get product name from cache if possible
    $product_name = wp_cache_get('product_name_' . $post_parent);
    if (false === $product_name) {
        $product = wc_get_product($post_parent);
        if ($product) {
            $product_name = $product->get_name();
            wp_cache_set('product_name_' . $post_parent, $product_name);
        }
    }

    if ($product_name) {
        $params['alt'] = $params['title'] = $product_name;
    }
    return $params;
}, 10, 4);

/**
 * Handle new product image uploads
 */
add_action('add_attachment', function($attachment_id) {
    $post_parent = get_post_field('post_parent', $attachment_id);
    if (!$post_parent || get_post_type($post_parent) !== 'product') {
        return;
    }

    $product = wc_get_product($post_parent);
    if (!$product) {
        return;
    }

    $product_name = $product->get_name();

    // Update attachment in a single query
    wp_update_post([
        'ID' => $attachment_id,
        'post_title' => $product_name,
        'post_excerpt' => $product_name,
        'post_content' => ''
    ]);

    update_post_meta($attachment_id, '_wp_attachment_image_alt', $product_name);
});

// Optional: Run this once to clean up existing images
//To clean up existing images, you can uncomment the last section and run it once. After it runs, it will set an option flag and never run again.
// add_action('init', function() {
//     if (get_option('product_images_cleaned')) {
//         return;
//     }
//     
//     // Your existing cleanup code here
//     
//     update_option('product_images_cleaned', true);
// });