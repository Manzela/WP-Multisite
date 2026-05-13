<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

/**
 * Customize title for category pages
 */
add_filter('wp_title', function ($title) {
    if (is_product_category()) {
        $category = get_queried_object();
        return $category->name . ' | ' . get_bloginfo('name');
    }
    return $title;
});

/* 
add_action('wp_body_open', function() {
    // Use WordPress conditional tags to include only certain pages
    if ( is_shop() || is_product_category() || is_product_tag() ) {
        echo \Roots\view()->make('sections.store-header-section')->render();
    }
}); */

/**
 * Change the placeholder of the fibo search input
 */
add_filter('dgwt/wcas/labels', function ($labels) {
    $labels['search_placeholder'] = __('Search for products...', 'ajax-search-for-woocommerce');
    return $labels;
});
// START [Search-Fix] Force form action to current subsite URL
add_filter('dgwt/wcas/form/action', function ($action) {
    return home_url('/');
});
// END [Search-Fix]

// START [Search-// Fix: Ensure search query explicitly targets 'product' post type
add_filter('dgwt/wcas/search_query/args', function ($args) {
    $args['post_type'] = 'product';
    return $args;
});

// DEBUG: Diagnose Search Refresh Issue
add_action('template_redirect', function () {
    if (isset($_GET['s'])) {
        error_log('SEARCH DEBUG: URI=' . $_SERVER['REQUEST_URI']);
        error_log('SEARCH DEBUG: is_search=' . (is_search() ? 'yes' : 'no'));
        error_log('SEARCH DEBUG: post_type=' . get_query_var('post_type'));
        error_log('SEARCH DEBUG: query=' . json_encode($GLOBALS['wp_query']->query));
    }
});
// END [Search-Fix]

// Add AJAX endpoint for getting last cart item
add_action('wc_ajax_get_cart_item', function () {
    $cart = WC()->cart->get_cart();

    // get the product as a cart-item
    $product_id = $_POST['product_id'];
    $variation_id = $_POST['variation_id'];

    foreach ($cart as $item) {
        if ($variation_id !== '') {
            if ($item['product_id'] == $product_id && $item['variation_id'] == $variation_id) {
                $last_item = $item;
                break;
            }
        } else {
            if ($item['product_id'] == $product_id) {
                $last_item = $item;
                break;
            }
        }
    }

    if (!$last_item) // return error if no item found
        wp_send_json_error();

    $product = wc_get_product($last_item['product_id']);
    $variation_id = $last_item['variation_id'];

    // check both wordpress and external image
    $image_url = wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail');
    if (!$image_url) {
        $image_url = get_post_meta($product->get_id(), '_external_image_url', true);
    }
    // Fallback to placeholder if no image found
    if (!$image_url) {
        $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
    }

    $data = [
        'title' => $product->get_title(),
        'price' => $product->get_price_html(),
        'quantity' => $last_item['quantity'],
        'image' => $image_url,
        'variation_data' => []
    ];

    // Get variation data if it exists
    if ($variation_id) {
        $variation = wc_get_product($variation_id);
        if ($variation) {
            foreach ($last_item['variation'] as $attribute => $value) {
                $taxonomy = str_replace('attribute_', '', $attribute);
                $term = get_term_by('slug', $value, $taxonomy);
                $label = wc_attribute_label($taxonomy, $product);
                $data['variation_data'][$label] = $term ? $term->name : $value;
            }
        }
    }
    wp_send_json_success($data);
});

// Make billing fields optional in checkout
add_filter('woocommerce_billing_fields', function ($fields) {
    $optional_fields = [
        'billing_first_name',
        'billing_last_name',
        'billing_address_1',
        'billing_postcode',
        'billing_city',
        'billing_phone',
        'billing_email',
        'billing_company',
        'billing_address_2',
        'billing_state',
        'billing_country',
    ];

    foreach ($optional_fields as $field) {
        if (isset($fields[$field])) {
            $fields[$field]['required'] = false;
        }
    }

    return $fields;
}, 10, 1);

// Copy contact fields to billing fields before checkout processing
add_action('woocommerce_checkout_process', function () {
    if (!empty($_POST['contact_first_name'])) {
        $_POST['billing_first_name'] = sanitize_text_field($_POST['contact_first_name']);
    }
    if (!empty($_POST['contact_last_name'])) {
        $_POST['billing_last_name'] = sanitize_text_field($_POST['contact_last_name']);
    }
    if (!empty($_POST['contact_phone'])) {
        $_POST['billing_phone'] = sanitize_text_field($_POST['contact_phone']);
    }
    if (!empty($_POST['contact_email'])) {
        $_POST['billing_email'] = sanitize_text_field($_POST['contact_email']);
    }
});

