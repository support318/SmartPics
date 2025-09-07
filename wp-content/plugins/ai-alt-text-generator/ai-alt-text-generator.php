<?php
/**
 * Plugin Name: AI Alt Text Generator
 * Plugin URI: https://github.com/candidstudios/ai-alt-text-generator
 * Description: Advanced AI-powered alt text, captions, and schema markup generation with multi-provider support, content analysis, and geotargeting capabilities.
 * Version: 1.0.0
 * Author: Candid Studios
 * Author URI: https://candidstudios.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-alt-text-generator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Network: false
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AIALT_VERSION', '1.0.0');
define('AIALT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AIALT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AIALT_PLUGIN_BASENAME', plugin_basename(__FILE__));

class AI_Alt_Text_Generator {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        $this->includes();
        $this->init_hooks();
        
        if (is_admin()) {
            $this->admin_init();
        }
        
        $this->public_init();
    }
    
    private function includes() {
        require_once AIALT_PLUGIN_PATH . 'includes/class-aialt-database.php';
        require_once AIALT_PLUGIN_PATH . 'includes/class-aialt-image-processor.php';
        require_once AIALT_PLUGIN_PATH . 'includes/class-aialt-ai-providers.php';
        require_once AIALT_PLUGIN_PATH . 'includes/class-aialt-content-analyzer.php';
        require_once AIALT_PLUGIN_PATH . 'includes/class-aialt-schema-generator.php';
        require_once AIALT_PLUGIN_PATH . 'includes/class-aialt-seo-integration.php';
        require_once AIALT_PLUGIN_PATH . 'includes/class-aialt-cloudflare-integration.php';
        require_once AIALT_PLUGIN_PATH . 'includes/class-aialt-similarity-detector.php';
        require_once AIALT_PLUGIN_PATH . 'includes/class-aialt-cache-manager.php';
        require_once AIALT_PLUGIN_PATH . 'includes/class-aialt-geotargeting.php';
        require_once AIALT_PLUGIN_PATH . 'includes/class-aialt-bulk-processor.php';
        
        if (is_admin()) {
            require_once AIALT_PLUGIN_PATH . 'admin/class-aialt-admin.php';
            require_once AIALT_PLUGIN_PATH . 'admin/class-aialt-settings.php';
            require_once AIALT_PLUGIN_PATH . 'admin/class-aialt-dashboard.php';
        }
        
        require_once AIALT_PLUGIN_PATH . 'public/class-aialt-public.php';
    }
    
    private function init_hooks() {
        add_action('wp_ajax_aialt_process_image', array($this, 'ajax_process_image'));
        add_action('wp_ajax_aialt_bulk_process', array($this, 'ajax_bulk_process'));
        add_action('wp_ajax_aialt_get_progress', array($this, 'ajax_get_progress'));
        
        add_filter('wp_handle_upload', array($this, 'handle_upload'), 10, 2);
        add_action('add_attachment', array($this, 'process_new_attachment'));
        
        add_action('wp_head', array($this, 'output_schema_markup'));
    }
    
    private function admin_init() {
        new AIALT_Admin();
    }
    
    private function public_init() {
        new AIALT_Public();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('ai-alt-text-generator', false, dirname(AIALT_PLUGIN_BASENAME) . '/languages');
    }
    
    public function activate() {
        AIALT_Database::create_tables();
        
        $default_settings = array(
            'vertex_ai_project_id' => '',
            'vertex_ai_location' => 'us-central1',
            'vertex_ai_api_key' => '',
            'openai_api_key' => '',
            'claude_api_key' => '',
            'cloudflare_account_id' => '',
            'cloudflare_api_token' => '',
            'r2_bucket_name' => '',
            'r2_access_key_id' => '',
            'r2_secret_access_key' => '',
            'gmb_api_key' => '',
            'vector_db_url' => '',
            'vector_db_api_key' => '',
            'primary_ai_provider' => 'vertex_ai',
            'fallback_providers' => array('openai', 'claude'),
            'enable_caching' => true,
            'enable_similarity_detection' => true,
            'enable_content_analysis' => true,
            'enable_schema_generation' => true,
            'enable_geotargeting' => false,
            'similarity_threshold' => 0.85,
            'cache_duration' => 30,
            'batch_size' => 10,
            'rate_limit' => 100
        );
        
        update_option('aialt_settings', $default_settings);
        
        wp_schedule_event(time(), 'hourly', 'aialt_cleanup_cache');
        
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('aialt_cleanup_cache');
        flush_rewrite_rules();
    }
    
    public function handle_upload($upload, $context) {
        if (!$this->should_process_upload($upload)) {
            return $upload;
        }
        
        $settings = get_option('aialt_settings', array());
        if (empty($settings['vertex_ai_api_key']) && empty($settings['openai_api_key']) && empty($settings['claude_api_key'])) {
            return $upload;
        }
        
        wp_schedule_single_event(time() + 5, 'aialt_process_uploaded_image', array($upload['file']));
        
        return $upload;
    }
    
    public function process_new_attachment($attachment_id) {
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }
        
        $settings = get_option('aialt_settings', array());
        if (!empty($settings['auto_process']) && $settings['auto_process']) {
            $this->queue_image_processing($attachment_id);
        }
    }
    
    public function output_schema_markup() {
        global $post;
        
        if (!is_singular() || !$post) {
            return;
        }
        
        $settings = get_option('aialt_settings', array());
        if (empty($settings['enable_schema_generation'])) {
            return;
        }
        
        $schema_generator = new AIALT_Schema_Generator();
        $schema = $schema_generator->generate_page_schema($post->ID);
        
        if (!empty($schema)) {
            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
        }
    }
    
    public function ajax_process_image() {
        check_ajax_referer('aialt_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_die(__('You do not have permission to perform this action.', 'ai-alt-text-generator'));
        }
        
        $attachment_id = intval($_POST['attachment_id']);
        if (!$attachment_id || !wp_attachment_is_image($attachment_id)) {
            wp_send_json_error(__('Invalid attachment ID.', 'ai-alt-text-generator'));
        }
        
        $result = $this->process_image($attachment_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function ajax_bulk_process() {
        check_ajax_referer('aialt_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'ai-alt-text-generator'));
        }
        
        $bulk_processor = new AIALT_Bulk_Processor();
        $job_id = $bulk_processor->start_bulk_job();
        
        if (is_wp_error($job_id)) {
            wp_send_json_error($job_id->get_error_message());
        }
        
        wp_send_json_success(array('job_id' => $job_id));
    }
    
    public function ajax_get_progress() {
        check_ajax_referer('aialt_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_die(__('You do not have permission to perform this action.', 'ai-alt-text-generator'));
        }
        
        $job_id = sanitize_text_field($_POST['job_id']);
        if (!$job_id) {
            wp_send_json_error(__('Invalid job ID.', 'ai-alt-text-generator'));
        }
        
        $bulk_processor = new AIALT_Bulk_Processor();
        $progress = $bulk_processor->get_job_progress($job_id);
        
        wp_send_json_success($progress);
    }
    
    private function should_process_upload($upload) {
        if (!isset($upload['type']) || strpos($upload['type'], 'image/') !== 0) {
            return false;
        }
        
        $settings = get_option('aialt_settings', array());
        return !empty($settings['auto_process']) && $settings['auto_process'];
    }
    
    private function queue_image_processing($attachment_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aialt_processing_queue';
        
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
        $processor = new AIALT_Image_Processor();
        return $processor->process_image($attachment_id);
    }
}

AI_Alt_Text_Generator::get_instance();