<?php

namespace EventTracker;

class VisitorManager {
    
    public function __construct() {
        add_action('wp_ajax_vtp_track_page_visit', [$this, 'track_page_visit']);
        add_action('wp_ajax_nopriv_vtp_track_page_visit', [$this, 'track_page_visit']);
    }
    
    public function track_page_visit() {
        check_ajax_referer('event_tracker_nonce', 'nonce');
        
        if (!is_user_logged_in() || !current_user_can('administrator')) {
            $visitor_data = $this->prepare_visitor_data();
            $this->save_to_foottracking_table($visitor_data);
        }
        
        wp_send_json_success('Page visit tracked successfully.');
    }
    
    private function prepare_visitor_data() {
        $visitor_id = sanitize_text_field($_POST['visitor_id'] ?? '');
        $current_page = sanitize_text_field(urldecode($_POST['current_page'] ?? $_SERVER['REQUEST_URI']));
        $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
        $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
        $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
        
        $site_id = get_current_blog_id();
        $site_details = get_blog_details($site_id);
        $domain = $site_details->domain . $site_details->path;
        $store_name = get_bloginfo('name', 'display');
        
        $user = wp_get_current_user();
        $email = ($user && $user->exists()) ? sanitize_email($user->user_email) : 'No email';
        
        $location = wp_json_encode(['lat' => $latitude, 'lng' => $longitude]);
        $date_time = current_time('mysql');
        
        return [
            'site_id' => $site_id,
            'domain' => $domain,
            'store_name' => $store_name,
            'visitor_id' => $visitor_id,
            'email' => $email,
            'rawdata' => $user_agent,
            'pagestamp' => $current_page,
            'location' => $location,
            'dateTime' => $date_time,
            'LdateTime' => $date_time,
        ];
    }
    
    private function save_to_foottracking_table($visitor_data) {
        global $wpdb;
        $table_foottracking = $wpdb->base_prefix . 'foottracking';
        
        $insert_result = $wpdb->insert(
            $table_foottracking,
            $visitor_data,
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($insert_result === false) {
            error_log('Error inserting foottracking data: ' . $wpdb->last_error);
        }
        
        return $insert_result !== false;
    }
    
    public function create_foottracking_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_foottracking = $wpdb->base_prefix . 'foottracking';
        
        $sql_foottracking = "
            CREATE TABLE IF NOT EXISTS {$table_foottracking} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                site_id BIGINT(20) UNSIGNED NOT NULL,
                domain VARCHAR(255) NOT NULL,
                store_name VARCHAR(255) NOT NULL,
                visitor_id VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                rawdata TEXT NOT NULL,
                pagestamp TEXT NOT NULL,
                location TEXT NOT NULL,
                dateTime DATETIME NOT NULL,
                LdateTime DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY site_id (site_id),
                KEY visitor_id (visitor_id),
                KEY email (email),
                KEY dateTime (dateTime)
            ) {$charset_collate};
        ";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_foottracking);
        
        if ($wpdb->last_error) {
            error_log('DB Error during table creation: ' . $wpdb->last_error);
        }
    }
    
    public function get_cookie_domain() {
        $host = $_SERVER['HTTP_HOST'];
        $host_parts = explode('.', $host);
        
        if (count($host_parts) > 2) {
            array_shift($host_parts);
        }
        
        return '.' . implode('.', $host_parts);
    }
} 