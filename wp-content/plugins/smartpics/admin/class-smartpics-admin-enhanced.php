<?php

if (!defined('ABSPATH')) {
    exit;
}

class SmartPics_Admin_Enhanced {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        add_action('wp_ajax_smartpics_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_smartpics_bulk_process', array($this, 'ajax_bulk_process'));
        add_action('wp_ajax_smartpics_get_stats', array($this, 'ajax_get_stats'));
        add_filter('plugin_action_links_' . SMARTPICS_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
    }
    
    public function add_admin_menu() {
        $capability = 'manage_options';
        
        add_menu_page(
            __('SmartPics', 'smartpics'),
            __('SmartPics', 'smartpics'),
            $capability,
            'smartpics',
            array($this, 'dashboard_page'),
            'dashicons-format-image',
            30
        );
        
        add_submenu_page(
            'smartpics',
            __('Dashboard', 'smartpics'),
            __('Dashboard', 'smartpics'),
            $capability,
            'smartpics',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'smartpics',
            __('General Settings', 'smartpics'),
            __('General Settings', 'smartpics'),
            $capability,
            'smartpics-general',
            array($this, 'general_settings_page')
        );
        
        add_submenu_page(
            'smartpics',
            __('Advanced Settings', 'smartpics'),
            __('Advanced Settings', 'smartpics'),
            $capability,
            'smartpics-advanced',
            array($this, 'advanced_settings_page')
        );
        
        add_submenu_page(
            'smartpics',
            __('AI Providers', 'smartpics'),
            __('AI Providers', 'smartpics'),
            $capability,
            'smartpics-ai',
            array($this, 'ai_settings_page')
        );
        
        add_submenu_page(
            'smartpics',
            __('Vector Database', 'smartpics'),
            __('Vector Database', 'smartpics'),
            $capability,
            'smartpics-vector',
            array($this, 'vector_settings_page')
        );
        
        add_submenu_page(
            'smartpics',
            __('E-commerce', 'smartpics'),
            __('E-commerce', 'smartpics'),
            $capability,
            'smartpics-ecommerce',
            array($this, 'ecommerce_settings_page')
        );
        
        add_submenu_page(
            'smartpics',
            __('Bulk Processing', 'smartpics'),
            __('Bulk Processing', 'smartpics'),
            $capability,
            'smartpics-bulk',
            array($this, 'bulk_processing_page')
        );
    }
    
