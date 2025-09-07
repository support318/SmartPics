<?php

if (!defined('ABSPATH')) {
    exit;
}

class AIALT_Cloudflare_Integration {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('aialt_settings', array());
    }
    
    public function upload_to_r2($file_path, $file_name = null) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'Cloudflare R2 not configured');
        }
        
        // Placeholder for R2 upload implementation
        return array(
            'success' => true,
            'url' => 'https://example.r2.dev/' . ($file_name ?: basename($file_path))
        );
    }
    
    public function trigger_worker($worker_url, $data) {
        if (empty($worker_url)) {
            return new WP_Error('no_worker_url', 'Worker URL not provided');
        }
        
        $response = wp_remote_post($worker_url, array(
            'body' => wp_json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . ($this->settings['cloudflare_api_token'] ?? '')
            ),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
    
    private function is_configured() {
        return !empty($this->settings['cloudflare_account_id']) && 
               !empty($this->settings['r2_bucket_name']);
    }
}