// Validate custom contact fields
add_action('woocommerce_checkout_process', function () {
    $required_fields = [
        'contact_first_name' => __('First name', 'woocommerce'),
        'contact_last_name' => __('Last name', 'woocommerce'),
        'contact_phone' => __('Phone Number', 'woocommerce'),
        'contact_email' => __('Email', 'woocommerce'),
    ];

    foreach ($required_fields as $field_key => $field_label) {
        if (empty($_POST[$field_key])) {
            wc_add_notice(sprintf(__('%s is a required field.', 'woocommerce'), $field_label), 'error');
        }
    }

    // Additional email validation
    if (!empty($_POST['contact_email']) && !is_email($_POST['contact_email'])) {
        wc_add_notice(__('The entered email address is invalid.', 'woocommerce'), 'error');
    }

    // Additional phone validation (optional - adjust pattern as needed)
    if (!empty($_POST['contact_phone']) && !preg_match('/^[0-9]{9,10}$/', $_POST['contact_phone'])) {
        wc_add_notice(__('The entered phone number is invalid.', 'woocommerce'), 'error');
    }
});

/**
 * Check if a variable product has a different price-range because some variation is on sale
 */
function check_product_sale_range($product)
{
    if (!$product->is_type('variable')) {
        return false;
    }

    $min_price = $product->get_variation_price('min');
    $max_price = $product->get_variation_price('max');
    $min_regular_price = $product->get_variation_regular_price('min');
    $max_regular_price = $product->get_variation_regular_price('max');

    return ($min_price < $min_regular_price || $max_price < $max_regular_price);
}

// pass all variations SKUs to JavaScript
add_action('wp_footer', function () {
    if (is_product()) {
        $product = wc_get_product(get_the_ID());
        if ($product->is_type('variable')) { // run only for variable products
            $variations = $product->get_available_variations();
            $variation_skus = [];
            foreach ($variations as $variation) {
                $variation_skus[$variation['variation_id']] = $variation['sku'];
            }
            echo '<script>var variations_skus = ' . json_encode($variation_skus) . ';</script>';
        }
    }
});

/**
 * Handle product variation URLs
 */
add_action('init', function () {
    add_rewrite_rule(
        '^product/([^/]+)/([^/]+)/?$',
        'index.php?product=$matches[1]&variation_sku=$matches[2]',
        'top'
    );
    add_rewrite_tag('%variation_sku%', '([^&]+)');
});

/**
 * Load correct variation on page load
 */
add_action('wp', function () {
    if (is_product() && get_query_var('variation_sku')) {
        $product = wc_get_product(get_queried_object_id());
        if ($product && $product->is_type('variable')) {
            // Get variation ID from SKU
            $variation_id = wc_get_product_id_by_sku(get_query_var('variation_sku'));
            if ($variation_id) {
                // Pass variation data to JavaScript
                add_action('wp_footer', function () use ($variation_id) {
                    echo sprintf(
                        '<script>var initial_variation_id = %d;</script>',
                        $variation_id
                    );
                });
            }
        }
    }
});

/**
 * Add canonical tags for variation URLs
 */
add_filter('woocommerce_get_canonical_url', function ($canonical_url) {
    if (is_product() && get_query_var('variation_sku')) {
        $product = wc_get_product(get_queried_object_id());
        if ($product && $product->is_type('variable')) {
            // Point canonical URL to the main product URL
            $canonical_url = get_permalink($product->get_id());
        }
    }
    return $canonical_url;
}, 20);

/**
 * Add variation meta tags
 */
add_action('wp_head', function () {
    if (is_product() && get_query_var('variation_sku')) {
        $product = wc_get_product(get_queried_object_id());
        if ($product && $product->is_type('variable')) {
            $variation_id = wc_get_product_id_by_sku(get_query_var('variation_sku'));
            if ($variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    // Add meta tags for the specific variation
                    echo sprintf(
                        '<meta property="product:price:amount" content="%s" />' . PHP_EOL .
                        '<meta property="product:price:currency" content="%s" />' . PHP_EOL .
                        '<meta property="product:availability" content="%s" />',
                        esc_attr($variation->get_price()),
                        esc_attr(get_woocommerce_currency()),
                        $variation->is_in_stock() ? 'in stock' : 'out of stock'
                    );
                }
            }
        }
    }
}, 10);

