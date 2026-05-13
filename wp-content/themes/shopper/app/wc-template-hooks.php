<?php

/**
 * WooCommerce Template Hooks
 *
 * Action/filter hooks used for WooCommerce functions/templates.
 *
 * @package WooCommerce/Templates
 * @version 2.1.0
 */

namespace App;

// remove_filter('body_class', 'wc_body_class');
// remove_filter('post_class', 'wc_product_post_class', 20);

/**
 * WP Header.
 *
 * @see wc_generator_tag()
 */
// remove_filter('get_the_generator_html', 'wc_generator_tag', 10);
// remove_filter('get_the_generator_xhtml', 'wc_generator_tag', 10);

/**
 * Content Wrappers.
 *
 * @see woocommerce_output_content_wrapper()
 * @see woocommerce_output_content_wrapper_end()
 */
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

/**
 * Sale flashes.
 *
 * @see woocommerce_show_product_loop_sale_flash()
 * @see woocommerce_show_product_sale_flash()
 */
// remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);
// remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10);

/**
 * Breadcrumbs.
 *
 * @see woocommerce_breadcrumb()
 */
// remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);

/**
 * Sidebar.
 *
 * @see woocommerce_get_sidebar()
 */
// remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

/**
 * Archive descriptions.
 *
 * @see woocommerce_taxonomy_archive_description()
 * @see woocommerce_product_archive_description()
 */
// remove_action('woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10);
// remove_action('woocommerce_archive_description', 'woocommerce_product_archive_description', 10);

/**
 * Product loop start.
 */
// remove_filter('woocommerce_product_loop_start', 'woocommerce_maybe_show_product_subcategories');

/**
 * Products Loop.
 *
 * @see woocommerce_result_count()
 * @see woocommerce_catalog_ordering()
 */
