<?php
/**
 * Plugin Name: SmartPics (Minimal)
 * Plugin URI: https://github.com/support318/SmartPics
 * Description: SmartPics - AI-powered image optimization (minimal working version)
 * Version: 1.0.0
 * Author: Candid Studios
 * Author URI: https://candidstudios.net
 * License: GPL v2 or later
 * Text Domain: smartpics
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SMARTPICS_VERSION', '1.0.0');
define('SMARTPICS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SMARTPICS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SMARTPICS_PLUGIN_BASENAME', plugin_basename(__FILE__));

class SmartPics_Minimal {
    
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
        if (is_admin()) {
            $this->admin_init();
        }
    }
    
    private function admin_init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'SmartPics',
            'SmartPics',
            'manage_options',
            'smartpics',
            array($this, 'admin_page'),
            'dashicons-format-image',
            30
        );
        
        add_submenu_page(
            'smartpics',
            'Settings',
            'Settings',
            'manage_options',
            'smartpics-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>SmartPics</h1>
            <div class="notice notice-success">
                <p><strong>🎉 SmartPics is now active!</strong></p>
                <p>The minimal version is working. Go to <a href="<?php echo admin_url('admin.php?page=smartpics-settings'); ?>">Settings</a> to configure your API keys.</p>
            </div>
            
            <div class="card" style="max-width: 600px;">
                <h2>Welcome to SmartPics</h2>
                <p>SmartPics is an advanced AI-powered image optimization plugin that automatically generates:</p>
                <ul>
                    <li>✅ Smart alt text using AI vision models</li>
                    <li>✅ SEO-optimized captions</li>
                    <li>✅ Schema markup for images</li>
                    <li>✅ Multi-provider AI support (Vertex AI, OpenAI, Claude)</li>
                </ul>
                <p><a href="<?php echo admin_url('admin.php?page=smartpics-settings'); ?>" class="button button-primary">Configure Settings</a></p>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $settings = array(
                'vertex_ai_api_key' => sanitize_text_field($_POST['vertex_ai_api_key'] ?? ''),
                'openai_api_key' => sanitize_text_field($_POST['openai_api_key'] ?? ''),
                'claude_api_key' => sanitize_text_field($_POST['claude_api_key'] ?? ''),
                'primary_ai_provider' => sanitize_text_field($_POST['primary_ai_provider'] ?? 'vertex_ai'),
            );
            update_option('smartpics_settings', $settings);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $settings = get_option('smartpics_settings', array());
        ?>
        <div class="wrap">
            <h1>SmartPics Settings</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row">Primary AI Provider</th>
                        <td>
                            <select name="primary_ai_provider">
                                <option value="vertex_ai" <?php selected($settings['primary_ai_provider'] ?? '', 'vertex_ai'); ?>>Google Vertex AI</option>
                                <option value="openai" <?php selected($settings['primary_ai_provider'] ?? '', 'openai'); ?>>OpenAI GPT-4V</option>
                                <option value="claude" <?php selected($settings['primary_ai_provider'] ?? '', 'claude'); ?>>Claude 3 Vision</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Vertex AI API Key</th>
                        <td><input type="password" name="vertex_ai_api_key" value="<?php echo esc_attr($settings['vertex_ai_api_key'] ?? ''); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">OpenAI API Key</th>
                        <td><input type="password" name="openai_api_key" value="<?php echo esc_attr($settings['openai_api_key'] ?? ''); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Claude API Key</th>
                        <td><input type="password" name="claude_api_key" value="<?php echo esc_attr($settings['claude_api_key'] ?? ''); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'smartpics') === false) {
            return;
        }
        
        // Simple inline styles for now
        echo '<style>
        .smartpics-card { background: white; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 20px; margin: 20px 0; }
        </style>';
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('smartpics', false, dirname(SMARTPICS_PLUGIN_BASENAME) . '/languages');
    }
    
    public function activate() {
        // Simple activation - just set default settings
        $default_settings = array(
            'primary_ai_provider' => 'vertex_ai',
            'vertex_ai_api_key' => '',
            'openai_api_key' => '',
            'claude_api_key' => '',
            'enable_auto_processing' => false,
        );
        
        add_option('smartpics_settings', $default_settings);
        add_option('smartpics_version', SMARTPICS_VERSION);
    }
    
    public function deactivate() {
        // Clean deactivation
    }
}

SmartPics_Minimal::get_instance();