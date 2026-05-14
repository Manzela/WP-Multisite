<?php

/**
 * Theme setup.
 */

namespace App;

//use Illuminate\Support\Facades\View;

add_filter('woocommerce_product_tabs', function ($tabs) {
    // Add new tab details
    $tabs['custom_tab'] = [
        'title'    => __('Returns and shipping policies', 'sage'), // Title of the tab
        'priority' => 50,                       // Order of the tab
        'callback' => __NAMESPACE__ . '\\customProductTabContent', // Function to display tab content
    ];
    return $tabs;
});

// add product's tags to additional information tab
add_filter('woocommerce_product_additional_information', function($product) {
    $tags = $product->get_tags();
    if(!empty($tags)) { ?>
        <table class="woocommerce-product-attributes shop_attributes" aria-label="Product Details">
            <tbody>
                <tr class="woocommerce-product-attributes-item woocommerce-product-attributes-item--tags">
                    <th class="woocommerce-product-attributes-item__label" scope="row">
                        <?php echo esc_html__('Product tags', 'woocommerce') ?>
                    </th>
                    <td class="woocommerce-product-attributes-item__value">
                        <p> <?php echo $tags ?> </p>
                    </td>
                </tr>
            </tbody>
        </table>
    <?php
    }
}, 10, 1);

// add SKU to the beginning of the description tab
add_filter('woocommerce_product_tabs', function($tabs) {
    if (is_product()) {
        $product = wc_get_product(get_the_ID());
        $sku = $product->get_sku();

        if (!empty($sku)) {
            $tabs['description']['callback'] = function() use ($product, $sku) { ?>
                <div class="flex mb-8">
                    <h2 class="<?php echo is_rtl()? 'ml-4' : 'mr-4' ?>"> <?php echo esc_html__('SKU', 'woocommerce') ?>: </h2>
                    <p id="sku_inside_desc" data-original-sku="<?php echo esc_attr($sku) ?>"> 
                        <?php echo esc_html($sku) ?> 
                    </p>
                </div>
                <?php
                echo apply_filters('the_content', $product->get_description()); // original description content
            };
            $tabs['description']['title'] = __('Description', 'woocommerce');
            $tabs['description']['priority'] = 10;
        }
        else{
            $tabs['description']['callback'] = function() use ($product) { ?>
                <?php
                echo apply_filters('the_content', $product->get_description()); // original description content
            };
            $tabs['description']['title'] = __('Description', 'woocommerce');
            $tabs['description']['priority'] = 10;
        }
    }
    return $tabs;
});


function customProductTabContent() {
    // Retrieve policies from store settings
    $options = get_option('store_settings');
    $policies = isset($options['policies']) ? $options['policies'] : [];

    // Render the Blade view with policies data
    echo view('woocommerce.partials.policies-accordion', compact('policies'))->render();
}

// Tenant-specific SEO text appended to description tab.
// Applies only to the "example-tenant-mall" subdomain.
add_filter('woocommerce_product_tabs', function($tabs) {
    if(strpos(esc_url($_SERVER['HTTP_HOST']), 'example-tenant-mall.example-network.shop') !== false) {
        if (is_product()) {
            $product = wc_get_product(get_the_ID());
            $additional_text = "Get it now at our local store";

            $tabs['description']['callback'] = function() use ($product, $additional_text) { ?>
                <?php
                echo apply_filters('the_content', $product->get_description()); // original description content
                echo '<p class="mt-4">' . esc_html($additional_text) . '</p>';
            };
        }
    }
    return $tabs;
});


