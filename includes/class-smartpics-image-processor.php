<?php

if (!defined('ABSPATH')) {
    exit;
}

class SmartPics_Image_Processor {
    
    private $ai_providers;
    private $cache_manager;
    private $similarity_detector;
    private $content_analyzer;
    
    public function __construct() {
        $this->ai_providers = new SmartPics_AI_Providers();
        $this->cache_manager = new SmartPics_Cache_Manager();
        $this->similarity_detector = new SmartPics_Similarity_Detector();
        $this->content_analyzer = new SmartPics_Content_Analyzer();
    }
    
    public function process_image($attachment_id, $context = array()) {
        $attachment = get_post($attachment_id);
        
        if (!$attachment || !wp_attachment_is_image($attachment_id)) {
            return new WP_Error('invalid_attachment', __('Invalid image attachment.', 'smartpics'));
        }
        
        $image_path = get_attached_file($attachment_id);
        if (!file_exists($image_path)) {
            return new WP_Error('file_not_found', __('Image file not found.', 'smartpics'));
        }
        
        // Check if already processed
        if ($this->is_already_processed($attachment_id)) {
            return new WP_Error('already_processed', __('Image already has alt text.', 'smartpics'));
        }
        
        // Check similarity cache
        $similarity_hash = $this->similarity_detector->generate_hash($image_path);
        $cached_similar = $this->cache_manager->get_similar_cached_result($similarity_hash);
        
        if ($cached_similar) {
            $this->apply_metadata($attachment_id, $cached_similar);
            return $cached_similar;
        }
        
        // Get context data
        $context_data = $this->get_context_data($attachment, $context);
        
        // Process with AI
        $result = $this->ai_providers->analyze_image($image_path, $context_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Cache the result
        $this->cache_manager->cache_result($similarity_hash, $result, $context_data);
        
        // Apply to WordPress
        $this->apply_metadata($attachment_id, $result);
        
        return $result;
    }
    
    private function is_already_processed($attachment_id) {
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        return !empty($alt_text);
    }
    
    private function get_context_data($attachment, $context = array()) {
        $data = array();
        
        // Get post context if attached to a post
        $post_id = $attachment->post_parent;
        if ($post_id) {
            $post = get_post($post_id);
            if ($post) {
                $data['post_id'] = $post_id;
                $data['post_title'] = $post->post_title;
                $data['post_content'] = wp_strip_all_tags($post->post_content);
                
                // Get SEO data
                $seo_integration = new SmartPics_SEO_Integration();
                $data['focus_keyword'] = $seo_integration->get_focus_keyword($post_id);
                
                // Get content analysis
                $analysis = $this->content_analyzer->analyze_content($post_id);
                if ($analysis) {
                    $data['topics'] = $analysis['topics'] ?? array();
                    $data['sentiment_score'] = $analysis['sentiment_score'] ?? null;
                }
            }
        }
        
        // Add any passed context
        $data = array_merge($data, $context);
        
        return $data;
    }
    
    private function apply_metadata($attachment_id, $result) {
        if (!empty($result['alt_text'])) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($result['alt_text']));
        }
        
        if (!empty($result['caption'])) {
            wp_update_post(array(
                'ID' => $attachment_id,
                'post_excerpt' => sanitize_text_field($result['caption'])
            ));
        }
        
        if (!empty($result['title'])) {
            wp_update_post(array(
                'ID' => $attachment_id,
                'post_title' => sanitize_text_field($result['title'])
            ));
        }
        
        // Store metadata for analytics
        update_post_meta($attachment_id, '_smartpics_processed', true);
        update_post_meta($attachment_id, '_smartpics_provider', $result['provider'] ?? 'unknown');
        update_post_meta($attachment_id, '_smartpics_confidence', $result['confidence'] ?? 0);
        update_post_meta($attachment_id, '_smartpics_processed_date', current_time('mysql'));
    }
    
    public function bulk_process($limit = 50, $offset = 0) {
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => $limit,
            'offset' => $offset,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_wp_attachment_image_alt',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_wp_attachment_image_alt',
                    'value' => '',
                    'compare' => '='
                )
            )
        ));
        
        $results = array(
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        foreach ($attachments as $attachment) {
            $results['processed']++;
            
            $result = $this->process_image($attachment->ID);
            
            if (is_wp_error($result)) {
                $results['failed']++;
                $results['errors'][] = array(
                    'id' => $attachment->ID,
                    'error' => $result->get_error_message()
                );
            } else {
                $results['successful']++;
            }
        }
        
        return $results;
    }
}