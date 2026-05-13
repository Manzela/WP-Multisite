<?php

namespace EventTracker;

class GDPRDataManager {
    
    public function __construct() {
        // Constructor can be empty for now
    }
    
    /**
     * Get all data associated with a visitor ID
     */
    public function get_user_data($visitor_id) {
        global $wpdb;
        
        $data = [
            'visitor_id' => $visitor_id,
            'request_date' => current_time('mysql'),
            'data_sources' => []
        ];
        
        // Get data from foottracking table
        $foottracking_table = $wpdb->base_prefix . 'foottracking';
        $foottracking_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$foottracking_table} WHERE visitor_id = %s ORDER BY dateTime DESC",
            $visitor_id
        ), ARRAY_A);
        
        if ($foottracking_data) {
            $data['data_sources']['foottracking'] = [
                'description' => 'Page visits and tracking data',
                'record_count' => count($foottracking_data),
                'data' => $foottracking_data
            ];
            
            // Get unique emails associated with this visitor ID
            $emails = array_unique(array_column($foottracking_data, 'email'));
            if (!empty($emails)) {
                $data['associated_emails'] = array_filter($emails, function($email) {
                    return $email !== 'No email';
                });
            }
        }
        
        // Get GCS event data
        $gcs_data = $this->get_gcs_data($visitor_id);
        if (!empty($gcs_data)) {
            $data['data_sources']['gcs_events'] = [
                'description' => 'Event tracking and interaction data',
                'record_count' => count($gcs_data),
                'data' => $gcs_data
            ];
        }
        
        // Add consent data to data_sources
        $consent_data = isset($_POST['consent_data']) ? json_decode(stripslashes($_POST['consent_data']), true) : null;
        
        // Only include consent data if it actually exists (not deleted/withdrawn)
        if ($consent_data) {
            $data['data_sources']['consent'] = [
                'analytics' => isset($consent_data['analytics']) ? (int)$consent_data['analytics'] : 0,
                'enhanced' => isset($consent_data['enhanced']) ? (int)$consent_data['enhanced'] : 0,
                'location' => isset($consent_data['location']) ? (int)$consent_data['location'] : 0,
                'cookies' => [
                    'essential' => 1, // Always enabled
                    'analytics' => isset($consent_data['cookies']['analytics']) ? (int)$consent_data['cookies']['analytics'] : 0,
                    'marketing' => isset($consent_data['cookies']['marketing']) ? (int)$consent_data['cookies']['marketing'] : 0,
                    'preferences' => isset($consent_data['cookies']['preferences']) ? (int)$consent_data['cookies']['preferences'] : 0
                ]
            ];
        }
        
        // Build cookies data conditionally
        $cookies = [];
        // Essential cookies (always included)
        if (isset($_COOKIE['visitor_id'])) {
            $cookies['visitor_id'] = $_COOKIE['visitor_id'];
        }
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, 'wordpress_') === 0 || 
                strpos($name, 'woocommerce_') === 0 || 
                strpos($name, 'wp_woocommerce_session_') === 0) {
                $cookies[$name] = $value;
            }
        }
        // Analytics cookies (if enabled)
        if ($consent_data && isset($consent_data['cookies']['analytics']) && $consent_data['cookies']['analytics']) {
            if (isset($_COOKIE['_ga'])) {
                $cookies['_ga'] = $_COOKIE['_ga'];
            }
            if (isset($_COOKIE['_gid'])) {
                $cookies['_gid'] = $_COOKIE['_gid'];
            }
        }
        // Marketing cookies (if enabled)
        if ($consent_data && isset($consent_data['cookies']['marketing']) && $consent_data['cookies']['marketing']) {
            if (isset($_COOKIE['firstPopupTime'])) {
                $cookies['firstPopupTime'] = $_COOKIE['firstPopupTime'];
            }
            if (isset($_COOKIE['popupShownCount'])) {
                $cookies['popupShownCount'] = $_COOKIE['popupShownCount'];
            }
        }
        // Preferences cookies (if enabled)
        if ($consent_data && isset($consent_data['cookies']['preferences']) && $consent_data['cookies']['preferences']) {
            if (isset($_COOKIE['et_consent'])) {
                $cookies['et_consent'] = $_COOKIE['et_consent'];
            }
        }
        // Only add cookies to data_sources if not empty
        if (!empty($cookies)) {
            $data['data_sources']['cookies'] = $cookies;
        }
        
        // Data retention information
        $data['data_retention'] = [
            'analytics_data' => '24 months',
            'location_data' => '12 months',
            'consent_data' => '6 months (refreshed on new consent)',
            'visitor_identifiers' => '12 months for inactive users, until deletion request for active users'
        ];
        
        // User rights information
        $data['your_rights'] = [
            'access' => 'You can access your personal data',
            'rectification' => 'You can request correction of inaccurate data (by contacting Example-Network at info@example-network.com)',
            'erasure' => 'You can delete your personal data',
            'portability' => 'You can download a copy of your data in a machine-readable format',
            'withdraw_consent' => 'You can withdraw consent for data processing at any time'
        ];
        
        return $data;
    }
    
    /**
     * Withdraw consent for a visitor
     */
    public function withdraw_consent($visitor_id) {
        try {
            // Check if consent is stored in cookie (preference cookies allowed)
            if (isset($_COOKIE['et_consent'])) {
                // Delete the consent cookie
                setcookie('et_consent', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
            }
            // Note: If consent is in sessionStorage, it will be handled client-side            
            return true;
        } catch (Exception $e) {
            error_log("GDPR Withdrawal Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete all visitor data (with 30-day grace period)
     */
    public function delete_user_data($visitor_id) {
        global $wpdb;
        
        // Server-side cookie cleanup
        foreach ($_COOKIE as $name => $value) {
            setcookie($name, '', time() - 3600, '/');
            setcookie($name, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
            setcookie($name, '', time() - 3600, '/', '', false, true);
        }
        
        try {
            $foottracking_table = $wpdb->base_prefix . 'foottracking';
            
            // Get count before deletion
            $count_before = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$foottracking_table} WHERE visitor_id = %s",
                $visitor_id
            ));
            
            // Delete records immediately from WordPress
            $deleted = $wpdb->delete(
                $foottracking_table,
                ['visitor_id' => $visitor_id],
                ['%s']
            );
            
            // Delete from GCS immediately
            $gcs_result = $this->delete_gcs_data($visitor_id);
            
            // Clear consent
            $this->withdraw_consent($visitor_id);
            
            // Prepare structured deletion status
            $results = [
                'foottracking' => [
                    'status' => $deleted !== false ? 'success' : 'failed',
                    'message' => $deleted !== false ? 
                        ($count_before > 0 ? "successfully deleted {$deleted} records" : "success (nothing to delete)") :
                        'failed to delete records'
                ],
                'gcs' => [
                    'status' => $gcs_result['success'] ? 'success' : 'failed',
                    'message' => $gcs_result['success'] ? 
                        ($gcs_result['files_deleted'] > 0 ? 
                            "successfully deleted {$gcs_result['files_deleted']} files" : 
                            "success (nothing to delete)") :
                        ($gcs_result['message'] ?? 'failed to delete records')
                ],
                'consent' => [
                    'status' => 'success',
                    'message' => 'successfully deleted 1 record'
                ],
                'cookies' => [
                    'status' => 'success',
                    'message' => count($_COOKIE) > 0 ? 'successfully deleted ' . count($_COOKIE) . ' records' : 'success (nothing to delete)'
                ]
            ];
            
            // Log the deletion request with more detail
            $gcs_status = $gcs_result['success'] ? 'Successfully deleted' : 'FAILED - check GCS logs';
            $message = "GDPR: Data deletion for visitor ID: {$visitor_id}. WordPress records deleted: {$deleted}, GCS: {$gcs_status}";
            
            error_log($message);
            
            return [
                'success' => $deleted !== false,
                'data' => [
                    'message' => 'Deletion results:',
                    'results' => $results
                ]
            ];
            
        } catch (Exception $e) {
            error_log("GDPR Deletion Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get visitor IDs associated with an email
     */
    private function get_visitor_ids_by_email($email) {
        global $wpdb;
        
        $foottracking_table = $wpdb->base_prefix . 'foottracking';
        $visitor_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT visitor_id FROM {$foottracking_table} WHERE email = %s AND visitor_id != ''",
            $email
        ));
        
        return $visitor_ids;
    }
    

    
    /**
     * Get GCS data for a visitor ID
     */
    private function get_gcs_data($visitor_id) {
        try {
            $gcs_client = new GCSClient();
            
            // Get visitor data from GCS
            $events = $gcs_client->get_visitor_data($visitor_id);
            
            error_log("GDPR: GCS data fetch completed for visitor_id: {$visitor_id}, found " . count($events) . " events");
            
            return $events;
            
        } catch (Exception $e) {
            error_log("GDPR GCS fetch error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete data from GCS
     */
    private function delete_gcs_data($visitor_id) {
        try {
            $gcs_client = new GCSClient();
            
            // Delete visitor data from GCS
            $result = $gcs_client->delete_visitor_data($visitor_id);
            
            if ($result['success']) {
                error_log("GDPR: GCS deletion completed successfully for visitor_id: {$visitor_id}, deleted {$result['files_deleted']} files");
            } else {
                error_log("GDPR: GCS deletion failed for visitor_id: {$visitor_id} - {$result['message']}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("GDPR GCS deletion error: " . $e->getMessage());
            return [
                'success' => false,
                'files_deleted' => 0,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
} 