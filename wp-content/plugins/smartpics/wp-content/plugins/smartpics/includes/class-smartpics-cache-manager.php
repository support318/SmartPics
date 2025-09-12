<?php

if (!defined('ABSPATH')) {
    exit;
}

class SmartPics_Cache_Manager {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('smartpics_settings', array());
    }
    
    public function cache_result($image_hash, $result, $context_data = array()) {
        if (!$this->is_caching_enabled()) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartpics_image_cache';
        
        $similarity_hash = $this->generate_similarity_hash($result);
        $cache_duration = $this->get_cache_duration();
        
        return $wpdb->replace(
            $table_name,
            array(
                'image_hash' => $image_hash,
                'similarity_hash' => $similarity_hash,
                'alt_text' => $result['alt_text'],
                'caption' => $result['caption'],
                'title' => $result['title'],
                'ai_provider' => $result['provider'],
                'confidence_score' => $result['confidence'],
                'context_data' => maybe_serialize($context_data),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', time() + ($cache_duration * DAY_IN_SECONDS)),
                'usage_count' => 1
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d')
        );
    }
    
    public function get_cached_result($image_hash) {
        if (!$this->is_caching_enabled()) {
            return null;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartpics_image_cache';
        
        $cached = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE image_hash = %s AND (expires_at IS NULL OR expires_at > NOW())",
            $image_hash
        ));
        
        if ($cached) {
            // Update usage count
            $wpdb->update(
                $table_name,
                array('usage_count' => $cached->usage_count + 1),
                array('id' => $cached->id),
                array('%d'),
                array('%d')
            );
            
            return array(
                'alt_text' => $cached->alt_text,
                'caption' => $cached->caption,
                'title' => $cached->title,
                'provider' => $cached->ai_provider,
                'confidence' => $cached->confidence_score,
                'cached' => true,
                'cache_date' => $cached->created_at
            );
        }
        
        return null;
    }
    
    public function get_similar_cached_result($similarity_hash, $threshold = null) {
        if (!$this->is_caching_enabled() || !$this->is_similarity_enabled()) {
            return null;
        }
        
        $threshold = $threshold ?? $this->get_similarity_threshold();
        $similar_results = SmartPics_Database::get_similar_images($similarity_hash, $threshold);
        
        if (!empty($similar_results)) {
            $best_match = $similar_results[0];
            
            // Update usage count
            global $wpdb;
            $table_name = $wpdb->prefix . 'smartpics_image_cache';
            $wpdb->update(
                $table_name,
                array('usage_count' => $best_match->usage_count + 1),
                array('id' => $best_match->id),
                array('%d'),
                array('%d')
            );
            
            return array(
                'alt_text' => $best_match->alt_text,
                'caption' => $best_match->caption,
                'title' => $best_match->title,
                'provider' => $best_match->ai_provider,
                'confidence' => $best_match->confidence_score,
                'cached' => true,
                'similarity_match' => true,
                'similarity_score' => $best_match->similarity_score,
                'cache_date' => $best_match->created_at
            );
        }
        
        return null;
    }
    
    public function clear_cache($type = 'all') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartpics_image_cache';
        
        switch ($type) {
            case 'expired':
                return $wpdb->query("DELETE FROM $table_name WHERE expires_at IS NOT NULL AND expires_at < NOW()");
                
            case 'low_usage':
                return $wpdb->query("DELETE FROM $table_name WHERE usage_count < 2 AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
                
            case 'old':
                return $wpdb->query("DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
                
            case 'all':
            default:
                return $wpdb->query("TRUNCATE TABLE $table_name");
        }
    }
    
    public function get_cache_statistics() {
        return SmartPics_Database::get_cache_statistics();
    }
    
    public function optimize_cache() {
        $this->clear_cache('expired');
        $this->clear_cache('low_usage');
        
        // Update similarity clusters
        $this->update_similarity_clusters();
        
        return array(
            'expired_cleared' => $this->clear_cache('expired'),
            'low_usage_cleared' => $this->clear_cache('low_usage'),
            'clusters_updated' => true
        );
    }
    
    private function generate_similarity_hash($result) {
        $content = $result['alt_text'] . $result['caption'] . $result['title'];
        return hash('sha256', $content);
    }
    
    private function update_similarity_clusters() {
        global $wpdb;
        $cache_table = $wpdb->prefix . 'aialt_image_cache';
        $clusters_table = $wpdb->prefix . 'smartpics_similarity_clusters';
        
        // Find images with high similarity that could be clustered
        $similar_groups = $wpdb->get_results("
            SELECT 
                similarity_hash,
                GROUP_CONCAT(image_hash) as member_hashes,
                COUNT(*) as cluster_size,
                MIN(id) as representative_id
            FROM $cache_table 
            WHERE expires_at > NOW()
            GROUP BY similarity_hash
            HAVING cluster_size > 1
        ");
        
        foreach ($similar_groups as $group) {
            $cluster_id = 'cluster_' . hash('md5', $group->similarity_hash);
            
            $wpdb->replace(
                $clusters_table,
                array(
                    'cluster_id' => $cluster_id,
                    'representative_hash' => $group->similarity_hash,
                    'member_hashes' => $group->member_hashes,
                    'cluster_size' => $group->cluster_size,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%d', '%s', '%s')
            );
        }
    }
    
    public function schedule_cleanup() {
        if (!wp_next_scheduled('smartpics_cleanup_cache')) {
            wp_schedule_event(time(), 'daily', 'smartpics_cleanup_cache');
        }
        
        add_action('smartpics_cleanup_cache', array($this, 'optimize_cache'));
    }
    
    private function is_caching_enabled() {
        return !empty($this->settings['enable_caching']);
    }
    
    private function is_similarity_enabled() {
        return !empty($this->settings['enable_similarity_detection']);
    }
    
    private function get_cache_duration() {
        return intval($this->settings['cache_duration'] ?? 30);
    }
    
    private function get_similarity_threshold() {
        return floatval($this->settings['similarity_threshold'] ?? 0.85);
    }
}