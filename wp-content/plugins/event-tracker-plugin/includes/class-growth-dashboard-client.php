<?php

namespace EventTracker;

use PDO;
use PDOException;
use Exception;

class GrowthDashboardClient {
    
    /**
     * @var PDO
     */
    private $connection;
    
    public function __construct($credentials = null) {
        if ($credentials === null) {
            $credentials = $this->get_credentials_from_settings();
        }
        
        if (!$credentials) {
            throw new Exception('Growth Dashboard DB credentials not found.');
        }
        
        $this->connection = $this->initialize_connection($credentials);
    }
    
    /**
     * Insert an event into the Growth Dashboard database.
     */
    public function insert_event(array $event_data) {
        try {
            if (!$this->is_real_event($event_data)) {
                return [
                    'success' => true,
                    'message' => 'Event skipped by Growth Dashboard filter.',
                    'details' => 'Filtered by is_real_event check.'
                ];
            }
            
            $payload = $this->map_event_to_columns($event_data);
            $columns = array_keys($payload);
            $escaped_columns = array_map(
                function ($column) {
                    return '`' . $column . '`';
                },
                $columns
            );
            $placeholders = array_map(
                function ($column) {
                    return ':' . $column;
                },
                $columns
            );
            
            $sql = sprintf(
                'INSERT INTO event_tracker (%s) VALUES (%s)',
                implode(', ', $escaped_columns),
                implode(', ', $placeholders)
            );
            
            $statement = $this->connection->prepare($sql);
            
            foreach ($payload as $column => $value) {
                if ($value === null) {
                    $statement->bindValue(':' . $column, null, PDO::PARAM_NULL);
                    continue;
                }
                
                $statement->bindValue(':' . $column, $value);
            }
            
            $statement->execute();
            
            return [
                'success' => true,
                'message' => 'Event inserted into Growth Dashboard database.'
            ];
            
        } catch (PDOException $e) {
            error_log('Event Tracker: Growth DB insert error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to insert event into Growth Dashboard database.',
                'details' => $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log('Event Tracker: Growth DB unexpected error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unexpected error while inserting event into Growth Dashboard database.',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Determine if the event should be stored in the Growth Dashboard database.
     */
    private function is_real_event(array $event_data) {
        $domain = strtolower($event_data['domain'] ?? '');
        $referral_source = strtolower($event_data['referral_source'] ?? '');
        
        // Group 1: Filter out known testing domains
        $testing_domains = [
            'multisitenetwork.test',
            'example-staging-api.shop',
            'example.com',
            'testsite.com',
        ];
        
        foreach ($testing_domains as $pattern) {
            if ($pattern !== '' && strpos($domain, $pattern) !== false) {
                return false;
            }
        }
        
        // Group 2: Filter out known internal referral sources
        $internal_referrals = [
            'example-tenant-aa.shop',
            'example-tenant-bb.shop',
            'example-tenant-cc.shop',
            'example-tenant-dd.shop',
            'example-tenant-c-local.store',
            'keter.co.il',
            'example-tenant-d-es.shop',
            'example-tenant-e-es.shop',
            'example-tenant-e-pt.shop',
            'example-tenant-ee.shop',
            'example-tenant-f-local.store',
            'example-tenant-ff.shop',
            'example-network.shop',
            'example-tenant-h-es.shop',
            'example-tenant-gg.shop',
            'example-tenant-hh.shop',
        ];
        // TEMPORARY: DONT FILTER OUT THOSE EVENTS! THEY ARE REAL! (the team eventes are probably "Direct Traffic")
        // foreach ($internal_referrals as $pattern) {
        //     if ($pattern !== '' && strpos($referral_source, $pattern) !== false) {
        //         return false;
        //     }
        // }
        
        return true;
    }
    
    /**
     * Map event data to database columns, applying default values as needed.
     */
    private function map_event_to_columns(array $event_data) {
        $now = date('Y-m-d H:i:s');
        
        return [
            'domain_id' => $event_data['domain_id'] ?? null,
            'location_id' => $event_data['location_id'] ?? null,
            'event_id' => $event_data['event_id'],
            'event_name' => $event_data['event_name'],
            'timestamp' => $event_data['timestamp'] ?? $now,
            'visitor_id' => $event_data['visitor_id'] ?? null,
            'session_id' => $event_data['session_id'] ?? null,
            'site_id' => $event_data['site_id'] ?? null,
            'domain' => $event_data['domain'] ?? null,
            'subdomain' => $event_data['subdomain'] ?? null,
            'store_name' => $event_data['store_name'] ?? null,
            'pagestamp' => $event_data['pagestamp'] ?? null,
            'latitude' => $event_data['latitude'] ?? null,
            'longitude' => $event_data['longitude'] ?? null,
            'referrer' => $event_data['referrer'] ?? null,
            'referral_source' => $event_data['referral_source'] ?? null,
            'search_engine' => $event_data['search_engine'] ?? null,
            'utm_source' => $event_data['utm_source'] ?? null,
            'utm_medium' => $event_data['utm_medium'] ?? null,
            'utm_campaign' => $event_data['utm_campaign'] ?? null,
            'utm_term' => $event_data['utm_term'] ?? null,
            'utm_content' => $event_data['utm_content'] ?? null,
            'user_agent' => $event_data['user_agent'] ?? null,
            'user_email' => $event_data['user_email'] ?? null,
            'product_properties' => isset($event_data['product_properties']) ? json_encode($event_data['product_properties']) : null,
            'custom_properties' => isset($event_data['custom_properties']) ? json_encode($event_data['custom_properties']) : null,
            'raw_data' => isset($event_data['raw_data']) ? json_encode($event_data['raw_data']) : null,
            'created_at' => $now,
            'updated_at' => $now
        ];
    }
    
    /**
     * Initialise the PDO connection using the supplied credentials.
     */
    private function initialize_connection(array $credentials) {
        $driver = $credentials['connection'] ?? 'mysql';
        $host = $credentials['host'] ?? null;
        $port = $credentials['port'] ?? 3306;
        $database = $credentials['database'] ?? null;
        $username = $credentials['username'] ?? null;
        $password = $credentials['password'] ?? null;
        
        if (empty($host) || empty($database) || empty($username)) {
            throw new Exception('Growth Dashboard DB credentials are incomplete.');
        }
        
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $driver,
            $host,
            $port,
            $database
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        return new PDO($dsn, $username, $password, $options);
    }
    
    /**
     * Retrieve credentials from the WordPress network settings.
     */
    private function get_credentials_from_settings() {
        $network_options = get_site_option('network_store_settings', []);
        $credentials_json = isset($network_options['network_growth_dashboard_db_credentials'])
            ? $network_options['network_growth_dashboard_db_credentials']
            : '';
        
        if (empty($credentials_json)) {
            error_log('Event Tracker: Growth Dashboard DB credentials not found in network settings.');
            return null;
        }
        
        $credentials = json_decode($credentials_json, true);
        
        if (!$credentials) {
            error_log('Event Tracker: Invalid Growth Dashboard DB credentials JSON.');
            return null;
        }
        
        return $credentials;
    }
}

