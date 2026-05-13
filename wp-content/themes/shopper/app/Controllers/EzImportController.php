<?php

namespace App\Controllers;

class EzImportController
{
    private $skip_image_download = false;

    /**
     * Update products and coupons
     * @param WP_REST_Request $request
     * @return array
     */
    public function updateProducts(\WP_REST_Request $request)
    {

        try {
            // Get skip_image_download parameter
            $this->skip_image_download = filter_var($request->get_param('skip_image_download'), FILTER_VALIDATE_BOOLEAN);
            $on_all_sites = filter_var($request->get_param('onallsites'), FILTER_VALIDATE_BOOLEAN);
            if ($on_all_sites && is_multisite()) {
                return $this->processMultiSiteImport($request);
            } else {
                return $this->processSingleSiteImport($request);
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ];
        }
    }

    private function processMultiSiteImport(\WP_REST_Request $request)
    {
        // Get all sites except main site
        $sites = get_sites([
            'site__not_in' => [get_main_site_id()],
            'archived' => 0,
            'spam' => 0,
            'deleted' => 0
        ]);

        $sites_results = [];
        $processed_count = 0;
        $success_count = 0;

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);

            try {
                // Check if WooCommerce is active
                if (!is_plugin_active('woocommerce/woocommerce.php')) {
                    error_log("WooCommerce not active on site {$site->blog_id}");
                    continue;
                }

                $result = $this->processSingleSiteImport($request);
                $processed_count++;

                if ($result['success']) {
                    $success_count++;
                }

                $sites_results[$site->blog_id] = [
                    'success' => $result['success'],
                    'site_name' => get_bloginfo('name'),
                    'site_url' => get_bloginfo('url'),
                    'results' => $result
                ];

            } catch (\Exception $e) {
                error_log("Error processing site {$site->blog_id}: " . $e->getMessage());
                $sites_results[$site->blog_id] = [
                    'success' => false,
                    'site_name' => get_bloginfo('name'),
                    'site_url' => get_bloginfo('url'),
                    'error' => $e->getMessage()
                ];
            }

