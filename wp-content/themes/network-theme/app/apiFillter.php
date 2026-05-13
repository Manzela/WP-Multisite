<?php

/**
 * Add product schema data
 * author: Edward Ziadeh
 * date: 15/01/2025
 * description: This file is used to add product schema data to the product page.
 */

namespace App;

add_filter('rank_math/json_ld', function ($data, $jsonld) {
    if (!is_product()) {
        return $data;
    }

    global $product;
    if (!$product) {
        return $data;
    }

    $product_data = [
        '@type' => 'Product',
        '@id' => get_permalink() . '#product',
        'name' => $product->get_name(),
        'description' => $product->get_description() ?: $product->get_short_description(),
        'sku' => $product->get_sku(),
        'brand' => [
            '@type' => 'Brand',
            'name' => get_bloginfo('name')
        ],
        'offers' => [
            '@type' => 'Offer',
            'price' => $product->get_price(),
            'priceCurrency' => get_woocommerce_currency(),
            'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => get_permalink(),
            'priceValidUntil' => date('Y-12-31')
        ]
    ];

    // Add image if exists
    if ($product->get_image_id()) {
        $product_data['image'] = [
            '@type' => 'ImageObject',
            'url' => wp_get_attachment_url($product->get_image_id())
        ];
    }

    // Add product data to schema
    $data['product'] = $product_data;

    return $data;
}, 10, 2);