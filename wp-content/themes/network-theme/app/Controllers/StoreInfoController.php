<?php

namespace App\Controllers;

class StoreInfoController
{
    public function index(\WP_REST_Request $request)
    {
        // Get parameters and convert to lowercase for consistent comparison
        $active_filter = strtolower($request->get_param('active')); // Can be 'true', 'false', or null/other for all
        $exclude_main = $request->get_param('exclude_main') === 'true';

        // Base query args - remove archived from base query to handle it in filter
        $args = [
            'public'   => 1,
            'mature'   => 0,
            'spam'     => 0,
            'deleted'  => 0
        ];

        // Handle archived based on active filter
        if ($active_filter === 'true') {
            $args['archived'] = 0; // Only published sites
        } elseif ($active_filter === 'false') {
            $args['archived'] = 1; // Only archived sites
        }
        // If no active filter, don't set archived to get all

        // Exclude main site if requested
        if ($exclude_main) {
            $args['site__not_in'] = [1];
        }

        // Get sites
        $sites = get_sites($args);
        $stores = [];
        $active_count = 0;
        $inactive_count = 0;

        foreach ($sites as $site) {
            // Switch to the site to get its data
            switch_to_blog($site->blog_id);

            // Get store settings
            $store_settings = get_option('store_settings');
            
            // Count active and inactive sites
            if (!$site->archived) {
                $active_count++;
            } else {
                $inactive_count++;
            }
            $stores[] = [
                'id' => $site->blog_id,
                'site_title' => get_bloginfo('name'),
                'url' => get_site_url($site->blog_id),
                'store_info' => $store_settings ?? null,
                'active' => !$site->archived // true if not archived, false if archived
            ];

            // Restore original site
            restore_current_blog();
        }

        return rest_ensure_response([
            'counts' => [
                'active' => $active_count,
                'inactive' => $inactive_count,
                'total' => count($sites)
            ],
            'stores' => $stores
        ]);
    }
} 