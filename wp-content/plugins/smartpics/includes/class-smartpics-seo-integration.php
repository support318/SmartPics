<?php

if (!defined('ABSPATH')) {
    exit;
}

class SmartPics_SEO_Integration {
    
    public function get_focus_keyword($post_id) {
        // Try RankMath first
        $rankmath_keyword = $this->get_rankmath_focus_keyword($post_id);
        if (!empty($rankmath_keyword)) {
            return $rankmath_keyword;
        }
        
        // Try Yoast SEO
        $yoast_keyword = $this->get_yoast_focus_keyword($post_id);
        if (!empty($yoast_keyword)) {
            return $yoast_keyword;
        }
        
        return '';
    }
    
    private function get_rankmath_focus_keyword($post_id) {
        if (!class_exists('RankMath')) {
            return '';
        }
        
        $focus_keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        return $focus_keyword ?: '';
    }
    
    private function get_yoast_focus_keyword($post_id) {
        if (!class_exists('WPSEO_Options')) {
            return '';
        }
        
        $focus_keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        return $focus_keyword ?: '';
    }
    
    public function get_seo_title($post_id) {
        // Try RankMath
        $rankmath_title = get_post_meta($post_id, 'rank_math_title', true);
        if (!empty($rankmath_title)) {
            return $rankmath_title;
        }
        
        // Try Yoast
        $yoast_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        if (!empty($yoast_title)) {
            return $yoast_title;
        }
        
        // Fallback to post title
        return get_the_title($post_id);
    }
    
    public function get_meta_description($post_id) {
        // Try RankMath
        $rankmath_desc = get_post_meta($post_id, 'rank_math_description', true);
        if (!empty($rankmath_desc)) {
            return $rankmath_desc;
        }
        
        // Try Yoast
        $yoast_desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        if (!empty($yoast_desc)) {
            return $yoast_desc;
        }
        
        // Generate from content
        $post = get_post($post_id);
        if ($post) {
            return wp_trim_words(wp_strip_all_tags($post->post_content), 25);
        }
        
        return '';
    }
    
    public function trigger_seo_analysis_update($post_id) {
        // Trigger RankMath analysis update
        if (class_exists('RankMath')) {
            do_action('rank_math/analysis/update', $post_id);
        }
        
        // For Yoast, we can't trigger automatic updates due to API limitations
        // Users will need to manually refresh the analysis
    }
    
    public function is_seo_plugin_active() {
        return class_exists('RankMath') || class_exists('WPSEO_Options');
    }
    
    public function get_active_seo_plugin() {
        if (class_exists('RankMath')) {
            return 'rankmath';
        }
        
        if (class_exists('WPSEO_Options')) {
            return 'yoast';
        }
        
        return null;
    }
}