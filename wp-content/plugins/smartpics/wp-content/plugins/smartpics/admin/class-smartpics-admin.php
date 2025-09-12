<?php

if (!defined('ABSPATH')) {
    exit;
}

class SmartPics_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        add_action('wp_ajax_smartpics_test_connection', array($this, 'ajax_test_connection'));
        add_filter('plugin_action_links_' . SMARTPICS_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
        
        new SmartPics_Settings();
    }
    
    public function add_admin_menu() {
        $capability = 'manage_options';
        
        add_menu_page(
            __('SmartPics', 'smartpics'),
            __('SmartPics', 'smartpics'),
            $capability,
            'smartpics-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-format-image',
            30
        );
        
        add_submenu_page(
            'smartpics-dashboard',
            __('Dashboard', 'smartpics'),
            __('Dashboard', 'smartpics'),
            $capability,
            'smartpics-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'smartpics-dashboard',
            __('Settings', 'smartpics'),
            __('Settings', 'smartpics'),
            $capability,
            'smartpics-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'smartpics-dashboard',
            __('Bulk Processing', 'smartpics'),
            __('Bulk Processing', 'smartpics'),
            $capability,
            'smartpics-bulk',
            array($this, 'bulk_processing_page')
        );
        
        add_submenu_page(
            'smartpics-dashboard',
            __('Analytics', 'smartpics'),
            __('Analytics', 'smartpics'),
            $capability,
            'smartpics-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'smartpics-dashboard',
            __('Cache Management', 'smartpics'),
            __('Cache Management', 'smartpics'),
            $capability,
            'smartpics-cache',
            array($this, 'cache_management_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'smartpics') === false) {
            return;
        }
        
        wp_enqueue_style(
            'smartpics-admin-style',
            SMARTPICS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SMARTPICS_VERSION
        );
        
        wp_enqueue_script(
            'smartpics-admin-script',
            SMARTPICS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            SMARTPICS_VERSION,
            true
        );
        
        wp_localize_script('smartpics-admin-script', 'smartpics_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smartpics_nonce'),
            'strings' => array(
                'processing' => __('Processing...', 'smartpics'),
                'success' => __('Success!', 'smartpics'),
                'error' => __('Error occurred', 'smartpics'),
                'confirm_bulk' => __('Are you sure you want to start bulk processing? This may take a while.', 'smartpics'),
                'confirm_cache_clear' => __('Are you sure you want to clear the cache?', 'smartpics'),
                'connection_test_success' => __('Connection test successful!', 'smartpics'),
                'connection_test_failed' => __('Connection test failed. Please check your settings.', 'smartpics')
            )
        ));
        
        if ($hook === 'toplevel_page_smartpics-dashboard' || $hook === 'ai-alt-text_page_smartpics-analytics') {
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        }
    }
    
    public function display_admin_notices() {
        $settings = get_option('smartpics_settings', array());
        $has_api_keys = !empty($settings['vertex_ai_api_key']) || 
                       !empty($settings['openai_api_key']) || 
                       !empty($settings['claude_api_key']);
        
        if (!$has_api_keys) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>';
            printf(
                __('AI Alt Text Generator requires at least one AI provider API key to function. <a href="%s">Configure your settings</a>.', 'smartpics'),
                admin_url('admin.php?page=smartpics-settings')
            );
            echo '</p>';
            echo '</div>';
        }
        
        $queue_count = SmartPics_Database::get_processing_queue_count('failed');
        if ($queue_count > 10) {
            echo '<div class="notice notice-error">';
            echo '<p>';
            printf(
                __('There are %d failed image processing jobs. <a href="%s">View details</a>.', 'smartpics'),
                $queue_count,
                admin_url('admin.php?page=smartpics-dashboard')
            );
            echo '</p>';
            echo '</div>';
        }
    }
    
    public function dashboard_page() {
        $dashboard = new SmartPics_Dashboard();
        $dashboard->render();
    }
    
    public function settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('AI Alt Text Generator Settings', 'smartpics') . '</h1>';
        
        settings_errors('smartpics_settings');
        
        echo '<form method="post" action="options.php">';
        settings_fields('smartpics_settings_group');
        do_settings_sections('smartpics-settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }
    
    public function bulk_processing_page() {
        include SMARTPICS_PLUGIN_PATH . 'templates/admin-bulk-processing.php';
    }
    
    public function analytics_page() {
        include SMARTPICS_PLUGIN_PATH . 'templates/admin-analytics.php';
    }
    
    public function cache_management_page() {
        include SMARTPICS_PLUGIN_PATH . 'templates/admin-cache-management.php';
    }
    
    public function ajax_test_connection() {
        check_ajax_referer('smartpics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'smartpics'));
        }
        
        $provider = sanitize_text_field($_POST['provider']);
        $api_key = sanitize_text_field($_POST['api_key']);
        
        $ai_providers = new SmartPics_AI_Providers();
        $result = $ai_providers->test_connection($provider, $api_key);
        
        if ($result) {
            wp_send_json_success(__('Connection successful!', 'smartpics'));
        } else {
            wp_send_json_error(__('Connection failed. Please check your API key.', 'smartpics'));
        }
    }
    
    public function add_plugin_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=smartpics-settings'),
            __('Settings', 'smartpics')
        );
        
        $dashboard_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=smartpics-dashboard'),
            __('Dashboard', 'smartpics')
        );
        
        array_unshift($links, $settings_link, $dashboard_link);
        
        return $links;
    }
}