/**
 * Replace product images with external URLs if they exist
 */
add_filter('woocommerce_product_get_image', function ($image, $product, $size, $attr, $placeholder) {
    $external_url = get_post_meta($product->get_id(), '_external_image_url', true);

    if ($external_url) {
        $classes = isset($attr['class']) ? $attr['class'] : 'wp-post-image';
        return sprintf(
            '<img src="%s" alt="%s" class="%s" />',
            esc_url($external_url),
            esc_attr($product->get_name()),
            esc_attr($classes)
        );
    }

    return $image;
}, 10, 5);

/**
 * Replace product gallery images with external URLs
 */
add_filter('woocommerce_product_get_gallery_image_ids', function ($attachment_ids, $product) {
    $external_gallery = get_post_meta($product->get_id(), '_external_gallery_urls', true);

    if (!empty($external_gallery) && is_array($external_gallery)) {
        // Create fake IDs for external gallery images
        return array_map(function ($index) use ($product) {
            return 'external_gallery_' . $product->get_id() . '_' . $index;
        }, array_keys($external_gallery));
    }

    return $attachment_ids;
}, 10, 2);

/**
 * Handle external gallery image URLs
 */
add_filter('wp_get_attachment_image_src', function ($image, $attachment_id, $size, $icon) {
    if (is_string($attachment_id) && strpos($attachment_id, 'external_gallery_') === 0) {
        $parts = explode('_', $attachment_id);
        $product_id = $parts[2];
        $index = end($parts);

        $product = wc_get_product($product_id);
        if ($product) {
            $external_gallery = $product->get_meta('_external_gallery_urls');
            if (isset($external_gallery[$index])) {
                return [$external_gallery[$index], 800, 800, false];
            }
        }
    }

    return $image;
}, 10, 4);

/**
 * Handle external image URLs in admin
 */
add_filter('woocommerce_admin_product_thumbnail', function ($image_html, $product_id) {
    $product = wc_get_product($product_id);
    if ($product) {
        $external_url = $product->get_meta('_external_image_url');
        if ($external_url) {
            return sprintf(
                '<img src="%s" alt="%s" width="150" height="150" style="max-width:100%%;height:auto;" />',
                esc_url($external_url),
                esc_attr($product->get_name())
            );
        }
    }
    return $image_html;
}, 10, 2);

/**
 * Add support for external images in product gallery
 */
add_filter('woocommerce_single_product_image_thumbnail_html', function ($html, $attachment_id) {
    if (is_string($attachment_id) && strpos($attachment_id, 'external_gallery_') === 0) {
        $parts = explode('_', $attachment_id);
        $product_id = $parts[2];
        $index = end($parts);

        $product = wc_get_product($product_id);
        if ($product) {
            $external_gallery = $product->get_meta('_external_gallery_urls');
            if (isset($external_gallery[$index])) {
                return sprintf(
                    '<div class="woocommerce-product-gallery__image"><img src="%s" alt="%s" class="wp-post-image" /></div>',
                    esc_url($external_gallery[$index]),
                    esc_attr(sprintf(__('Gallery image #%d', 'woocommerce'), $index + 1))
                );
            }
        }
    }
    return $html;
}, 10, 2);

add_filter('woocommerce_product_tabs', function ($tabs) {
    if (isset($tabs['reviews'])) {
        // Store the original callback
        $original_callback = $tabs['reviews']['callback'];

        // Replace with our custom callback
        $tabs['reviews']['callback'] = function () use ($original_callback) {
            // Get the original content
            ob_start();
            $original_callback();
            $content = ob_get_clean();

            // Get our custom string
            $product = wc_get_product(get_the_ID());
            $store_name = get_option('store_settings')['seo']['store_name'] ?? '';
            $translated_string = sprintf(__('be the first to review %s', 'sage'), $product->get_name());

            if (!empty($store_name))
                $translated_string .= ', ' . sprintf(__('available at %s', 'sage'), $store_name);
            $translated_string .= '!';

            // Replace the "Be the first to review..." text in the review form title
            $content = preg_replace(
                '/<span id="reply-title".*?>(.*?)<\/span>/s',
                '<span id="reply-title" class="comment-reply-title">' . $translated_string . '</span>',
                $content
            );

            echo $content;
        };
    }
    return $tabs;
}, 98);
