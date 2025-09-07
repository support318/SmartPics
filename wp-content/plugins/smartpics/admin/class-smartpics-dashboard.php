<?php

if (!defined('ABSPATH')) {
    exit;
}

class SmartPics_Dashboard {
    
    public function render() {
        echo '<div class="smartpics-admin-page smartpics-dashboard">';
        echo '<div class="smartpics-header">';
        echo '<h1>' . __('SmartPics', 'smartpics') . '<span class="version">v' . SMARTPICS_VERSION . '</span></h1>';
        echo '</div>';
        
        $this->render_stats_grid();
        $this->render_provider_grid();
        $this->render_recent_activity();
        
        echo '</div>';
    }
    
    private function render_stats_grid() {
        $stats = $this->get_dashboard_stats();
        
        echo '<div class="smartpics-stats-grid">';
        
        foreach ($stats as $stat) {
            echo '<div class="smartpics-stat-card" data-stat="' . esc_attr($stat['key']) . '">';
            echo '<h3>' . esc_html($stat['label']) . '</h3>';
            echo '<div class="smartpics-stat-value">' . esc_html($stat['value']) . '</div>';
            if (!empty($stat['change'])) {
                echo '<div class="smartpics-stat-change ' . esc_attr($stat['change_class']) . '">' . esc_html($stat['change']) . '</div>';
            }
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    private function render_provider_grid() {
        echo '<div class="smartpics-provider-grid"></div>';
    }
    
    private function render_recent_activity() {
        echo '<div class="smartpics-chart-container">';
        echo '<h3>' . __('Recent Activity', 'smartpics') . '</h3>';
        echo '<table class="smartpics-table smartpics-recent-activity">';
        echo '<thead><tr><th>Image</th><th>Provider</th><th>Status</th><th>Date</th></tr></thead>';
        echo '<tbody></tbody>';
        echo '</table>';
        echo '</div>';
    }
    
    private function get_dashboard_stats() {
        return array(
            array(
                'key' => 'processed_images',
                'label' => __('Images Processed', 'smartpics'),
                'value' => $this->get_processed_count(),
                'change' => '+12 today',
                'change_class' => 'positive'
            ),
            array(
                'key' => 'cached_images',
                'label' => __('Cached Results', 'smartpics'),
                'value' => $this->get_cache_count()
            ),
            array(
                'key' => 'cost_savings',
                'label' => __('Cost Savings', 'smartpics'),
                'value' => '$' . number_format($this->get_cost_savings(), 2)
            ),
            array(
                'key' => 'success_rate',
                'label' => __('Success Rate', 'smartpics'),
                'value' => $this->get_success_rate() . '%'
            )
        );
    }
    
    private function get_processed_count() {
        global $wpdb;
        return $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_smartpics_processed' AND meta_value = '1'"
        ) ?: 0;
    }
    
    private function get_cache_count() {
        $stats = SmartPics_Database::get_cache_statistics();
        return $stats->total_cached ?? 0;
    }
    
    private function get_cost_savings() {
        $stats = SmartPics_Database::get_cache_statistics();
        return ($stats->total_cache_hits ?? 0) * 0.01; // Estimate $0.01 saved per cache hit
    }
    
    private function get_success_rate() {
        global $wpdb;
        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}smartpics_processing_queue"
        ) ?: 1;
        
        $successful = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}smartpics_processing_queue WHERE status = 'completed'"
        ) ?: 0;
        
        return round(($successful / $total) * 100, 1);
    }
}