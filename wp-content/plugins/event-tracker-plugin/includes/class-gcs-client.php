<?php

namespace EventTracker;

use Google\Cloud\Storage\StorageClient;

class GCSClient {
    
    private $storage_client;
    private $bucket_name;
    private $project_id;
    
    public function __construct($service_account_json = null, $project_id = 'i-for-ai', $bucket_name = 'eu_big_data_storage_idr') {
        $this->project_id = $project_id;
        $this->bucket_name = $bucket_name;
        
        // Check if Google Cloud Storage is available
        if (!class_exists('Google\Cloud\Storage\StorageClient')) {
            throw new \Exception('Google Cloud Storage library not found. Please ensure google/cloud-storage is installed via Composer.');
        }
        
        // If no credentials provided, try to get from WordPress network settings
        if ($service_account_json === null) {
            $service_account_json = $this->get_credentials_from_settings();
        }
        
        if ($service_account_json === null) {
            throw new \Exception('Google Cloud Storage credentials not found. Please configure credentials in network settings.');
        }
        
        // Initialize Google Cloud Storage client
        $this->storage_client = new StorageClient([
            'projectId' => $project_id,
            'keyFile' => $service_account_json
        ]);
    }
    
    /**
     * Get GCS credentials from WordPress network settings
     */
    private function get_credentials_from_settings() {
        // Get credentials from WordPress network settings
        $network_options = get_site_option('network_store_settings', []);
        $credentials_json = isset($network_options['network_google_cloud_storage_api_key']) ? $network_options['network_google_cloud_storage_api_key'] : '';
        
        if (empty($credentials_json)) {
            error_log('Event Tracker: GCS credentials not found in network settings');
            return null;
        }
        
        $credentials = json_decode($credentials_json, true);
        
        if (!$credentials) {
            error_log('Event Tracker: Invalid GCS credentials JSON');
            return null;
        }
        
        return $credentials;
    }
    
