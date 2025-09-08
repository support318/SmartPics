<?php
/**
 * Plugin Name: SmartPics Lite
 * Plugin URI: https://github.com/support318/SmartPics
 * Description: Lightweight version of SmartPics - AI-powered image optimization with minimal resource usage
 * Version: 1.0.1
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

// Prevent multiple instances
if (defined('SMARTPICS_LITE_VERSION')) {
    return;
}

define('SMARTPICS_LITE_VERSION', '1.0.1');
define('SMARTPICS_LITE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SMARTPICS_LITE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SMARTPICS_LITE_PLUGIN_BASENAME', plugin_basename(__FILE__));

class SmartPics_Lite {
    
    private static $instance = null;
    private $loaded_components = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Only hook essential WordPress events
        add_action('init', array($this, 'init'), 20); // Lower priority
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Resource monitoring
        add_action('wp_footer', array($this, 'maybe_log_resources'));
    }
    
    public function init() {
        // Only load if not in resource-constrained environment
        if ($this->is_resource_constrained()) {
            add_action('admin_notices', array($this, 'resource_warning'));
            return;
        }
        
        // Load textdomain
        load_plugin_textdomain('smartpics', false, dirname(SMARTPICS_LITE_PLUGIN_BASENAME) . '/languages');
        
        // Only load admin components when needed
        if (is_admin()) {
            add_action('current_screen', array($this, 'maybe_load_admin'));
        }
    }
    
    public function maybe_load_admin() {
        $screen = get_current_screen();
        
        // Only load admin on our plugin pages
        if ($screen && (strpos($screen->id, 'smartpics') !== false || $screen->id === 'plugins')) {
            $this->load_component('admin');
        }
    }
    
    private function load_component($component) {
        if (isset($this->loaded_components[$component])) {
            return; // Already loaded
        }
        
        switch ($component) {
            case 'admin':
                $this->load_admin();
                break;
            case 'ai_providers':
                $this->safe_include('includes/class-smartpics-ai-providers.php');
                break;
            case 'database':
                $this->safe_include('includes/class-smartpics-database.php');
                break;
            case 'image_processor':
                $this->safe_include('includes/class-smartpics-image-processor.php');
                break;
        }
        
        $this->loaded_components[$component] = true;
    }
    
    private function load_admin() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('SmartPics Lite', 'smartpics'),
            __('SmartPics Lite', 'smartpics'),
            'manage_options',
            'smartpics-lite',
            array($this, 'admin_page'),
            'dashicons-format-image',
            30
        );
        
        add_submenu_page(
            'smartpics-lite',
            __('Settings', 'smartpics'),
            __('Settings', 'smartpics'),
            'manage_options',
            'smartpics-lite-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('SmartPics Lite', 'smartpics'); ?></h1>
            
            <?php if ($this->is_resource_constrained()): ?>
                <div class="notice notice-warning">
                    <p><strong><?php _e('Resource Optimization Mode', 'smartpics'); ?></strong></p>
                    <p><?php _e('SmartPics is running in lightweight mode to conserve server resources. Some features may be limited.', 'smartpics'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 800px;">
                <h2><?php _e('Welcome to SmartPics Lite', 'smartpics'); ?></h2>
                <p><?php _e('A resource-optimized version of SmartPics designed for shared hosting environments.', 'smartpics'); ?></p>
                
                <h3><?php _e('Available Features:', 'smartpics'); ?></h3>
                <ul>
                    <li>✅ <?php _e('AI-powered alt text generation (on-demand)', 'smartpics'); ?></li>
                    <li>✅ <?php _e('Basic image optimization', 'smartpics'); ?></li>
                    <li>✅ <?php _e('Simple settings management', 'smartpics'); ?></li>
                    <li>✅ <?php _e('Resource usage monitoring', 'smartpics'); ?></li>
                </ul>
                
                <p>
                    <a href="<?php echo admin_url('admin.php?page=smartpics-lite-settings'); ?>" class="button button-primary">
                        <?php _e('Configure Settings', 'smartpics'); ?>
                    </a>
                    <button type="button" class="button" onclick="smartpicsTestResource()"><?php _e('Test Resource Usage', 'smartpics'); ?></button>
                </p>
            </div>
            
            <div class="card" style="max-width: 800px;">
                <h3><?php _e('Resource Usage Status', 'smartpics'); ?></h3>
                <div id="smartpics-resource-status">
                    <?php $this->display_resource_status(); ?>
                </div>
            </div>
        </div>
        
        <script>
        function smartpicsTestResource() {
            var button = event.target;
            button.disabled = true;
            button.textContent = '<?php _e('Testing...', 'smartpics'); ?>';
            
            jQuery.post(ajaxurl, {
                action: 'smartpics_test_resource',
                nonce: '<?php echo wp_create_nonce('smartpics_nonce'); ?>'
            }, function(response) {
                button.disabled = false;
                button.textContent = '<?php _e('Test Resource Usage', 'smartpics'); ?>';
                
                if (response.success) {
                    jQuery('#smartpics-resource-status').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                } else {
                    jQuery('#smartpics-resource-status').html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>');
                }
            });
        }
        </script>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            if (wp_verify_nonce($_POST['smartpics_nonce'] ?? '', 'smartpics_settings')) {
                $settings = array(
                    'openai_api_key' => sanitize_text_field($_POST['openai_api_key'] ?? ''),
                    'enable_auto_processing' => isset($_POST['enable_auto_processing']),
                    'max_processing_per_hour' => min(50, intval($_POST['max_processing_per_hour'] ?? 10)), // Limit to 50/hour
                    'enable_resource_monitoring' => isset($_POST['enable_resource_monitoring'])
                );
                update_option('smartpics_lite_settings', $settings);
                echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'smartpics') . '</p></div>';
            }
        }
        
        $settings = get_option('smartpics_lite_settings', array());
        ?>
        <div class="wrap">
            <h1><?php _e('SmartPics Lite Settings', 'smartpics'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('smartpics_settings', 'smartpics_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('OpenAI API Key', 'smartpics'); ?></th>
                        <td>
                            <input type="password" name="openai_api_key" value="<?php echo esc_attr($settings['openai_api_key'] ?? ''); ?>" class="regular-text">
                            <p class="description"><?php _e('Only OpenAI is supported in Lite version to minimize resource usage.', 'smartpics'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Auto Processing', 'smartpics'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_auto_processing" <?php checked($settings['enable_auto_processing'] ?? false); ?>>
                                <?php _e('Automatically process new images', 'smartpics'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Max Processing Per Hour', 'smartpics'); ?></th>
                        <td>
                            <input type="number" name="max_processing_per_hour" value="<?php echo esc_attr($settings['max_processing_per_hour'] ?? 10); ?>" min="1" max="50" class="small-text">
                            <p class="description"><?php _e('Limit processing to prevent resource overload. Maximum 50 per hour.', 'smartpics'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Resource Monitoring', 'smartpics'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_resource_monitoring" <?php checked($settings['enable_resource_monitoring'] ?? true); ?>>
                                <?php _e('Enable resource usage monitoring', 'smartpics'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h3><?php _e('Resource Usage Tips', 'smartpics'); ?></h3>
                <ul>
                    <li><?php _e('Keep "Max Processing Per Hour" low (10-20) for shared hosting', 'smartpics'); ?></li>
                    <li><?php _e('Disable auto-processing if you have many image uploads', 'smartpics'); ?></li>
                    <li><?php _e('Process images manually during low-traffic hours', 'smartpics'); ?></li>
                    <li><?php _e('Monitor the resource status regularly', 'smartpics'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'smartpics') === false) {
            return;
        }
        
        // Only load minimal CSS
        wp_add_inline_style('wp-admin', '
            .smartpics-lite-status { 
                padding: 10px; 
                background: #f0f0f1; 
                border-radius: 4px; 
                margin: 10px 0; 
            }
            .smartpics-resource-ok { color: #00a32a; }
            .smartpics-resource-warning { color: #dba617; }
            .smartpics-resource-error { color: #d63638; }
        ');
    }
    
    public function activate() {
        // Minimal activation - just create options
        $default_settings = array(
            'openai_api_key' => '',
            'enable_auto_processing' => false,
            'max_processing_per_hour' => 10,
            'enable_resource_monitoring' => true
        );
        
        add_option('smartpics_lite_settings', $default_settings);
        add_option('smartpics_lite_version', SMARTPICS_LITE_VERSION);
        add_option('smartpics_lite_install_time', time());
        
        // Create minimal database table only if needed
        $this->create_minimal_table();
    }
    
    public function deactivate() {
        // Clean deactivation
        wp_clear_scheduled_hook('smartpics_lite_cleanup');
    }
    
    private function create_minimal_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'smartpics_lite_log';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            attachment_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            resource_usage int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY attachment_id (attachment_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function safe_include($file) {
        $full_path = SMARTPICS_LITE_PLUGIN_PATH . $file;
        if (file_exists($full_path) && !class_exists(basename($file, '.php'))) {
            include_once $full_path;
        }
    }
    
    private function is_resource_constrained() {
        // Check various resource indicators
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = $this->convert_to_bytes($memory_limit);
        $memory_usage = memory_get_usage(true);
        
        // If using more than 80% of memory limit, we're constrained
        if ($memory_usage > ($memory_limit_bytes * 0.8)) {
            return true;
        }
        
        // Check execution time
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time > 0 && $max_execution_time < 60) {
            return true; // Less than 60 seconds suggests shared hosting
        }
        
        // Check recent resource usage
        $recent_usage = get_option('smartpics_lite_recent_usage', 0);
        if ($recent_usage > 100) { // More than 100 operations in recent period
            return true;
        }
        
        return false;
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
        <div class="notice notice-warning">
            <p><strong><?php _e('SmartPics Resource Warning', 'smartpics'); ?></strong></p>
            <p><?php _e('SmartPics is running in resource-constrained mode. Some features are limited to prevent server overload.', 'smartpics'); ?></p>
        </div>
        <?php
    }
    
    public function maybe_log_resources() {
        $settings = get_option('smartpics_lite_settings', array());
        if (!($settings['enable_resource_monitoring'] ?? true)) {
            return;
        }
        
        // Only log occasionally to avoid overhead
        if (rand(1, 100) <= 5) { // 5% chance
            $usage = array(
                'memory' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
            );
            update_option('smartpics_lite_last_usage', $usage);
        }
    }
    
    private function display_resource_status() {
        $last_usage = get_option('smartpics_lite_last_usage', array());
        $memory_limit = ini_get('memory_limit');
        
        if (!empty($last_usage)) {
            $memory_usage_mb = round($last_usage['memory'] / 1024 / 1024, 2);
            $peak_memory_mb = round($last_usage['peak_memory'] / 1024 / 1024, 2);
            $execution_time = round($last_usage['time'], 3);
            
            echo '<div class="smartpics-lite-status">';
            echo '<p><strong>' . __('Last Request:', 'smartpics') . '</strong></p>';
            echo '<p>' . sprintf(__('Memory Usage: %s MB (Peak: %s MB, Limit: %s)', 'smartpics'), $memory_usage_mb, $peak_memory_mb, $memory_limit) . '</p>';
            echo '<p>' . sprintf(__('Execution Time: %s seconds', 'smartpics'), $execution_time) . '</p>';
            
            // Status indicator
            if ($memory_usage_mb < 32 && $execution_time < 1) {
                echo '<p class="smartpics-resource-ok">✅ ' . __('Resource usage is optimal', 'smartpics') . '</p>';
            } elseif ($memory_usage_mb < 64 && $execution_time < 3) {
                echo '<p class="smartpics-resource-warning">⚠️ ' . __('Resource usage is moderate', 'smartpics') . '</p>';
            } else {
                echo '<p class="smartpics-resource-error">❌ ' . __('High resource usage detected', 'smartpics') . '</p>';
            }
            echo '</div>';
        } else {
            echo '<p>' . __('No resource data available yet.', 'smartpics') . '</p>';
        }
    }
    
    // AJAX handler for resource testing
    public function __construct() {
        parent::__construct();
        add_action('wp_ajax_smartpics_test_resource', array($this, 'ajax_test_resource'));
    }
    
    public function ajax_test_resource() {
        check_ajax_referer('smartpics_nonce', 'nonce');
        
        $start_time = microtime(true);
        $start_memory = memory_get_usage(true);
        
        // Simulate light processing
        for ($i = 0; $i < 1000; $i++) {
            $dummy = hash('md5', $i);
        }
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage(true);
        
        $execution_time = round($end_time - $start_time, 4);
        $memory_used = round(($end_memory - $start_memory) / 1024, 2);
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Resource test completed in %s seconds using %s KB of memory. This is safe for shared hosting.', 'smartpics'),
                $execution_time,
                $memory_used
            )
        ));
    }
}

// Initialize the lite version
SmartPics_Lite::get_instance();