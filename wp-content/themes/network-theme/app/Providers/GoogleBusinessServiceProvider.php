<?php
/**
 * GoogleBusinessServiceProvider
 * 
 * This service provider handles the integration with Google Business Profile.
 * It manages the synchronization of store information with Google My Business data,
 * handles API calls to Google Places API, and provides error handling for the integration.
 * 
 * Author: Antigravity
 * Date: 28/04/2025
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Exception;

class GoogleBusinessServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {

        // Register AJAX handlers:
        add_action('wp_ajax_sync_gmb_ajax', [$this, 'handleAjaxSync']);
        add_action('wp_ajax_toggle_gmb_auto_sync', [$this, 'handleToggleAutoSync']);
        add_action('wp_ajax_clear_gmb_reviews', [$this, 'handleClearReviews']);
        add_action('wp_ajax_refresh_reviews_html', [$this, 'refreshReviewsHtml']);
        add_action('wp_ajax_clear_gmb_posts', [$this, 'handleClearPosts']);
        add_action('wp_ajax_refresh_posts_html', [$this, 'refreshPostsHtml']);
        add_action('wp_ajax_fetch_gmb_posts_ajax', [$this, 'handleFetchPosts']);


        // Enqueue scripts for the store settings page
        add_action('admin_enqueue_scripts', function ($hook) {
            if (strpos($hook, 'store-settings') !== false) {
                wp_enqueue_script('jquery');

                // Get the correct path to the script
                $script_path = get_template_directory() . '/resources/scripts/store-settings/google-business.js';
                $script_url = get_template_directory_uri() . '/resources/scripts/store-settings/google-business.js';

                wp_enqueue_script(
                    'google-business-sync',
                    $script_url,
                    ['jquery'],
                    filemtime($script_path),
                    true
                );

                wp_localize_script('google-business-sync', 'gmbSettings', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('gmb_sync_nonce')
                ]);
            }
        });

        // Add hook to display reviews everywhere
        add_action('google-business-reviews', [$this, 'displayReviews']);

        // Add hook to display posts everywhere
        add_action('google-business-posts', [$this, 'displayPosts']);
    }

    public function handleToggleAutoSync()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gmb_sync_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $disabled = isset($_POST['disabled']) && $_POST['disabled'] == 1;

        // Update the option
        update_option('gmb_disable_auto_sync', $disabled);

        // If disabled, clear any scheduled updates
        if ($disabled) {
            wp_clear_scheduled_hook('gmb_scheduled_sync');
        }

        wp_send_json_success([
            'message' => $disabled ? 'Automatic sync disabled' : 'Automatic sync enabled',
            'disabled' => $disabled
        ]);
    }

    public function handleAjaxSync()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gmb_sync_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        // Reset the reviews updated flag before syncing
        // this flag will be used to indicate if refreshReviewsHtml is needed
        update_option('gmb_reviews_updated', false);

        $result = $this->syncGMBData();

        // Check if there was an error
        $error_message = get_option('gmb_sync_error', '');
        if ($result === false) {
            wp_send_json_error([
                'message' => !empty($error_message) ? $error_message : 'Sync failed. An unknown error occurred.',
                'status' => 'error'
            ]);
            return;
        }

        $updates = get_option('gmb_last_updates', []);


        // Check if there are actual updates
        $has_real_updates = !empty($updates);

        if ($result === 'no_updates' || !$has_real_updates) {
            wp_send_json_success([
                'message' => 'No updates were needed. Your store information already matches Google Business Profile.',
                'last_sync' => get_option('gmb_last_sync'),
                'updates' => [],
                'status' => 'no_updates'
            ]);
            return;
        }

        if ($result === true) {
            wp_send_json_success([
                'message' => 'Store information successfully synced with Google Business Profile!',
                'last_sync' => get_option('gmb_last_sync'),
                'updates' => $updates,
                'reviews_updated' => get_option('gmb_reviews_updated', false),
                'status' => 'updated'
            ]);
            return;
        }
    }

    public function syncGMBData()
    {
        delete_option('gmb_sync_error');
        delete_option('gmb_last_updates');

        $options = get_option('store_settings');
        $domain = parse_url(home_url(), PHP_URL_HOST);

        if (empty($domain)) {
            update_option('gmb_sync_error', 'Could not determine domain name.');
            return false;
        }

        $place_data = $this->findPlaceData($domain);

        if (!$place_data) {
            // There was an error finding the place data, which is already logged in 'gmb_sync_error'
            // Don't proceed further - return false to indicate error
            return false;
        }

        // Validate the place data
        if (empty($place_data['formattedAddress'])) {
            update_option('gmb_sync_error', 'Invalid business profile data received from Google.');
            return false;
        }

        $updates = $this->updateStoreSettings($options, $place_data);

        // START [GMB-SYNC] Best-effort mall fetch via Business Profile API
        // Uses relationshipData.parentLocation from mybusinessbusinessinformation API
        // Non-blocking: silently skips if Merchantor_Auth is unavailable
        $mall_updates = $this->fetchParentLocationMall($options, $place_data);
        if (!empty($mall_updates)) {
            $updates = array_merge($updates, $mall_updates);
        }
        // END [GMB-SYNC] Best-effort mall fetch

        if (empty($updates)) {
            update_option('gmb_last_sync', time());
            // This is a successful sync, just with no changes needed
            return 'no_updates';
        }

        update_option('gmb_last_sync', time());
        update_option('gmb_last_updates', $updates);

        return true;
    }

    private function findPlaceData($domain)
    {
        $network_options = get_site_option('network_store_settings', []);
        $api_key = isset($network_options['network_google_places_api_key']) ? sanitize_text_field($network_options['network_google_places_api_key']) : '';

        if (empty($api_key)) {
            update_option('gmb_sync_error', 'Google Places API key not found in network settings.');
            return false;
        }
        $store_settings = get_option('store_settings', []);
        $gmb_profile_name = isset($store_settings['seo']['gmb_name']) ? trim($store_settings['seo']['gmb_name']) : '';

        // Use GMB profile name if provided, otherwise fall back to blogname
        if (!empty($gmb_profile_name)) {
            $search_query = $gmb_profile_name;
        } else {
            // Use blogname as search query — produces far better Google Places results
            // than URL cleaning (e.g. "example-tenant-g Example City Branch" vs "example-tenant-g directory/es/tiendas/...")
            $search_query = get_option('blogname', '');
            if (empty($search_query)) {
                // Last resort: clean the URL
                $full_url = home_url();
                $clean_url = str_replace(['http://', 'https://'], '', $full_url);
                $search_query = str_replace(['.', '-'], ' ', $clean_url);
            }
        }

        // Try with the search query
        $place_id = $this->findPlaceIdByQuery($api_key, $search_query);

        if (!$place_id) {
            update_option('gmb_sync_error', sprintf(
                'Could not find a Google Business Profile for "%s".',
                $search_query
            ));
            return false;
        }

        $place_details = $this->getPlaceDetails($api_key, $place_id);

        if (!$place_details) {
            update_option('gmb_sync_error', 'Failed to retrieve business profile details from Google. ' . get_option('gmb_sync_error'));
            return false;
        }

        return $place_details;
    }

    private function findPlaceIdByQuery($api_key, $query)
    {
        $url = "https://places.googleapis.com/v1/places:searchText";

        $headers = [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $api_key,
            'X-Goog-FieldMask' => 'places.id,places.formattedAddress'
        ];

        $body = json_encode([
            'textQuery' => $query
        ]);

        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body' => $body
        ]);

        if (is_wp_error($response)) {
            update_option('gmb_sync_error', 'API request failed: ' . $response->get_error_message());
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['error'])) {
            update_option('gmb_sync_error', 'API error: ' . ($data['error']['message'] ?? 'Unknown error'));
            return false;
        }

        if (empty($data['places'])) {
            update_option('gmb_sync_error', 'No matching business found for: ' . $query);
            return false;
        }

        // Return the first result's ID
        return $data['places'][0]['id'];
    }

    private function getPlaceDetails($api_key, $place_id)
    {
        $url = "https://places.googleapis.com/v1/places/{$place_id}";
        $fields = 'displayName,primaryType,types,formattedAddress,addressComponents,internationalPhoneNumber,websiteUri,regularOpeningHours,location,googleMapsUri,reviews,rating,userRatingCount,editorialSummary,priceLevel,paymentOptions';

        $headers = [
            'X-Goog-Api-Key' => $api_key,
            'X-Goog-FieldMask' => $fields . ',accessibilityOptions,parkingOptions,restroom,allowsDogs,goodForChildren,goodForGroups,servesBeer,servesWine,curbsidePickup,delivery,dineIn,takeout,outdoorSeating'
        ];

        $response = wp_remote_get($url, [
            'headers' => $headers
        ]);

        if (is_wp_error($response)) {
            update_option('gmb_sync_error', 'API request failed: ' . $response->get_error_message());
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['error'])) {
            update_option('gmb_sync_error', 'API error: ' . ($data['error']['message'] ?? 'Unknown error'));
            return false;
        }

        if (empty($data['formattedAddress'])) {
            update_option('gmb_sync_error', 'Business profile data is incomplete or unavailable.');
            return false;
        }

        if (isset($data['rating']) && isset($data['userRatingCount'])) {
            $reviews_header = [
                'rating' => $data['rating'] ?? 0,
                'userRatingCount' => $data['userRatingCount'] ?? 0
            ];
            update_option('gmb_reviews_header', $reviews_header);
        }

        return $data;
    }

    private function updateStoreSettings(&$options, $place_data)
    {
        $updates = [];

        if (!isset($options['store_info'])) {
            $options['store_info'] = [];
        }
        if (!isset($options['seo'])) {
            $options['seo'] = [];
        }

        // Address components
        if (!empty($place_data['addressComponents'])) {
            $address_parts = [
                'street_number' => '',
                'route' => '',
                'locality' => '',
                'postal_code' => '',
                'country' => ''
            ];

            foreach ($place_data['addressComponents'] as $component) {
                // Skip components that don't have types or have null types
                if (empty($component['types']) || !is_array($component['types'])) {
                    continue;
                }

                if (in_array('street_number', $component['types'])) {
                    $address_parts['street_number'] = $component['longText'];
                }
                if (in_array('route', $component['types'])) {
                    $address_parts['route'] = $component['longText'];
                }
                if (in_array('locality', $component['types'])) {
                    $address_parts['locality'] = $component['longText'];
                }
                if (in_array('postal_code', $component['types'])) {
                    $address_parts['postal_code'] = $component['longText'];
                }
                if (in_array('country', $component['types'])) {
                    $address_parts['country'] = $component['shortText'];
                }
            }

            // Combine street number and route for full address
            $street_address = trim($address_parts['street_number'] . ' ' . $address_parts['route']);
            if (!empty($street_address)) {
                $old_address = $options['store_info']['address'] ?? '';
                if ($old_address !== $street_address) {
                    $options['store_info']['address'] = $street_address;
                    $updates[] = sprintf('Street address changed from "%s" to "%s"', $old_address, $street_address);
                }
            } elseif (!empty($place_data['formattedAddress'])) {
                // Fall back to formattedAddress if we couldn't extract components
                $old_address = $options['store_info']['address'] ?? '';
                if ($old_address !== $place_data['formattedAddress']) {
                    $options['store_info']['address'] = $place_data['formattedAddress'];
                    $updates[] = sprintf('Street address changed from "%s" to "%s"', $old_address, $place_data['formattedAddress']);
                }
            }

            // Update city
            if (!empty($address_parts['locality'])) {
                $old_city = $options['store_info']['city'] ?? '';
                if ($old_city !== $address_parts['locality']) {
                    $options['store_info']['city'] = $address_parts['locality'];
                    $updates[] = sprintf('City changed from "%s" to "%s"', $old_city, $address_parts['locality']);
                }
            }

            // Update postcode
            if (!empty($address_parts['postal_code'])) {
                $old_postcode = $options['store_info']['postcode'] ?? '';
                if ($old_postcode !== $address_parts['postal_code']) {
                    $options['store_info']['postcode'] = $address_parts['postal_code'];
                    $updates[] = sprintf('Postcode changed from "%s" to "%s"', $old_postcode, $address_parts['postal_code']);
                }
            }

            // Update country
            if (!empty($address_parts['country'])) {
                $old_country = $options['store_info']['country'] ?? '';
                if ($old_country !== $address_parts['country']) {
                    $options['store_info']['country'] = $address_parts['country'];
                    $updates[] = sprintf('Country changed from "%s" to "%s"', $old_country, $address_parts['country']);
                }
            }
        } elseif (!empty($place_data['formattedAddress'])) {
            // Handle case where addressComponents is not available
            $old_address = $options['store_info']['address'] ?? '';
            if ($old_address !== $place_data['formattedAddress']) {
                $options['store_info']['address'] = $place_data['formattedAddress'];
                $updates[] = sprintf('Address changed from "%s" to "%s"', $old_address, $place_data['formattedAddress']);
            }
        }
        // Store Description
        if (!empty($place_data['editorialSummary']) && !empty($place_data['editorialSummary']['text'])) {
            $new_description = wp_kses_post($place_data['editorialSummary']['text']);
            $old_description = $options['store_info']['description'] ?? '';

            if ($old_description !== $new_description) {
                $options['store_info']['description'] = $new_description;
                $updates[] = sprintf('Store description changed from "%s" to "%s"', $old_description, $new_description);
            }
        }

        // Phone
        if (!empty($place_data['internationalPhoneNumber'])) {
            $new_phone = sanitize_text_field($place_data['internationalPhoneNumber']);
            $old_phone = $options['store_info']['phone'] ?? '';
            if ($old_phone !== $new_phone) {
                $options['store_info']['phone'] = $new_phone;
                $updates[] = sprintf('Phone number changed from "%s" to "%s"', $old_phone, $new_phone);
            }
        }

        // Website
        if (!empty($place_data['websiteUri'])) {
            // Remove query parameters from website URI
            $clean_uri = preg_replace('/\?.*/', '', $place_data['websiteUri']);
            $old_ecommerce_link = $options['store_info']['ecommerce_link'] ?? '';
            if ($old_ecommerce_link !== $clean_uri) {
                $options['store_info']['ecommerce_link'] = esc_url_raw($clean_uri);
                $updates[] = sprintf('Website URL (eCommerce Link) changed from "%s" to "%s"', $old_ecommerce_link, $clean_uri);
            }
        }

        // Coordinates
        if (!empty($place_data['location'])) {
            $new_lat = (float) $place_data['location']['latitude'];
            $new_lng = (float) $place_data['location']['longitude'];
            $old_lat = (float) ($options['store_info']['latitude'] ?? 0);
            $old_lng = (float) ($options['store_info']['longitude'] ?? 0);

            if ($old_lat !== $new_lat || $old_lng !== $new_lng) {
                $options['store_info']['latitude'] = $new_lat;
                $options['store_info']['longitude'] = $new_lng;
                $updates[] = sprintf(
                    'Coordinates changed from (%f, %f) to (%f, %f)',
                    $old_lat,
                    $old_lng,
                    $new_lat,
                    $new_lng
                );
            }
        }

        // START [GMB-SYNC] Price Level — map Google's PRICE_LEVEL enum to $ symbols
        if (!empty($place_data['priceLevel'])) {
            $price_map = [
                'PRICE_LEVEL_FREE' => '$',
                'PRICE_LEVEL_INEXPENSIVE' => '$',
                'PRICE_LEVEL_MODERATE' => '$$',
                'PRICE_LEVEL_EXPENSIVE' => '$$$',
                'PRICE_LEVEL_VERY_EXPENSIVE' => '$$$',
            ];
            $new_price = $price_map[$place_data['priceLevel']] ?? null;
            if ($new_price) {
                $old_price = $options['store_info']['price_range'] ?? '';
                if ($old_price !== $new_price) {
                    $options['store_info']['price_range'] = $new_price;
                    $updates[] = sprintf('Price range updated from "%s" to "%s" (API: %s)', $old_price, $new_price, $place_data['priceLevel']);
                }
            }
        }
        // END [GMB-SYNC] Price Level

        // START [GMB-SYNC] Payment Options — map Google's paymentOptions to human-readable list
        if (!empty($place_data['paymentOptions']) && is_array($place_data['paymentOptions'])) {
            $payment_labels = [];
            $po = $place_data['paymentOptions'];
            if (!empty($po['acceptsCreditCards']))
                $payment_labels[] = 'Credit Cards';
            if (!empty($po['acceptsDebitCards']))
                $payment_labels[] = 'Debit Cards';
            if (!empty($po['acceptsCashOnly']))
                $payment_labels[] = 'Cash';
            if (!empty($po['acceptsNfc']))
                $payment_labels[] = 'NFC';

            if (!empty($payment_labels)) {
                $new_payment = implode(', ', $payment_labels);
                $old_payment = $options['store_info']['payment_accepted'] ?? '';
                if ($old_payment !== $new_payment) {
                    $options['store_info']['payment_accepted'] = $new_payment;
                    $updates[] = sprintf('Payment methods updated to "%s"', $new_payment);
                }
            }
        }
        // END [GMB-SYNC] Payment Options

        // Google Maps URI for SEO
        if (!empty($place_data['googleMapsUri'])) {
            $old_gmb_link = $options['seo']['gmb_link'] ?? '';
            $new_gmb_link = esc_url_raw($place_data['googleMapsUri']);

            if ($old_gmb_link !== $new_gmb_link) {
                $options['seo']['gmb_link'] = $new_gmb_link;
                $updates[] = sprintf(
                    'Google My Business link changed from "%s" to "%s"',
                    $old_gmb_link,
                    $new_gmb_link
                );
            }
        }

        // START [GMB-SYNC] Verified Business Name — populate gmb_name from Google's displayName
        // This MUST run before the store_name derivation below, which reads gmb_name.
        if (!empty($place_data['displayName']['text'])) {
            $new_gmb_name = sanitize_text_field($place_data['displayName']['text']);
            $old_gmb_name = $options['seo']['gmb_name'] ?? '';
            if ($old_gmb_name !== $new_gmb_name) {
                $options['seo']['gmb_name'] = $new_gmb_name;
                $updates[] = sprintf('GMB verified name updated from "%s" to "%s"', $old_gmb_name, $new_gmb_name);
            }
        }
        // END [GMB-SYNC] Verified Business Name

        // [SYNC-FIX] Store Name — critical for footer, pickup notice, about page, schema
        // Uses gmb_name (which is the Google-verified business name) as the canonical store name.
        $new_store_name = $options['seo']['gmb_name'] ?? get_option('blogname', '');
        if (!empty($new_store_name)) {
            $old_store_name = $options['seo']['store_name'] ?? '';
            if ($old_store_name !== $new_store_name) {
                $options['seo']['store_name'] = $new_store_name;
                $updates[] = sprintf('Store name changed from "%s" to "%s"', $old_store_name, $new_store_name);
            }

            // START [GMB-SYNC] Enriched Image Alt Tag — name + address + city + neighborhood/mall
            $alt_parts = [$new_store_name];
            if (!empty($options['store_info']['address'])) {
                $alt_parts[] = $options['store_info']['address'];
            }
            if (!empty($options['store_info']['city'])) {
                $alt_parts[] = $options['store_info']['city'];
            }
            // Append neighborhood or mall for richer context
            if (!empty($options['seo']['neighborhood'])) {
                $alt_parts[] = $options['seo']['neighborhood'];
            } elseif (!empty($options['seo']['mall'])) {
                $alt_parts[] = $options['seo']['mall'];
            }
            $new_image_alt = sanitize_text_field(implode(' - ', array_filter($alt_parts)));
            $old_image_alt = $options['seo']['image_alt'] ?? '';
            if ($old_image_alt !== $new_image_alt) {
                $options['seo']['image_alt'] = $new_image_alt;
                $updates[] = sprintf('Image alt tag updated to "%s"', $new_image_alt);
            }
            // END [GMB-SYNC] Enriched Image Alt Tag
        }

        // [SYNC-FIX] Neighborhood — extract from addressComponents for local SEO
        if (!empty($place_data['addressComponents'])) {
            $neighborhood_parts = [];
            foreach ($place_data['addressComponents'] as $component) {
                if (empty($component['types']) || !is_array($component['types'])) {
                    continue;
                }
                if (
                    in_array('sublocality', $component['types']) ||
                    in_array('sublocality_level_1', $component['types']) ||
                    in_array('neighborhood', $component['types'])
                ) {
                    $neighborhood_parts[] = $component['longText'];
                }
            }
            if (!empty($neighborhood_parts)) {
                $new_neighborhood = implode(', ', $neighborhood_parts);
                $old_neighborhood = $options['seo']['neighborhood'] ?? '';
                if ($old_neighborhood !== $new_neighborhood) {
                    $options['seo']['neighborhood'] = $new_neighborhood;
                    $updates[] = sprintf('Neighborhood changed from "%s" to "%s"', $old_neighborhood, $new_neighborhood);
                }
            }
        }

        // START [GMB-SYNC] SEO City — copy store_info.city to seo.city for SEO tab
        if (!empty($options['store_info']['city'])) {
            $new_seo_city = $options['store_info']['city'];
            $old_seo_city = $options['seo']['city'] ?? '';
            if ($old_seo_city !== $new_seo_city) {
                $options['seo']['city'] = $new_seo_city;
                $updates[] = sprintf('SEO city updated to "%s"', $new_seo_city);
            }
        }
        // END [GMB-SYNC] SEO City

        // [SYNC-FIX] Store Code — Google's place_id for schema/structured data
        if (!empty($place_data['id'])) {
            $old_code = $options['seo']['storeCode'] ?? '';
            if ($old_code !== $place_data['id']) {
                $options['seo']['storeCode'] = $place_data['id'];
                $updates[] = 'Store code (Google Place ID) has been updated';
            }
        }

        // [NEW] Accessibility -> accessible
        if (!empty($place_data['accessibilityOptions'])) {
            // Check for any wheelchair accessible feature to mark as accessible
            $is_accessible = false;
            $opts = $place_data['accessibilityOptions'];

            if (
                (!empty($opts['wheelchairAccessibleEntrance']) && $opts['wheelchairAccessibleEntrance']) ||
                (!empty($opts['wheelchairAccessibleParking']) && $opts['wheelchairAccessibleParking']) ||
                (!empty($opts['wheelchairAccessibleRestroom']) && $opts['wheelchairAccessibleRestroom']) ||
                (!empty($opts['wheelchairAccessibleSeating']) && $opts['wheelchairAccessibleSeating'])
            ) {
                $is_accessible = true;
            }

            if ($is_accessible) {
                // If checkbox is unchecked or not set, set it
                if (empty($options['accessible'])) {
                    $options['accessible'] = 1;
                    $updates[] = 'Store marked as Accessible based on GMB data';
                }
            }
        }

        // Opening hours
        if (!empty($place_data['regularOpeningHours']) && !empty($place_data['regularOpeningHours']['periods']) && is_array($place_data['regularOpeningHours']['periods'])) {
            $day_map = [
                0 => 'sunday',
                1 => 'monday',
                2 => 'tuesday',
                3 => 'wednesday',
                4 => 'thursday',
                5 => 'friday',
                6 => 'saturday',
            ];

            $new_hours = [];
            $old_hours = $options['store_info']['hours'] ?? [];

            // Initialize all days as closed first
            foreach ($day_map as $day_key) {
                $new_hours[$day_key] = ['closed' => true, 'periods' => []];
            }

            // Process each period from Google API
            foreach ($place_data['regularOpeningHours']['periods'] as $period) {
                $day_num = isset($period['open']['day']) ? (int) $period['open']['day'] : -1;
                $day_key = $day_map[$day_num] ?? '';

                if (empty($day_key))
                    continue;

                // Mark this day as open
                $new_hours[$day_key]['closed'] = false;

                if (!isset($period['close'])) {
                    // Open 24 hours
                    $new_hours[$day_key]['periods'][] = [
                        'open' => '00:00',
                        'close' => '23:59',
                    ];
                } else {
                    // Format the open time properly with leading zeros
                    $open_hour = isset($period['open']['hour']) ? sprintf('%02d', (int) $period['open']['hour']) : '00';
                    $open_minute = isset($period['open']['minute']) ? sprintf('%02d', (int) $period['open']['minute']) : '00';
                    $open_time = $open_hour . ':' . $open_minute;

                    // Format the close time properly with leading zeros
                    $close_hour = isset($period['close']['hour']) ? sprintf('%02d', (int) $period['close']['hour']) : '00';
                    $close_minute = isset($period['close']['minute']) ? sprintf('%02d', (int) $period['close']['minute']) : '00';
                    $close_time = $close_hour . ':' . $close_minute;

                    $new_hours[$day_key]['periods'][] = [
                        'open' => $open_time,
                        'close' => $close_time,
                    ];
                }
            }

            // Compare hours arrays as serialized strings to ensure accurate comparison
            if (serialize($old_hours) !== serialize($new_hours)) {
                $options['store_info']['hours'] = $new_hours;
                $updates[] = 'Opening hours have been updated';
            }
        }

        // Reviews (compare and store)
        if (!empty($place_data['reviews']) && is_array($place_data['reviews'])) {
            $old_reviews = get_option('gmb_reviews', []);
            $new_reviews = $place_data['reviews'];

            // If we have first time reviews or just different ones - store them
            if (!empty($new_reviews) && (empty($old_reviews) || serialize($old_reviews) !== serialize($new_reviews))) {
                update_option('gmb_reviews', $new_reviews);
                $updates[] = 'Reviews have been updated';
                // this flag indicates that refreshReviewsHtml is needed
                update_option('gmb_reviews_updated', true);
            }

            // Store reviews header info for later use (in displayReviews function)
            if (isset($place_data['rating']) && isset($place_data['userRatingCount'])) {
                $reviews_header = [
                    'rating' => $place_data['rating'],
                    'userRatingCount' => $place_data['userRatingCount']
                ];
                update_option('gmb_reviews_header', $reviews_header);
            }
        }

        // [NEW] Amenities & Services -> store_info[amenities]
        $amenities = [];

        // Parking
        if (!empty($place_data['parkingOptions'])) {
            $p = $place_data['parkingOptions'];
            if (!empty($p['freeParkingLot']) || !empty($p['freeStreetParking']) || !empty($p['freeGarageParking'])) {
                $amenities[] = 'Free Parking';
            } elseif (!empty($p['paidParkingLot']) || !empty($p['paidStreetParking']) || !empty($p['paidGarageParking']) || !empty($p['valetParking'])) {
                $amenities[] = 'Paid Parking';
            }
        }

        // Boolean attributes
        $bool_map = [
            'restroom' => 'Restroom',
            'allowsDogs' => 'Dogs Allowed',
            'goodForChildren' => 'Good for Kids',
            'goodForGroups' => 'Good for Groups',
            'servesBeer' => 'Serves Beer',
            'servesWine' => 'Serves Wine',
            'curbsidePickup' => 'Curbside Pickup',
            'delivery' => 'Home Delivery',
            'dineIn' => 'Dine In',
            'takeout' => 'Takeout',
            'outdoorSeating' => 'Outdoor Seating'
        ];

        foreach ($bool_map as $key => $label) {
            if (!empty($place_data[$key]) && $place_data[$key] === true) {
                $amenities[] = $label;
            }
        }

        if (!empty($amenities)) {
            // Sort for consistency
            sort($amenities);
            $old_amenities = $options['store_info']['amenities'] ?? [];
            if (is_array($old_amenities))
                sort($old_amenities);

            if (serialize($old_amenities) !== serialize($amenities)) {
                $options['store_info']['amenities'] = $amenities;
                $updates[] = 'Store amenities updated (' . implode(', ', $amenities) . ')';
            }
        }

        // START [GMB-SYNC] Aggregate Rating — rating_value & review_count for Schema AggregateRating
        if (isset($place_data['rating'])) {
            $new_rating = round((float) $place_data['rating'], 1);
            $old_rating = isset($options['store_info']['rating_value']) ? round((float) $options['store_info']['rating_value'], 1) : 0;
            if ($old_rating !== $new_rating) {
                $options['store_info']['rating_value'] = $new_rating;
                $updates[] = sprintf('Rating updated from %s to %s', $old_rating, $new_rating);
            }
        }
        if (isset($place_data['userRatingCount'])) {
            $new_count = absint($place_data['userRatingCount']);
            $old_count = absint($options['store_info']['review_count'] ?? 0);
            if ($old_count !== $new_count) {
                $options['store_info']['review_count'] = $new_count;
                $updates[] = sprintf('Review count updated from %d to %d', $old_count, $new_count);
            }
        }
        // END [GMB-SYNC] Aggregate Rating

        // START [GMB-SYNC] Business Type — map Google primaryType/types to schema-compatible slugs
        $business_type_map = include __DIR__ . '/../Data/BusinessTypes.php';
        $new_business_types = [];

        // Primary type takes precedence
        if (!empty($place_data['primaryType']) && isset($business_type_map[$place_data['primaryType']])) {
            $new_business_types[] = $place_data['primaryType'];
        }

        // Additional types from the types[] array
        if (!empty($place_data['types']) && is_array($place_data['types'])) {
            foreach ($place_data['types'] as $gtype) {
                if (isset($business_type_map[$gtype]) && !in_array($gtype, $new_business_types)) {
                    $new_business_types[] = $gtype;
                }
            }
        }

        if (!empty($new_business_types)) {
            $old_business_types = $options['store_info']['business_type'] ?? [];
            if (!is_array($old_business_types)) {
                $old_business_types = [];
            }
            sort($new_business_types);
            $sorted_old = $old_business_types;
            if (is_array($sorted_old)) {
                sort($sorted_old);
            }
            if (serialize($sorted_old) !== serialize($new_business_types)) {
                $options['store_info']['business_type'] = $new_business_types;
                $updates[] = 'Business type updated to: ' . implode(', ', $new_business_types);
            }
        }
        // END [GMB-SYNC] Business Type

        // Only update option if there were any changes
        if (!empty($updates)) {
            update_option('store_settings', $options);
        }

        return $updates;
    }

    // START [getFilteredReviews] — Called statically from store-about-section.blade.php
    /**
     * Get filtered GMB reviews by minimum star rating, limited to a max count.
     *
     * Called statically from Blade views:
     *   \App\Providers\GoogleBusinessServiceProvider::getFilteredReviews($minRating, $limit)
     *
     * @param int $minRating Minimum star rating to include (1-5). Default 5.
     * @param int $limit     Maximum number of reviews to return. Default 5.
     * @return array          Array of review arrays, or empty array.
     */
    public static function getFilteredReviews($minRating = 5, $limit = 5)
    {
        $reviews = get_option('gmb_reviews', []);

        if (empty($reviews) || !is_array($reviews)) {
            return [];
        }

        $filtered = array_filter($reviews, function ($review) use ($minRating) {
            $rating = isset($review['rating']) ? (int) $review['rating'] : 0;
            return $rating >= $minRating;
        });

        // Re-index and limit
        return array_slice(array_values($filtered), 0, $limit);
    }
    // END [getFilteredReviews]

    public function displayReviews()
    {
        $reviews = get_option('gmb_reviews', []);

        // Always create the container, even if empty
        // this will be used for later injection of reviews
        echo '<div class="gmb-reviews-container" style="max-width: 800px; margin-top: 20px; direction: ltr;">';

        if (is_admin()) { // show message only on admin page
            echo (empty($reviews)) ?
                '<p>' . __('No reviews available. Run a sync to fetch reviews.', 'sage') . '</p>'
                : '<button id="clear-reviews" class="button ml-2">' . __('Clear reviews', 'sage') . '</button>';
        }
        if (empty($reviews)) {
            echo '</div>'; // Close the container if no reviews
            return;
        }

        $reviews_header = get_option('gmb_reviews_header', []);
        $rating = $reviews_header['rating'];
        $rating_count = $reviews_header['userRatingCount'];
        $gmb_link = get_option('store_settings', [])['seo']['gmb_link'] ?? '';

        // reviews header
        echo '<div class="gmb-reviews-header" style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 4px;">';
        echo '<h2 style="margin-top: 0;">' . esc_html(get_bloginfo('name')) . '</h2>';
        echo '<div class="gmb-rating" style="display: flex; align-items: center; margin-bottom: 10px;">';

        if ($rating > 0) {
            // Display star rating
            echo '<div class="gmb-stars" style="color: #fbbc04; font-size: 20px; margin-right: 8px;">';
            $full_stars = floor($rating);
            $half_star = ($rating - $full_stars) >= 0.5;

            for ($i = 1; $i <= 5; $i++) {
                if ($i <= $full_stars) {
                    echo '★'; // Full star
                } elseif ($i == $full_stars + 1 && $half_star) {
                    echo '⯨'; // Half star (approximation)
                } else {
                    echo '☆'; // Empty star
                }
            }

            echo '</div>';
            echo '<span style="font-size: 16px; font-weight: 500;">' . number_format($rating, 1) . ' ' . __('out of 5', 'sage') . '</span>';
            echo '<span style="margin-left: 10px; color: #70757a;">(' . number_format($rating_count) . ' ' . __('reviews', 'sage') . ')</span>';
        } else {
            echo '<span style="color: #70757a;">' . __('No ratings yet', 'sage') . '</span>';
        }

        echo '</div>';

        // Link to Google Maps
        if (!empty($gmb_link)) {
            echo '<a href="' . esc_url($gmb_link) . '" target="_blank" style="display: inline-block; color: #1a73e8; text-decoration: none;">' . __('View on Google Maps', 'sage') . '</a>';
        }

        echo '</div>'; // End business header

        // Reviews section
        echo '<div class="gmb-reviews-section">';
        echo '<h3>' . __('Customer Reviews', 'sage') . '</h3>';

        if (!empty($reviews)) {
            echo '<div class="gmb-reviews-list" style="display: flex; flex-direction: column; gap: 20px;">';

            foreach ($reviews as $review) {
                $this->renderReview($review);
            }

            echo '</div>';
        } else {
            echo '<p style="color: #70757a;">' . __('No reviews available.', 'sage') . '</p>';
        }

        echo '</div>'; // End reviews section

        echo '</div>'; // End container
    }

    private function renderReview($review)
    {
        // Extract review data
        $author_name = isset($review['authorAttribution']['displayName']) ? $review['authorAttribution']['displayName'] : __('Anonymous', 'sage');
        $author_url = isset($review['authorAttribution']['uri']) ? $review['authorAttribution']['uri'] : '';
        $profile_photo = isset($review['authorAttribution']['photoUri']) ? $review['authorAttribution']['photoUri'] : '';
        $rating = isset($review['rating']) ? $review['rating'] : 0;

        // Fix for text that might be an array
        if (isset($review['text'])) {
            $review_text = is_array($review['text']) ? (isset($review['text']['text']) ? $review['text']['text'] : '') : $review['text'];
        } else {
            $review_text = '';
        }

        $review_time = isset($review['relativePublishTimeDescription']) ? $review['relativePublishTimeDescription'] : '';

        echo '<div class="gmb-review" style="padding: 15px; border: 1px solid #e8eaed; border-radius: 8px;">';

        // Review header with author info
        echo '<div class="gmb-review-header" style="display: flex; align-items: center; margin-bottom: 8px;">';

        // Author photo
        echo '<div class="gmb-author-photo" style="margin-right: 12px;">';
        if (!empty($profile_photo)) {
            echo '<img src="' . esc_url($profile_photo) . '" alt="' . esc_attr($author_name) . '" style="width: 40px; height: 40px; border-radius: 50%;">';
        } else {
            echo '<div style="width: 40px; height: 40px; border-radius: 50%; background-color: #dadce0; display: flex; align-items: center; justify-content: center; color: #70757a; font-weight: bold;">' . esc_html(substr($author_name, 0, 1)) . '</div>';
        }
        echo '</div>';

        // Author name and review time
        echo '<div class="gmb-author-info">';
        if (!empty($author_url)) {
            echo '<a href="' . esc_url($author_url) . '" target="_blank" style="font-weight: 500; color: #202124; text-decoration: none;">' . esc_html($author_name) . '</a>';
        } else {
            echo '<span style="font-weight: 500; color: #202124;">' . esc_html($author_name) . '</span>';
        }

        if (!empty($review_time)) {
            echo '<div style="color: #70757a; font-size: 12px;">' . esc_html($review_time) . '</div>';
        }
        echo '</div>';

        echo '</div>'; // End review header

        // Star rating
        if ($rating > 0) {
            echo '<div class="gmb-review-rating" style="color: #fbbc04; margin-bottom: 8px;">';
            for ($i = 1; $i <= 5; $i++) {
                echo $i <= $rating ? '★' : '☆';
            }
            echo '</div>';
        }

        // Review text
        if (!empty($review_text)) {
            echo '<div class="gmb-review-text" style="color: #202124; line-height: 1.5;">' . esc_html($review_text) . '</div>';
        }

        echo '</div>'; // End review
    }

    public function handleClearReviews()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gmb_sync_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        // Delete stored reviews and header
        delete_option('gmb_reviews');
        delete_option('gmb_reviews_header');

        wp_send_json_success([
            'message' => 'Reviews cleared successfully.',
            'status' => 'cleared'
        ]);
    }

    public function refreshReviewsHtml()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gmb_sync_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        ob_start();
        $this->displayReviews();
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public function displayPosts()
    {
        $posts = get_option('gmb_posts', []);

        // Create container even if empty
        echo '<div class="gmb-posts-container" style="max-width: 800px; margin-top: 20px;">';

        // Admin controls section
        if (is_admin()) {
            echo '<div class="gmb-posts-admin-controls" style="margin-bottom: 20px;">';
            echo '<button id="fetch-gmb-posts" class="button button-primary">Fetch Google Posts</button>';

            if (!empty($posts)) {
                echo ' <button id="clear-posts" class="button">Clear Posts</button>';
            }

            echo '<div id="gmb-posts-status" style="margin-top: 10px;"></div>';
            echo '</div>';
        }

        if (empty($posts)) {
            if (is_admin()) {
                echo '<p>No posts available. Use the "Fetch Google Posts" button to retrieve posts.</p>';
                echo '<p>Message for developers: This fetch relies on Merchantor authentication tokens. Please ensure the Merchantor authentication status is configured in Merchantor page. (reauthenticate if needed)</p>';
            }
            echo '</div>'; // Close container
            return;
        }

        $gmb_link = get_option('store_settings', [])['seo']['gmb_link'] ?? '';

        // Posts header
        echo '<div class="gmb-posts-header" style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 4px;">';
        echo '<h2 style="margin-top: 0;">' . esc_html(get_bloginfo('name')) . ' - ' . __('Updates', 'sage') . '</h2>';

        if (!empty($gmb_link)) {
            echo '<a href="' . esc_url($gmb_link) . '" target="_blank" style="display: inline-block; color: #1a73e8; text-decoration: none;">' . __('View on Google Maps', 'sage') . '</a>';
        }

        echo '</div>';

        // Posts section
        echo '<div class="gmb-posts-section">';
        echo '<h3>' . __('Recent Updates', 'sage') . '</h3>';

        echo '<div class="gmb-posts-list" style="display: flex; flex-direction: column; gap: 20px;">';

        foreach ($posts as $post) {
            $this->renderPost($post);
        }

        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    private function renderPost($post)
    {
        // Extract post data - check for both API formats
        $summary = isset($post['summary']) ? $post['summary'] : (isset($post['callToAction']['actionType']) ? $post['callToAction']['actionType'] : '');

        // Handle different date formats from different APIs
        $post_time = '';
        if (isset($post['createTime'])) {
            $post_time = $post['createTime'];
        } elseif (isset($post['createTime']['seconds'])) {
            // Convert timestamp to formatted date
            $timestamp = $post['createTime']['seconds'];
            $post_time = date('c', $timestamp);
        }

        // Format date if available
        $formatted_date = '';
        if (!empty($post_time)) {
            $timestamp = strtotime($post_time);
            $formatted_date = date_i18n(get_option('date_format'), $timestamp);
        }

        // Handle different post types
        $topic_type = isset($post['topicType']) ? $post['topicType'] : '';
        $call_to_action = isset($post['callToAction']) ? $post['callToAction'] : null;
        $event = isset($post['event']) ? $post['event'] : null;
        $offer = isset($post['offer']) ? $post['offer'] : null;

        echo '<div class="gmb-post" style="padding: 15px; border: 1px solid #e8eaed; border-radius: 8px;">';

        // Post header
        echo '<div class="gmb-post-header" style="display: flex; justify-content: space-between; margin-bottom: 12px;">';
        echo '<span style="font-weight: 500; color: #202124;">' . esc_html(get_bloginfo('name')) . '</span>';

        if (!empty($formatted_date)) {
            echo '<span style="color: #70757a; font-size: 14px;">' . esc_html($formatted_date) . '</span>';
        }
        echo '</div>'; // End post header

        // Post topic type badge
        if (!empty($topic_type)) {
            $topic_class = '';
            $topic_text = '';

            switch ($topic_type) {
                case 'OFFER':
                    $topic_class = 'background-color: #e6f4ea; color: #137333;';
                    $topic_text = 'Offer';
                    break;
                case 'EVENT':
                    $topic_class = 'background-color: #e8f0fe; color: #1967d2;';
                    $topic_text = 'Event';
                    break;
                case 'WHAT_IS_NEW':
                    $topic_class = 'background-color: #fef7e0; color: #b06000;';
                    $topic_text = 'What\'s New';
                    break;
                default:
                    $topic_class = 'background-color: #f1f3f4; color: #5f6368;';
                    $topic_text = $topic_type;
            }

            echo '<div class="gmb-post-type" style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; margin-bottom: 10px; ' . $topic_class . '">' . esc_html($topic_text) . '</div>';
        }

        // Post media (if available)
        if (!empty($post['media']) && is_array($post['media'])) {
            echo '<div class="gmb-post-media" style="margin-bottom: 12px;">';
            foreach ($post['media'] as $media) {
                if (isset($media['name'])) {
                    echo '<img src="' . esc_url($media['name']) . '" alt="Post media" style="max-width: 100%; border-radius: 8px;">';
                } elseif (isset($media['googleUrl'])) {
                    echo '<img src="' . esc_url($media['googleUrl']) . '" alt="Post media" style="max-width: 100%; border-radius: 8px;">';
                }
            }
            echo '</div>';
        }

        // Post summary/content
        if (!empty($summary)) {
            echo '<div class="gmb-post-content" style="color: #202124; line-height: 1.5; margin-bottom: 12px;">' . esc_html($summary) . '</div>';
        }

        // Event details
        if ($event) {
            echo '<div class="gmb-post-event" style="margin-bottom: 12px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">';

            if (!empty($event['title'])) {
                echo '<div style="font-weight: 500; margin-bottom: 5px;">' . esc_html($event['title']) . '</div>';
            }

            if (!empty($event['schedule'])) {
                $schedule = $event['schedule'];
                $start_date = '';
                $end_date = '';

                if (!empty($schedule['startDate'])) {
                    $start_date = sprintf(
                        '%04d-%02d-%02d',
                        $schedule['startDate']['year'] ?? 0,
                        $schedule['startDate']['month'] ?? 0,
                        $schedule['startDate']['day'] ?? 0
                    );

                    if (!empty($schedule['startTime'])) {
                        $start_date .= sprintf(
                            ' %02d:%02d',
                            $schedule['startTime']['hours'] ?? 0,
                            $schedule['startTime']['minutes'] ?? 0
                        );
                    }
                }

                if (!empty($schedule['endDate'])) {
                    $end_date = sprintf(
                        '%04d-%02d-%02d',
                        $schedule['endDate']['year'] ?? 0,
                        $schedule['endDate']['month'] ?? 0,
                        $schedule['endDate']['day'] ?? 0
                    );

                    if (!empty($schedule['endTime'])) {
                        $end_date .= sprintf(
                            ' %02d:%02d',
                            $schedule['endTime']['hours'] ?? 0,
                            $schedule['endTime']['minutes'] ?? 0
                        );
                    }
                }

                if (!empty($start_date)) {
                    echo '<div style="color: #5f6368; font-size: 14px;">';
                    echo 'Start: ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($start_date)));
                    echo '</div>';
                }

                if (!empty($end_date)) {
                    echo '<div style="color: #5f6368; font-size: 14px;">';
                    echo 'End: ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($end_date)));
                    echo '</div>';
                }
            }

            echo '</div>';
        }

        // Offer details
        if ($offer) {
            echo '<div class="gmb-post-offer" style="margin-bottom: 12px; padding: 10px; background-color: #e6f4ea; border-radius: 4px;">';

            if (!empty($offer['couponCode'])) {
                echo '<div style="font-weight: 500; margin-bottom: 5px;">Code: ' . esc_html($offer['couponCode']) . '</div>';
            }

            if (!empty($offer['redeemOnlineUrl'])) {
                echo '<div style="margin-bottom: 5px;">';
                echo '<a href="' . esc_url($offer['redeemOnlineUrl']) . '" target="_blank" style="color: #1a73e8; text-decoration: none;">Redeem Online</a>';
                echo '</div>';
            }

            if (!empty($offer['termsConditions'])) {
                echo '<div style="color: #5f6368; font-size: 12px;">';
                echo 'Terms: ' . esc_html($offer['termsConditions']);
                echo '</div>';
            }

            echo '</div>';
        }

        // Call to action button
        if ($call_to_action && !empty($call_to_action['url'])) {
            $action_text = 'Learn More';
            switch ($call_to_action['actionType']) {
                case 'BOOK':
                    $action_text = 'Book';
                    break;
                case 'ORDER':
                    $action_text = 'Order';
                    break;
                case 'SHOP':
                    $action_text = 'Shop';
                    break;
                case 'LEARN_MORE':
                    $action_text = 'Learn More';
                    break;
                case 'SIGN_UP':
                    $action_text = 'Sign Up';
                    break;
                case 'CALL':
                    $action_text = 'Call';
                    break;
            }

            echo '<div class="gmb-post-cta">';
            echo '<a href="' . esc_url($call_to_action['url']) . '" target="_blank" style="display: inline-block; padding: 8px 16px; background-color: #1a73e8; color: white; text-decoration: none; border-radius: 4px; font-weight: 500;">' . esc_html($action_text) . '</a>';
            echo '</div>';
        }

        echo '</div>'; // End post
    }

    public function handleClearPosts()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gmb_sync_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        // Delete stored posts
        delete_option('gmb_posts');

        wp_send_json_success([
            'message' => 'Google Business posts cleared successfully.',
            'status' => 'cleared'
        ]);
    }

    public function refreshPostsHtml()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gmb_sync_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        ob_start();
        $this->displayPosts();
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public function handleFetchPosts()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gmb_sync_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        // Check if Merchantor_Auth class exists
        if (!class_exists('Merchantor_Auth')) {
            wp_send_json_error(['message' => 'Merchantor_Auth class not found. Cannot authenticate with Google.']);
            return;
        }

        try {
            // Get access token from Merchantor_Auth
            $auth = Merchantor_Auth::get_instance();

            if (!method_exists($auth, 'ensure_valid_tokens')) {
                wp_send_json_error(['message' => 'The Merchantor_Auth class does not have the required ensure_valid_tokens method.']);
                return;
            }

            $tokens = $auth->ensure_valid_tokens();

            if (empty($tokens) || empty($tokens['access_token'])) {
                wp_send_json_error(['message' => 'Failed to get valid access token. Please check Merchantor authentication status.']);
                return;
            }

            $access_token = $tokens['access_token'];

            // Get account name - you may need to adjust this based on your setup
            $site_name = get_bloginfo('name');
            $account_name = $this->getAccountName($access_token);

            if (empty($account_name)) {
                wp_send_json_error(['message' => 'Could not find Google Business account. Verify that your Google account has Business Profile permissions.']);
                return;
            }

            // Log successful account retrieval for debugging
            error_log('Successfully retrieved account name: ' . $account_name);

            // Fetch posts for the location
            $locations = $this->getLocations($access_token, $account_name);

            if (empty($locations)) {
                wp_send_json_error(['message' => 'No business locations found for this account.']);
                return;
            }

            // Log successful locations retrieval
            error_log('Successfully retrieved locations. Found ' . count($locations) . ' locations.');

            // Use the first location
            $location = $locations[0]['name'];

            // Now fetch posts
            $posts = $this->fetchBusinessPosts($access_token, $location);

            if (empty($posts)) {
                wp_send_json_success([
                    'message' => 'No posts found for this business.',
                    'status' => 'no_posts'
                ]);
                return;
            }

            // Store the posts
            update_option('gmb_posts', $posts);
            update_option('gmb_posts_updated', true);
            update_option('gmb_posts_last_fetch', time());

            wp_send_json_success([
                'message' => 'Google Business posts successfully fetched!',
                'count' => count($posts),
                'status' => 'success'
            ]);

        } catch (Exception $e) {
            error_log('Google Business Post Fetch Error: ' . $e->getMessage());
            error_log('Error trace: ' . $e->getTraceAsString());

            wp_send_json_error([
                'message' => 'Error fetching posts: ' . $e->getMessage(),
                'status' => 'error',
                'details' => $e->getMessage()
            ]);
        } catch (\Error $e) {
            // Catch PHP 7+ errors as well
            error_log('Google Business Post Fetch Error (PHP Error): ' . $e->getMessage());
            error_log('Error trace: ' . $e->getTraceAsString());

            wp_send_json_error([
                'message' => 'PHP Error: ' . $e->getMessage(),
                'status' => 'error',
                'details' => $e->getMessage()
            ]);
        }
    }

    private function getAccountName($access_token)
    {
        // Call the My Business Account API to get the account name
        $url = 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts';

        error_log("Fetching account info from URL: " . $url);

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30 // Increase timeout to 30 seconds
        ]);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("Google Business Account API Error: " . $error_message);
            throw new Exception("API request failed: " . $error_message);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        error_log("Account API Response status: " . $status_code);

        if ($status_code !== 200) {
            error_log("Account API Error response body: " . $body);
            throw new Exception("Account API returned error status: " . $status_code . ". Details: " . $body);
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON parse error in getAccountName: " . json_last_error_msg());
            throw new Exception("Failed to parse account API response: " . json_last_error_msg());
        }

        if (empty($data['accounts']) || !is_array($data['accounts'])) {
            error_log("No accounts found in response: " . substr(json_encode($data), 0, 1000));
            throw new Exception("No Google Business accounts found for this token.");
        }

        // Log account names for debugging
        foreach ($data['accounts'] as $index => $account) {
            error_log("Account {$index}: " . ($account['name'] ?? 'No name') . " - " . ($account['accountName'] ?? 'No accountName'));
        }

        // Return the first account name
        return $data['accounts'][0]['name'];
    }

    private function fetchBusinessPosts($access_token, $location)
    {
        // Now fetch posts for this location
        $url = "https://mybusinessposts.googleapis.com/v1/{$location}/localPosts";

        error_log("Fetching posts from URL: " . $url);

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30 // Increase timeout to 30 seconds
        ]);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("Google Business Post API Error: " . $error_message);
            throw new Exception("API request failed: " . $error_message);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        error_log("API Response status: " . $status_code);

        if ($status_code !== 200) {
            error_log("API Error response body: " . $body);
            throw new Exception("API returned error status: " . $status_code . ". Details: " . $body);
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON parse error: " . json_last_error_msg());
            throw new Exception("Failed to parse API response: " . json_last_error_msg());
        }

        error_log("API Response parsed successfully.");

        // The API might return different response structures
        if (isset($data['localPosts'])) {
            error_log("Found localPosts key in response with " . count($data['localPosts']) . " posts.");
            return $data['localPosts'];
        } elseif (isset($data['posts'])) {
            error_log("Found posts key in response with " . count($data['posts']) . " posts.");
            return $data['posts'];
        } else {
            // Log the first 1000 characters of response to see what we're getting
            error_log("Unexpected API response format. First 1000 chars: " . substr(json_encode($data), 0, 1000));
            return [];
        }
    }

    // START [GMB-SYNC] Fetch Parent Location (Mall) from Business Profile API
    /**
     * Best-effort fetch of the parent location (mall/shopping center) via
     * the Google Business Profile API's relationshipData field.
     *
     * Non-blocking: silently returns [] if Merchantor_Auth is unavailable,
     * tokens are expired, or the location has no parent.
     *
     * @param array &$options  store_settings reference (will be updated in-place + saved)
     * @param array $place_data  Place data from the Places API (used for location matching)
     * @return array  List of update messages, or empty array
     */
    private function fetchParentLocationMall(&$options, $place_data)
    {
        $updates = [];

        // 1. Check if Merchantor_Auth is available
        if (!class_exists('Merchantor_Auth')) {
            return $updates;
        }

        try {
            $auth = Merchantor_Auth::get_instance();
            if (!method_exists($auth, 'ensure_valid_tokens')) {
                return $updates;
            }

            $tokens = $auth->ensure_valid_tokens();
            if (empty($tokens) || empty($tokens['access_token'])) {
                return $updates;
            }

            $access_token = $tokens['access_token'];

            // 2. Get account name and locations
            $account_name = $this->getAccountName($access_token);
            if (empty($account_name)) {
                return $updates;
            }

            // 3. Fetch locations with relationshipData in readMask
            $url = "https://mybusinessbusinessinformation.googleapis.com/v1/{$account_name}/locations";
            $url = add_query_arg('readMask', 'name,title,relationshipData', $url);

            $response = wp_remote_get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 15
            ]);

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return $updates;
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (empty($data['locations']) || !is_array($data['locations'])) {
                return $updates;
            }

            // 4. Find the matching location (match by title or Place ID)
            $store_name = $options['seo']['gmb_name'] ?? '';
            $matched_location = null;
            foreach ($data['locations'] as $loc) {
                $loc_title = $loc['title'] ?? $loc['locationName'] ?? '';
                if (!empty($store_name) && stripos($loc_title, $store_name) !== false) {
                    $matched_location = $loc;
                    break;
                }
            }

            // Fallback: use first location if only one exists
            if (!$matched_location && count($data['locations']) === 1) {
                $matched_location = $data['locations'][0];
            }

            if (empty($matched_location)) {
                return $updates;
            }

            // 5. Extract parent location (mall) from relationshipData
            $parent_ref = $matched_location['relationshipData']['parentLocation'] ?? null;
            if (empty($parent_ref)) {
                return $updates;
            }

            // The parentLocation contains a reference like "locations/{location_id}"
            // We need to GET the parent location to retrieve its title (mall name)
            $parent_location_name = $parent_ref['placeId'] ?? $parent_ref['relationshipType'] ?? null;
            $parent_resource = $parent_ref['name'] ?? null; // e.g. "locations/12345"

            if (!empty($parent_resource)) {
                // Fetch the parent location details to get the mall name
                $parent_url = "https://mybusinessbusinessinformation.googleapis.com/v1/{$parent_resource}";
                $parent_url = add_query_arg('readMask', 'title', $parent_url);

                $parent_response = wp_remote_get($parent_url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $access_token,
                        'Content-Type' => 'application/json'
                    ],
                    'timeout' => 10
                ]);

                if (!is_wp_error($parent_response) && wp_remote_retrieve_response_code($parent_response) === 200) {
                    $parent_data = json_decode(wp_remote_retrieve_body($parent_response), true);
                    $mall_name = $parent_data['title'] ?? $parent_data['locationName'] ?? '';

                    if (!empty($mall_name)) {
                        $old_mall = $options['seo']['mall'] ?? '';
                        if ($old_mall !== $mall_name) {
                            $options['seo']['mall'] = sanitize_text_field($mall_name);
                            update_option('store_settings', $options);
                            $updates[] = sprintf('Mall/Shopping Center updated to "%s" (from Business Profile API)', $mall_name);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Non-blocking: log and continue
            error_log('GMB_MALL_FETCH_INFO: ' . $e->getMessage());
        }

        return $updates;
    }
    // END [GMB-SYNC] Fetch Parent Location (Mall)

    private function getLocations($access_token, $account_name)
    {
        $url = "https://mybusinessbusinessinformation.googleapis.com/v1/{$account_name}/locations";

        error_log("Fetching locations from URL: " . $url);

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30 // Increase timeout to 30 seconds
        ]);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("Google Business Locations API Error: " . $error_message);
            throw new Exception("API request failed: " . $error_message);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        error_log("Locations API Response status: " . $status_code);

        if ($status_code !== 200) {
            error_log("Locations API Error response body: " . $body);
            throw new Exception("Locations API returned error status: " . $status_code . ". Details: " . $body);
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON parse error in getLocations: " . json_last_error_msg());
            throw new Exception("Failed to parse locations API response: " . json_last_error_msg());
        }

        if (empty($data['locations']) || !is_array($data['locations'])) {
            error_log("No locations found in response: " . substr(json_encode($data), 0, 1000));
            throw new Exception("No Google Business locations found for this account.");
        }

        // Log locations for debugging
        foreach ($data['locations'] as $index => $location) {
            error_log("Location {$index}: " . ($location['name'] ?? 'No name') . " - " .
                ($location['title'] ?? $location['locationName'] ?? 'No location name'));
        }

        return $data['locations'];
    }
}