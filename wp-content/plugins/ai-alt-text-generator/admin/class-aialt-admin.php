<?php

if (!defined('ABSPATH')) {
    exit;
}

class AIALT_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        add_action('wp_ajax_aialt_test_connection', array($this, 'ajax_test_connection'));
        add_filter('plugin_action_links_' . AIALT_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
        
        new AIALT_Settings();
    }
    
    public function add_admin_menu() {
        $capability = 'manage_options';
        
        add_menu_page(
            __('AI Alt Text Generator', 'ai-alt-text-generator'),
            __('AI Alt Text', 'ai-alt-text-generator'),
            $capability,
            'aialt-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-format-image',
            30
        );
        
        add_submenu_page(
            'aialt-dashboard',
            __('Dashboard', 'ai-alt-text-generator'),
            __('Dashboard', 'ai-alt-text-generator'),
            $capability,
            'aialt-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'aialt-dashboard',
            __('Settings', 'ai-alt-text-generator'),
            __('Settings', 'ai-alt-text-generator'),
            $capability,
            'aialt-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'aialt-dashboard',
            __('Bulk Processing', 'ai-alt-text-generator'),
            __('Bulk Processing', 'ai-alt-text-generator'),
            $capability,
            'aialt-bulk',
            array($this, 'bulk_processing_page')
        );
        
        add_submenu_page(
            'aialt-dashboard',
            __('Analytics', 'ai-alt-text-generator'),
            __('Analytics', 'ai-alt-text-generator'),
            $capability,
            'aialt-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'aialt-dashboard',
            __('Cache Management', 'ai-alt-text-generator'),
            __('Cache Management', 'ai-alt-text-generator'),
            $capability,
            'aialt-cache',
            array($this, 'cache_management_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'aialt') === false) {
            return;
        }
        
        wp_enqueue_style(
            'aialt-admin-style',
            AIALT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AIALT_VERSION
        );
        
        wp_enqueue_script(
            'aialt-admin-script',
            AIALT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            AIALT_VERSION,
            true
        );
        
        wp_localize_script('aialt-admin-script', 'aialt_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aialt_nonce'),
            'strings' => array(
                'processing' => __('Processing...', 'ai-alt-text-generator'),
                'success' => __('Success!', 'ai-alt-text-generator'),
                'error' => __('Error occurred', 'ai-alt-text-generator'),
                'confirm_bulk' => __('Are you sure you want to start bulk processing? This may take a while.', 'ai-alt-text-generator'),
                'confirm_cache_clear' => __('Are you sure you want to clear the cache?', 'ai-alt-text-generator'),
                'connection_test_success' => __('Connection test successful!', 'ai-alt-text-generator'),
                'connection_test_failed' => __('Connection test failed. Please check your settings.', 'ai-alt-text-generator')
            )
        ));
        
        if ($hook === 'toplevel_page_aialt-dashboard' || $hook === 'ai-alt-text_page_aialt-analytics') {
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        }
    }
    
    public function display_admin_notices() {
        $settings = get_option('aialt_settings', array());
        $has_api_keys = !empty($settings['vertex_ai_api_key']) || 
                       !empty($settings['openai_api_key']) || 
                       !empty($settings['claude_api_key']);
        
        if (!$has_api_keys) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>';
            printf(
                __('AI Alt Text Generator requires at least one AI provider API key to function. <a href="%s">Configure your settings</a>.', 'ai-alt-text-generator'),
                admin_url('admin.php?page=aialt-settings')
            );
            echo '</p>';
            echo '</div>';
        }
        
        $queue_count = AIALT_Database::get_processing_queue_count('failed');
        if ($queue_count > 10) {
            echo '<div class="notice notice-error">';
            echo '<p>';
            printf(
                __('There are %d failed image processing jobs. <a href="%s">View details</a>.', 'ai-alt-text-generator'),
                $queue_count,
                admin_url('admin.php?page=aialt-dashboard')
            );
            echo '</p>';
            echo '</div>';
        }
    }
    
    public function dashboard_page() {
        $dashboard = new AIALT_Dashboard();
        $dashboard->render();
    }
    
    public function settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('AI Alt Text Generator Settings', 'ai-alt-text-generator') . '</h1>';
        
        settings_errors('aialt_settings');
        
        echo '<form method="post" action="options.php">';
        settings_fields('aialt_settings_group');
        do_settings_sections('aialt-settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }
    
    public function bulk_processing_page() {
        include AIALT_PLUGIN_PATH . 'templates/admin-bulk-processing.php';
    }
    
    public function analytics_page() {
        include AIALT_PLUGIN_PATH . 'templates/admin-analytics.php';
    }
    
    public function cache_management_page() {
        include AIALT_PLUGIN_PATH . 'templates/admin-cache-management.php';
    }
    
    public function ajax_test_connection() {
        check_ajax_referer('aialt_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'ai-alt-text-generator'));
        }
        
        $provider = sanitize_text_field($_POST['provider']);
        $api_key = sanitize_text_field($_POST['api_key']);
        
        $ai_providers = new AIALT_AI_Providers();
        $result = $ai_providers->test_connection($provider, $api_key);
        
        if ($result) {
            wp_send_json_success(__('Connection successful!', 'ai-alt-text-generator'));
        } else {
            wp_send_json_error(__('Connection failed. Please check your API key.', 'ai-alt-text-generator'));
        }
    }
    
    public function add_plugin_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=aialt-settings'),
            __('Settings', 'ai-alt-text-generator')
        );
        
        $dashboard_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=aialt-dashboard'),
            __('Dashboard', 'ai-alt-text-generator')
        );
        
        array_unshift($links, $settings_link, $dashboard_link);
        
        return $links;
    }
}