    /**
     * Insert event data to visitor's daily file
     */
    public function insert_event($event_data) {
        try {
            $visitor_id = $event_data['visitor_id'];
            $event_date = $this->get_event_date($event_data['timestamp']);
            
            // File path: visitorid_DD-MM-YYYY.json (no Legacy prefix)
            $file_path = "{$visitor_id}_{$event_date}.json";
            
            // Check if file exists
            if ($this->file_exists($file_path)) {
                // File exists, append to it
                return $this->append_event_to_file($file_path, $event_data);
            } else {
                // New file, create it
                $file_content = json_encode([$event_data], JSON_PRETTY_PRINT);
                return $this->create_file($file_path, $file_content, [
                    'visitor_id' => $visitor_id,
                    'event_date' => $event_date,
                    'event_count' => 1,
                    'source' => 'event-tracker'
                ]);
            }
            
        } catch (Exception $e) {
            error_log('Event Tracker: GCS store event error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to store event',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all files for a specific visitor
     */
    public function get_visitor_files($visitor_id) {
        try {
            $bucket = $this->storage_client->bucket($this->bucket_name);
            
            // List files with visitor_id prefix
            $objects = $bucket->objects([
                'prefix' => $visitor_id . '_'
            ]);
            
            $files = [];
            foreach ($objects as $object) {
                $files[] = [
                    'name' => $object->name(),
                    'size' => $object->info()['size'] ?? 0,
                    'updated' => $object->info()['updated'] ?? '',
                    'metadata' => $object->info()['metadata'] ?? []
                ];
            }
            
            return [
                'success' => true,
                'files' => $files
            ];
            
        } catch (Exception $e) {
            error_log('Event Tracker: GCS get visitor files error: ' . $e->getMessage());
            return [
                'success' => false,
                'files' => []
            ];
        }
    }
    
    /**
     * Get all event data for a specific visitor
     */
    public function get_visitor_data($visitor_id) {
        try {
            $visitor_files = $this->get_visitor_files($visitor_id);
            
            if (!$visitor_files['success']) {
                return [];
            }
            
            $all_events = [];
            foreach ($visitor_files['files'] as $file) {
                $file_content = $this->get_file_content($file['name']);
                if ($file_content['success']) {
                    $events = json_decode($file_content['content'], true);
                    if (is_array($events)) {
                        $all_events = array_merge($all_events, $events);
                    }
                }
            }
            
            return $all_events;
            
        } catch (Exception $e) {
            error_log('Event Tracker: GCS get visitor data error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete all files for a specific visitor
     */
    public function delete_visitor_data($visitor_id) {
        try {
            $visitor_files = $this->get_visitor_files($visitor_id);
            
            if (!$visitor_files['success']) {
                return [
                    'success' => true,
                    'files_deleted' => 0,
                    'message' => 'No files found to delete'
                ];
            }
            
            $deleted_count = 0;
            $bucket = $this->storage_client->bucket($this->bucket_name);
            
            foreach ($visitor_files['files'] as $file) {
                try {
                    $object = $bucket->object($file['name']);
                    $object->delete();
                    $deleted_count++;
                } catch (Exception $e) {
                    error_log('Event Tracker: Failed to delete file ' . $file['name'] . ': ' . $e->getMessage());
                }
            }
            
            return [
                'success' => true,
                'files_deleted' => $deleted_count,
                'message' => "Successfully deleted {$deleted_count} files"
            ];
            
        } catch (Exception $e) {
            error_log('Event Tracker: GCS delete visitor data error: ' . $e->getMessage());
            return [
                'success' => false,
                'files_deleted' => 0,
                'message' => 'Failed to delete visitor data: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if a file exists in the bucket
     */
    public function file_exists($file_path) {
        try {
            $bucket = $this->storage_client->bucket($this->bucket_name);
            $object = $bucket->object($file_path);
            return $object->exists();
        } catch (Exception $e) {
            error_log('Event Tracker: GCS file exists check error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get file content from bucket
     */
    public function get_file_content($file_path) {
        try {
            $bucket = $this->storage_client->bucket($this->bucket_name);
            $object = $bucket->object($file_path);
            
            if (!$object->exists()) {
                return [
                    'success' => false,
                    'content' => '',
                    'message' => 'File not found'
                ];
            }
            
            $content = $object->downloadAsString();
            
            return [
                'success' => true,
                'content' => $content,
                'message' => 'File retrieved successfully'
            ];
            
        } catch (Exception $e) {
            error_log('Event Tracker: GCS get file content error: ' . $e->getMessage());
            return [
                'success' => false,
                'content' => '',
                'message' => 'Failed to get file content: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a new file in the bucket
     */
    public function create_file($file_path, $content, $metadata = []) {
        try {
            $bucket = $this->storage_client->bucket($this->bucket_name);
            
            $object = $bucket->upload($content, [
                'name' => $file_path,
                'metadata' => array_merge([
                    'source' => 'event-tracker',
                    'created' => date('c')
                ], $metadata)
            ]);
            
            return [
                'success' => true,
                'message' => 'File created successfully',
                'details' => "Created file: {$file_path}"
            ];
            
        } catch (Exception $e) {
            error_log('Event Tracker: GCS create file error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create file',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update existing file content
     */
    public function update_file($file_path, $content, $metadata = []) {
        try {
            $bucket = $this->storage_client->bucket($this->bucket_name);
            
            $object = $bucket->upload($content, [
                'name' => $file_path,
                'metadata' => array_merge([
                    'source' => 'event-tracker',
                    'updated' => date('c')
                ], $metadata)
            ]);
            
            return [
                'success' => true,
                'message' => 'File updated successfully',
                'details' => "Updated file: {$file_path}"
            ];
            
        } catch (Exception $e) {
            error_log('Event Tracker: GCS update file error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update file',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Append event to existing file
     */
    private function append_event_to_file($file_path, $event_data) {
        try {
            // Get existing content
            $existing_content = $this->get_file_content($file_path);
            if (!$existing_content['success']) {
                return $existing_content;
            }
            
            // Parse existing events
            $existing_events = json_decode($existing_content['content'], true);
            if (!is_array($existing_events)) {
                $existing_events = [];
            }
            
            // Add new event
            $existing_events[] = $event_data;
            
            // Update file
            $new_content = json_encode($existing_events, JSON_PRETTY_PRINT);
            return $this->update_file($file_path, $new_content, [
                'visitor_id' => $event_data['visitor_id'],
                'event_date' => $this->get_event_date($event_data['timestamp']),
                'event_count' => count($existing_events),
                'source' => 'event-tracker'
            ]);
            
        } catch (Exception $e) {
            error_log('Event Tracker: GCS append event error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to append event',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract date from timestamp for file naming
     */
    private function get_event_date($timestamp) {
        try {
            $date = new \DateTime($timestamp);
            return $date->format('d-m-Y');
        } catch (Exception $e) {
            error_log('Event Tracker: Invalid timestamp format: ' . $timestamp);
            return date('d-m-Y'); // fallback to current date
        }
    }
} 