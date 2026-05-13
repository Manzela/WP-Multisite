<?php

namespace App\Controllers;

class EzExportController
{
    /**
     * Get products
     * @param WP_REST_Request $request
     * @return array
     */
    public function getProducts(\WP_REST_Request $request)
    {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return [
                'success' => false,
                'message' => 'WooCommerce is not active',
            ];
        }

        // Get query parameters with defaults
        $page = $request->get_param('page') ?? 1;
        $per_page = $request->get_param('per_page') ?? 10;
        $category = $request->get_param('category') ?? '';
        $search = $request->get_param('search') ?? '';
        $all = $request->get_param('all') ?? false;

        // Setup query arguments
        $args = [
            'status' => 'publish',
            'paginate' => true,
        ];

        // If all products requested, set limit to -1
        if ($all) {
            $args['limit'] = -1;
            $args['page'] = 1;
        } else {
            $args['limit'] = $per_page;
            $args['page'] = $page;
        }

        // Add category filter if provided
        if (!empty($category)) {
            $args['category'] = [$category];
        }

        // Add search filter if provided
        if (!empty($search)) {
            $args['s'] = $search;
        }

        // Get products
        $products = wc_get_products($args);

        // Format the response
        $response = [
            'success' => true,
            'data' => [
                'products' => array_map(function($product) {
                    $product_id = $product->get_id();
                    $product_cats = wc_get_product_term_ids($product_id, 'product_cat');
                    $valid_coupons = $this->getValidCouponsForProduct($product_id);

                    // Get image URL with fallback to external image URL
                    $image_url = wp_get_attachment_url($product->get_image_id());
                    if (!$image_url) {
                        $image_url = get_post_meta($product_id, '_external_image_url', true);
                    }

                    // Get gallery images with fallback to external gallery URLs
                    $gallery_images = [];
                    $attachment_ids = $product->get_gallery_image_ids();
                    if (!empty($attachment_ids)) {
                        foreach ($attachment_ids as $attachment_id) {
                            $gallery_images[] = wp_get_attachment_url($attachment_id);
                        }
                    } else {
                        $external_gallery = get_post_meta($product_id, '_external_gallery_urls', true);
                        if (!empty($external_gallery)) {
                            $gallery_images = is_array($external_gallery) ? $external_gallery : explode(',', $external_gallery);
                        }
                    }

                    // Get product categories
                    $categories = [];
                    $terms = get_the_terms($product_id, 'product_cat');
                    if (!empty($terms) && !is_wp_error($terms)) {
                        foreach ($terms as $term) {
                            $categories[] = [
                                'id' => $term->term_id,
                                'name' => $term->name,
                            ];
                        }
                    }

                    // Get product tags
                    $tags = [];
                    $tag_terms = get_the_terms($product_id, 'product_tag');
                    if (!empty($tag_terms) && !is_wp_error($tag_terms)) {
                        foreach ($tag_terms as $term) {
                            $tags[] = [
                                'id' => $term->term_id,
                                'name' => $term->name,
                            ];
                        }
                    }

                    // Get product brand
                    $brand = '';
                    $brand_terms = get_the_terms($product_id, 'product_brand');
                    if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
                        $brand = $brand_terms[0]->name;
                    }

                    return [
                        'id' => $product_id,
                        'name' => $product->get_name(),
                        'sku' => $product->get_sku(),
                        'price' => $product->get_price(),
                        'regular_price' => $product->get_regular_price(),
                        'sale_price' => $product->get_sale_price(),
                        'image' => $image_url,
                        'gallery_images' => $gallery_images,
                        'permalink' => get_permalink($product_id),
                        'stock_status' => $product->get_stock_status(),
                        'stock_quantity' => $product->get_stock_quantity(),
                        'type' => $product->get_type(),
                        'dimensions' => [
                            'length' => $product->get_length(),
                            'width' => $product->get_width(),
                            'height' => $product->get_height(),
                            'unit' => get_option('woocommerce_dimension_unit'),
                        ],
                        'weight' => [
                            'value' => $product->get_weight(),
                            'unit' => get_option('woocommerce_weight_unit'),
                        ],
                        'attributes' => array_map(function($attribute) {
                            $attribute_options = [];
                            $options = $attribute->get_options();
                            
                            // Get the attribute taxonomy name
                            $attribute_taxonomy = $attribute->get_taxonomy();
                            
                            // Process options to include both value and name
                            if (!empty($options)) {
                                foreach ($options as $option) {
                                    // For taxonomy-based attributes
                                    if ($attribute_taxonomy) {
                                        $term = get_term($option, $attribute_taxonomy);
                                        if ($term && !is_wp_error($term)) {
                                            $attribute_options[] = [
                                                'id' => $option,
                                                'name' => $term->name,
                                                'slug' => $term->slug
                                            ];
                                        }
                                    } 
                                    // For custom product attributes (non-taxonomy)
                                    else {
                                        $attribute_options[] = [
                                            'id' => $option,
                                            'name' => $option
                                        ];
                                    }
                                }
                            }
                            
                            return [
                                'name' => wc_attribute_label($attribute->get_name()),
                                'options' => $attribute_options,
                                'raw_options' => $options, // Keep original options array for backward compatibility
                                'visible' => $attribute->get_visible(),
                                'variation' => $attribute->get_variation(),
                            ];
                        }, $product->get_attributes()),
                        'variations' => $product->get_type() === 'variable' ? array_map(function($variation) {
                            $variation_obj = wc_get_product($variation);
                            $variation_attributes = $variation_obj->get_variation_attributes();
                            $enhanced_attributes = [];
                            
                            // Process each variation attribute to include more information
                            foreach ($variation_attributes as $attribute_name => $attribute_value) {
                                // Get the clean attribute name without the 'attribute_' prefix
                                $clean_attribute_name = str_replace('attribute_', '', $attribute_name);
                                
                                // Check if it's a taxonomy-based attribute
                                if (taxonomy_exists($clean_attribute_name)) {
                                    // Get the term object for this attribute value
                                    $term = get_term_by('slug', $attribute_value, $clean_attribute_name);
                                    
                                    if ($term && !is_wp_error($term)) {
                                        $enhanced_attributes[$clean_attribute_name] = [
                                            'value' => $attribute_value, // The slug
                                            'name' => $term->name,       // The display name
                                            'term_id' => $term->term_id  // The term ID
                                        ];
                                    } else {
                                        // Fallback if term not found
                                        $enhanced_attributes[$clean_attribute_name] = [
                                            'value' => $attribute_value,
                                            'name' => $attribute_value
                                        ];
                                    }
                                } else {
                                    // For custom product attributes
                                    $enhanced_attributes[$clean_attribute_name] = [
                                        'value' => $attribute_value,
                                        'name' => $attribute_value
                                    ];
                                }
                            }
                            
                            return [
                                'id' => $variation,
                                'sku' => $variation_obj->get_sku(),
                                'price' => $variation_obj->get_price(),
                                'regular_price' => $variation_obj->get_regular_price(),
                                'sale_price' => $variation_obj->get_sale_price(),
                                'stock_status' => $variation_obj->get_stock_status(),
                                'stock_quantity' => $variation_obj->get_stock_quantity(),
                                'attributes' => $enhanced_attributes,
                                'raw_attributes' => $variation_obj->get_variation_attributes(), // Keep original for backward compatibility
                            ];
                        }, $product->get_children()) : [],
                        'short_description' => $product->get_short_description(),
                        'description' => $product->get_description(),
                        'brand' => $brand,
                        'categories' => $categories,
                        'tags' => $tags,
                        'seo' => [
                            'meta_title' => get_post_meta($product_id, '_shopper_meta_title', true),
                            'meta_description' => get_post_meta($product_id, '_shopper_meta_description', true),
                            'focus_keywords' => get_post_meta($product_id, '_shopper_focus_keywords', true),
                            'canonical_url' => $this->decodeUrlForExport(get_post_meta($product_id, '_shopper_canonical_url', true)),
                            'redirect_to' => $this->decodeUrlForExport(get_post_meta($product_id, '_shopper_redirect_to', true)),
                            'redirect_type' => get_post_meta($product_id, '_shopper_redirect_type', true),
                            'image_alt_tag' => get_post_meta($product_id, '_shopper_image_alt', true),
                            'source_url' => get_post_meta($product_id, '_shopper_source_url', true),
                            'display_rank' => get_post_meta($product_id, '_shopper_display_rank', true),
                        ],
                        'coupons' => $valid_coupons,
                    ];
                }, $products->products),
                'total' => $products->total,
                'total_pages' => $all ? 1 : $products->max_num_pages,
                'current_page' => $all ? 1 : $page,
            ],
        ];

        return $response;
    }

    /**
     * Decode URL for export to preserve Hebrew and Arabic characters
     * @param string $url
     * @return string
     */
    private function decodeUrlForExport($url) {
        if (empty($url)) {
            return $url;
        }
        
        // Decode URL to preserve Hebrew and Arabic characters
        $decoded_url = urldecode($url);
        
        // Additional decoding for double-encoded URLs
        if ($decoded_url !== $url) {
            $decoded_url = urldecode($decoded_url);
        }
        
        return $decoded_url;
    }

    /**
     * Get valid coupons for a specific product
     * @param int $product_id
     * @return array
     */
    private function getValidCouponsForProduct($product_id) {
        $all_coupons = $this->getAllActiveCoupons();
        
        $valid_coupons = array_filter($all_coupons, function($coupon) use ($product_id) {
            // If product is explicitly excluded
            if (!empty($coupon['excluded_product_ids']) && in_array($product_id, $coupon['excluded_product_ids'])) {
                return false;
            }
            
            // If coupon has specific product IDs, check if this product is included
            if (!empty($coupon['product_ids']) && !in_array($product_id, $coupon['product_ids'])) {
                return false;
            }
            
            return true;
        });
        
        return array_values($valid_coupons);
    }

    /**
     * Get all active coupons
     * @return array
     */
    private function getAllActiveCoupons() {
        try {
            $args = array(
                'posts_per_page' => -1,
                'post_type'      => 'shop_coupon',
                'post_status'    => 'publish',
                'orderby'        => 'ID',
                'order'          => 'ASC',
            );

            $coupons_posts = get_posts($args);
            $coupons = [];

            foreach ($coupons_posts as $coupon_post) {
                $coupon = new \WC_Coupon($coupon_post->ID);
                
                // Skip expired coupons
                if ($coupon->get_date_expires() && $coupon->get_date_expires()->getTimestamp() < time()) {
                    continue;
                }

                // Skip if usage limit is reached
                if ($coupon->get_usage_limit() > 0 && $coupon->get_usage_count() >= $coupon->get_usage_limit()) {
                    continue;
                }

                $coupons[] = [
                    'id' => $coupon->get_id(),
                    'code' => $coupon->get_code(),
                    'amount' => $coupon->get_amount(),
                    'discount_type' => $coupon->get_discount_type(),
                    'description' => $coupon->get_description(),
                    'expiry_date' => $coupon->get_date_expires() ? $coupon->get_date_expires()->date('Y-m-d') : null,
                    'minimum_amount' => $coupon->get_minimum_amount(),
                    'maximum_amount' => $coupon->get_maximum_amount(),
                    'individual_use' => $coupon->get_individual_use(),
                    'product_ids' => $coupon->get_product_ids(),
                    'excluded_product_ids' => $coupon->get_excluded_product_ids(),
                    'product_categories' => $coupon->get_product_categories(),
                    'excluded_product_categories' => $coupon->get_excluded_product_categories(),
                    'usage_count' => $coupon->get_usage_count(),
                    'usage_limit' => $coupon->get_usage_limit(),
                    'usage_limit_per_user' => $coupon->get_usage_limit_per_user(),
                ];
            }

            return $coupons;
        } catch (\Exception $e) {
            error_log('Error getting coupons: ' . $e->getMessage());
            return [];
        }
    }
}