remove_action('woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10);
remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
// remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
// remove_action('woocommerce_no_products_found', 'wc_no_products_found');

/**
 * Product Loop Items.
 *
 * @see woocommerce_template_loop_product_link_open()
 * @see woocommerce_template_loop_product_link_close()
 * @see woocommerce_template_loop_add_to_cart()
 * @see woocommerce_template_loop_product_thumbnail()
 * @see woocommerce_template_loop_product_title()
 * @see woocommerce_template_loop_category_link_open()
 * @see woocommerce_template_loop_category_title()
 * @see woocommerce_template_loop_category_link_close()
 * @see woocommerce_template_loop_price()
 * @see woocommerce_template_loop_rating()
 */
// remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
// remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);
// remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
// remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
// remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);

// remove_action('woocommerce_before_subcategory', 'woocommerce_template_loop_category_link_open', 10);
// remove_action('woocommerce_shop_loop_subcategory_title', 'woocommerce_template_loop_category_title', 10);
// remove_action('woocommerce_after_subcategory', 'woocommerce_template_loop_category_link_close', 10);

// remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
// remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);

/**
 * Subcategories.
 *
 * @see woocommerce_subcategory_thumbnail()
 */
// remove_action('woocommerce_before_subcategory_title', 'woocommerce_subcategory_thumbnail', 10);

/**
 * Before Single Products Summary Div.
 *
 * @see woocommerce_show_product_images()
 * @see woocommerce_show_product_thumbnails()
 */
// remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
// remove_action('woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20);

/**
 * After Single Products Summary Div.
 *
 * @see woocommerce_output_product_data_tabs()
 * @see woocommerce_upsell_display()
 * @see woocommerce_output_related_products()
 */
remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
// remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
// remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);

/**
 * Product Summary Box.
 *
 * @see woocommerce_template_single_title()
 * @see woocommerce_template_single_rating()
 * @see woocommerce_template_single_price()
 * @see woocommerce_template_single_excerpt()
 * @see woocommerce_template_single_meta()
 * @see woocommerce_template_single_sharing()
 */
// remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
// remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
// remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
// remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
// remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
// remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);

/**
 * Reviews
 *
 * @see woocommerce_review_display_gravatar()
 * @see woocommerce_review_display_rating()
 * @see woocommerce_review_display_meta()
 * @see woocommerce_review_display_comment_text()
 */
// remove_action('woocommerce_review_before', 'woocommerce_review_display_gravatar', 10);
// remove_action('woocommerce_review_before_comment_meta', 'woocommerce_review_display_rating', 10);
// remove_action('woocommerce_review_meta', 'woocommerce_review_display_meta', 10);
// remove_action('woocommerce_review_comment_text', 'woocommerce_review_display_comment_text', 10);

/**
 * Product Add to cart.
 *
 * @see woocommerce_template_single_add_to_cart()
 * @see woocommerce_simple_add_to_cart()
 * @see woocommerce_grouped_add_to_cart()
 * @see woocommerce_variable_add_to_cart()
 * @see woocommerce_external_add_to_cart()
 * @see woocommerce_single_variation()
 * @see woocommerce_single_variation_add_to_cart_button()
 */
// remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
// remove_action('woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30);
// remove_action('woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30);
// remove_action('woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30);
// remove_action('woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30);
// remove_action('woocommerce_single_variation', 'woocommerce_single_variation', 10);
// remove_action('woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20);

/**
 * Pagination after shop loops.
 *
 * @see woocommerce_pagination()
 */
// remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);

/**
 * Product page tabs.
 */
// remove_filter('woocommerce_product_tabs', 'woocommerce_default_product_tabs');
// remove_filter('woocommerce_product_tabs', 'woocommerce_sort_product_tabs', 99);

/**
 * Additional Information tab.
 *
 * @see wc_display_product_attributes()
 */
// remove_action('woocommerce_product_additional_information', 'wc_display_product_attributes', 10);

/**
 * Checkout.
 *
 * @see woocommerce_checkout_login_form()
 * @see woocommerce_checkout_coupon_form()
 * @see woocommerce_order_review()
 * @see woocommerce_checkout_payment()
 * @see wc_checkout_privacy_policy_text()
 * @see wc_terms_and_conditions_page_content()
 * @see wc_get_pay_buttons()
 */
// remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10);
// remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
// remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10);
// remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
// remove_action('woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20);
// remove_action('woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30);
// remove_action('woocommerce_checkout_before_customer_details', 'wc_get_pay_buttons', 30);

/**
 * Cart widget
 */
// remove_action('woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10);
// remove_action('woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_proceed_to_checkout', 20);
// remove_action('woocommerce_widget_shopping_cart_total', 'woocommerce_widget_shopping_cart_subtotal', 10);

/**
 * Cart.
 *
 * @see woocommerce_cross_sell_display()
 * @see woocommerce_cart_totals()
 * @see wc_get_pay_buttons()
 * @see woocommerce_button_proceed_to_checkout()
 * @see wc_empty_cart_message()
 */
// remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');
// remove_action('woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10);
// remove_action('woocommerce_proceed_to_checkout', 'wc_get_pay_buttons', 10);
// remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
// remove_action('woocommerce_cart_is_empty', 'wc_empty_cart_message', 10);

/**
 * Footer.
 *
 * @see  wc_print_js()
 * @see woocommerce_demo_store()
 */
// remove_action('wp_footer', 'wc_print_js', 25);
// remove_action('wp_footer', 'woocommerce_demo_store');

/**
 * Order details.
 *
 * @see woocommerce_order_details_table()
 * @see woocommerce_order_again_button()
 */
// remove_action('woocommerce_view_order', 'woocommerce_order_details_table', 10);
// remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);
// remove_action('woocommerce_order_details_after_order_table', 'woocommerce_order_again_button');

/**
 * Order downloads.
 *
 * @see woocommerce_order_downloads_table()
 */
//remove_action('woocommerce_available_downloads', 'woocommerce_order_downloads_table', 10);

/**
 * Auth.
 *
 * @see woocommerce_output_auth_header()
 * @see woocommerce_output_auth_footer()
 */
// remove_action('woocommerce_auth_page_header', 'woocommerce_output_auth_header', 10);
// remove_action('woocommerce_auth_page_footer', 'woocommerce_output_auth_footer', 10);



/**
 * My Account.
 */
// remove_action('woocommerce_account_navigation', 'woocommerce_account_navigation');
// remove_action('woocommerce_account_content', 'woocommerce_account_content');
// remove_action('woocommerce_account_orders_endpoint', 'woocommerce_account_orders');
// remove_action('woocommerce_account_view-order_endpoint', 'woocommerce_account_view_order');
remove_action('woocommerce_account_downloads_endpoint', 'woocommerce_account_downloads');
// remove_action('woocommerce_account_edit-address_endpoint', 'woocommerce_account_edit_address');
// remove_action('woocommerce_account_payment-methods_endpoint', 'woocommerce_account_payment_methods');
// remove_action('woocommerce_account_add-payment-method_endpoint', 'woocommerce_account_add_payment_method');
// remove_action('woocommerce_account_edit-account_endpoint', 'woocommerce_account_edit_account');
// remove_action('woocommerce_register_form', 'wc_registration_privacy_policy_text', 20);

/**
 * Notices.
 */
// remove_action('woocommerce_cart_is_empty', 'woocommerce_output_all_notices', 5);
// remove_action('woocommerce_shortcode_before_product_cat_loop', 'woocommerce_output_all_notices', 10);
// remove_action('woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10);
// remove_action('woocommerce_before_single_product', 'woocommerce_output_all_notices', 10);
// remove_action('woocommerce_before_cart', 'woocommerce_output_all_notices', 10);
// remove_action('woocommerce_before_checkout_form_cart_notices', 'woocommerce_output_all_notices', 10);
// remove_action('woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10);
// remove_action('woocommerce_account_content', 'woocommerce_output_all_notices', 5);
// remove_action('woocommerce_before_customer_login_form', 'woocommerce_output_all_notices', 10);
// remove_action('woocommerce_before_lost_password_form', 'woocommerce_output_all_notices', 10);
// remove_action('before_woocommerce_pay', 'woocommerce_output_all_notices', 10);
// remove_action('woocommerce_before_reset_password_form', 'woocommerce_output_all_notices', 10);

/**
 * Remove WooCommerce default styles
 */
add_filter('woocommerce_enqueue_styles', function ($enqueue_styles) {
    //unset($enqueue_styles['woocommerce-general']);     // General WooCommerce styles
    unset($enqueue_styles['woocommerce-layout']); // WooCommerce layout styles
    //unset($enqueue_styles['woocommerce-smallscreen']); // WooCommerce small screen/responsive styles
    //    wp_dequeue_style('wc-blocks-style-cart');
    return $enqueue_styles;
});

// Or if you want to remove all styles completely:
//add_filter('woocommerce_enqueue_styles', '__return_empty_array');

/**
 * Add cart count to WooCommerce AJAX fragments
 */
add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    $count = WC()->cart->get_cart_contents_count();
    // Get site's text direction from WordPress options
    $is_rtl = get_option('WPLANG') === 'he_IL' || is_rtl();

    $fragments['.cart-count'] = sprintf(
        '<span class="cart-count absolute mb-4 %s bg-[red] text-white text-sm rounded-full h-5 w-5 flex items-center justify-center %s">%d</span>',
        $is_rtl ? 'mr-3' : 'ml-3',
        $count === 0 ? 'hidden' : '',
        $count
    );
    return $fragments;
});

