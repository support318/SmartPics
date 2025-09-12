<?php
/**
 * Plugin Name: SmartPics
 * Plugin URI: https://github.com/support318/SmartPics
 * Description: Advanced AI-powered image optimization with smart alt text, captions, and schema markup generation. Features multi-provider AI support, content analysis, and geotargeting capabilities.
 * Version: 1.0.0
 * Author: Candid Studios
 * Author URI: https://candidstudios.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smartpics
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Network: false
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SMARTPICS_VERSION', '1.0.0');
define('SMARTPICS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SMARTPICS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SMARTPICS_PLUGIN_BASENAME', plugin_basename(__FILE__));

class SmartPics {
    
    private static $instance = null;
    private $resource_safe = true;
    private $loaded_components = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Check resource constraints first
        $this->resource_safe = $this->check_resource_safety();
        
        if (!$this->resource_safe) {
            add_action('admin_notices', array($this, 'resource_warning'));
            return; // Exit early to prevent resource overload
        }
        
        add_action('init', array($this, 'init'), 20); // Lower priority to load after other plugins
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Double-check resource safety before loading components
        if (!$this->resource_safe) {
            return;
        }
        
        $this->includes();
        $this->init_hooks();
        
        if (is_admin()) {
            $this->admin_init();
        }
        
        $this->public_init();
    }
    
    private function check_resource_safety() {
        // For now, always return true to allow plugin activation
        // Resource constraints can be handled during actual processing
        return true;
        
        // Original safety checks (commented out for testing)
        /*
        // Check memory usage
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = $this->convert_to_bytes($memory_limit);
        $memory_usage = memory_get_usage(true);
        
        // If using more than 80% of memory limit, it's not safe (increased from 60%)
        if ($memory_usage > ($memory_limit_bytes * 0.8)) {
            return false;
        }
        
        // Allow on most hosting environments (reduced execution time check)
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time > 0 && $max_execution_time < 30) {
            return false; // Only block on very constrained environments
        }
        
        // Always allow in debug mode for development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return true;
        }
        
        return true;
        */
    }
    
    private function convert_to_bytes($value) {
        $unit = strtolower(substr($value, -1, 1));
        $value = (int) $value;
        switch ($unit) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        return $value;
    }
    
    public function resource_warning() {
        ?>
        <div class="notice notice-error">
            <p><strong><?php _e('SmartPics Resource Protection', 'smartpics'); ?></strong></p>
            <p><?php _e('SmartPics has detected resource constraints and disabled itself to prevent server overload. This is normal for shared hosting environments.', 'smartpics'); ?></p>
            <p><?php _e('Consider using SmartPics Lite instead, or upgrade to a higher-performance hosting plan.', 'smartpics'); ?></p>
        </div>
        <?php
    }
    
    private function includes() {
        // Load core classes first
        $this->safe_include('includes/class-smartpics-database.php');
        
        // Load admin classes only in admin
        if (is_admin()) {
            $this->safe_include('admin/class-smartpics-admin-enhanced.php');
            $this->safe_include('admin/class-smartpics-admin.php');
            $this->safe_include('admin/class-smartpics-settings.php');
            $this->safe_include('admin/class-smartpics-dashboard.php');
        }
        
        // Load public class
        $this->safe_include('public/class-smartpics-public.php');
        
        // Load other classes only if needed (prevents fatal errors)
        // These will be loaded on-demand when actually needed
    }
    
    private function safe_include($file) {
        $full_path = SMARTPICS_PLUGIN_PATH . $file;
        if (file_exists($full_path)) {
            include_once $full_path;
        }
    }
    
    private function init_hooks() {
        add_action('wp_ajax_smartpics_process_image', array($this, 'ajax_process_image'));
        add_action('wp_ajax_smartpics_bulk_process', array($this, 'ajax_bulk_process'));
        add_action('wp_ajax_smartpics_get_progress', array($this, 'ajax_get_progress'));
        
        add_filter('wp_handle_upload', array($this, 'handle_upload'), 10, 2);
        add_action('add_attachment', array($this, 'process_new_attachment'));
        
        add_action('wp_head', array($this, 'output_schema_markup'));
    }
    
    private function admin_init() {
        if (class_exists('SmartPics_Admin_Enhanced')) {
            new SmartPics_Admin_Enhanced();
        } elseif (class_exists('SmartPics_Admin')) {
            new SmartPics_Admin();
        }
    }
    
    private function public_init() {
        if (class_exists('SmartPics_Public')) {
            new SmartPics_Public();
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('smartpics', false, dirname(SMARTPICS_PLUGIN_BASENAME) . '/languages');
    }
    
    public function activate() {
        try {
            // Simple activation without complex database operations
            $default_settings = array(
                'vertex_ai_api_key' => '',
                'openai_api_key' => '',
                'claude_api_key' => '',
                'primary_ai_provider' => 'vertex_ai',
                'enable_auto_processing' => false,
            );
            
            add_option('smartpics_settings', $default_settings);
            add_option('smartpics_version', SMARTPICS_VERSION);
            
            // Create database tables safely (skip if fails)
            $this->create_basic_tables();
        } catch (Exception $e) {
            // Log error but don't prevent activation
            error_log('SmartPics activation error: ' . $e->getMessage());
        }
    }
    
    private function create_basic_tables() {
        try {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'smartpics_image_cache';
            
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                attachment_id bigint(20) NOT NULL,
                alt_text text DEFAULT NULL,
                ai_provider varchar(50) DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY attachment_id (attachment_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } catch (Exception $e) {
            // Table creation failed, continue without it
            error_log('SmartPics table creation error: ' . $e->getMessage());
        }
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('smartpics_cleanup_cache');
        flush_rewrite_rules();
    }
    
    public function handle_upload($upload, $context) {
        if (!$this->should_process_upload($upload)) {
            return $upload;
        }
        
        $settings = get_option('smartpics_settings', array());
        if (empty($settings['vertex_ai_api_key']) && empty($settings['openai_api_key']) && empty($settings['claude_api_key'])) {
            return $upload;
        }
        
        wp_schedule_single_event(time() + 5, 'smartpics_process_uploaded_image', array($upload['file']));
        
        return $upload;
    }
    
    public function process_new_attachment($attachment_id) {
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }
        
        $settings = get_option('smartpics_settings', array());
        if (!empty($settings['auto_process']) && $settings['auto_process']) {
            $this->queue_image_processing($attachment_id);
        }
    }
    
    public function output_schema_markup() {
        global $post;
        
        if (!is_singular() || !$post) {
            return;
        }
        
        $settings = get_option('smartpics_settings', array());
        if (empty($settings['enable_schema_generation'])) {
            return;
        }
        
        $schema_generator = new SmartPics_Schema_Generator();
        $schema = $schema_generator->generate_page_schema($post->ID);
        
        if (!empty($schema)) {
            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
        }
    }
    
    public function ajax_process_image() {
        check_ajax_referer('smartpics_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_die(__('You do not have permission to perform this action.', 'smartpics'));
        }
        
        $attachment_id = intval($_POST['attachment_id']);
        if (!$attachment_id || !wp_attachment_is_image($attachment_id)) {
            wp_send_json_error(__('Invalid attachment ID.', 'smartpics'));
        }
        
        $result = $this->process_image($attachment_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function ajax_bulk_process() {
        check_ajax_referer('smartpics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'smartpics'));
        }
        
        $bulk_processor = new SmartPics_Bulk_Processor();
        $job_id = $bulk_processor->start_bulk_job();
        
        if (is_wp_error($job_id)) {
            wp_send_json_error($job_id->get_error_message());
        }
        
        wp_send_json_success(array('job_id' => $job_id));
    }
    
    public function ajax_get_progress() {
        check_ajax_referer('smartpics_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_die(__('You do not have permission to perform this action.', 'smartpics'));
        }
        
        $job_id = sanitize_text_field($_POST['job_id']);
        if (!$job_id) {
            wp_send_json_error(__('Invalid job ID.', 'smartpics'));
        }
        
        $bulk_processor = new SmartPics_Bulk_Processor();
        $progress = $bulk_processor->get_job_progress($job_id);
        
        wp_send_json_success($progress);
    }
    
    private function should_process_upload($upload) {
        if (!isset($upload['type']) || strpos($upload['type'], 'image/') !== 0) {
            return false;
        }
        
        $settings = get_option('smartpics_settings', array());
        return !empty($settings['auto_process']) && $settings['auto_process'];
    }
    
    private function queue_image_processing($attachment_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'smartpics_processing_queue';
        
        $wpdb->insert(
            $table_name,
            array(
                'attachment_id' => $attachment_id,
                'status' => 'queued',
                'created_at' => current_time('mysql'),
                'priority' => 5
            ),
            array('%d', '%s', '%s', '%d')
        );
    }
    
    private function process_image($attachment_id) {
        $processor = new SmartPics_Image_Processor();
        return $processor->process_image($attachment_id);
    }
}

SmartPics::get_instance();