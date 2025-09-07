<?php

if (!defined('ABSPATH')) {
    exit;
}

class AIALT_Public {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('wp_get_attachment_image_attributes', array($this, 'enhance_image_attributes'), 10, 3);
    }
    
    public function enqueue_scripts() {
        // Only enqueue if needed for frontend functionality
        if (is_admin()) {
            return;
        }
        
        wp_enqueue_style(
            'aialt-public-style',
            AIALT_PLUGIN_URL . 'assets/css/public.css',
            array(),
            AIALT_VERSION
        );
    }
    
    public function enhance_image_attributes($attr, $attachment, $size) {
        // Ensure alt text is always present
        if (empty($attr['alt'])) {
            $alt_text = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
            if (!empty($alt_text)) {
                $attr['alt'] = $alt_text;
            }
        }
        
        return $attr;
    }
}