// this element was remove from "woocommerce_before_shop_loop" hook, and added here instead
add_action('woocommerce_before_main_content', 'woocommerce_output_all_notices', 10);

// related to my-account.js script
// define user info in a javascript format in order to use it in my-account page (scripts)
add_action('woocommerce_account_content', function () {
    // Ensure the user is logged in
    if (is_user_logged_in()) {
        // Get current user info
        $current_user = wp_get_current_user();
        $user_info = [
            'first_name' => $current_user->user_firstname,
            'last_name' => $current_user->user_lastname,
            'email' => $current_user->user_email,
            'shipping_address' => [
                'first_name' => get_user_meta($current_user->ID, 'shipping_first_name', true),
                'last_name' => get_user_meta($current_user->ID, 'shipping_last_name', true),
                'address_1' => get_user_meta($current_user->ID, 'shipping_address_1', true),
                'address_2' => get_user_meta($current_user->ID, 'shipping_address_2', true),
                'city' => get_user_meta($current_user->ID, 'shipping_city', true),
                'state' => get_user_meta($current_user->ID, 'shipping_state', true),
                'postcode' => get_user_meta($current_user->ID, 'shipping_postcode', true),
                'country' => get_user_meta($current_user->ID, 'shipping_country', true),
            ],
        ];

        // Output user info as a JavaScript variable
        echo '<script type="text/javascript">
            const userInfo = ' .
            json_encode($user_info) .
            ';
        </script>';
    }
});

// related to my-account.js script
// define order data in a javascript format in order to use it in my-account page (scripts)
add_action('woocommerce_account_content', function () {
    // Ensure the user is logged in
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $customer_orders = wc_get_orders(array(
            'customer_id' => $current_user->ID,
        ));

        $orders_data = array();
        foreach ($customer_orders as $order) {
            foreach ($order->get_items() as $item_id => $item) {
                $product = $item->get_product();
                if ($product) {
                    $orders_data[] = array(
                        'product_name' => $item->get_name(),
                        'product_id' => $product->get_id(),
                        'product_image' => wp_get_attachment_url($product->get_image_id()),
                    );
                }
            }
        }
        // Output orders data as a JavaScript variable
        echo '<script type="text/javascript">
            const ordersData = ' . json_encode($orders_data) . ';
        </script>';
    }
});

// related to utils/translations.js script
// define translations in a javascript format in order to use them in the website (scripts)
add_action('wp_footer', function () {
    $translations = [
        'woocommerce' => [
            'Select options' => __('Select options', 'woocommerce'),
            'Out of stock' => __('Out of stock', 'woocommerce'),
            'Add to cart' => __('Add to cart', 'woocommerce'),
            'In stock' => __('In stock', 'woocommerce'),
            'Units in stock' => __('Units in stock', 'woocommerce'),
            'Quantity' => __('Quantity', 'woocommerce'),
            'Order' => __('Order', 'woocommerce'),
        ],
        'sage' => [
            'Hi' => __('Hi', 'sage'),
            'Show More Information' => __('Show More Information', 'sage'),
            'Show Less Information' => __('Show Less Information', 'sage'),
        ],
    ];
    echo '<script type="text/javascript">
        var translations = ' . json_encode($translations) . ';
    </script>';
});


/* Permanently disable unused fields */
add_filter(
    'woocommerce_checkout_fields',
    [\App\Providers\DeliveryRulesServiceProvider::class, 'removeUnusedCheckoutFields'],
    30
);

/* Permanently disable delivery fields for stores that have no delivery available */
add_filter(
    'woocommerce_checkout_fields',
    [\App\Providers\DeliveryRulesServiceProvider::class, 'removeDeliveryFields'],
    40
);

