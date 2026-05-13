<?php

namespace EventTracker;

class EventProcessor {
    
    private $gcs_client;
    private $growth_client;
    
    public function __construct() {
        $this->gcs_client = new GCSClient();        
        try {
            $this->growth_client = new GrowthDashboardClient();
        } catch (\Exception $e) {
            $this->growth_client = null;
            error_log('Event Tracker: Growth Dashboard client initialization failed: ' . $e->getMessage());
        }
    }
    
    public function process_event($event_data) {
        // Basic validation
        if (!$this->validate_event($event_data)) {
            error_log('Event Tracker: Event validation failed');
            return false;
        }
        
        // Clean and prepare data
        $cleaned_data = $this->clean_event_data($event_data);
        
        // Send to GCS
        $gcs_response = $this->gcs_client->insert_event($cleaned_data);
        if (!($gcs_response['success'] ?? false)) {
            error_log('Event Tracker: GCS insert failed');
        }
        
        // Send to Growth Dashboard database (if available)
        if ($this->growth_client) {
            $growth_response = $this->growth_client->insert_event($cleaned_data);
        } else {
            $growth_response = [
                'success' => false,
                'message' => 'Growth Dashboard client unavailable',
            ];
        }
        if (!($growth_response['success'] ?? false)) {
            error_log('Event Tracker: Growth Dashboard database insert failed');
        }
        
        return [
            'success' => ($gcs_response['success'] ?? false) && ($growth_response['success'] ?? false),
            'gcs_response' => $gcs_response,
            'growth_response' => $growth_response
        ];
    }
    
    private function validate_event($event_data) {
        // Check required fields
        if (empty($event_data['event_name'])) {
            return false;
        }
        
        if (empty($event_data['timestamp'])) {
            return false;
        }
        
        if (empty($event_data['visitor_id'])) {
            return false;
        }
        
        if (empty($event_data['domain'])) {
            return false;
        }
        
        if (empty($event_data['pagestamp'])) {
            return false;
        }
        
        return true;
    }
    
    private function clean_event_data($event_data) {
        return [
            'event_id' => !empty($event_data['event_id']) ? sanitize_text_field($event_data['event_id']) : uniqid('event_', true),
            'event_name' => sanitize_text_field($event_data['event_name']),
            'timestamp' => $this->format_timestamp($event_data['timestamp']),
            'visitor_id' => !empty($event_data['visitor_id']) ? sanitize_text_field($event_data['visitor_id']) : 'unknown',
            'session_id' => !empty($event_data['session_id']) ? sanitize_text_field($event_data['session_id']) : null,
            'site_id' => !empty($event_data['site_id']) ? intval($event_data['site_id']) : get_current_blog_id(),
            'domain' => sanitize_text_field($event_data['domain']),
            'subdomain' => !empty($event_data['subdomain']) ? sanitize_text_field($event_data['subdomain']) : '',
            'store_name' => !empty($event_data['store_name']) ? sanitize_text_field($event_data['store_name']) : get_bloginfo('name', 'display'),
            'pagestamp' => !empty($event_data['pagestamp']) ? sanitize_text_field($event_data['pagestamp']) : '/',
            'latitude' => !empty($event_data['latitude']) ? floatval($event_data['latitude']) : null,
            'longitude' => !empty($event_data['longitude']) ? floatval($event_data['longitude']) : null,
            'referrer' => !empty($event_data['referrer']) ? sanitize_text_field($event_data['referrer']) : '',
            'referral_source' => !empty($event_data['referral_source']) ? sanitize_text_field($event_data['referral_source']) : null,
            'search_engine' => !empty($event_data['search_engine']) ? sanitize_text_field($event_data['search_engine']) : null,
            'utm_source' => !empty($event_data['utm_source']) ? sanitize_text_field($event_data['utm_source']) : null,
            'utm_medium' => !empty($event_data['utm_medium']) ? sanitize_text_field($event_data['utm_medium']) : null,
            'utm_campaign' => !empty($event_data['utm_campaign']) ? sanitize_text_field($event_data['utm_campaign']) : null,
            'utm_term' => !empty($event_data['utm_term']) ? sanitize_text_field($event_data['utm_term']) : null,
            'utm_content' => !empty($event_data['utm_content']) ? sanitize_text_field($event_data['utm_content']) : null,
            'user_agent' => !empty($event_data['user_agent']) ? sanitize_text_field($event_data['user_agent']) : sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'),
            'user_email' => !empty($event_data['user_email']) ? sanitize_email($event_data['user_email']) : null,
            'user_id' => !empty($event_data['user_id']) ? intval($event_data['user_id']) : null,
            'product_properties' => !empty($event_data['product_properties']) ? $event_data['product_properties'] : [],
            'custom_properties' => !empty($event_data['custom_properties']) ? $event_data['custom_properties'] : [],
            'raw_data' => !empty($event_data['raw_data']) ? sanitize_text_field($event_data['raw_data']) : null
        ];
    }
    
    private function format_timestamp($timestamp) {
        // Convert JavaScript ISO timestamp to standard timestamp format
        try {
            $date = new \DateTime($timestamp);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            error_log('Event Tracker: Invalid timestamp format: ' . $timestamp);
            return date('Y-m-d H:i:s'); // fallback to current time
        }
    }
} 