<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GrowthDashboardServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register the provider with the network settings
        add_action('network_admin_menu', [$this, 'addGrowthDashboardTab']);
        
        // Register AJAX handlers
        add_action('wp_ajax_generate_company_details', [$this, 'handleGenerateCompanyDetails']);
        add_action('wp_ajax_generate_store_locations', [$this, 'handleGenerateStoreLocations']);
        add_action('wp_ajax_fetch_total_impressions', [$this, 'handleFetchTotalImpressions']);
        add_action('wp_ajax_generate_product_discovery', [$this, 'handleGenerateProductDiscovery']);
        add_action('wp_ajax_fetch_events', [$this, 'handleFetchEvents']);
    }

    public function boot()
    {
        // Add any boot-specific code here
    }

    public function addGrowthDashboardTab()
    {
        // This method will be called by NetworkFieldsServiceProvider to add the tab
        // The actual tab addition is handled in the NetworkFieldsServiceProvider
    }

    /**
     * Handle AJAX request to generate company details
     */
    public function handleGenerateCompanyDetails()
    {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'growth_dashboard_nonce')) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_network_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        $network_settings = get_site_option('network_store_settings', []);
        
        // Get clean domain name and site name
        $site_url = get_site_url();
        $parsed_url = parse_url($site_url);
        $domain_slug = $parsed_url['host'] ?? '';
        
        // Remove www. prefix if present
        $domain_slug = preg_replace('/^www\./', '', $domain_slug);
        
        // Remove all domain suffixes (.com, .org, .test, etc.)
        $domain_slug = preg_replace('/\.[^.]+$/', '', $domain_slug);
        
        // Get the site name for display
        $display_name = get_bloginfo('name');
        
        // Get logo URL
        $logo_url = '';
        if (!empty($network_settings['network_store_logo'])) {
            $logo_url = wp_get_attachment_url($network_settings['network_store_logo']);
        }
        
        // Get colors
        $primary_color = $network_settings['network_primary_color'] ?? '';
        $secondary_color = $network_settings['network_secondary_color'] ?? '';
        
        $company_data = [
            'company' => [
                'id' => $domain_slug,
                'name' => $display_name,
                'logo' => $logo_url,
                'dashboard_title' => 'Example Network ROI Impact Dashboard'
            ],
            'branding' => [
                'primary_color' => $primary_color,
                'secondary_color' => $secondary_color,
                'accent_color' => $primary_color
            ],
            'assets' => [
                'sample_search_results' => [],
                'login_branding' => [
                    'logo' => 'https://static.wixstatic.com/media/775ef7_0455eced07074b16bcac73a75288a99b~mv2.png/v1/fill/w_82,h_82,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/Add%20(8).png',
                    'logo_alt' => 'Example Network',
                    'tagline' => 'Quietly disrupting retail discovery.',
                    'background_gradient' => 'from-blue-50 to-teal-50',
                    'company_context' => $domain_slug
                ],
                'icons' => [
                    'favicon' => "/api/assets/{$domain_slug}/favicon.svg",
                    'apple_touch_icon' => "/api/assets/{$domain_slug}/apple-touch-icon.png"
                ]
            ]
        ];
        
        wp_send_json_success($company_data);
    }

    /**
     * Handle AJAX request to generate store locations
     */
    public function handleGenerateStoreLocations()
    {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'growth_dashboard_nonce')) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_network_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        $store_locations = [];
        $sites = get_sites();
        $store_counter = 1;

        foreach ($sites as $site) {
            // Switch to the subdomain to get its settings
            switch_to_blog($site->blog_id);
            
            // Get store settings for this subdomain
            $store_settings = get_option('store_settings', []);
            $store_info = $store_settings['store_info'] ?? [];
            
            // Only include stores that have address information
            if (!empty($store_info['address']) && !empty($store_info['postcode'])) {
                $store_location = [
                    'id' => 'store-' . $store_counter,
                    'name' => get_bloginfo('name'),
                    'address' => $store_info['address'] ?? '',
                    'postal_code' => $store_info['postcode'] ?? '',
                    'region' => $store_info['city'] ?? '',
                    'coordinates' => [
                        'lat' => !empty($store_info['latitude']) ? (float)$store_info['latitude'] : null,
                        'lng' => !empty($store_info['longitude']) ? (float)$store_info['longitude'] : null
                    ],
                    'traffic' => null,
                    'performance_category' => '',
                    'metrics' => [
                        'impressions' => null,
                        'clicks' => null,
                        'traffic_value_score' => null,
                        'conversion_rate' => null,
                        'market_average_conversion' => null,
                        'directions' => null,
                        'calls' => null,
                        'website_clicks' => null,
                        'buy_online_clicked' => null,
                        'buy_in_store_clicked' => null,
                        'previous_directions' => null,
                        'previous_calls' => null,
                        'previous_website_clicks' => null
                    ],
                    'top_channels' => [],
                    'top_queries' => [],
                    'historical_data' => [],
                    'search_origins' => []
                ];
                
                $store_locations[] = $store_location;
                $store_counter++;
            }
            
            // Switch back to the original blog
            restore_current_blog();
        }

        $locations_data = [
            'store_locations' => $store_locations
        ];
        
        wp_send_json_success($locations_data);
    }

    /**
     * Handle AJAX request to fetch total impressions
     */
    public function handleFetchTotalImpressions()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'growth_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Check permissions
        if (!current_user_can('manage_network_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        try {
            $target_domain = preg_replace('/^www\./', '', parse_url(get_site_url())['host'] ?? '');
            
            // Get impressions from all sources
            $google_impressions = $this->fetchImpressionsFromGoogle($target_domain);
            $google_shopping_impressions = $this->fetchImpressionsFromGoogleShopping($target_domain);
            $bing_impressions = $this->fetchImpressionsFromBing($target_domain);
            
            // Get debug info with full responses
            $debug_info = [
                'google_search_console' => $google_impressions,
                'google_shopping' => $this->fetchGoogleShoppingDebugInfo($target_domain),
                'bing_search' => $this->fetchBingDebugInfo($target_domain)
            ];
            
            // extract impressions
            $google_impressions_value = 0;
            if (isset($google_impressions['rows']) && is_array($google_impressions['rows'])) {
                foreach ($google_impressions['rows'] as $row) {
                    $google_impressions_value += $row['impressions'] ?? 0;
                }
            }
            $google_shopping_value = $google_shopping_impressions['impressions'] ?? 0;
            $bing_impressions_value = $bing_impressions['impressions'] ?? 0;
            
            $impressions_data = [
                "traffic_value" => [
                    "annual_value" => "N/A",
                    "total_impressions" => $google_impressions_value + $google_shopping_value + $bing_impressions_value,
                    "market_share_gained" => "NUM",
                    "growth_trajectory" => "NUM"
                ],
                "traffic_sources" => [
                    ["name" => "Google Search", "value" => $google_impressions_value],
                    ["name" => "Google Maps", "value" => 0],
                    ["name" => "Google Shopping", "value" => $google_shopping_value],
                    ["name" => "Bing Search", "value" => $bing_impressions_value],
                    ["name" => "Bing Maps", "value" => 0],
                    ["name" => "ChatGPT", "value" => 0],
                    ["name" => "Perplexity", "value" => 0]
                ],
                "debug_info" => $debug_info
            ];
            
            wp_send_json_success($impressions_data);
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to fetch impressions: ' . $e->getMessage());
        }
    }

    /**
     * Fetch total impressions from Google Search Console
     */
    private function fetchImpressionsFromGoogle($domain)
    {
        try {
            // Get valid tokens from merchantor
            $tokens = $this->getGoogleTokens();
            
            // Debug: Check what scopes are actually in the token
            $token_info_url = 'https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' . $tokens['access_token'];
            $token_info_response = wp_remote_get($token_info_url);
            $token_info = json_decode(wp_remote_retrieve_body($token_info_response), true);
                        
            // Get the GSC property name from network settings
            $network_settings = get_site_option('network_store_settings', []);
            $gsc_property_name = $network_settings['gsc_property_name'] ?? '';
            $is_subdomain = $network_settings['is_subdomain'] ?? '';
            
            if (empty($gsc_property_name)) {
                throw new \Exception('GSC Property Name not configured. Please set it in the Growth Dashboard tab.');
            }
            
            // Use the exact property name from GSC settings
            $encoded_site_url = urlencode($gsc_property_name);
            $api_url = 'https://www.googleapis.com/webmasters/v3/sites/' . ($is_subdomain ? '' : 'sc-domain:') . $encoded_site_url . '/searchAnalytics/query';            
            
            $request_body = [
                'startDate' => '2010-01-01', // Very old date to get "all time"
                'endDate' => date('Y-m-d'),  // Today
                'dimensions' => [], // No dimensions needed for total count
                'rowLimit' => 1,   // We only need the total, not individual rows
                'startRow' => 0
            ];
            
            $response = wp_remote_post($api_url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $tokens['access_token'],
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($request_body),
                'timeout' => 30
            ]);
            
            if (is_wp_error($response)) {
                return ['error' => 'API request failed: ' . $response->get_error_message()];
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['error'])) {
                return [
                    'error' => 'Google API error: ' . ($data['error']['message'] ?? 'Unknown error')
                ];
            }
            
            return $data;
            
        } catch (\Exception $e) {
            return ['error' => 'Failed to fetch from Google Search Console: ' . $e->getMessage()];
        }
    }

    /**
     * Get valid Google tokens from merchantor
     */
    private function getGoogleTokens()
    {
        try {
            // Check if merchantor plugin is available - use global namespace
            if (!class_exists('\Merchantor_Auth')) {
                throw new Exception('Merchantor plugin not available');
            }
            
            // Get tokens from merchantor - use global namespace
            $merchantor_auth = \Merchantor_Auth::get_instance();
            return $merchantor_auth->ensure_valid_tokens();
            
        } catch (Exception $e) {
            throw new Exception('Failed to get Google tokens: ' . $e->getMessage());
        }
    }

    /**
     * Fetch Google Shopping impressions using Merchantor data and Content API
     */
    private function fetchImpressionsFromGoogleShopping($domain)
    {
        try {
            // Get valid tokens from Merchantor
            $tokens = $this->getGoogleTokens();
            
            // Check if this is a subdomain-specific request (example-network.shop case)
            $network_settings = get_site_option('network_store_settings', []);
            $is_subdomain = $network_settings['is_subdomain'] ?? '';
            $gsc_property_name = $network_settings['gsc_property_name'] ?? '';
            
            if ($is_subdomain && !empty($gsc_property_name)) {
                // For example-network.shop subdomains, get specific merchant for the exact subdomain
                $merchant_ids = $this->getMerchantIdsBySubdomain($gsc_property_name);
            } else {
                // For regular domains, get all merchants for the domain
                $merchant_ids = $this->getMerchantIdsByDomain($domain);
            }
            
            if (empty($merchant_ids)) {
                return [ 'impressions' => 0 ];
            }
            
            $total_impressions = 0;
            
            // Fetch impressions for each merchant account
            foreach ($merchant_ids as $merchant_data) {
                try {
                    $merchant_impressions = $this->fetchMerchantImpressions(
                        $merchant_data['gmc_id'], 
                        $tokens['access_token']
                    );
                    
                    $total_impressions += $merchant_impressions['impressions'];
                    
                } catch (\Exception $e) {
                    // Silently continue if one merchant fails
                    continue;
                }
            }
            
            return [
                'impressions' => $total_impressions
            ];
            
        } catch (\Exception $e) {
            return [ 'impressions' => 0 ];
        }
    }
    
    /**
     * Get merchant IDs for a domain from Merchantor database
     */
    private function getMerchantIdsByDomain($domain)
    {
        global $wpdb;
        
        // Get the clean domain name without protocol
        $clean_domain = str_replace(['http://', 'https://'], '', $domain);
        $clean_domain = rtrim($clean_domain, '/');
        
        $table = $wpdb->base_prefix . 'merchantor_merchants';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT gmc_id, subdomain, parent_domain FROM {$table} 
                WHERE parent_domain = %s AND status = 'active'",
                $clean_domain
            ),
            ARRAY_A
        );
        
        return $results;
    }
    
    /**
     * Get merchant ID for a specific subdomain (example-network.shop case)
     */
    private function getMerchantIdsBySubdomain($subdomain_property)
    {
        global $wpdb;
        
        // Extract subdomain from property name (e.g., "s-phone.example-network.shop" -> "s-phone")
        $parts = explode('.', $subdomain_property);
        $subdomain = $parts[0];
        
        $table = $wpdb->base_prefix . 'merchantor_merchants';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT gmc_id, subdomain, parent_domain FROM {$table} 
                WHERE subdomain = %s AND status = 'active'",
                $subdomain
            ),
            ARRAY_A
        );
        
        return $results;
    }
    
    /**
     * Fetch impressions for a specific merchant using Content API for Shopping
     */
    private function fetchMerchantImpressions($merchant_id, $access_token)
    {
        // Content API for Shopping - Reports endpoint for ALL TIME impressions
        $api_url = "https://content.googleapis.com/content/v2.1/{$merchant_id}/reports/search";
        $end_date = date('Y-m-d'); // Today
        $start_date = '2010-01-01'; // Very old date to get "all time"
        
        $query = [
            'query' => "SELECT metrics.impressions FROM MerchantPerformanceView WHERE segments.date BETWEEN '{$start_date}' AND '{$end_date}'"
        ];
        
        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($query),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception('API request failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            throw new \Exception("API returned {$response_code}: {$body}");
        }
        
        $data = json_decode($body, true);
        
        $total_impressions = 0;
        if (isset($data['results']) && is_array($data['results'])) {
            foreach ($data['results'] as $result) {
                if (isset($result['metrics']['impressions'])) {
                    $total_impressions += (int) $result['metrics']['impressions'];
                }
            }
        }
        
        return [ 'impressions' => $total_impressions ];
    }

    /**
     * Fetch total impressions from Bing Webmaster Tools
     */
    private function fetchImpressionsFromBing($domain)
    {
        try {
            // Get Bing API key from network settings
            $network_settings = get_site_option('network_store_settings', []);
            $bing_api_key = $network_settings['network_bing_api_key'] ?? '';
            
            if (empty($bing_api_key)) {
                return [
                    'impressions' => 0,
                    'error' => 'Bing API Key not configured'
                ];
            }
            
            // Check for subdomain case (example-network.shop)
            $is_subdomain = $network_settings['is_subdomain'] ?? '';
            $gsc_property_name = $network_settings['gsc_property_name'] ?? '';
            
            // Determine the site URL to use
            if ($is_subdomain && !empty($gsc_property_name)) {
                // Use the specific subdomain from GSC property name
                $site_url = $this->normalizeSiteUrlForBing($gsc_property_name);
            } else {
                // Use the main domain
                $site_url = $this->normalizeSiteUrlForBing($domain);
            }
            
            // Bing Webmaster Tools API endpoint
            $api_url = 'https://ssl.bing.com/webmaster/api.svc/json/GetRankAndTrafficStats';
            
            // Add API key and site URL as query parameters
            $api_url_with_params = $api_url . '?' . http_build_query([
                'apikey' => $bing_api_key,
                'siteUrl' => $site_url
            ]);
            
            // Make the API request
            $response = wp_remote_get($api_url_with_params, [
                'timeout' => 30,
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_site_url()
                ]
            ]);
            
            if (is_wp_error($response)) {
                return [
                    'impressions' => 0,
                    'error' => 'API request failed: ' . $response->get_error_message()
                ];
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($response_code !== 200) {
                return [
                    'impressions' => 0,
                    'error' => "Bing API returned HTTP {$response_code}"
                ];
            }
            
            // Parse the JSON response
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'impressions' => 0,
                    'error' => 'Invalid JSON response from Bing API'
                ];
            }
            
            // Extract impressions from the response
            $total_impressions = $this->extractImpressionsFromBingResponse($data);
            
            return [ 'impressions' => $total_impressions ];
            
        } catch (\Exception $e) {
            return [
                'impressions' => 0,
                'error' => 'Failed to fetch from Bing Webmaster Tools: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Normalize site URL for Bing API
     */
    private function normalizeSiteUrlForBing($domain)
    {
        // Remove protocol if present
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        
        // Remove www. prefix if present
        $domain = preg_replace('/^www\./', '', $domain);
        
        // Remove trailing slash
        $domain = rtrim($domain, '/');
        
        // Bing API expects the full URL format
        return 'https://' . $domain;
    }

    /**
     * Extract impressions from Bing API response
     */
    private function extractImpressionsFromBingResponse($data)
    {
        $total_impressions = 0;
        
        // Bing API response structure can vary, so we'll check multiple possible paths
        if (isset($data['d']) && is_array($data['d'])) {
            // Response wrapped in 'd' property (common in Microsoft APIs)
            foreach ($data['d'] as $item) {
                if (isset($item['Impressions'])) {
                    $total_impressions += (int) $item['Impressions'];
                } elseif (isset($item['impressions'])) {
                    $total_impressions += (int) $item['impressions'];
                }
            }
        } elseif (isset($data['Impressions'])) {
            // Direct impressions property
            $total_impressions = (int) $data['Impressions'];
        } elseif (isset($data['impressions'])) {
            // Lowercase impressions property
            $total_impressions = (int) $data['impressions'];
        } elseif (is_array($data)) {
            // Iterate through response to find impressions data
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            if (isset($item['Impressions'])) {
                                $total_impressions += (int) $item['Impressions'];
                            } elseif (isset($item['impressions'])) {
                                $total_impressions += (int) $item['impressions'];
                            }
                        }
                    }
                } elseif (strtolower($key) === 'impressions') {
                    $total_impressions += (int) $value;
                }
            }
        }
        
        return $total_impressions;
    }

    /**
     * Handle AJAX request to generate product discovery data
     */
    public function handleGenerateProductDiscovery()
    {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'growth_dashboard_nonce')) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_network_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        try {
            // Get network settings to check if this is a subdomain case (example-network.shop)
            $network_settings = get_site_option('network_store_settings', []);
            $is_subdomain = $network_settings['is_subdomain'] ?? '';
            $gsc_property_name = $network_settings['gsc_property_name'] ?? '';
            
            if ($is_subdomain && !empty($gsc_property_name)) {
                // Example-network case: use specific subdomain and always count as 1
                $subdomain_count = 1; // Always 1 for example-network.shop
                
                // Extract subdomain from GSC property name (e.g., "s-phone.example-network.shop" -> "s-phone")
                $parts = explode('.', $gsc_property_name);
                $target_subdomain = $parts[0];
                
                // Find the specific subdomain site
                $sites = get_sites();
                $first_subdomain_product_count = 0;
                
                foreach ($sites as $site) {
                    if ($site->blog_id != get_main_site_id()) {
                        switch_to_blog($site->blog_id);
                        $site_url = get_site_url();
                        $parsed_url = parse_url($site_url);
                        $site_host = $parsed_url['host'] ?? '';
                        
                        // Check if this site matches our target subdomain
                        if (strpos($site_host, $target_subdomain . '.') === 0) {
                            $first_subdomain_product_count = wp_count_posts('product')->publish;
                            restore_current_blog();
                            break;
                        }
                        
                        restore_current_blog();
                    }
                }
            } else {
                // Regular case: use first subdomain and real count
                $sites = get_sites();
                $subdomain_count = count($sites) - 1; // Exclude main site
                
                // Get product count from the first subdomain (skip main site)
                $first_subdomain_product_count = 0;
                foreach ($sites as $site) {
                    if ($site->blog_id != get_main_site_id()) {
                        // Found first subdomain, get product count and break
                        switch_to_blog($site->blog_id);
                        $first_subdomain_product_count = wp_count_posts('product')->publish;
                        restore_current_blog();
                        break; // Exit loop after first subdomain
                    }
                }
            }

            // Calculate the values
            $before_active_products = $first_subdomain_product_count;
            $active_products = $before_active_products * ($subdomain_count + 1);

            $product_discovery_data = [
                'product_discovery' => [
                    'total_products' => $active_products,
                    'serp_visibility' => [
                        'before_active_products' => $before_active_products,
                        'active_products' => $active_products,
                        'active_products_percent' => 'NUM',
                        'opportunity_products' => 'NUM',
                        'opportunity_products_percent' => 'NUM'
                    ],
                    'products_visibility_in_serp' => [
                        ['name' => 'STRING', 'value' => 'NUM', 'percentage' => 'NUM']
                    ],
                    'opportunities' => [
                        'high_potential' => [
                            ['name' => 'STRING', 'potential' => 'NUM', 'competitor_rank' => 'NUM']
                        ]
                    ]
                ]
            ];

            wp_send_json_success($product_discovery_data);

        } catch (Exception $e) {
            wp_send_json_error('Failed to generate product discovery data: ' . $e->getMessage());
        }
    }

    /**
     * Handle AJAX request to fetch events
     */
    public function handleFetchEvents()
    {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'growth_dashboard_nonce')) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_network_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        try {
            // Get the target domain (main domain)
            $site_url = get_site_url();
            $parsed_url = parse_url($site_url);
            $target_domain = $parsed_url['host'] ?? '';
            
            // Remove www. prefix if present
            $target_domain = preg_replace('/^www\./', '', $target_domain);
            
            // Check if this is a progress request or initial request
            $current_day = isset($_POST['current_day']) ? intval($_POST['current_day']) : 0;
            $total_days = 30;
            
            // Process single day first
            $day_events = $this->fetch_events_for_single_day($target_domain, $current_day);
            
            // Store events in transient (accumulate across days)
            $existing_events = get_transient('growth_dashboard_events_' . $target_domain);
            if ($existing_events === false) {
                $existing_events = [];
            }
            $existing_events = array_merge($existing_events, $day_events);
            set_transient('growth_dashboard_events_' . $target_domain, $existing_events, HOUR_IN_SECONDS);
            
            // Check if this was the last day
            if ($current_day >= ($total_days - 1)) { // Changed from >= $total_days to >= ($total_days - 1)
                // All days processed, return final result
                $final_events = $existing_events; // Use the events we just processed
                
                // Clean up transient
                delete_transient('growth_dashboard_events_' . $target_domain);
                
                wp_send_json_success([
                    'events' => $final_events,
                    'completed' => true,
                    'total_events' => count($final_events),
                    'current_day' => $current_day,
                    'total_days' => $total_days
                ]);
                return;
            }
            
            // Return progress info for next day
            wp_send_json_success([
                'current_day' => $current_day,
                'total_days' => $total_days,
                'day_events_found' => count($day_events),
                'total_events_so_far' => count($existing_events),
                'completed' => false,
                'next_day' => $current_day + 1
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to fetch events: ' . $e->getMessage());
        }
    }

    /**
     * Fetch events for a single day
     */
    private function fetch_events_for_single_day($target_domain, $day_offset)
    {
        $gcs_client = $this->init_gcs_client();
        $day_events = [];
        
        // Calculate the specific date for this day
        $target_date = date('d-m-Y', strtotime("-{$day_offset} days"));
        
        // List files for this specific date only
        $files = $this->list_gcs_files_for_date($gcs_client, $target_date);
        
        foreach ($files as $file) {
            // Parse file content
            $file_events = $this->parse_gcs_file($gcs_client, $file['name']);
            
            // Filter by domain
            $domain_events = array_filter($file_events, function($event) use ($target_domain) {
                return isset($event['domain']) && $event['domain'] === $target_domain;
            });
            
            $day_events = array_merge($day_events, $domain_events);
        }
        
        return $day_events;
    }

    /**
     * List files for a specific date
     */
    private function list_gcs_files_for_date($storage_client, $date)
    {
        try {
            $bucket = $storage_client->bucket('eu_big_data_storage_idr');
            
            // List objects with date suffix pattern
            $objects = $bucket->objects();
            
            $files = [];
            foreach ($objects as $object) {
                $object_name = $object->name();
                
                // Skip Legacy folder
                if (strpos($object_name, 'Legacy/') === 0) {
                    continue;
                }
                
                // Check if file matches the target date
                if (strpos($object_name, "_{$date}.json") !== false) {
                    $info = $object->info();
                    $files[] = [
                        'name' => $object_name,
                        'size' => $info['size'] ?? 0,
                        'updated' => $info['updated'] ?? '',
                        'metadata' => $info['metadata'] ?? []
                    ];
                }
            }
            
            return $files;
            
        } catch (Exception $e) {
            throw new Exception('Failed to list GCS files for date ' . $date . ': ' . $e->getMessage());
        }
    }

    /**
     * Initialize GCS client
     */
    private function init_gcs_client()
    {
        // Get credentials from network settings
        $network_options = get_site_option('network_store_settings', []);
        $credentials_json = $network_options['network_google_cloud_storage_api_key'] ?? '';
        
        if (empty($credentials_json)) {
            throw new Exception('Google Cloud Storage credentials not found in network settings');
        }
        
        $credentials = json_decode($credentials_json, true);
        if (!$credentials) {
            throw new Exception('Invalid GCS credentials JSON');
        }
        
        // Initialize Google Cloud Storage client
        return new \Google\Cloud\Storage\StorageClient([
            'projectId' => 'i-for-ai',
            'keyFile' => $credentials
        ]);
    }

    /**
     * List all files in GCS bucket (excluding Legacy folder)
     */
    private function list_gcs_files($storage_client)
    {
        try {
            $bucket = $storage_client->bucket('eu_big_data_storage_idr');
            
            // List objects with prefix filtering to exclude Legacy folder
            // We'll list all objects and filter out Legacy/ prefix
            $objects = $bucket->objects();
            
            $files = [];
            foreach ($objects as $object) {
                $object_name = $object->name();
                
                // Skip Legacy folder entirely
                if (strpos($object_name, 'Legacy/') === 0) {
                    continue; // Skip this file without adding to debug
                }
                
                $info = $object->info();
                $files[] = [
                    'name' => $object_name,
                    'size' => $info['size'] ?? 0,
                    'updated' => $info['updated'] ?? '',
                    'metadata' => $info['metadata'] ?? []
                ];
            }
            
            return $files;
            
        } catch (Exception $e) {
            throw new Exception('Failed to list GCS files: ' . $e->getMessage());
        }
    }

    /**
     * Parse GCS file content
     */
    private function parse_gcs_file($storage_client, $file_path)
    {
        try {
            $bucket = $storage_client->bucket('eu_big_data_storage_idr');
            $object = $bucket->object($file_path);
            
            if (!$object->exists()) {
                return [];
            }
            
            $content = $object->downloadAsString();
            $events = json_decode($content, true);
            
            return is_array($events) ? $events : [];
            
        } catch (Exception $e) {
            error_log('Failed to parse GCS file ' . $file_path . ': ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if date is within range (DD-MM-YYYY format)
     */
    private function is_date_in_range($file_date, $start_date, $end_date)
    {
        try {
            $file_timestamp = \DateTime::createFromFormat('d-m-Y', $file_date);
            $start_timestamp = \DateTime::createFromFormat('d-m-Y', $start_date);
            $end_timestamp = \DateTime::createFromFormat('d-m-Y', $end_date);
            
            if (!$file_timestamp || !$start_timestamp || !$end_timestamp) {
                return false;
            }
            
            return $file_timestamp >= $start_timestamp && $file_timestamp <= $end_timestamp;
            
        } catch (Exception $e) {
            error_log('Date range check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch Google Shopping debug info with full responses from all merchants
     */
    private function fetchGoogleShoppingDebugInfo($domain)
    {
        try {
            // Get valid tokens from Merchantor
            $tokens = $this->getGoogleTokens();
            
            // Check if this is a subdomain-specific request (example-network.shop case)
            $network_settings = get_site_option('network_store_settings', []);
            $is_subdomain = $network_settings['is_subdomain'] ?? '';
            $gsc_property_name = $network_settings['gsc_property_name'] ?? '';
            
            if ($is_subdomain && !empty($gsc_property_name)) {
                // For example-network.shop subdomains, get specific merchant for the exact subdomain
                $merchant_ids = $this->getMerchantIdsBySubdomain($gsc_property_name);
            } else {
                // For regular domains, get all merchants for the domain
                $merchant_ids = $this->getMerchantIdsByDomain($domain);
            }
            
            if (empty($merchant_ids)) {
                return [ 'merchants' => [], 'error' => 'No merchant IDs found' ];
            }
            
            $debug_responses = [];
            
            // Fetch full responses for each merchant account
            foreach ($merchant_ids as $merchant_data) {
                try {
                    $merchant_response = $this->fetchMerchantImpressionsDebug($merchant_data['gmc_id'], $tokens['access_token']);
                    $debug_responses[] = [
                        'merchant_id' => $merchant_data['gmc_id'],
                        'subdomain' => $merchant_data['subdomain'] ?? '',
                        'parent_domain' => $merchant_data['parent_domain'] ?? '',
                        'response' => $merchant_response
                    ];
                } catch (\Exception $e) {
                    $debug_responses[] = [
                        'merchant_id' => $merchant_data['gmc_id'],
                        'subdomain' => $merchant_data['subdomain'] ?? '',
                        'parent_domain' => $merchant_data['parent_domain'] ?? '',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return [
                'merchants' => $debug_responses,
                'total_merchants' => count($merchant_ids)
            ];
            
        } catch (\Exception $e) {
            return [ 'error' => 'Failed to fetch Google Shopping debug info: ' . $e->getMessage() ];
        }
    }

    /**
     * Fetch Bing debug info with full response
     */
    private function fetchBingDebugInfo($domain)
    {
        try {
            // Get Bing API key from network settings
            $network_settings = get_site_option('network_store_settings', []);
            $bing_api_key = $network_settings['network_bing_api_key'] ?? '';
            
            if (empty($bing_api_key)) {
                return [
                    'error' => 'Bing API Key not configured',
                    'api_key_configured' => false
                ];
            }
            
            // Check for subdomain case (example-network.shop)
            $is_subdomain = $network_settings['is_subdomain'] ?? '';
            $gsc_property_name = $network_settings['gsc_property_name'] ?? '';
            
            // Determine the site URL to use
            if ($is_subdomain && !empty($gsc_property_name)) {
                // Use the specific subdomain from GSC property name
                $site_url = $this->normalizeSiteUrlForBing($gsc_property_name);
            } else {
                // Use the main domain
                $site_url = $this->normalizeSiteUrlForBing($domain);
            }
            
            // Bing Webmaster Tools API endpoint
            $api_url = 'https://ssl.bing.com/webmaster/api.svc/json/GetRankAndTrafficStats';
            
            // Add API key and site URL as query parameters
            $api_url_with_params = $api_url . '?' . http_build_query([
                'apikey' => $bing_api_key,
                'siteUrl' => $site_url
            ]);
            
            // Make the API request
            $response = wp_remote_get($api_url_with_params, [
                'timeout' => 30,
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_site_url()
                ]
            ]);
            
            if (is_wp_error($response)) {
                return [
                    'error' => 'API request failed: ' . $response->get_error_message(),
                    'request_url' => $api_url_with_params,
                    'site_url_used' => $site_url
                ];
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            // Parse the JSON response
            $data = json_decode($body, true);
            
            return [
                'response_code' => $response_code,
                'raw_response' => $data,
                'request_url' => $api_url_with_params,
                'site_url_used' => $site_url,
                'api_key_configured' => true,
                'json_decode_error' => json_last_error() !== JSON_ERROR_NONE ? json_last_error_msg() : null
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to fetch Bing debug info: ' . $e->getMessage(),
                'api_key_configured' => !empty($bing_api_key)
            ];
        }
    }

    /**
     * Fetch full merchant impressions response for debug purposes
     */
    private function fetchMerchantImpressionsDebug($merchant_id, $access_token)
    {
        // Content API for Shopping - Reports endpoint for ALL TIME impressions
        $api_url = "https://content.googleapis.com/content/v2.1/{$merchant_id}/reports/search";
        $end_date = date('Y-m-d'); // Today
        $start_date = '2010-01-01'; // Very old date to get "all time"
        
        $query = [
            'query' => "SELECT metrics.impressions FROM MerchantPerformanceView WHERE segments.date BETWEEN '{$start_date}' AND '{$end_date}'"
        ];
        
        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($query),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return [
                'error' => 'API request failed: ' . $response->get_error_message(),
                'request_url' => $api_url,
                'query' => $query
            ];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        $data = json_decode($body, true);
        
        return [
            'response_code' => $response_code,
            'raw_response' => $data,
            'request_url' => $api_url,
            'query' => $query,
            'json_decode_error' => json_last_error() !== JSON_ERROR_NONE ? json_last_error_msg() : null
        ];
    }
}
