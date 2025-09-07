<?php
/**
 * Plugin Name: SmartPics Debug
 * Description: Debug version of SmartPics to identify fatal error
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('SMARTPICS_VERSION', '1.0.0');
define('SMARTPICS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SMARTPICS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SMARTPICS_PLUGIN_BASENAME', plugin_basename(__FILE__));

class SmartPics_Debug {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function init() {
        $this->safe_includes();
        add_action('admin_menu', array($this, 'add_debug_menu'));
    }
    
    public function add_debug_menu() {
        add_menu_page(
            'SmartPics Debug',
            'SmartPics Debug',
            'manage_options',
            'smartpics-debug',
            array($this, 'debug_page')
        );
    }
    
    public function debug_page() {
        echo '<div class="wrap">';
        echo '<h1>SmartPics Debug - Plugin Loaded Successfully!</h1>';
        echo '<p>Plugin path: ' . SMARTPICS_PLUGIN_PATH . '</p>';
        echo '<p>If you see this, the basic plugin structure works.</p>';
        echo '</div>';
    }
    
    private function safe_includes() {
        $files_to_load = array(
            'includes/class-smartpics-database.php',
            'includes/class-smartpics-image-processor.php',
            'includes/class-smartpics-ai-providers.php',
        );
        
        foreach ($files_to_load as $file) {
            $full_path = SMARTPICS_PLUGIN_PATH . $file;
            if (file_exists($full_path)) {
                try {
                    require_once $full_path;
                    error_log("SmartPics Debug: Successfully loaded $file");
                } catch (Exception $e) {
                    error_log("SmartPics Debug: Error loading $file - " . $e->getMessage());
                }
            } else {
                error_log("SmartPics Debug: File not found - $full_path");
            }
        }
    }
    
    public function activate() {
        // Simple activation without complex database setup
        add_option('smartpics_debug_activated', true);
    }
}

SmartPics_Debug::get_instance();