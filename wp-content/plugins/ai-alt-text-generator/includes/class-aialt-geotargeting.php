<?php

if (!defined('ABSPATH')) {
    exit;
}

class AIALT_Geotargeting {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('aialt_settings', array());
    }
    
    public function is_enabled() {
        return !empty($this->settings['enable_geotargeting']) && 
               !empty($this->settings['gmb_api_key']);
    }
    
    public function get_business_data($business_id = null) {
        if (!$this->is_enabled()) {
            return null;
        }
        
        // Placeholder for GMB API integration
        return array(
            'name' => 'Sample Business',
            'address' => '123 Main St, City, State',
            'phone' => '(555) 123-4567'
        );
    }
}