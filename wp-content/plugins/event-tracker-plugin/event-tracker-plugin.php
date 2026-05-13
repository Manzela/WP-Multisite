<?php

/*
Plugin Name: Event Tracker Plugin
Description: A plugin to track events and send them to Google Cloud Storage for analytics.
Version: 1.0
Author: Sharon Chen
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EVENT_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EVENT_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader
require_once EVENT_TRACKER_PLUGIN_DIR . 'vendor/autoload.php';

// Include plugin classes
require_once EVENT_TRACKER_PLUGIN_DIR . 'includes/class-gcs-client.php';
require_once EVENT_TRACKER_PLUGIN_DIR . 'includes/class-growth-dashboard-client.php';
require_once EVENT_TRACKER_PLUGIN_DIR . 'includes/class-event-processor.php';
require_once EVENT_TRACKER_PLUGIN_DIR . 'includes/class-visitor-manager.php';
require_once EVENT_TRACKER_PLUGIN_DIR . 'includes/class-foottracking-admin.php';
require_once EVENT_TRACKER_PLUGIN_DIR . 'includes/class-foottracking-table-list.php';
require_once EVENT_TRACKER_PLUGIN_DIR . 'includes/class-consent-manager.php';
require_once EVENT_TRACKER_PLUGIN_DIR . 'includes/class-gdpr-data-manager.php';
require_once EVENT_TRACKER_PLUGIN_DIR . 'includes/class-cookie-registry.php';
require_once EVENT_TRACKER_PLUGIN_DIR . 'includes/class-third-party-script-manager.php';

// Initialize the plugin
function event_tracker_init() {
    new EventTracker\VisitorManager();
    new EventTracker\FoottrackingAdmin();
    new EventTracker\ConsentManager();
    new EventTracker\CookieRegistry();
    new EventTracker\ThirdPartyScriptManager();
}
add_action('plugins_loaded', 'event_tracker_init');

// Activation hook for database tables
function event_tracker_activate_plugin() {
    $visitor_manager = new EventTracker\VisitorManager();
    $visitor_manager->create_foottracking_table();
}
register_activation_hook(__FILE__, 'event_tracker_activate_plugin');

// Enqueue scripts and styles
function event_tracker_enqueue_scripts() {
    // Enqueue visitor tracking script
    wp_enqueue_script(
        'visitor-tracking-script',
        EVENT_TRACKER_PLUGIN_URL . 'js/visitor-tracking.js',
        array('jquery'),
        '1.0.0',
        true
    );

    // Enqueue referral tracking script
    wp_enqueue_script(
        'referral-tracker-script',
        EVENT_TRACKER_PLUGIN_URL . 'js/referral-tracker.js',
        array('jquery'),
        '1.0.0',
        true
    );

    // Enqueue our tracking script (consent manager is loaded separately by ConsentManager class)
    wp_enqueue_script(
        'event-tracker-script',
        EVENT_TRACKER_PLUGIN_URL . 'js/event-tracker.js',
        array('jquery', 'visitor-tracking-script', 'referral-tracker-script'),
        '1.0.0',
        true
    );

    // Enqueue GDPR controls script
    wp_enqueue_script(
        'gdpr-controls-script',
        EVENT_TRACKER_PLUGIN_URL . 'js/gdpr-controls.js',
        array('jquery', 'event-tracker-script'),
        '1.0.0',
        true
    );

    // Pass data to JavaScript
    $user = wp_get_current_user();
    $site_id = get_current_blog_id();
    $store_name = get_bloginfo('name', 'display');

    wp_localize_script('event-tracker-script', 'eventTrackerData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('event_tracker_nonce'),
        'consent_nonce' => wp_create_nonce('et_consent_nonce'),
        'user_email' => ($user && $user->exists()) ? $user->user_email : '',
        'user_id' => ($user && $user->exists()) ? $user->ID : null,
        'site_id' => $site_id,
        'store_name' => $store_name
    ));
}
add_action('wp_enqueue_scripts', 'event_tracker_enqueue_scripts');

// AJAX handlers for event tracking
add_action('wp_ajax_track_event', 'event_tracker_handle_ajax');
add_action('wp_ajax_nopriv_track_event', 'event_tracker_handle_ajax');

// AJAX handlers for consent management
add_action('wp_ajax_et_save_consent', 'event_tracker_consent_ajax_handler');
add_action('wp_ajax_nopriv_et_save_consent', 'event_tracker_consent_ajax_handler');

// GDPR endpoints
add_action('wp_ajax_et_gdpr_access', 'event_tracker_gdpr_access_handler');
add_action('wp_ajax_nopriv_et_gdpr_access', 'event_tracker_gdpr_access_handler');
add_action('wp_ajax_et_gdpr_withdraw', 'event_tracker_gdpr_withdraw_handler');
add_action('wp_ajax_nopriv_et_gdpr_withdraw', 'event_tracker_gdpr_withdraw_handler');
add_action('wp_ajax_et_gdpr_delete', 'event_tracker_gdpr_delete_handler');
add_action('wp_ajax_nopriv_et_gdpr_delete', 'event_tracker_gdpr_delete_handler');

function event_tracker_handle_ajax() {
    try {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'event_tracker_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Get event data
        $event_data = json_decode(stripslashes($_POST['event_data']), true);
        
        if (!$event_data) {
            wp_send_json_error('Invalid event data - JSON decode failed');
            return;
        }

        // Process the event
        $processor = new EventTracker\EventProcessor();
        $result = $processor->process_event($event_data);

        if ($result) {
            wp_send_json_success('Event tracked successfully');
        } else {
            wp_send_json_error('Failed to track event - processor returned false');
        }
    } catch (Exception $e) {
        // Capture the actual error and send it back
        wp_send_json_error('AJAX Exception: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    } catch (Error $e) {
        // Catch PHP fatal errors too
        wp_send_json_error('AJAX Fatal Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    }
}

function event_tracker_consent_ajax_handler() {
    $consent_manager = new EventTracker\ConsentManager();
    $consent_manager->save_consent();
}

// GDPR Data Access Handler
function event_tracker_gdpr_access_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'et_consent_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $visitor_id = sanitize_text_field($_POST['visitor_id']);
    if (empty($visitor_id)) {
        wp_send_json_error('Invalid visitor ID');
        return;
    }
    
    try {
        $gdpr_manager = new EventTracker\GDPRDataManager();
        $user_data = $gdpr_manager->get_user_data($visitor_id);
        
        wp_send_json_success([
            'message' => 'Data access request processed successfully.',
            'data' => $user_data
        ]);
    } catch (Exception $e) {
        error_log('GDPR Access Error: ' . $e->getMessage());
        wp_send_json_error('Failed to process data access request');
    }
}

// GDPR Data Deletion Handler
function event_tracker_gdpr_delete_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'et_consent_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $visitor_id = sanitize_text_field($_POST['visitor_id']);
    if (empty($visitor_id)) {
        wp_send_json_error('Invalid visitor ID');
        return;
    }
    
    try {
        $gdpr_manager = new EventTracker\GDPRDataManager();
        $result = $gdpr_manager->delete_user_data($visitor_id);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error('Failed to process deletion request: ' . ($result['error'] ?? 'Unknown error'));
        }
    } catch (Exception $e) {
        error_log('GDPR Deletion Error: ' . $e->getMessage());
        wp_send_json_error('Failed to process data deletion request');
    }
}

// GDPR Consent Withdrawal Handler
function event_tracker_gdpr_withdraw_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'et_consent_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $visitor_id = sanitize_text_field($_POST['visitor_id']);
    if (empty($visitor_id)) {
        wp_send_json_error('Invalid visitor ID');
        return;
    }
    
    try {
        $gdpr_manager = new EventTracker\GDPRDataManager();
        $result = $gdpr_manager->withdraw_consent($visitor_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'Consent withdrawn successfully. All tracking has been stopped.'
            ]);
        } else {
            wp_send_json_error('Failed to withdraw consent');
        }
    } catch (Exception $e) {
        error_log('GDPR Withdrawal Error: ' . $e->getMessage());
        wp_send_json_error('Failed to process consent withdrawal');
    }
}
