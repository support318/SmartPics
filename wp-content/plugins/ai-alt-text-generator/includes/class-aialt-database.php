<?php

if (!defined('ABSPATH')) {
    exit;
}

class AIALT_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array(
            'processing_queue' => "
                CREATE TABLE {$wpdb->prefix}aialt_processing_queue (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    attachment_id bigint(20) UNSIGNED NOT NULL,
                    post_id bigint(20) UNSIGNED DEFAULT NULL,
                    status varchar(20) NOT NULL DEFAULT 'queued',
                    priority int(11) NOT NULL DEFAULT 5,
                    attempts int(11) NOT NULL DEFAULT 0,
                    max_attempts int(11) NOT NULL DEFAULT 3,
                    error_message text DEFAULT NULL,
                    created_at datetime NOT NULL,
                    updated_at datetime DEFAULT NULL,
                    processed_at datetime DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY attachment_id (attachment_id),
                    KEY status (status),
                    KEY priority (priority),
                    KEY created_at (created_at)
                ) $charset_collate;
            ",
            
            'image_cache' => "
                CREATE TABLE {$wpdb->prefix}aialt_image_cache (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    image_hash varchar(64) NOT NULL,
                    similarity_hash varchar(64) NOT NULL,
                    alt_text text DEFAULT NULL,
                    caption text DEFAULT NULL,
                    title varchar(255) DEFAULT NULL,
                    ai_provider varchar(50) NOT NULL,
                    confidence_score decimal(3,2) DEFAULT NULL,
                    context_data longtext DEFAULT NULL,
                    created_at datetime NOT NULL,
                    updated_at datetime DEFAULT NULL,
                    expires_at datetime DEFAULT NULL,
                    usage_count int(11) NOT NULL DEFAULT 0,
                    PRIMARY KEY (id),
                    UNIQUE KEY image_hash (image_hash),
                    KEY similarity_hash (similarity_hash),
                    KEY ai_provider (ai_provider),
                    KEY expires_at (expires_at),
                    KEY usage_count (usage_count)
                ) $charset_collate;
            ",
            
            'content_analysis' => "
                CREATE TABLE {$wpdb->prefix}aialt_content_analysis (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    post_id bigint(20) UNSIGNED NOT NULL,
                    content_hash varchar(64) NOT NULL,
                    extracted_topics longtext DEFAULT NULL,
                    keywords longtext DEFAULT NULL,
                    sentiment_score decimal(3,2) DEFAULT NULL,
                    readability_score int(11) DEFAULT NULL,
                    context_embeddings longtext DEFAULT NULL,
                    created_at datetime NOT NULL,
                    updated_at datetime DEFAULT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY post_content_hash (post_id, content_hash),
                    KEY post_id (post_id),
                    KEY created_at (created_at)
                ) $charset_collate;
            ",
            
            'schema_markup' => "
                CREATE TABLE {$wpdb->prefix}aialt_schema_markup (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    post_id bigint(20) UNSIGNED NOT NULL,
                    attachment_id bigint(20) UNSIGNED DEFAULT NULL,
                    schema_type varchar(100) NOT NULL,
                    schema_data longtext NOT NULL,
                    is_active tinyint(1) NOT NULL DEFAULT 1,
                    created_at datetime NOT NULL,
                    updated_at datetime DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY post_id (post_id),
                    KEY attachment_id (attachment_id),
                    KEY schema_type (schema_type),
                    KEY is_active (is_active)
                ) $charset_collate;
            ",
            
            'ai_provider_stats' => "
                CREATE TABLE {$wpdb->prefix}aialt_ai_provider_stats (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    provider varchar(50) NOT NULL,
                    date date NOT NULL,
                    total_requests int(11) NOT NULL DEFAULT 0,
                    successful_requests int(11) NOT NULL DEFAULT 0,
                    failed_requests int(11) NOT NULL DEFAULT 0,
                    total_tokens int(11) NOT NULL DEFAULT 0,
                    estimated_cost decimal(10,4) NOT NULL DEFAULT 0.0000,
                    average_response_time int(11) NOT NULL DEFAULT 0,
                    created_at datetime NOT NULL,
                    updated_at datetime DEFAULT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY provider_date (provider, date),
                    KEY provider (provider),
                    KEY date (date)
                ) $charset_collate;
            ",
            
            'bulk_jobs' => "
                CREATE TABLE {$wpdb->prefix}aialt_bulk_jobs (
                    id varchar(36) NOT NULL,
                    user_id bigint(20) UNSIGNED NOT NULL,
                    status varchar(20) NOT NULL DEFAULT 'queued',
                    total_images int(11) NOT NULL DEFAULT 0,
                    processed_images int(11) NOT NULL DEFAULT 0,
                    successful_images int(11) NOT NULL DEFAULT 0,
                    failed_images int(11) NOT NULL DEFAULT 0,
                    settings longtext DEFAULT NULL,
                    error_log longtext DEFAULT NULL,
                    created_at datetime NOT NULL,
                    started_at datetime DEFAULT NULL,
                    completed_at datetime DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY user_id (user_id),
                    KEY status (status),
                    KEY created_at (created_at)
                ) $charset_collate;
            ",
            
            'geotargeting_data' => "
                CREATE TABLE {$wpdb->prefix}aialt_geotargeting_data (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    business_id varchar(255) NOT NULL,
                    business_name varchar(255) NOT NULL,
                    address text DEFAULT NULL,
                    phone varchar(50) DEFAULT NULL,
                    website varchar(255) DEFAULT NULL,
                    business_type varchar(100) DEFAULT NULL,
                    coordinates point DEFAULT NULL,
                    service_area longtext DEFAULT NULL,
                    embeddings longtext DEFAULT NULL,
                    last_updated datetime NOT NULL,
                    is_active tinyint(1) NOT NULL DEFAULT 1,
                    PRIMARY KEY (id),
                    UNIQUE KEY business_id (business_id),
                    KEY business_name (business_name),
                    KEY business_type (business_type),
                    KEY is_active (is_active),
                    SPATIAL KEY coordinates (coordinates)
                ) $charset_collate;
            ",
            
            'similarity_clusters' => "
                CREATE TABLE {$wpdb->prefix}aialt_similarity_clusters (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    cluster_id varchar(64) NOT NULL,
                    representative_hash varchar(64) NOT NULL,
                    member_hashes longtext NOT NULL,
                    cluster_size int(11) NOT NULL DEFAULT 1,
                    created_at datetime NOT NULL,
                    updated_at datetime DEFAULT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY cluster_id (cluster_id),
                    KEY representative_hash (representative_hash),
                    KEY cluster_size (cluster_size)
                ) $charset_collate;
            "
        );
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $table_name => $sql) {
            dbDelta($sql);
        }
        
        self::create_indexes();
    }
    
    private static function create_indexes() {
        global $wpdb;
        
        $indexes = array(
            // Composite indexes for common queries
            "CREATE INDEX idx_queue_status_priority ON {$wpdb->prefix}aialt_processing_queue (status, priority, created_at)",
            "CREATE INDEX idx_cache_similarity_expires ON {$wpdb->prefix}aialt_image_cache (similarity_hash, expires_at)",
            "CREATE INDEX idx_content_post_updated ON {$wpdb->prefix}aialt_content_analysis (post_id, updated_at)",
            "CREATE INDEX idx_schema_post_type_active ON {$wpdb->prefix}aialt_schema_markup (post_id, schema_type, is_active)",
            "CREATE INDEX idx_stats_provider_date ON {$wpdb->prefix}aialt_ai_provider_stats (provider, date DESC)",
            "CREATE INDEX idx_bulk_user_status ON {$wpdb->prefix}aialt_bulk_jobs (user_id, status, created_at DESC)"
        );
        
        foreach ($indexes as $index_sql) {
            $wpdb->query($index_sql);
        }
    }
    
    public static function get_processing_queue_count($status = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aialt_processing_queue';
        
        if ($status) {
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE status = %s",
                $status
            ));
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
    
    public static function get_cache_statistics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aialt_image_cache';
        
        return $wpdb->get_row("
            SELECT 
                COUNT(*) as total_cached,
                SUM(usage_count) as total_cache_hits,
                COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active_cache,
                AVG(confidence_score) as avg_confidence
            FROM $table_name
        ");
    }
    
    public static function cleanup_expired_cache() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aialt_image_cache';
        
        return $wpdb->query("
            DELETE FROM $table_name 
            WHERE expires_at IS NOT NULL AND expires_at < NOW()
        ");
    }
    
    public static function get_provider_statistics($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aialt_ai_provider_stats';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                provider,
                SUM(total_requests) as total_requests,
                SUM(successful_requests) as successful_requests,
                SUM(failed_requests) as failed_requests,
                SUM(total_tokens) as total_tokens,
                SUM(estimated_cost) as estimated_cost,
                AVG(average_response_time) as avg_response_time,
                (SUM(successful_requests) / SUM(total_requests) * 100) as success_rate
            FROM $table_name 
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY provider
            ORDER BY total_requests DESC
        ", $days));
    }
    
    public static function log_provider_request($provider, $success = true, $tokens = 0, $cost = 0, $response_time = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aialt_ai_provider_stats';
        $today = current_time('Y-m-d');
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE provider = %s AND date = %s",
            $provider,
            $today
        ));
        
        if ($existing) {
            $wpdb->update(
                $table_name,
                array(
                    'total_requests' => $existing->total_requests + 1,
                    'successful_requests' => $existing->successful_requests + ($success ? 1 : 0),
                    'failed_requests' => $existing->failed_requests + ($success ? 0 : 1),
                    'total_tokens' => $existing->total_tokens + $tokens,
                    'estimated_cost' => $existing->estimated_cost + $cost,
                    'average_response_time' => (($existing->average_response_time * $existing->total_requests) + $response_time) / ($existing->total_requests + 1),
                    'updated_at' => current_time('mysql')
                ),
                array(
                    'provider' => $provider,
                    'date' => $today
                ),
                array('%d', '%d', '%d', '%d', '%f', '%d', '%s'),
                array('%s', '%s')
            );
        } else {
            $wpdb->insert(
                $table_name,
                array(
                    'provider' => $provider,
                    'date' => $today,
                    'total_requests' => 1,
                    'successful_requests' => $success ? 1 : 0,
                    'failed_requests' => $success ? 0 : 1,
                    'total_tokens' => $tokens,
                    'estimated_cost' => $cost,
                    'average_response_time' => $response_time,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%d', '%d', '%d', '%d', '%f', '%d', '%s')
            );
        }
    }
    
    public static function get_content_analysis($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aialt_content_analysis';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_id = %d ORDER BY updated_at DESC LIMIT 1",
            $post_id
        ));
    }
    
    public static function save_content_analysis($post_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aialt_content_analysis';
        $content_hash = md5(serialize($data));
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE post_id = %d AND content_hash = %s",
            $post_id,
            $content_hash
        ));
        
        if ($existing) {
            return $wpdb->update(
                $table_name,
                array(
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $existing->id),
                array('%s'),
                array('%d')
            );
        }
        
        return $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'content_hash' => $content_hash,
                'extracted_topics' => maybe_serialize($data['topics']),
                'keywords' => maybe_serialize($data['keywords']),
                'sentiment_score' => $data['sentiment_score'],
                'readability_score' => $data['readability_score'],
                'context_embeddings' => maybe_serialize($data['embeddings']),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%f', '%d', '%s', '%s')
        );
    }
    
    public static function get_similar_images($similarity_hash, $threshold = 0.85) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aialt_image_cache';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT *, 
                   (1 - (BIT_COUNT(CONV(similarity_hash, 16, 10) ^ CONV(%s, 16, 10)) / 64.0)) as similarity_score
            FROM $table_name 
            WHERE (1 - (BIT_COUNT(CONV(similarity_hash, 16, 10) ^ CONV(%s, 16, 10)) / 64.0)) >= %f
              AND expires_at > NOW()
            ORDER BY similarity_score DESC
            LIMIT 10
        ", $similarity_hash, $similarity_hash, $threshold));
    }
}