            restore_current_blog();
        }

        return [
            'success' => true,
            'message' => 'Multi-site import completed',
            'data' => [
                'sites_total' => count($sites),
                'sites_processed' => $processed_count,
                'sites_succeeded' => $success_count,
                'sites_failed' => $processed_count - $success_count,
                'sites_results' => $sites_results
            ]
        ];
    }

    private function processSingleSiteImport(\WP_REST_Request $request)
    {
        $data = json_decode($request->get_body(), true);

        // Log the first few products for debugging
        if (!empty($data['products'])) {
            $sample_products = array_slice($data['products'], 0, min(3, count($data['products'])));
            error_log('Processing import with sample products: ' . print_r($sample_products, true));
        }

        if (empty($data['products'])) {
            return [
                'success' => false,
                'message' => 'No products data found'
            ];
        }

        $results = [
            'products' => [],
            'coupons' => []
        ];

        foreach ($data['products'] as $product_data) {
            try {
                $result = $this->processProduct($product_data);
                $results['products'][] = $result;
            } catch (\Exception $e) {
                error_log('Error processing product: ' . $e->getMessage());
                $results['products'][] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'product_data' => $product_data
                ];
            }
        }

        // Count deleted products
        $deleted_products = array_filter($results['products'], function($p) {
            return $p['success'] && isset($p['message']) && strpos($p['message'], 'deleted') !== false;
        });

        return [
            'success' => true,
            'data' => $results,
            'debug' => [
                'skip_image_download' => $this->skip_image_download,
                'site_id' => get_current_blog_id(),
                'site_name' => get_bloginfo('name'),
                'products_processed' => count($results['products']),
                'products_succeeded' => count(array_filter($results['products'], function($p) { 
                    return $p['success']; 
                })),
                'products_deleted' => count($deleted_products)
            ]
        ];
    }

    private function handleImage($image_url, $product_id, $is_gallery = false)
    {
        error_log("handleImage called with product ID: {$product_id}");
        
        if (empty($product_id)) {
            throw new \Exception('No product ID provided for image handling');
        }
        if ($this->skip_image_download) {
            $sanitized_url = esc_url_raw($image_url);
            
            if ($is_gallery) {
                // For gallery, get existing gallery URLs or initialize empty array
                $gallery_urls = get_post_meta($product_id, '_external_gallery_urls', true);
                $gallery_urls = is_array($gallery_urls) ? $gallery_urls : array();
                
                // Add new URL if it doesn't exist
                if (!in_array($sanitized_url, $gallery_urls)) {
                    $gallery_urls[] = $sanitized_url;
                }
                
                // Update gallery URLs
                update_post_meta($product_id, '_external_gallery_urls', $gallery_urls);
                error_log("Updated gallery URLs for product {$product_id}: " . print_r($gallery_urls, true));
            } else {
                // For main image
                update_post_meta($product_id, '_external_image_url', $sanitized_url);
                error_log("Updated main image URL for product {$product_id}: {$sanitized_url}");
            }
            
            return $sanitized_url;
        } else {
            // Download and attach image
            $upload_dir = wp_upload_dir();
            
            // Get image data
            $response = wp_safe_remote_get($image_url);
            if (is_wp_error($response)) {
                throw new \Exception('Failed to download image: ' . $response->get_error_message());
            }
            
            $image_data = wp_remote_retrieve_body($response);
            if (empty($image_data)) {
                throw new \Exception('Empty image data received');
            }

            // Generate unique filename
            $filename = wp_unique_filename($upload_dir['path'], basename($image_url));
            $filepath = $upload_dir['path'] . '/' . $filename;

            // Save file
            if (!file_put_contents($filepath, $image_data)) {
                throw new \Exception('Failed to save image file');
            }

            // Prepare attachment data
            $wp_filetype = wp_check_filetype($filename, null);
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name($filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            // Insert attachment
            $attach_id = wp_insert_attachment($attachment, $filepath, $product_id);
            if (is_wp_error($attach_id)) {
                throw new \Exception('Failed to create attachment: ' . $attach_id->get_error_message());
            }

            // Generate metadata and update attachment
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
            wp_update_attachment_metadata($attach_id, $attach_data);

            return $attach_id;
        }
    }

    public function processProduct($product_data)
    {
        try {
            // Check if this is a delete request - check for 'delete' field, but maintain backward compatibility with 'deleted'
            if (isset($product_data['delete']) && ($product_data['delete'] === true || $product_data['delete'] === 'true')) {
                error_log('Delete request detected for product: ' . print_r($product_data, true));
                return $this->deleteProduct($product_data);
            }
            // For backward compatibility, also check for 'deleted'
            elseif (isset($product_data['deleted']) && ($product_data['deleted'] === true || $product_data['deleted'] === 'true')) {
                error_log('Delete request detected (using legacy "deleted" field) for product: ' . print_r($product_data, true));
                // Convert to the standard format
                $product_data['delete'] = $product_data['deleted'];
                return $this->deleteProduct($product_data);
            }
            
            // Get or create product
            $product_id = null;
            $is_new_product = false;
            
            // Try to find by ID first
            if (!empty($product_data['id'])) {
                $product_id = absint($product_data['id']);
                $product = wc_get_product($product_id);
                
                // If product doesn't exist with this ID, don't create a new one
                if (!$product) {
                    error_log('Product with ID ' . $product_id . ' not found. Cannot update non-existent product.');
                    return [
                        'success' => false,
                        'message' => 'Product with ID ' . $product_id . ' not found. Cannot update non-existent product.',
                        'data' => $product_data
                    ];
                }
            }
            // If no ID, try to find by SKU
            elseif (!empty($product_data['sku'])) {
                $product_id = wc_get_product_id_by_sku($product_data['sku']);
                $product = $product_id ? wc_get_product($product_id) : new \WC_Product();
                $is_new_product = !$product_id;
            } 
            // If no SKU, try to find by source URL
            elseif (!empty($product_data['seo']) && !empty($product_data['seo']['source_url'])) {
                $source_url = $this->encodeUrlForImport($product_data['seo']['source_url']);
                $normalized_source_url = $this->normalizeUrlForComparison($source_url);
                
                error_log('Looking for product with source URL: ' . $source_url);
                error_log('Normalized source URL: ' . $normalized_source_url);
                
                // Get all possible variations of the URL for matching
                $url_variations = $this->getUrlVariations($normalized_source_url);
                
                // Query for products with this source URL using multiple variations
                $args = array(
                    'post_type'      => 'product',
                    'posts_per_page' => 1,
                    'meta_query'     => array(
                        'relation' => 'OR',
                        array(
                            'key'     => '_shopper_source_url',
                            'value'   => $url_variations,
                            'compare' => 'IN'
                        )
                    )
                );
                
                $query = new \WP_Query($args);
                
                if ($query->have_posts()) {
                    $query->the_post();
                    $product_id = get_the_ID();
                    $product = wc_get_product($product_id);
                    error_log('Found product by source URL: ' . $source_url . ', ID: ' . $product_id);
                } else {
                    // For debugging, let's see what's actually in the database
                    global $wpdb;
                    $stored_urls = $wpdb->get_col("
                        SELECT meta_value 
                        FROM {$wpdb->postmeta} 
                        WHERE meta_key = '_shopper_source_url' 
                        AND meta_value LIKE '%" . esc_sql($wpdb->esc_like(parse_url($normalized_source_url, PHP_URL_HOST))) . "%'
                    ");
                    error_log('Stored URLs in database for host ' . parse_url($normalized_source_url, PHP_URL_HOST) . ': ' . print_r($stored_urls, true));
                    error_log('URL variations we searched for: ' . print_r($url_variations, true));
                    
                    $product = new \WC_Product();
                    $is_new_product = true;
                    error_log('No product found with source URL: ' . $source_url . ', creating new product');
                }
                
                wp_reset_postdata();
            }
            // If neither ID, SKU, nor source URL, create a new product
            else {
                $product = new \WC_Product();
                $is_new_product = true;
            }

            // Handle creation date if provided
            if (!empty($product_data['created_at'])) {
                $created_at = sanitize_text_field($product_data['created_at']);
                $created_at_gmt = get_gmt_from_date($created_at);
                
                if ($is_new_product) {
                    // For new products, set the creation date before saving
                    $product->set_date_created($created_at);
                    error_log('Set creation date for new product: ' . $created_at);
                } else {
                    // For existing products, update the creation date
                    wp_update_post([
                        'ID' => $product->get_id(),
                        'post_date' => $created_at,
                        'post_date_gmt' => $created_at_gmt
                    ]);
                    error_log('Updated creation date for existing product ' . $product->get_id() . ': ' . $created_at);
                }
            }

            // Set basic data
            if (isset($product_data['name'])) {
                $product->set_name($product_data['name']);
            }
            if (isset($product_data['sku'])) {
                $product->set_sku($product_data['sku']);
            }
            if (isset($product_data['regular_price'])) {
                $product->set_regular_price($product_data['regular_price']);
            }
            // Add support for sale price
            if (isset($product_data['sale_price'])) {
                $product->set_sale_price($product_data['sale_price']);
            }
            if (isset($product_data['description'])) {
                $product->set_description($product_data['description']);
            }
            if (isset($product_data['short_description'])) {
                $product->set_short_description($product_data['short_description']);
            }
            if (isset($product_data['stock_status'])) {
                $product->set_stock_status($product_data['stock_status']);
            }

            // Save product first to ensure we have an ID
            $product->save();
            $product_id = $product->get_id();

            error_log('Processing product ID: ' . $product_id . ' with skip_image_download: ' . ($this->skip_image_download ? 'true' : 'false'));

            // Handle display rank - consolidated handling
            $display_rank = 1; // Default value
            if (isset($product_data['seo']['display_rank'])) {
                $display_rank = absint($product_data['seo']['display_rank']);
                error_log('Setting display rank from seo.display_rank: ' . $display_rank);
            } elseif (isset($product_data['display_rank'])) {
                $display_rank = absint($product_data['display_rank']);
                error_log('Setting display rank from display_rank: ' . $display_rank);
            }
            $display_rank = max(1, min(10, $display_rank));
            update_post_meta($product_id, '_shopper_display_rank', $display_rank);
            error_log('Set display rank for product ' . $product_id . ': ' . $display_rank);
            
            // Set product type if specified
            if (isset($product_data['type']) && in_array($product_data['type'], array('simple', 'variable'))) {
                $product_type = $product_data['type'];
                
                // If type is variable but no variations are provided, change to simple
                if ($product_type === 'variable' && (empty($product_data['variations']) || !is_array($product_data['variations']))) {
                    error_log('Product type is set to variable but no variations provided, changing to simple');
                    $product_type = 'simple';
                }
                
                wp_set_object_terms($product->get_id(), $product_type, 'product_type');
                
                // If variable product, create a WC_Product_Variable instead
                if ($product_type === 'variable' && !($product instanceof \WC_Product_Variable)) {
                    $product->save(); // Save current product to get ID
                    $product_id = $product->get_id();
                    $product = new \WC_Product_Variable($product_id);
                }
            }

            // Handle categories
            if (!empty($product_data['categories'])) {
                $category_ids = [];
                foreach ($product_data['categories'] as $category) {
                    if (!empty($category['name'])) {
                        // Handle parent category first if exists
                        $parent_id = 0;
                        if (!empty($category['parent'])) {
                            $parent_term = get_term_by('name', $category['parent'], 'product_cat');
                            if (!$parent_term) {
                                // Create parent category if it doesn't exist
                                $parent_result = wp_insert_term($category['parent'], 'product_cat');
                                if (!is_wp_error($parent_result)) {
                                    $parent_id = $parent_result['term_id'];
                                }
                            } else {
                                $parent_id = $parent_term->term_id;
                            }
                        }

                        // Get or create the category
                        $term = get_term_by('name', $category['name'], 'product_cat');
                        if (!$term) {
                            $term = wp_insert_term(
                                $category['name'], 
                                'product_cat',
                                array('parent' => $parent_id)
                            );
                            if (!is_wp_error($term)) {
                                $category_ids[] = $term['term_id'];
                            }
                        } else {
                            // Update parent if needed
                            if ($parent_id !== 0 && $term->parent !== $parent_id) {
                                wp_update_term($term->term_id, 'product_cat', array('parent' => $parent_id));
                            }
                            $category_ids[] = $term->term_id;
                        }
                    }
                }
                if (!empty($category_ids)) {
                    $product->set_category_ids($category_ids);
                }
            }

            // Handle brand after product is saved
            if (!empty($product_data['brand']) || (!empty($product_data['brands']) && !empty($product_data['brands']['name']))) {
                error_log('Processing brand for product: ' . $product_id);
                
                // Get brand data from either format
                if (!empty($product_data['brand']) && is_array($product_data['brand'])) {
                    // Handle new format: "brand": {"name": "Nike", "slug": "nike", ...}
                    $brand_name = !empty($product_data['brand']['name']) ? $product_data['brand']['name'] : '';
                    $brand_description = !empty($product_data['brand']['description']) ? $product_data['brand']['description'] : '';
                    $brand_slug = !empty($product_data['brand']['slug']) ? 
                                 sanitize_title($product_data['brand']['slug']) : 
                                 sanitize_title($brand_name);
                    $brand_image = !empty($product_data['brand']['image']) ? $product_data['brand']['image'] : '';
                } elseif (!empty($product_data['brand']) && is_string($product_data['brand'])) {
                    // Handle legacy format: "brand": "Nike"
                    $brand_name = $product_data['brand'];
                    $brand_description = '';
                    $brand_slug = sanitize_title($brand_name);
                    $brand_image = '';
                } else {
                    // Handle old format: "brands": {"name": "Nike", ...}
                    $brand_name = $product_data['brands']['name'];
                    $brand_description = !empty($product_data['brands']['description']) ? $product_data['brands']['description'] : '';
                    $brand_slug = !empty($product_data['brands']['slug']) ? 
                                 sanitize_title($product_data['brands']['slug']) : 
                                 sanitize_title($brand_name);
                    $brand_image = !empty($product_data['brands']['image']) ? $product_data['brands']['image'] : '';
                }
                
                error_log('Brand data: ' . print_r([
                    'name' => $brand_name,
                    'description' => $brand_description,
                    'slug' => $brand_slug,
                    'image' => $brand_image
                ], true));

                // Get or create the brand term
                error_log('Looking for brand with slug: ' . $brand_slug);
                $brand_term = get_term_by('slug', $brand_slug, 'product_brand');
                
                if (!$brand_term) {
                    error_log('Brand not found, creating new brand');
                    $brand_args = [
                        'slug' => $brand_slug,
                        'description' => $brand_description,
                    ];
                    
                    error_log('Creating brand with args: ' . print_r($brand_args, true));
                    $term_result = wp_insert_term(
                        $brand_name,
                        'product_brand',
                        $brand_args
                    );
                    
                    if (is_wp_error($term_result)) {
                        error_log('Failed to create brand: ' . $term_result->get_error_message());
                        throw new \Exception('Failed to create brand term: ' . $term_result->get_error_message());
                    }
                    
                    $brand_term_id = $term_result['term_id'];
                    error_log('Created new brand with ID: ' . $brand_term_id);
                    
                    // Handle brand image if provided
                    if (!empty($brand_image)) {
                        error_log('Processing brand image: ' . $brand_image);
                        $image_id = $this->downloadAndAttachImage($brand_image, 0);
                        if ($image_id) {
                            update_term_meta($brand_term_id, 'thumbnail_id', $image_id);
                            error_log('Set brand image ID: ' . $image_id);
                        } else {
                            error_log('Failed to process brand image');
                        }
                    }
                } else {
                    $brand_term_id = $brand_term->term_id;
                    error_log('Found existing brand with ID: ' . $brand_term_id);
                    
                    // Update brand description if provided
                    if (!empty($brand_description)) {
                        error_log('Updating brand description');
                        $update_result = wp_update_term($brand_term_id, 'product_brand', [
                            'description' => $brand_description
                        ]);
                        if (is_wp_error($update_result)) {
                            error_log('Failed to update brand description: ' . $update_result->get_error_message());
                        }
                    }
                    
                    // Update brand image if provided
                    if (!empty($brand_image)) {
                        error_log('Updating brand image: ' . $brand_image);
                        $image_id = $this->downloadAndAttachImage($brand_image, 0);
                        if ($image_id) {
                            update_term_meta($brand_term_id, 'thumbnail_id', $image_id);
                            error_log('Updated brand image ID: ' . $image_id);
                        } else {
                            error_log('Failed to update brand image');
                        }
                    }
                }

                // Set the brand for the product
                error_log('Setting brand ' . $brand_term_id . ' for product ' . $product_id);
                $set_terms_result = wp_set_object_terms($product_id, [$brand_term_id], 'product_brand');
                if (is_wp_error($set_terms_result)) {
                    error_log('Failed to set brand for product: ' . $set_terms_result->get_error_message());
                    throw new \Exception('Failed to set brand for product: ' . $set_terms_result->get_error_message());
                } else {
                    error_log('Successfully set brand for product. Result: ' . print_r($set_terms_result, true));
                }

                // Check if brand was actually set
                $check_terms = wp_get_object_terms($product_id, 'product_brand');
                if (is_wp_error($check_terms)) {
                    error_log('Error checking product brands: ' . $check_terms->get_error_message());
                } else {
                    error_log('Product brands after setting: ' . print_r($check_terms, true));
                }
            }

            // Handle product tags
            if (!empty($product_data['tags']) && is_array($product_data['tags'])) {
                error_log('Processing tags for product: ' . $product_id);
                $tag_ids = [];
                
                foreach ($product_data['tags'] as $tag) {
                    if (is_array($tag) && !empty($tag['name'])) {
                        $tag_name = $tag['name'];
                    } else if (is_string($tag)) {
                        $tag_name = $tag;
                    } else {
                        continue;
                    }
                    
                    // Get or create the tag
                    $term = get_term_by('name', $tag_name, 'product_tag');
                    if (!$term) {
                        error_log('Tag not found, creating new tag: ' . $tag_name);
                        $term_result = wp_insert_term($tag_name, 'product_tag');
                        if (!is_wp_error($term_result)) {
                            $tag_ids[] = $term_result['term_id'];
                            error_log('Created new tag with ID: ' . $term_result['term_id']);
                        } else {
                            error_log('Failed to create tag: ' . $term_result->get_error_message());
                        }
                    } else {
                        $tag_ids[] = $term->term_id;
                        error_log('Found existing tag with ID: ' . $term->term_id);
                    }
                }
                
                if (!empty($tag_ids)) {
                    $set_tags_result = wp_set_object_terms($product_id, $tag_ids, 'product_tag');
                    if (is_wp_error($set_tags_result)) {
                        error_log('Failed to set tags for product: ' . $set_tags_result->get_error_message());
                    } else {
                        error_log('Successfully set tags for product. Result: ' . print_r($set_tags_result, true));
                    }
                }
            }

            // Handle attributes and variations
            if (!empty($product_data['attributes']) && is_array($product_data['attributes'])) {
                error_log('Processing attributes for product: ' . $product_id);
                
                $attributes = array();
                $has_variation_attributes = false;
                
                foreach ($product_data['attributes'] as $attribute) {
                    if (empty($attribute['name']) || empty($attribute['options']) || !is_array($attribute['options'])) {
                        continue;
                    }
                    
                    // Check if this attribute is used for variations
                    if (isset($attribute['variation']) && $attribute['variation']) {
                        $has_variation_attributes = true;
                    }
                    
                    $attribute_name = wc_clean($attribute['name']);
                    // Support custom attribute slug if provided
                    $attribute_slug = isset($attribute['slug']) ? wc_sanitize_taxonomy_name($attribute['slug']) : wc_sanitize_taxonomy_name($attribute_name);
                    $attribute_id = 0;
                    
                    // Check if this is a taxonomy-based attribute
                    $attribute_taxonomy_name = 'pa_' . $attribute_slug;
                    
                    error_log('Processing attribute: ' . $attribute_name . ' with options: ' . print_r($attribute['options'], true));
                    
                    // Check if the attribute taxonomy exists
                    $taxonomy_exists = taxonomy_exists($attribute_taxonomy_name);
                    
                    if (!$taxonomy_exists) {
                        error_log('Attribute taxonomy does not exist, creating: ' . $attribute_taxonomy_name);
                        
                        // Create the taxonomy
                        $attribute_id = wc_create_attribute(array(
                            'name' => $attribute_name,
                            'slug' => $attribute_slug,
                            'type' => 'select',
                            'order_by' => 'menu_order',
                            'has_archives' => false
                        ));
                        
                        if (is_wp_error($attribute_id)) {
                            error_log('Failed to create attribute: ' . $attribute_id->get_error_message());
                            continue;
                        }
                        
                        error_log('Created attribute with ID: ' . $attribute_id);
                        
                        // Register the taxonomy
                        register_taxonomy(
                            $attribute_taxonomy_name,
                            apply_filters('woocommerce_taxonomy_objects_' . $attribute_taxonomy_name, array('product')),
                            apply_filters('woocommerce_taxonomy_args_' . $attribute_taxonomy_name, array(
                                'labels' => array(
                                    'name' => $attribute_name,
                                ),
                                'hierarchical' => true,
                                'show_ui' => false,
                                'query_var' => true,
                                'rewrite' => false,
                            ))
                        );
                        
                        // Set the relationship between the attribute and its taxonomy
                        update_option('_wc_attribute_taxonomy_' . $attribute_id, $attribute_taxonomy_name);
                    } else {
                        // Get the attribute ID if it already exists
                        $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_slug);
                        error_log('Found existing attribute with ID: ' . $attribute_id);
                    }
                    
                    // Add attribute terms
                    $term_ids = array();
                    foreach ($attribute['options'] as $index => $option) {
                        // Handle both string and object formats for options
                        if (is_array($option)) {
                            $option_name = wc_clean($option['name']);
                            $option_slug = isset($option['slug']) ? $option['slug'] : sanitize_title($option_name);
                        } else {
                            $option_name = wc_clean($option);
                            // Check if custom slugs are provided separately
                            if (isset($attribute['option_slugs'][$index])) {
                                $option_slug = $attribute['option_slugs'][$index];
                            } else {
                                // Check if option is numeric only and add prefix if needed
                                if (is_numeric($option_name)) {
                                    $option_slug = 'size-' . $option_name; // You can change 'size-' to any appropriate prefix
                                } else {
                                    $option_slug = sanitize_title($option_name);
                                }
                            }
                        }
                        
                        // Always prioritize slug-based lookup for uniqueness
                        $term = get_term_by('slug', $option_slug, $attribute_taxonomy_name);
                        
                        if (!$term) {
                            // Term with this slug doesn't exist, create a new one
                            error_log('Creating new term: ' . $option_name . ' with slug: ' . $option_slug . ' for taxonomy: ' . $attribute_taxonomy_name);
                            $term_result = wp_insert_term(
                                $option_name, 
                                $attribute_taxonomy_name,
                                array('slug' => $option_slug)
                            );
                            
                            if (is_wp_error($term_result)) {
                                error_log('Failed to create term: ' . $term_result->get_error_message());
                                continue;
                            }
                            
                            $term_ids[] = $term_result['term_id'];
                            error_log('Successfully created term with ID: ' . $term_result['term_id']);
                        } else {
                            // Term with this slug exists, check if name needs updating
                            if ($term->name !== $option_name) {
                                error_log('Updating term name: ' . $term->name . ' -> ' . $option_name . ' for slug: ' . $option_slug);
                                wp_update_term($term->term_id, $attribute_taxonomy_name, array('name' => $option_name));
                            }
                            $term_ids[] = $term->term_id;
                            error_log('Using existing term with ID: ' . $term->term_id . ' (slug: ' . $term->slug . ')');
                        }
                    }
                    
                    // Create the attribute object for the product
                    $product_attribute = new \WC_Product_Attribute();
                    $product_attribute->set_id($attribute_id);
                    $product_attribute->set_name($attribute_taxonomy_name);
                    $product_attribute->set_options($term_ids);
                    $product_attribute->set_position(isset($attribute['position']) ? $attribute['position'] : 0);
                    $product_attribute->set_visible(isset($attribute['visible']) ? (bool)$attribute['visible'] : true);
                    $product_attribute->set_variation(isset($attribute['variation']) ? (bool)$attribute['variation'] : false);
                    
                    $attributes[] = $product_attribute;
                    
                    error_log('Added attribute: ' . $attribute_taxonomy_name . ' with terms: ' . print_r($term_ids, true));
                }
                
                // Set product attributes
                if (!empty($attributes)) {
                    $product->set_attributes($attributes);
                    error_log('Set attributes for product: ' . $product_id);
                    
                    // If product has variation attributes but type is not explicitly set to variable, make it variable
                    if ($has_variation_attributes && !isset($product_data['type'])) {
                        error_log('Product has variation attributes, setting type to variable');
                        wp_set_object_terms($product_id, 'variable', 'product_type');
                        
                        // Convert to variable product if needed
                        if (!($product instanceof \WC_Product_Variable)) {
                            $product->save(); // Save current product to get ID
                            $product = new \WC_Product_Variable($product_id);
                            error_log('Converted product to WC_Product_Variable');
                        }
                    }
                }
            }
            
            // Save product to update attributes
            $product->save();
            
            // Handle variations if this is a variable product
            if (!empty($product_data['variations']) && is_array($product_data['variations'])) {
                error_log('Product has ' . count($product_data['variations']) . ' variations to process');
                error_log('Current product type: ' . get_class($product));
                
                // If product has variations but is not a variable product, convert it
                if (!($product instanceof \WC_Product_Variable)) {
                    error_log('Converting product to variable type');
                    wp_set_object_terms($product_id, 'variable', 'product_type');
                    $product = new \WC_Product_Variable($product_id);
                    error_log('Product converted to: ' . get_class($product));
                }
                
                error_log('Processing variations for product: ' . $product_id);
                
                // Get and delete all existing variations
                $existing_variations = $product->get_children();
                foreach ($existing_variations as $variation_id) {
                    error_log('Deleting existing variation: ' . $variation_id);
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        $variation->delete(true);
                    }
                }
                
                // Create new variations
                foreach ($product_data['variations'] as $index => $variation_data) {
                    try {
                        error_log('Processing variation ' . ($index + 1) . ': ' . print_r($variation_data, true));
                        
                        // Create new variation
                        $variation = new \WC_Product_Variation();
                        $variation->set_parent_id($product_id);
                        
                        // Set variation data
                        if (isset($variation_data['sku'])) {
                            $variation->set_sku($variation_data['sku']);
                        }
                        if (isset($variation_data['regular_price'])) {
                            $variation->set_regular_price($variation_data['regular_price']);
                        }
                        if (isset($variation_data['sale_price'])) {
                            $variation->set_sale_price($variation_data['sale_price']);
                        }
                        if (isset($variation_data['stock_status'])) {
                            $variation->set_stock_status($variation_data['stock_status']);
                        }
                        if (isset($variation_data['stock_quantity'])) {
                            $variation->set_manage_stock(true);
                            $variation->set_stock_quantity($variation_data['stock_quantity']);
                        }
                        
                        // Set variation attributes
                        if (!empty($variation_data['attributes']) && is_array($variation_data['attributes'])) {
                            $variation_attributes = array();
                            foreach ($variation_data['attributes'] as $attribute_name => $attribute_value) {
                                $taxonomy = 'pa_' . wc_sanitize_taxonomy_name($attribute_name);
                                
                                // Handle both string and object formats for variation attributes
                                if (is_array($attribute_value)) {
                                    // New format: "Color": {"name": "Blue", "slug": "ff0000"}
                                    $term_name = $attribute_value['name'];
                                    $term_slug = $attribute_value['slug'];
                                    
                                    // Find term by slug (preferred method for uniqueness)
                                    $term = get_term_by('slug', $term_slug, $taxonomy);
                                    if ($term) {
                                        $variation_attributes[$taxonomy] = $term->slug;
                                        error_log('Found term by slug for variation: ' . $attribute_name . ' = ' . $term_name . ' (slug: ' . $term->slug . ')');
                                    } else {
                                        error_log('Term not found by slug for variation: ' . $attribute_name . ' = ' . $term_name . ' (slug: ' . $term_slug . ') in taxonomy: ' . $taxonomy);
                                    }
                                } else {
                                    // Legacy format: "Color": "Blue"
                                    $term_name = $attribute_value;
                                    
                                    // Try to find by name first
                                    $term = get_term_by('name', $term_name, $taxonomy);
                                    if ($term) {
                                        $variation_attributes[$taxonomy] = $term->slug;
                                        error_log('Found term by name for variation: ' . $attribute_name . ' = ' . $term_name . ' (slug: ' . $term->slug . ')');
                                    } else {
                                        error_log('Term not found for variation: ' . $attribute_name . ' = ' . $term_name . ' in taxonomy: ' . $taxonomy);
                                        // Try to find by slug as fallback
                                        $term = get_term_by('slug', sanitize_title($term_name), $taxonomy);
                                        if ($term) {
                                            $variation_attributes[$taxonomy] = $term->slug;
                                            error_log('Found term by slug for variation: ' . $attribute_name . ' = ' . $term_name . ' (slug: ' . $term->slug . ')');
                                        } else {
                                            error_log('Could not find term for variation attribute: ' . $attribute_name . ' = ' . $term_name);
                                        }
                                    }
                                }
                            }
                            
                            // Set the attributes
                            if (!empty($variation_attributes)) {
                                $variation->set_attributes($variation_attributes);
                                error_log('Set variation attributes: ' . print_r($variation_attributes, true));
                            } else {
                                error_log('No variation attributes to set');
                            }
                        }
                        
                        // Handle variation image
                        if (!empty($variation_data['image'])) {
                            if ($this->skip_image_download) {
                                $variation_image_url = esc_url_raw($variation_data['image']);
                                update_post_meta($variation->get_id(), '_external_image_url', $variation_image_url);
                            } else {
                                $variation->save(); // Save to get ID
                                $variation_image_id = $this->downloadAndAttachImage($variation_data['image'], $variation->get_id());
                                if ($variation_image_id) {
                                    $variation->set_image_id($variation_image_id);
                                }
                            }
                        }
                        
                        // Save variation
                        $variation->save();
                        $variation_id = $variation->get_id();
                        error_log('Saved variation ID: ' . $variation_id);
                        
                        // Double-check that the variation was created and is linked to the parent
                        if ($variation_id) {
                            // Verify parent ID is set correctly
                            $parent_id = $variation->get_parent_id();
                            if ($parent_id != $product_id) {
                                error_log('Variation parent ID mismatch. Expected: ' . $product_id . ', Got: ' . $parent_id);
                                // Fix parent ID
                                $variation->set_parent_id($product_id);
                                $variation->save();
                                error_log('Fixed variation parent ID to: ' . $product_id);
                            }
                            
                            // Make sure the variation is in the product's children
                            $children = $product->get_children();
                            if (!in_array($variation_id, $children)) {
                                error_log('Variation not found in product children. Adding it manually.');
                                // Force refresh the product cache
                                clean_post_cache($product_id);
                                $product = wc_get_product($product_id);
                            }
                        } else {
                            error_log('Failed to save variation. Trying alternative method.');
                            
                            // Try alternative method to create variation
                            $variation_post = array(
                                'post_title'  => $product->get_title(),
                                'post_name'   => 'product-' . $product_id . '-variation',
                                'post_status' => 'publish',
                                'post_parent' => $product_id,
                                'post_type'   => 'product_variation',
                                'guid'        => $product->get_permalink()
                            );
                            
                            // Insert the variation post
                            $variation_id = wp_insert_post($variation_post);
                            
                            if (!is_wp_error($variation_id)) {
                                error_log('Created variation post with ID: ' . $variation_id);
                                
                                // Set variation data as post meta
                                if (isset($variation_data['sku'])) {
                                    update_post_meta($variation_id, '_sku', $variation_data['sku']);
                                }
                                if (isset($variation_data['regular_price'])) {
                                    update_post_meta($variation_id, '_regular_price', $variation_data['regular_price']);
                                    update_post_meta($variation_id, '_price', $variation_data['regular_price']);
                                }
                                if (isset($variation_data['sale_price'])) {
                                    update_post_meta($variation_id, '_sale_price', $variation_data['sale_price']);
                                    if ($variation_data['sale_price'] < $variation_data['regular_price']) {
                                        update_post_meta($variation_id, '_price', $variation_data['sale_price']);
                                    }
                                }
                                if (isset($variation_data['stock_status'])) {
                                    update_post_meta($variation_id, '_stock_status', $variation_data['stock_status']);
                                }
                                if (isset($variation_data['stock_quantity'])) {
                                    update_post_meta($variation_id, '_stock', $variation_data['stock_quantity']);
                                    update_post_meta($variation_id, '_manage_stock', 'yes');
                                }
                                
                                // Set variation attributes
                                if (!empty($variation_data['attributes']) && is_array($variation_data['attributes'])) {
                                    foreach ($variation_data['attributes'] as $attribute_name => $attribute_value) {
                                        $taxonomy = 'pa_' . wc_sanitize_taxonomy_name($attribute_name);
                                        
                                        // Handle both string and object formats for variation attributes
                                        if (is_array($attribute_value)) {
                                            // New format: "Color": {"name": "Blue", "slug": "ff0000"}
                                            $term_name = $attribute_value['name'];
                                            $term_slug = $attribute_value['slug'];
                                            
                                            // Find term by slug (preferred method for uniqueness)
                                            $term = get_term_by('slug', $term_slug, $taxonomy);
                                            if ($term) {
                                                update_post_meta($variation_id, 'attribute_' . $taxonomy, $term->slug);
                                                error_log('Set variation attribute via meta (by slug): ' . $taxonomy . ' = ' . $term->slug);
                                            } else {
                                                error_log('Term not found by slug for variation attribute (alternative method): ' . $attribute_name . ' = ' . $term_name . ' (slug: ' . $term_slug . ')');
                                            }
                                        } else {
                                            // Legacy format: "Color": "Blue"
                                            $term_name = $attribute_value;
                                            $term = get_term_by('name', $term_name, $taxonomy);
                                            if ($term) {
                                                update_post_meta($variation_id, 'attribute_' . $taxonomy, $term->slug);
                                                error_log('Set variation attribute via meta (by name): ' . $taxonomy . ' = ' . $term->slug);
                                            } else {
                                                error_log('Term not found for variation attribute (alternative method): ' . $attribute_name . ' = ' . $term_name);
                                            }
                                        }
                                    }
                                }
                                
                                // Set variation image
                                if (!empty($variation_data['image'])) {
                                    if ($this->skip_image_download) {
                                        update_post_meta($variation_id, '_external_image_url', esc_url_raw($variation_data['image']));
                                    } else {
                                        $image_id = $this->downloadAndAttachImage($variation_data['image'], $variation_id);
                                        if ($image_id) {
                                            update_post_meta($variation_id, '_thumbnail_id', $image_id);
                                        }
                                    }
                                }
                            } else {
                                error_log('Failed to create variation post: ' . $variation_id->get_error_message());
                            }
                        }
                        
                    } catch (\Exception $e) {
                        error_log('Error processing variation: ' . $e->getMessage());
                    }
                }
                
                // Make sure the parent product has the correct type
                wp_set_object_terms($product_id, 'variable', 'product_type');
                
                // Update product variation lookup table
                \WC_Product_Variable::sync($product_id);
                
                // Force refresh the product cache to ensure all variations are recognized
                clean_post_cache($product_id);
                $product = wc_get_product($product_id);
                
                // Verify variations were created
                $variations = $product->get_available_variations();
                $variation_count = count($variations);
                error_log('Product has ' . $variation_count . ' available variations after sync');
                
                if ($variation_count === 0) {
                    error_log('No variations found after sync. Attempting manual sync.');
                    
                    // Get all variation IDs for this product
                    global $wpdb;
                    $variation_ids = $wpdb->get_col($wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = 'product_variation'",
                        $product_id
                    ));
                    
                    error_log('Found ' . count($variation_ids) . ' variation posts in database');
                    
                    if (!empty($variation_ids)) {
                        // Manually update the product's children
                        update_post_meta($product_id, '_children', $variation_ids);
                        
                        // Force WooCommerce to recognize the variations
                        delete_transient('wc_product_children_' . $product_id);
                        
                        // Refresh the product again
                        clean_post_cache($product_id);
                        $product = new \WC_Product_Variable($product_id);
                        $product->save();
                        
                        // Check again
                        $variations = $product->get_available_variations();
                        error_log('Product has ' . count($variations) . ' available variations after manual sync');
                    }
                }
                
                error_log('Synced variations for product: ' . $product_id);
            } else {
                // If no variations, ensure the product is set as simple type
                if (!($product instanceof \WC_Product_Simple)) {
                    error_log('Product has no variations, setting as simple product');
                    wp_set_object_terms($product_id, 'simple', 'product_type');
                    $product = new \WC_Product_Simple($product_id);
                    $product->save();
                }
            }

            // Handle main image
            if (!empty($product_data['image'])) {
                if ($this->skip_image_download) {
                    // Save as external URL
                    $main_image_url = esc_url_raw($product_data['image']);
                    update_post_meta($product_id, '_external_image_url', $main_image_url);
                    error_log('Saved external main image URL: ' . $main_image_url);
                } else {
                    // Download and set as product image
                    $main_image_id = $this->downloadAndAttachImage($product_data['image'], $product_id);
                    if ($main_image_id) {
                        $product->set_image_id($main_image_id);
                        $product->save();
                        error_log('Set main image ID: ' . $main_image_id);
                    }
                }
            }
            
            // Handle SEO fields
            if (!empty($product_data['seo']) && is_array($product_data['seo'])) {
                error_log('Processing SEO data for product: ' . $product_id);
                
                // Meta title
                if (isset($product_data['seo']['meta_title'])) {
                    update_post_meta($product_id, '_shopper_meta_title', sanitize_text_field($product_data['seo']['meta_title']));
                    error_log('Set meta title: ' . $product_data['seo']['meta_title']);
                }
                
                // Meta description
                if (isset($product_data['seo']['meta_description'])) {
                    update_post_meta($product_id, '_shopper_meta_description', sanitize_text_field($product_data['seo']['meta_description']));
                    error_log('Set meta description: ' . $product_data['seo']['meta_description']);
                }
                
                // Focus keywords
                if (isset($product_data['seo']['focus_keywords'])) {
                    update_post_meta($product_id, '_shopper_focus_keywords', sanitize_text_field($product_data['seo']['focus_keywords']));
                    error_log('Set focus keywords: ' . $product_data['seo']['focus_keywords']);
                }
                
                // Canonical URL
                if (isset($product_data['seo']['canonical_url'])) {
                    update_post_meta($product_id, '_shopper_canonical_url', esc_url_raw($product_data['seo']['canonical_url']));
                    error_log('Set canonical URL: ' . $product_data['seo']['canonical_url']);
                }
                
                // Redirect to
                if (isset($product_data['seo']['redirect_to'])) {
                    update_post_meta($product_id, '_shopper_redirect_to', esc_url_raw($product_data['seo']['redirect_to']));
                    error_log('Set redirect to: ' . $product_data['seo']['redirect_to']);
                }
                
                // Redirect type
                if (isset($product_data['seo']['redirect_type'])) {
                    update_post_meta($product_id, '_shopper_redirect_type', sanitize_text_field($product_data['seo']['redirect_type']));
                    error_log('Set redirect type: ' . $product_data['seo']['redirect_type']);
                }
                
                // Image alt
                if (isset($product_data['seo']['image_alt'])) {
                    update_post_meta($product_id, '_shopper_image_alt', sanitize_text_field($product_data['seo']['image_alt']));
                    error_log('Set image alt: ' . $product_data['seo']['image_alt']);
                }
                
                // Also handle image_alt_tag as an alternative field name
                if (isset($product_data['seo']['image_alt_tag']) && !isset($product_data['seo']['image_alt'])) {
                    update_post_meta($product_id, '_shopper_image_alt', sanitize_text_field($product_data['seo']['image_alt_tag']));
                    error_log('Set image alt from image_alt_tag: ' . $product_data['seo']['image_alt_tag']);
                }
                // Source URL
                if (isset($product_data['seo']['source_url'])) {
                    update_post_meta($product_id, '_shopper_source_url', $product_data['seo']['source_url']);
                    error_log('Set source URL: ' . $product_data['seo']['source_url']);
                }
            }
            
            // Handle gallery images
            if (!empty($product_data['gallery_images']) && is_array($product_data['gallery_images'])) {
                if ($this->skip_image_download) {
                    // Save as external URLs
                    $gallery_urls = array_values(array_filter(array_map('esc_url_raw', $product_data['gallery_images'])));
                    update_post_meta($product_id, '_external_gallery_urls', $gallery_urls);
                    error_log('Saved external gallery URLs: ' . print_r($gallery_urls, true));
                } else {
                    // Download and set as gallery images
                    $gallery_ids = [];
                    foreach ($product_data['gallery_images'] as $gallery_url) {
                        $attachment_id = $this->downloadAndAttachImage($gallery_url, $product_id);
                        if ($attachment_id) {
                            $gallery_ids[] = $attachment_id;
                            error_log('Added gallery image ID: ' . $attachment_id);
                        }
                    }
                    if (!empty($gallery_ids)) {
                        $product->set_gallery_image_ids($gallery_ids);
                        error_log('Set gallery image IDs: ' . print_r($gallery_ids, true));
                    }
                }
            }

            // Save product again to update all changes
            $product->save();

            return [
                'success' => true,
                'product_id' => $product_id,
                'message' => 'Product processed successfully',
                'debug' => [
                    'skip_image_download' => $this->skip_image_download,
                    'categories' => $category_ids ?? [],
                    'images' => [
                        'main_image' => $this->skip_image_download ? 
                            get_post_meta($product_id, '_external_image_url', true) : 
                            $product->get_image_id(),
                        'gallery_images' => $this->skip_image_download ? 
                            get_post_meta($product_id, '_external_gallery_urls', true) : 
                            $product->get_gallery_image_ids()
                    ]
                ]
            ];

        } catch (\Exception $e) {
            error_log('Error processing product: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate all possible variations of a URL for flexible matching
     * @param string $url
     * @return array
     */
    private function getUrlVariations($url) {
        if (empty($url)) {
            return array();
        }
        
        $variations = array();
        
        // Add the original string exactly as it is
        $variations[] = $url;
        
        // Add the opposite trailing slash version
        if (substr($url, -1) === '/') {
            // If string ends with slash, add version without slash
            $variations[] = rtrim($url, '/');
        } else {
            // If string doesn't end with slash, add version with slash
            $variations[] = $url . '/';
        }
        
        // Add URL decoded version (in case Hebrew characters are encoded)
        $variations[] = urldecode($url);
        
        // Add URL encoded version (in case Hebrew characters need encoding)
        $variations[] = urlencode($url);
        
        // Add raw URL encoded version
        $variations[] = rawurlencode($url);
        
        // If string has Hebrew/Arabic characters, add different encoding variations
        if (preg_match('/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}]/u', $url)) {
            // Add percent-encoded version
            $variations[] = preg_replace_callback('/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}]/u', function($matches) {
                return urlencode($matches[0]);
            }, $url);
            
            // Add raw percent-encoded version
            $variations[] = preg_replace_callback('/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}]/u', function($matches) {
                return rawurlencode($matches[0]);
            }, $url);
        }
        
        // Remove duplicates and empty values
        $variations = array_filter(array_unique($variations));
        
        return $variations;
    }

    /**
     * Normalize URL for comparison - handles various formats and encodings
     * @param string $url
     * @return string
     */
    private function normalizeUrlForComparison($url) {
        if (empty($url)) {
            return $url;
        }
        
        // Remove any leading/trailing whitespace
        $url = trim($url);
        
        // Return the URL as-is, no normalization
        return $url;
    }

    /**
     * Encode URL for import to preserve Hebrew and Arabic characters
     * @param string $url
     * @return string
     */
    private function encodeUrlForImport($url) {
        if (empty($url)) {
            return $url;
        }
        
        // Normalize the URL first
        $normalized_url = $this->normalizeUrlForComparison($url);
        
        // For Hebrew/Arabic URLs, preserve the original encoding
        if (preg_match('/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}]/u', $normalized_url)) {
            return $normalized_url;
        }
        
        // For other URLs, use standard encoding
        return esc_url_raw($normalized_url);
    }

    private function downloadAndAttachImage($image_url, $product_id)
    {
        try {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            // Download file to temp dir
            $temp_file = download_url($image_url);
            if (is_wp_error($temp_file)) {
                throw new \Exception('Error downloading file: ' . $temp_file->get_error_message());
            }

            // Prepare file data
            $file = array(
                'name' => basename($image_url),
                'type' => mime_content_type($temp_file),
                'tmp_name' => $temp_file,
                'error' => 0,
                'size' => filesize($temp_file)
            );

            // Move the temporary file into the uploads directory
            $attachment_id = media_handle_sideload($file, $product_id);

            if (is_wp_error($attachment_id)) {
                @unlink($temp_file);
                throw new \Exception('Error attaching file: ' . $attachment_id->get_error_message());
            }

            @unlink($temp_file); // Clean up
            return $attachment_id;

        } catch (\Exception $e) {
            error_log('Error downloading image: ' . $e->getMessage());
            if (isset($temp_file) && is_string($temp_file) && file_exists($temp_file)) {
                @unlink($temp_file);
            }
            return false;
        }
    }

    /**
     * Delete a product based on ID, SKU, or source URL
     * 
     * @param array $product_data Product data containing identification information and 'delete' flag
     * @return array Result of the deletion operation
     */
    private function deleteProduct($product_data)
    {
        $product_id = null;
        $identifier = '';
        
        error_log('Attempting to delete product with data: ' . print_r($product_data, true));
        
        // Try to find product by ID
        if (!empty($product_data['id'])) {
            $product_id = absint($product_data['id']);
            $identifier = 'ID: ' . $product_id;
            error_log('Product identified by ID: ' . $product_id);
            
            // Verify the post exists
            if (!get_post($product_id)) {
                error_log('Post with ID ' . $product_id . ' does not exist');
                return [
                    'success' => false,
                    'message' => 'Post with ID ' . $product_id . ' does not exist',
                    'product_id' => $product_id
                ];
            }
        } 
        // Try to find product by SKU
        elseif (!empty($product_data['sku'])) {
            $product_id = wc_get_product_id_by_sku($product_data['sku']);
            $identifier = 'SKU: ' . $product_data['sku'];
            error_log('Product identified by SKU: ' . $product_data['sku'] . ', ID: ' . $product_id);
        } 
        // Try to find product by source URL
        elseif (!empty($product_data['seo']) && !empty($product_data['seo']['source_url'])) {
            $source_url = $this->encodeUrlForImport($product_data['seo']['source_url']);
            $normalized_source_url = $this->normalizeUrlForComparison($source_url);
            
            // Get all possible variations of the URL for matching
            $url_variations = $this->getUrlVariations($normalized_source_url);
            
            // Query for products with this source URL using multiple variations
            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => 1,
                'meta_query'     => array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_shopper_source_url',
                        'value'   => $url_variations,
                        'compare' => 'IN'
                    )
                )
            );
            
            $query = new \WP_Query($args);
            
            if ($query->have_posts()) {
                $query->the_post();
                $product_id = get_the_ID();
                $identifier = 'Source URL: ' . $source_url;
                error_log('Product identified by Source URL: ' . $source_url . ', ID: ' . $product_id);
            }
            
            wp_reset_postdata();
        }
        
        // If no product found, return error
        if (!$product_id) {
            error_log('Product not found for deletion. Data: ' . print_r($product_data, true));
            return [
                'success' => false,
                'message' => 'Product not found for deletion',
                'data'    => $product_data
            ];
        }
        
        // Check if post exists and is a product
        $post_type = get_post_type($product_id);
        if (!$post_type) {
            error_log('Post ID ' . $product_id . ' does not exist');
            return [
                'success' => false,
                'message' => 'Post ID ' . $product_id . ' does not exist',
                'product_id' => $product_id
            ];
        }
        
        if ($post_type !== 'product') {
            error_log('Post ID ' . $product_id . ' exists but is not a product (type: ' . $post_type . ')');
            return [
                'success' => false,
                'message' => 'Post ID exists but is not a product',
                'post_type' => $post_type,
                'product_id' => $product_id
            ];
        }
        
        // Delete the product permanently
        error_log('Attempting to delete product ID: ' . $product_id);
        
        // Force delete the product (bypass trash)
        $result = wp_delete_post($product_id, true);
        
        if ($result) {
            error_log('Product deleted successfully. ' . $identifier);
            return [
                'success' => true,
                'message' => 'Product deleted successfully',
                'identifier' => $identifier,
                'product_id' => $product_id
            ];
        } else {
            error_log('Failed to delete product. ' . $identifier);
            
            // Try an alternative method if the first one failed
            $product = wc_get_product($product_id);
            if ($product) {
                $product->delete(true);
                error_log('Attempted alternative deletion method for product ID: ' . $product_id);
                
                // Check if product still exists
                if (!wc_get_product($product_id)) {
                    error_log('Product deleted successfully using alternative method. ' . $identifier);
                    return [
                        'success' => true,
                        'message' => 'Product deleted successfully using alternative method',
                        'identifier' => $identifier,
                        'product_id' => $product_id
                    ];
                }
            }
            
            return [
                'success' => false,
                'message' => 'Failed to delete product',
                'identifier' => $identifier,
                'product_id' => $product_id
            ];
        }
    }
} 