<?php

namespace App;
/**
 * Product sorting functionality
 */
class ProductSortFilter
{
    /**
     * Initialize the product sorting functionality
     */
    public function __construct()
    {        
        // Remove default sorting dropdown
       // remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
        
        // Add our custom sorting
        add_filter('posts_clauses', [$this, 'customOrderClauses'], 999, 2);
    }

    /**
     * Custom clauses for ordering
     */
    public function customOrderClauses($clauses, $query) 
    {
        global $wpdb;

        if (!is_admin() && (is_shop() || is_product_category())) {
            if(isset($_GET['orderby']) && $_GET['orderby'] !== ''){
                // Only apply rank sorting if no specific sorting is selected
                return $clauses;
            }

            // Join with postmeta table
            $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS rank_meta ON ({$wpdb->posts}.ID = rank_meta.post_id AND rank_meta.meta_key = '_shopper_display_rank')";
            
            // Order by rank in descending order (higher numbers first), then by post ID for consistent ordering
            $clauses['orderby'] = "CAST(IFNULL(rank_meta.meta_value, '1') AS UNSIGNED) DESC, {$wpdb->posts}.ID ASC";
            
            // Ensure we're only getting products
            $clauses['where'] .= " AND {$wpdb->posts}.post_type = 'product'";

            // Remove the INSERT query - this should be done once during setup, not on every page load
            // The INSERT query was causing performance issues and potential race conditions
        }
        
        return $clauses;
    }
}

// Initialize the class
add_action('init', function() {
    new ProductSortFilter();
});  