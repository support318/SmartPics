<?php

if (!defined('ABSPATH')) {
    exit;
}

class AIALT_Schema_Generator {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('aialt_settings', array());
    }
    
    public function generate_page_schema($post_id) {
        if (!$this->is_schema_enabled()) {
            return null;
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return null;
        }
        
        $schema = array();
        
        // Get images in the post
        $images = $this->get_post_images($post_id);
        
        foreach ($images as $image) {
            $image_schema = $this->generate_image_schema($image, $post);
            if ($image_schema) {
                $schema[] = $image_schema;
            }
        }
        
        // Generate main content schema
        $main_schema = $this->generate_content_schema($post);
        if ($main_schema) {
            $schema[] = $main_schema;
        }
        
        return !empty($schema) ? $schema : null;
    }
    
    private function generate_image_schema($attachment_id, $post) {
        $image_url = wp_get_attachment_image_url($attachment_id, 'full');
        $image_meta = wp_get_attachment_metadata($attachment_id);
        
        if (!$image_url) {
            return null;
        }
        
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        $caption = wp_get_attachment_caption($attachment_id);
        $title = get_the_title($attachment_id);
        
        $schema = array(
            '@type' => 'ImageObject',
            '@id' => get_permalink($post->ID) . '#image-' . $attachment_id,
            'url' => $image_url,
            'contentUrl' => $image_url
        );
        
        if (!empty($alt_text)) {
            $schema['name'] = $alt_text;
            $schema['alternateName'] = $alt_text;
        }
        
        if (!empty($title)) {
            $schema['headline'] = $title;
        }
        
        if (!empty($caption)) {
            $schema['description'] = $caption;
        }
        
        if (!empty($image_meta)) {
            $schema['width'] = $image_meta['width'] ?? null;
            $schema['height'] = $image_meta['height'] ?? null;
            
            if (isset($image_meta['filesize'])) {
                $schema['contentSize'] = $image_meta['filesize'];
            }
        }
        
        $schema['uploadDate'] = get_the_date('c', $attachment_id);
        
        // Add author information
        $author = get_user_by('id', $post->post_author);
        if ($author) {
            $schema['author'] = array(
                '@type' => 'Person',
                'name' => $author->display_name
            );
        }
        
        // Add license information if available
        $license = get_post_meta($attachment_id, '_aialt_license', true);
        if (!empty($license)) {
            $schema['license'] = $license;
        }
        
        return $schema;
    }
    
    private function generate_content_schema($post) {
        $schema_types = $this->settings['schema_types'] ?? array();
        
        if (empty($schema_types)) {
            return null;
        }
        
        $post_type = get_post_type($post);
        $schema = null;
        
        // Determine appropriate schema type
        if (in_array('Article', $schema_types) && in_array($post_type, array('post', 'page'))) {
            $schema = $this->generate_article_schema($post);
        } elseif (in_array('Product', $schema_types) && $post_type === 'product') {
            $schema = $this->generate_product_schema($post);
        } elseif (in_array('Organization', $schema_types)) {
            $schema = $this->generate_organization_schema($post);
        }
        
        return $schema;
    }
    
    private function generate_article_schema($post) {
        $schema = array(
            '@type' => 'Article',
            '@id' => get_permalink($post->ID) . '#article',
            'headline' => get_the_title($post->ID),
            'url' => get_permalink($post->ID),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID),
            'articleBody' => wp_strip_all_tags($post->post_content)
        );
        
        // Add author
        $author = get_user_by('id', $post->post_author);
        if ($author) {
            $schema['author'] = array(
                '@type' => 'Person',
                'name' => $author->display_name,
                'url' => get_author_posts_url($author->ID)
            );
        }
        
        // Add featured image
        $featured_image_id = get_post_thumbnail_id($post->ID);
        if ($featured_image_id) {
            $featured_image_url = wp_get_attachment_image_url($featured_image_id, 'large');
            if ($featured_image_url) {
                $schema['image'] = array(
                    '@type' => 'ImageObject',
                    'url' => $featured_image_url
                );
            }
        }
        
        // Add publisher information
        $schema['publisher'] = $this->get_publisher_schema();
        
        // Add categories as keywords
        $categories = get_the_category($post->ID);
        if (!empty($categories)) {
            $schema['keywords'] = array_map(function($cat) {
                return $cat->name;
            }, $categories);
        }
        
        return $schema;
    }
    
    private function generate_product_schema($post) {
        // This would be expanded for WooCommerce integration
        $schema = array(
            '@type' => 'Product',
            '@id' => get_permalink($post->ID) . '#product',
            'name' => get_the_title($post->ID),
            'url' => get_permalink($post->ID),
            'description' => wp_strip_all_tags($post->post_content)
        );
        
        // Add product images
        $gallery_images = get_post_meta($post->ID, '_product_image_gallery', true);
        if (!empty($gallery_images)) {
            $image_ids = explode(',', $gallery_images);
            $images = array();
            
            foreach ($image_ids as $image_id) {
                $image_url = wp_get_attachment_image_url(trim($image_id), 'large');
                if ($image_url) {
                    $images[] = $image_url;
                }
            }
            
            if (!empty($images)) {
                $schema['image'] = $images;
            }
        }
        
        return $schema;
    }
    
    private function generate_organization_schema($post) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        
        $schema = array(
            '@type' => 'Organization',
            '@id' => $site_url . '#organization',
            'name' => $site_name,
            'url' => $site_url
        );
        
        // Add logo if available
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_url) {
                $schema['logo'] = array(
                    '@type' => 'ImageObject',
                    'url' => $logo_url
                );
            }
        }
        
        return $schema;
    }
    
    private function get_publisher_schema() {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        
        $publisher = array(
            '@type' => 'Organization',
            'name' => $site_name,
            'url' => $site_url
        );
        
        // Add logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_url) {
                $publisher['logo'] = array(
                    '@type' => 'ImageObject',
                    'url' => $logo_url
                );
            }
        }
        
        return $publisher;
    }
    
    private function get_post_images($post_id) {
        $images = array();
        
        // Get featured image
        $featured_image_id = get_post_thumbnail_id($post_id);
        if ($featured_image_id) {
            $images[] = $featured_image_id;
        }
        
        // Get images from content
        $post = get_post($post_id);
        if ($post) {
            preg_match_all('/wp-image-(\d+)/', $post->post_content, $matches);
            if (!empty($matches[1])) {
                $images = array_merge($images, $matches[1]);
            }
            
            // Also check for attachment URLs
            preg_match_all('/src=["\']([^"\']*wp-content\/uploads[^"\']*)["\']/', $post->post_content, $url_matches);
            if (!empty($url_matches[1])) {
                foreach ($url_matches[1] as $url) {
                    $attachment_id = attachment_url_to_postid($url);
                    if ($attachment_id) {
                        $images[] = $attachment_id;
                    }
                }
            }
        }
        
        return array_unique($images);
    }
    
    private function is_schema_enabled() {
        return !empty($this->settings['enable_schema_generation']);
    }
    
    public function save_schema_data($post_id, $schema_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aialt_schema_markup';
        
        // Delete existing schema for this post
        $wpdb->delete($table_name, array('post_id' => $post_id), array('%d'));
        
        // Insert new schema data
        foreach ($schema_data as $schema) {
            $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'schema_type' => $schema['@type'],
                    'schema_data' => wp_json_encode($schema),
                    'is_active' => 1,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%d', '%s')
            );
        }
    }
}