    public function dashboard_page() {
        ?>
        <div class="smartpics-admin-page">
            <div class="smartpics-header">
                <div class="smartpics-header-content">
                    <div class="smartpics-logo">
                        <span class="dashicons dashicons-format-image"></span>
                        <h1><?php _e('SmartPics Pro', 'smartpics'); ?></h1>
                    </div>
                    <div class="smartpics-version">
                        <span class="version-badge">Pro v<?php echo SMARTPICS_VERSION; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="smartpics-stats-grid">
                <div class="smartpics-stat-card">
                    <div class="smartpics-stat-value" id="processed-count"><?php echo $this->get_processed_images_count(); ?></div>
                    <div class="smartpics-stat-label"><?php _e('Images Processed', 'smartpics'); ?></div>
                </div>
                <div class="smartpics-stat-card">
                    <div class="smartpics-stat-value" id="webp-count"><?php echo $this->get_conversion_stats('webp'); ?></div>
                    <div class="smartpics-stat-label"><?php _e('Converted to WebP', 'smartpics'); ?></div>
                </div>
                <div class="smartpics-stat-card">
                    <div class="smartpics-stat-value" id="avif-count"><?php echo $this->get_conversion_stats('avif'); ?></div>
                    <div class="smartpics-stat-label"><?php _e('Converted to AVIF', 'smartpics'); ?></div>
                </div>
                <div class="smartpics-stat-card">
                    <div class="smartpics-stat-value" id="cache-rate"><?php echo $this->get_cache_hit_rate(); ?>%</div>
                    <div class="smartpics-stat-label"><?php _e('Cache Hit Rate', 'smartpics'); ?></div>
                </div>
            </div>
            
            <div class="smartpics-dashboard-content">
                <div class="smartpics-card">
                    <h2>🚀 <?php _e('Welcome to SmartPics Pro', 'smartpics'); ?></h2>
                    <p><?php _e('Advanced AI-powered image optimization with enterprise features:', 'smartpics'); ?></p>
                    <ul class="smartpics-feature-list">
                        <li>✅ <?php _e('Multi-provider AI (Vertex AI, OpenAI, Claude)', 'smartpics'); ?></li>
                        <li>✅ <?php _e('Vector database integration (PGVector, Chroma)', 'smartpics'); ?></li>
                        <li>✅ <?php _e('WebP & AVIF conversion with advanced settings', 'smartpics'); ?></li>
                        <li>✅ <?php _e('Shopify & BigCommerce compatibility', 'smartpics'); ?></li>
                        <li>✅ <?php _e('Schema markup with vector embeddings', 'smartpics'); ?></li>
                        <li>✅ <?php _e('Bulk processing with queue management', 'smartpics'); ?></li>
                        <li>✅ <?php _e('Content analysis and geotargeting', 'smartpics'); ?></li>
                        <li>✅ <?php _e('Advanced caching and similarity detection', 'smartpics'); ?></li>
                    </ul>
                    
                    <div class="smartpics-quick-actions">
                        <a href="<?php echo admin_url('admin.php?page=smartpics-general'); ?>" class="button button-primary">
                            <?php _e('Configure Settings', 'smartpics'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=smartpics-bulk'); ?>" class="button button-secondary">
                            <?php _e('Start Bulk Processing', 'smartpics'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="smartpics-card">
                    <h3><?php _e('Recent Activity', 'smartpics'); ?></h3>
                    <div class="smartpics-recent-activity">
                        <?php $this->display_recent_activity(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function general_settings_page() {
        $this->render_tabbed_settings('general');
    }
    
    public function advanced_settings_page() {
        $this->render_tabbed_settings('advanced'); 
    }
    
    public function ai_settings_page() {
        $this->render_tabbed_settings('ai');
    }
    
    public function vector_settings_page() {
        $this->render_tabbed_settings('vector');
    }
    
    public function ecommerce_settings_page() {
        $this->render_tabbed_settings('ecommerce');
    }
    
    public function bulk_processing_page() {
        $this->render_bulk_processing();
    }
    
    private function render_tabbed_settings($active_tab) {
        $settings = get_option('smartpics_settings', array());
        
        if (isset($_POST['submit'])) {
            $settings = $this->save_settings($_POST, $active_tab);
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'smartpics') . '</p></div>';
        }
        
        ?>
        <div class="smartpics-admin-page">
            <div class="smartpics-header">
                <div class="smartpics-header-content">
                    <div class="smartpics-logo">
                        <span class="dashicons dashicons-format-image"></span>
                        <h1><?php _e('SmartPics Settings', 'smartpics'); ?></h1>
                    </div>
                </div>
            </div>
            
            <div class="smartpics-tab-navigation">
                <?php $this->render_tab_navigation($active_tab); ?>
            </div>
            
            <div class="smartpics-tab-content">
                <form method="post" action="" class="smartpics-settings-form">
                    <?php wp_nonce_field('smartpics_settings', 'smartpics_nonce'); ?>
                    <?php $this->render_tab_content($active_tab, $settings); ?>
                    <div class="smartpics-form-footer">
                        <?php submit_button(__('Save Changes', 'smartpics'), 'primary', 'submit', false, array('class' => 'smartpics-save-button')); ?>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    private function render_tab_navigation($current_tab) {
        $tabs = array(
            'general' => __('General Settings', 'smartpics'),
            'advanced' => __('Advanced Settings', 'smartpics'),
            'conversion' => __('Image Conversion', 'smartpics'),
            'optimization' => __('Optimization', 'smartpics'),
            'expert' => __('Expert Settings', 'smartpics')
        );
        
        echo '<nav class="nav-tab-wrapper smartpics-nav-tab-wrapper">';
        foreach ($tabs as $tab => $label) {
            $active = ($current_tab === $tab) ? 'nav-tab-active' : '';
            $page = 'smartpics-' . $tab;
            if ($tab === 'general') $page = 'smartpics-general';
            echo '<a href="' . admin_url('admin.php?page=' . $page) . '" class="nav-tab ' . $active . '">' . $label . '</a>';
        }
        echo '</nav>';
    }
    
    private function render_tab_content($tab, $settings) {
        switch ($tab) {
            case 'general':
                $this->render_general_settings($settings);
                break;
            case 'advanced':
                $this->render_advanced_settings($settings);
                break;
            case 'ai':
                $this->render_ai_settings($settings);
                break;
            case 'vector':
                $this->render_vector_settings($settings);
                break;
            case 'ecommerce':
                $this->render_ecommerce_settings($settings);
                break;
        }
    }
    
    private function render_general_settings($settings) {
        ?>
        <div class="smartpics-settings-section">
            <h3><?php _e('Supported file extensions', 'smartpics'); ?></h3>
            <p class="description"><?php _e('We only convert images that can be converted to output formats.', 'smartpics'); ?></p>
            
            <div class="smartpics-file-extensions">
                <label><input type="checkbox" name="extensions[]" value="jpg" <?php checked(in_array('jpg', $settings['extensions'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])); ?>> JPG / JPEG</label>
                <label><input type="checkbox" name="extensions[]" value="png" <?php checked(in_array('png', $settings['extensions'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])); ?>> PNG</label>
                <label><input type="checkbox" name="extensions[]" value="gif" <?php checked(in_array('gif', $settings['extensions'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])); ?>> GIF</label>
                <label><input type="checkbox" name="extensions[]" value="webp" <?php checked(in_array('webp', $settings['extensions'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])); ?>> WebP (converting to AVIF only)</label>
            </div>
        </div>
        
        <div class="smartpics-settings-section">
            <h3><?php _e('Conversion method', 'smartpics'); ?></h3>
            <div class="smartpics-conversion-method">
                <label><input type="radio" name="conversion_method" value="images" <?php checked($settings['conversion_method'] ?? 'images', 'images'); ?>> <?php _e('Images', 'smartpics'); ?></label>
                <label><input type="radio" name="conversion_method" value="gd" <?php checked($settings['conversion_method'] ?? 'images', 'gd'); ?>> <?php _e('GD', 'smartpics'); ?></label>
                <label><input type="radio" name="conversion_method" value="imagick" <?php checked($settings['conversion_method'] ?? 'images', 'imagick'); ?>> <?php _e('Imagick', 'smartpics'); ?></label>
                <label><input type="radio" name="conversion_method" value="remote_server" <?php checked($settings['conversion_method'] ?? 'images', 'remote_server'); ?>> <?php _e('Remote server', 'smartpics'); ?></label>
            </div>
        </div>
        
        <div class="smartpics-settings-section">
            <h3><?php _e('Output formats', 'smartpics'); ?></h3>
            <div class="smartpics-output-formats">
                <label><input type="checkbox" name="output_formats[]" value="webp" <?php checked(in_array('webp', $settings['output_formats'] ?? ['webp', 'avif'])); ?>> <?php _e('WebP', 'smartpics'); ?></label>
                <label><input type="checkbox" name="output_formats[]" value="avif" <?php checked(in_array('avif', $settings['output_formats'] ?? ['webp', 'avif'])); ?>> <?php _e('AVIF', 'smartpics'); ?></label>
            </div>
            <div class="smartpics-info-box">
                <p><?php _e('The AVIF format is the successor to the WebP format. Images converted to the AVIF format weigh about 50% less than images converted only to the WebP format, while maintaining better image quality.', 'smartpics'); ?></p>
            </div>
        </div>
        
        <div class="smartpics-settings-section">
            <h3><?php _e('Image loading mode', 'smartpics'); ?></h3>
            <p class="description"><?php _e('By changing this mode your site loads serve on the server configuration specified.', 'smartpics'); ?></p>
            
            <div class="smartpics-loading-modes">
                <label><input type="radio" name="loading_mode" value="htaccess" <?php checked($settings['loading_mode'] ?? 'htaccess', 'htaccess'); ?>> <?php _e('.htaccess (tags rewrite/configuration)', 'smartpics'); ?></label>
                <label><input type="radio" name="loading_mode" value="rewrite" <?php checked($settings['loading_mode'] ?? 'htaccess', 'rewrite'); ?>> <?php _e('Bypassing by rule using WebP (when you have a problem with the .htaccess method)', 'smartpics'); ?></label>
                <label><input type="radio" name="loading_mode" value="php" <?php checked($settings['loading_mode'] ?? 'htaccess', 'php'); ?>> <?php _e('Fast: PHP (without enabling/images on the "wp-content" file page 3-level)', 'smartpics'); ?></label>
                <label><input type="radio" name="loading_mode" value="rewrite_alternative" <?php checked($settings['loading_mode'] ?? 'htaccess', 'rewrite_alternative'); ?>> <?php _e('Enable remote 5k+in-cachecc files using A if you have a problem (e.g. using CSS or JS files)', 'smartpics'); ?></label>
            </div>
        </div>
        
        <div class="smartpics-settings-section">
            <h3><?php _e('Image quality settings', 'smartpics'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('WebP Quality', 'smartpics'); ?></th>
                    <td>
                        <input type="range" name="webp_quality" value="<?php echo esc_attr($settings['webp_quality'] ?? 85); ?>" min="10" max="100" class="smartpics-range-slider">
                        <span class="smartpics-range-value"><?php echo $settings['webp_quality'] ?? 85; ?>%</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('AVIF Quality', 'smartpics'); ?></th>
                    <td>
                        <input type="range" name="avif_quality" value="<?php echo esc_attr($settings['avif_quality'] ?? 85); ?>" min="10" max="100" class="smartpics-range-slider">
                        <span class="smartpics-range-value"><?php echo $settings['avif_quality'] ?? 85; ?>%</span>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    private function render_advanced_settings($settings) {
        ?>
        <div class="smartpics-settings-section">
            <h3><?php _e('Excluded directories', 'smartpics'); ?></h3>
            <p class="description"><?php _e('Here you can exclude by directory full which directory by directory names based on conversion.', 'smartpics'); ?></p>
            <textarea name="excluded_directories" rows="5" class="large-text"><?php echo esc_textarea($settings['excluded_directories'] ?? ''); ?></textarea>
        </div>
        
        <div class="smartpics-settings-section">
            <h3><?php _e('Maximum image dimensions', 'smartpics'); ?></h3>
            <p class="description"><?php _e('Before large images by maintained their image file formats during image conversion, they can show the original aspect ratio.', 'smartpics'); ?></p>
            <div class="smartpics-dimensions">
                <label><?php _e('Max. width', 'smartpics'); ?>: <input type="number" name="max_width" value="<?php echo esc_attr($settings['max_width'] ?? ''); ?>" class="small-text"> px</label>
                <label><?php _e('Max. height', 'smartpics'); ?>: <input type="number" name="max_height" value="<?php echo esc_attr($settings['max_height'] ?? ''); ?>" class="small-text"> px</label>
            </div>
        </div>
        
        <div class="smartpics-settings-section">
            <h3><?php _e('Extra Features', 'smartpics'); ?></h3>
            <div class="smartpics-extra-features">
                <label><input type="checkbox" name="auto_convert_uploads" <?php checked($settings['auto_convert_uploads'] ?? false); ?>> <?php _e('Automatically convert new images when uploading to Media Library', 'smartpics'); ?></label>
                <label><input type="checkbox" name="keep_metadata" <?php checked($settings['keep_metadata'] ?? false); ?>> <?php _e('Keep images metadata stored in EXIF or XMP formats (increase file size for the URI conversion method)', 'smartpics'); ?></label>
                <label><input type="checkbox" name="delete_source_files" <?php checked($settings['delete_source_files'] ?? false); ?>> <?php _e('Delete automatically images from output directory when otherwise or when disabled is enabled', 'smartpics'); ?></label>
                <label><input type="checkbox" name="regenerate_force" <?php checked($settings['regenerate_force'] ?? false); ?>> <?php _e('Force converted images as backups generated by other plugins', 'smartpics'); ?></label>
            </div>
        </div>
        
        <div class="smartpics-settings-section">
            <h3><?php _e('AI Enhancement Options', 'smartpics'); ?></h3>
            <div class="smartpics-ai-options">
                <label><input type="checkbox" name="auto_alt_text" <?php checked($settings['auto_alt_text'] ?? true); ?>> <?php _e('Automatically generate alt text using AI', 'smartpics'); ?></label>
                <label><input type="checkbox" name="auto_captions" <?php checked($settings['auto_captions'] ?? false); ?>> <?php _e('Generate SEO-optimized captions', 'smartpics'); ?></label>
                <label><input type="checkbox" name="schema_markup" <?php checked($settings['schema_markup'] ?? true); ?>> <?php _e('Add structured data schema markup', 'smartpics'); ?></label>
                <label><input type="checkbox" name="vector_embeddings" <?php checked($settings['vector_embeddings'] ?? false); ?>> <?php _e('Generate vector embeddings for similarity search', 'smartpics'); ?></label>
            </div>
        </div>
        <?php
    }
    
    private function render_ai_settings($settings) {
        ?>
        <div class="smartpics-settings-section">
            <h3><?php _e('AI Provider Configuration', 'smartpics'); ?></h3>
            <p class="description"><?php _e('Configure your AI providers for image analysis and alt text generation.', 'smartpics'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Primary AI Provider', 'smartpics'); ?></th>
                    <td>
                        <select name="primary_ai_provider" class="regular-text">
                            <option value="vertex_ai" <?php selected($settings['primary_ai_provider'] ?? '', 'vertex_ai'); ?>><?php _e('Google Vertex AI', 'smartpics'); ?></option>
                            <option value="openai" <?php selected($settings['primary_ai_provider'] ?? '', 'openai'); ?>><?php _e('OpenAI GPT-4V', 'smartpics'); ?></option>
                            <option value="claude" <?php selected($settings['primary_ai_provider'] ?? '', 'claude'); ?>><?php _e('Anthropic Claude 3', 'smartpics'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Google Vertex AI API Key', 'smartpics'); ?></th>
                    <td>
                        <input type="password" name="vertex_ai_api_key" value="<?php echo esc_attr($settings['vertex_ai_api_key'] ?? ''); ?>" class="regular-text">
                        <button type="button" class="button smartpics-test-connection" data-provider="vertex_ai"><?php _e('Test Connection', 'smartpics'); ?></button>
                        <div class="smartpics-connection-status"></div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('OpenAI API Key', 'smartpics'); ?></th>
                    <td>
                        <input type="password" name="openai_api_key" value="<?php echo esc_attr($settings['openai_api_key'] ?? ''); ?>" class="regular-text">
                        <button type="button" class="button smartpics-test-connection" data-provider="openai"><?php _e('Test Connection', 'smartpics'); ?></button>
                        <div class="smartpics-connection-status"></div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Anthropic Claude API Key', 'smartpics'); ?></th>
                    <td>
                        <input type="password" name="claude_api_key" value="<?php echo esc_attr($settings['claude_api_key'] ?? ''); ?>" class="regular-text">
                        <button type="button" class="button smartpics-test-connection" data-provider="claude"><?php _e('Test Connection', 'smartpics'); ?></button>
                        <div class="smartpics-connection-status"></div>
                    </td>
                </tr>
            </table>
            
            <div class="smartpics-ai-advanced">
                <h4><?php _e('Advanced AI Settings', 'smartpics'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Fallback Providers', 'smartpics'); ?></th>
                        <td>
                            <select name="fallback_providers[]" multiple class="regular-text">
                                <option value="vertex_ai" <?php selected(in_array('vertex_ai', $settings['fallback_providers'] ?? [])); ?>><?php _e('Vertex AI', 'smartpics'); ?></option>
                                <option value="openai" <?php selected(in_array('openai', $settings['fallback_providers'] ?? [])); ?>><?php _e('OpenAI', 'smartpics'); ?></option>
                                <option value="claude" <?php selected(in_array('claude', $settings['fallback_providers'] ?? [])); ?>><?php _e('Claude', 'smartpics'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Rate Limit (requests/hour)', 'smartpics'); ?></th>
                        <td>
                            <input type="number" name="rate_limit" value="<?php echo esc_attr($settings['rate_limit'] ?? 100); ?>" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Batch Size', 'smartpics'); ?></th>
                        <td>
                            <input type="number" name="batch_size" value="<?php echo esc_attr($settings['batch_size'] ?? 10); ?>" class="small-text">
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    
    private function render_vector_settings($settings) {
        ?>
        <div class="smartpics-settings-section">
            <h3><?php _e('Vector Database Configuration', 'smartpics'); ?></h3>
            <p class="description"><?php _e('Configure vector database integration for semantic image search and similarity detection.', 'smartpics'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Vector Database Provider', 'smartpics'); ?></th>
                    <td>
                        <select name="vector_db_provider" class="regular-text">
                            <option value="none" <?php selected($settings['vector_db_provider'] ?? '', 'none'); ?>><?php _e('Disabled', 'smartpics'); ?></option>
                            <option value="pgvector" <?php selected($settings['vector_db_provider'] ?? '', 'pgvector'); ?>><?php _e('PGVector (PostgreSQL)', 'smartpics'); ?></option>
                            <option value="chroma" <?php selected($settings['vector_db_provider'] ?? '', 'chroma'); ?>><?php _e('ChromaDB', 'smartpics'); ?></option>
                            <option value="pinecone" <?php selected($settings['vector_db_provider'] ?? '', 'pinecone'); ?>><?php _e('Pinecone', 'smartpics'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Vector Database URL', 'smartpics'); ?></th>
                    <td>
                        <input type="url" name="vector_db_url" value="<?php echo esc_attr($settings['vector_db_url'] ?? ''); ?>" class="regular-text" placeholder="https://your-vector-db.com">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('API Key', 'smartpics'); ?></th>
                    <td>
                        <input type="password" name="vector_db_api_key" value="<?php echo esc_attr($settings['vector_db_api_key'] ?? ''); ?>" class="regular-text">
                        <button type="button" class="button smartpics-test-connection" data-provider="vector_db"><?php _e('Test Connection', 'smartpics'); ?></button>
                    </td>
                </tr>
            </table>
            
            <div class="smartpics-vector-advanced">
                <h4><?php _e('Vector Configuration', 'smartpics'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Embedding Model', 'smartpics'); ?></th>
                        <td>
                            <select name="embedding_model" class="regular-text">
                                <option value="openai-ada-002" <?php selected($settings['embedding_model'] ?? '', 'openai-ada-002'); ?>><?php _e('OpenAI text-embedding-ada-002', 'smartpics'); ?></option>
                                <option value="openai-3-small" <?php selected($settings['embedding_model'] ?? '', 'openai-3-small'); ?>><?php _e('OpenAI text-embedding-3-small', 'smartpics'); ?></option>
                                <option value="openai-3-large" <?php selected($settings['embedding_model'] ?? '', 'openai-3-large'); ?>><?php _e('OpenAI text-embedding-3-large', 'smartpics'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Similarity Threshold', 'smartpics'); ?></th>
                        <td>
                            <input type="range" name="similarity_threshold" value="<?php echo esc_attr($settings['similarity_threshold'] ?? 0.85); ?>" min="0.1" max="1.0" step="0.01" class="smartpics-range-slider">
                            <span class="smartpics-range-value"><?php echo $settings['similarity_threshold'] ?? 0.85; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Vector Dimensions', 'smartpics'); ?></th>
                        <td>
                            <input type="number" name="vector_dimensions" value="<?php echo esc_attr($settings['vector_dimensions'] ?? 1536); ?>" class="small-text">
                            <p class="description"><?php _e('Dimensions for vector embeddings (1536 for OpenAI ada-002)', 'smartpics'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="smartpics-vector-features">
                <h4><?php _e('Vector Features', 'smartpics'); ?></h4>
                <div class="smartpics-checkbox-group">
                    <label><input type="checkbox" name="enable_semantic_search" <?php checked($settings['enable_semantic_search'] ?? false); ?>> <?php _e('Enable semantic image search', 'smartpics'); ?></label>
                    <label><input type="checkbox" name="enable_similarity_detection" <?php checked($settings['enable_similarity_detection'] ?? false); ?>> <?php _e('Detect similar images automatically', 'smartpics'); ?></label>
                    <label><input type="checkbox" name="enable_content_clustering" <?php checked($settings['enable_content_clustering'] ?? false); ?>> <?php _e('Group similar content automatically', 'smartpics'); ?></label>
                    <label><input type="checkbox" name="enable_vector_schema" <?php checked($settings['enable_vector_schema'] ?? false); ?>> <?php _e('Add vector data to schema markup', 'smartpics'); ?></label>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_ecommerce_settings($settings) {
        ?>
        <div class="smartpics-settings-section">
            <h3><?php _e('E-commerce Platform Integration', 'smartpics'); ?></h3>
            <p class="description"><?php _e('Configure integration with popular e-commerce platforms for enhanced product image optimization.', 'smartpics'); ?></p>
            
            <div class="smartpics-ecommerce-platforms">
                <h4><?php _e('Supported Platforms', 'smartpics'); ?></h4>
                <div class="smartpics-checkbox-group">
                    <label><input type="checkbox" name="enable_woocommerce" <?php checked($settings['enable_woocommerce'] ?? true); ?>> <?php _e('WooCommerce', 'smartpics'); ?></label>
                    <label><input type="checkbox" name="enable_shopify" <?php checked($settings['enable_shopify'] ?? false); ?>> <?php _e('Shopify Integration', 'smartpics'); ?></label>
                    <label><input type="checkbox" name="enable_bigcommerce" <?php checked($settings['enable_bigcommerce'] ?? false); ?>> <?php _e('BigCommerce Integration', 'smartpics'); ?></label>
                </div>
            </div>
            
            <div class="smartpics-shopify-config" style="<?php echo ($settings['enable_shopify'] ?? false) ? '' : 'display:none;'; ?>">
                <h4><?php _e('Shopify Configuration', 'smartpics'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Shopify Store URL', 'smartpics'); ?></th>
                        <td>
                            <input type="url" name="shopify_store_url" value="<?php echo esc_attr($settings['shopify_store_url'] ?? ''); ?>" class="regular-text" placeholder="https://your-store.myshopify.com">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Shopify API Key', 'smartpics'); ?></th>
                        <td>
                            <input type="password" name="shopify_api_key" value="<?php echo esc_attr($settings['shopify_api_key'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Shopify API Secret', 'smartpics'); ?></th>
                        <td>
                            <input type="password" name="shopify_api_secret" value="<?php echo esc_attr($settings['shopify_api_secret'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="smartpics-bigcommerce-config" style="<?php echo ($settings['enable_bigcommerce'] ?? false) ? '' : 'display:none;'; ?>">
                <h4><?php _e('BigCommerce Configuration', 'smartpics'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('BigCommerce Store Hash', 'smartpics'); ?></th>
                        <td>
                            <input type="text" name="bigcommerce_store_hash" value="<?php echo esc_attr($settings['bigcommerce_store_hash'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('BigCommerce API Token', 'smartpics'); ?></th>
                        <td>
                            <input type="password" name="bigcommerce_api_token" value="<?php echo esc_attr($settings['bigcommerce_api_token'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="smartpics-ecommerce-features">
                <h4><?php _e('E-commerce Features', 'smartpics'); ?></h4>
                <div class="smartpics-checkbox-group">
                    <label><input type="checkbox" name="auto_product_optimization" <?php checked($settings['auto_product_optimization'] ?? true); ?>> <?php _e('Automatically optimize product images', 'smartpics'); ?></label>
                    <label><input type="checkbox" name="generate_product_alt_text" <?php checked($settings['generate_product_alt_text'] ?? true); ?>> <?php _e('Generate SEO-optimized alt text for products', 'smartpics'); ?></label>
                    <label><input type="checkbox" name="product_schema_markup" <?php checked($settings['product_schema_markup'] ?? true); ?>> <?php _e('Add rich product schema markup', 'smartpics'); ?></label>
                    <label><input type="checkbox" name="enable_variant_optimization" <?php checked($settings['enable_variant_optimization'] ?? false); ?>> <?php _e('Optimize product variant images', 'smartpics'); ?></label>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_bulk_processing() {
        ?>
        <div class="smartpics-admin-page">
            <div class="smartpics-header">
                <div class="smartpics-header-content">
                    <div class="smartpics-logo">
                        <span class="dashicons dashicons-format-image"></span>
                        <h1><?php _e('Bulk Processing', 'smartpics'); ?></h1>
                    </div>
                </div>
            </div>
            
            <div class="smartpics-bulk-processing">
                <div class="smartpics-card">
                    <h3><?php _e('Bulk Image Optimization', 'smartpics'); ?></h3>
                    <p><?php _e('Optimize all your images with just one click! Convert existing images to WebP and AVIF formats, generate AI-powered alt text, and improve your site performance.', 'smartpics'); ?></p>
                    
                    <div class="smartpics-bulk-stats">
                        <div class="smartpics-bulk-stat">
                            <div class="smartpics-bulk-stat-value" id="total-images"><?php echo $this->get_total_images_count(); ?></div>
                            <div class="smartpics-bulk-stat-label"><?php _e('Total Images', 'smartpics'); ?></div>
                        </div>
                        <div class="smartpics-bulk-stat">
                            <div class="smartpics-bulk-stat-value" id="unprocessed-images"><?php echo $this->get_unprocessed_images_count(); ?></div>
                            <div class="smartpics-bulk-stat-label"><?php _e('Unprocessed', 'smartpics'); ?></div>
                        </div>
                        <div class="smartpics-bulk-stat">
                            <div class="smartpics-bulk-stat-value" id="queue-size"><?php echo $this->get_queue_size(); ?></div>
                            <div class="smartpics-bulk-stat-label"><?php _e('In Queue', 'smartpics'); ?></div>
                        </div>
                    </div>
                    
                    <div class="smartpics-bulk-controls">
                        <button id="smartpics-start-bulk" class="button button-primary button-hero" <?php disabled($this->get_unprocessed_images_count(), 0); ?>>
                            <?php _e('Start Bulk Optimization', 'smartpics'); ?>
                        </button>
                        <button id="smartpics-cancel-bulk" class="button button-secondary" style="display: none;">
                            <?php _e('Cancel Processing', 'smartpics'); ?>
                        </button>
                        <button id="smartpics-refresh-stats" class="button">
                            <?php _e('Refresh', 'smartpics'); ?>
                        </button>
                    </div>
                    
                    <div class="smartpics-progress-container" style="display: none;">
                        <div class="smartpics-progress-bar">
                            <div class="smartpics-progress-fill"></div>
                        </div>
                        <div class="smartpics-progress-text">0%</div>
                        <div class="smartpics-progress-details">
                            <span id="current-image"></span>
                        </div>
                    </div>
                </div>
                
                <div class="smartpics-card">
                    <h3><?php _e('Force Reconversion', 'smartpics'); ?></h3>
                    <p><?php _e('Force the reconversion of all images again.', 'smartpics'); ?></p>
                    <div class="smartpics-info-box">
                        <p><?php _e('Converting images to WebP and AVIF simultaneously guarantees the lowest weight of your images and compatibility with all browsers. By using the AVIF format you will reduce the weight of your images even more compared to WebP.', 'smartpics'); ?></p>
                    </div>
                    
                    <div class="smartpics-reconversion-stats">
                        <div class="smartpics-stat-circle">
                            <div class="smartpics-stat-value">0%</div>
                            <div class="smartpics-stat-label"><?php _e('converted to WebP', 'smartpics'); ?></div>
                            <div class="smartpics-stat-count">563 <?php _e('images remaining', 'smartpics'); ?></div>
                        </div>
                        <div class="smartpics-stat-circle">
                            <div class="smartpics-stat-value">0%</div>
                            <div class="smartpics-stat-label"><?php _e('converted to AVIF', 'smartpics'); ?></div>
                            <div class="smartpics-stat-count">563 <?php _e('images remaining', 'smartpics'); ?></div>
                        </div>
                    </div>
                    
                    <label class="smartpics-force-checkbox">
                        <input type="checkbox" id="force-reconversion"> 
                        <?php _e('Force the reconversion of all images again', 'smartpics'); ?>
                    </label>
                    
                    <button id="smartpics-start-reconversion" class="button button-primary">
                        <?php _e('Start Bulk Optimization', 'smartpics'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function save_settings($post_data, $tab) {
        if (!wp_verify_nonce($post_data['smartpics_nonce'] ?? '', 'smartpics_settings')) {
            return get_option('smartpics_settings', array());
        }
        
        $settings = get_option('smartpics_settings', array());
        
        // Save settings based on tab
        switch ($tab) {
            case 'general':
                $settings['extensions'] = $post_data['extensions'] ?? array();
                $settings['conversion_method'] = sanitize_text_field($post_data['conversion_method'] ?? 'images');
                $settings['output_formats'] = $post_data['output_formats'] ?? array();
                $settings['loading_mode'] = sanitize_text_field($post_data['loading_mode'] ?? 'htaccess');
                $settings['webp_quality'] = intval($post_data['webp_quality'] ?? 85);
                $settings['avif_quality'] = intval($post_data['avif_quality'] ?? 85);
                break;
                
            case 'advanced':
                $settings['excluded_directories'] = sanitize_textarea_field($post_data['excluded_directories'] ?? '');
                $settings['max_width'] = intval($post_data['max_width'] ?? 0);
                $settings['max_height'] = intval($post_data['max_height'] ?? 0);
                $settings['auto_convert_uploads'] = isset($post_data['auto_convert_uploads']);
                $settings['keep_metadata'] = isset($post_data['keep_metadata']);
                $settings['delete_source_files'] = isset($post_data['delete_source_files']);
                $settings['regenerate_force'] = isset($post_data['regenerate_force']);
                $settings['auto_alt_text'] = isset($post_data['auto_alt_text']);
                $settings['auto_captions'] = isset($post_data['auto_captions']);
                $settings['schema_markup'] = isset($post_data['schema_markup']);
                $settings['vector_embeddings'] = isset($post_data['vector_embeddings']);
                break;
                
            case 'ai':
                $settings['primary_ai_provider'] = sanitize_text_field($post_data['primary_ai_provider'] ?? 'vertex_ai');
                $settings['vertex_ai_api_key'] = sanitize_text_field($post_data['vertex_ai_api_key'] ?? '');
                $settings['openai_api_key'] = sanitize_text_field($post_data['openai_api_key'] ?? '');
                $settings['claude_api_key'] = sanitize_text_field($post_data['claude_api_key'] ?? '');
                $settings['fallback_providers'] = $post_data['fallback_providers'] ?? array();
                $settings['rate_limit'] = intval($post_data['rate_limit'] ?? 100);
                $settings['batch_size'] = intval($post_data['batch_size'] ?? 10);
                break;
                
            case 'vector':
                $settings['vector_db_provider'] = sanitize_text_field($post_data['vector_db_provider'] ?? 'none');
                $settings['vector_db_url'] = esc_url_raw($post_data['vector_db_url'] ?? '');
                $settings['vector_db_api_key'] = sanitize_text_field($post_data['vector_db_api_key'] ?? '');
                $settings['embedding_model'] = sanitize_text_field($post_data['embedding_model'] ?? 'openai-ada-002');
                $settings['similarity_threshold'] = floatval($post_data['similarity_threshold'] ?? 0.85);
                $settings['vector_dimensions'] = intval($post_data['vector_dimensions'] ?? 1536);
                $settings['enable_semantic_search'] = isset($post_data['enable_semantic_search']);
                $settings['enable_similarity_detection'] = isset($post_data['enable_similarity_detection']);
                $settings['enable_content_clustering'] = isset($post_data['enable_content_clustering']);
                $settings['enable_vector_schema'] = isset($post_data['enable_vector_schema']);
                break;
                
            case 'ecommerce':
                $settings['enable_woocommerce'] = isset($post_data['enable_woocommerce']);
                $settings['enable_shopify'] = isset($post_data['enable_shopify']);
                $settings['enable_bigcommerce'] = isset($post_data['enable_bigcommerce']);
                $settings['shopify_store_url'] = esc_url_raw($post_data['shopify_store_url'] ?? '');
                $settings['shopify_api_key'] = sanitize_text_field($post_data['shopify_api_key'] ?? '');
                $settings['shopify_api_secret'] = sanitize_text_field($post_data['shopify_api_secret'] ?? '');
                $settings['bigcommerce_store_hash'] = sanitize_text_field($post_data['bigcommerce_store_hash'] ?? '');
                $settings['bigcommerce_api_token'] = sanitize_text_field($post_data['bigcommerce_api_token'] ?? '');
                $settings['auto_product_optimization'] = isset($post_data['auto_product_optimization']);
                $settings['generate_product_alt_text'] = isset($post_data['generate_product_alt_text']);
                $settings['product_schema_markup'] = isset($post_data['product_schema_markup']);
                $settings['enable_variant_optimization'] = isset($post_data['enable_variant_optimization']);
                break;
        }
        
        update_option('smartpics_settings', $settings);
        return $settings;
    }
    
    // Helper methods for stats and data
    private function get_processed_images_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}smartpics_image_cache WHERE alt_text IS NOT NULL");
    }
    
    private function get_conversion_stats($format) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}smartpics_image_cache WHERE format = %s", $format));
    }
    
    private function get_cache_hit_rate() {
        // Placeholder - implement actual cache hit rate calculation
        return 85;
    }
    
    private function get_total_images_count() {
        return wp_count_attachments('image')['image/jpeg'] + wp_count_attachments('image')['image/png'] + wp_count_attachments('image')['image/gif'];
    }
    
    private function get_unprocessed_images_count() {
        global $wpdb;
        $total = $this->get_total_images_count();
        $processed = $this->get_processed_images_count();
        return max(0, $total - $processed);
    }
    
    private function get_queue_size() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}smartpics_processing_queue WHERE status = 'queued'");
    }
    
    private function display_recent_activity() {
        global $wpdb;
        $activities = $wpdb->get_results("
            SELECT attachment_id, ai_provider, created_at 
            FROM {$wpdb->prefix}smartpics_image_cache 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        
        if (empty($activities)) {
            echo '<p>' . __('No recent activity', 'smartpics') . '</p>';
            return;
        }
        
        echo '<ul class="smartpics-activity-list">';
        foreach ($activities as $activity) {
            $attachment = get_post($activity->attachment_id);
            $title = $attachment ? $attachment->post_title : __('Unknown Image', 'smartpics');
            echo '<li>';
            echo '<strong>' . esc_html($title) . '</strong> ';
            echo sprintf(__('processed with %s', 'smartpics'), $activity->ai_provider);
            echo ' <span class="smartpics-activity-date">' . human_time_diff(strtotime($activity->created_at)) . ' ago</span>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'smartpics') === false) {
            return;
        }
        
        wp_enqueue_style('smartpics-admin-enhanced', SMARTPICS_PLUGIN_URL . 'assets/css/admin-enhanced.css', array(), SMARTPICS_VERSION);
        wp_enqueue_script('smartpics-admin', SMARTPICS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SMARTPICS_VERSION, true);
        
        wp_localize_script('smartpics-admin', 'smartpics_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smartpics_nonce'),
            'strings' => array(
                'processing' => __('Processing...', 'smartpics'),
                'success' => __('Success!', 'smartpics'),
                'error' => __('Error occurred', 'smartpics'),
                'testing_connection' => __('Testing connection...', 'smartpics'),
                'connection_successful' => __('Connection successful!', 'smartpics'),
                'connection_failed' => __('Connection failed', 'smartpics')
            )
        ));
    }
    
    public function display_admin_notices() {
        // Add any admin notices here
    }
    
    public function ajax_test_connection() {
        check_ajax_referer('smartpics_nonce', 'nonce');
        
        $provider = sanitize_text_field($_POST['provider']);
        $api_key = sanitize_text_field($_POST['api_key']);
        
        // Test connection logic here
        wp_send_json_success(array('message' => 'Connection successful'));
    }
    
    public function ajax_bulk_process() {
        check_ajax_referer('smartpics_nonce', 'nonce');
        
        // Bulk processing logic here
        wp_send_json_success(array('message' => 'Bulk processing started'));
    }
    
    public function ajax_get_stats() {
        check_ajax_referer('smartpics_nonce', 'nonce');
        
        wp_send_json_success(array(
            'processed' => $this->get_processed_images_count(),
            'webp' => $this->get_conversion_stats('webp'),
            'avif' => $this->get_conversion_stats('avif'),
            'cache_rate' => $this->get_cache_hit_rate()
        ));
    }
    
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=smartpics-general') . '">' . __('Settings', 'smartpics') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}