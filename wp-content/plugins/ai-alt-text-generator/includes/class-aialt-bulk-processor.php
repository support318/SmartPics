<?php

if (!defined('ABSPATH')) {
    exit;
}

class AIALT_Bulk_Processor {
    
    public function start_bulk_job() {
        $job_id = wp_generate_uuid4();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'aialt_bulk_jobs';
        
        $wpdb->insert(
            $table_name,
            array(
                'id' => $job_id,
                'user_id' => get_current_user_id(),
                'status' => 'queued',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s')
        );
        
        return $job_id;
    }
    
    public function get_job_progress($job_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aialt_bulk_jobs';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %s",
            $job_id
        ), ARRAY_A);
    }
}