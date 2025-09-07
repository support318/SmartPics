<?php

if (!defined('ABSPATH')) {
    exit;
}

class SmartPics_Settings {
    
    private $settings_sections = array();
    private $settings_fields = array();
    
    public function __construct() {
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    public function admin_init() {
        register_setting(
            'smartpics_settings_group',
            'smartpics_settings',
            array($this, 'sanitize_settings')
        );
        
        $this->setup_settings_sections();
        $this->setup_settings_fields();
        $this->register_settings_sections();
        $this->register_settings_fields();
    }
    
    private function setup_settings_sections() {
        $this->settings_sections = array(
            'ai_providers' => array(
                'title' => __('AI Providers', 'smartpics'),
                'description' => __('Configure your AI provider API keys and settings. At least one provider is required.', 'smartpics')
            ),
            'cloudflare' => array(
                'title' => __('Cloudflare Integration', 'smartpics'),
                'description' => __('Configure Cloudflare R2 storage and Workers for image processing.', 'smartpics')
            ),
            'content_analysis' => array(
                'title' => __('Content Analysis', 'smartpics'),
                'description' => __('Settings for content context analysis and topic modeling.', 'smartpics')
            ),
            'seo_integration' => array(
                'title' => __('SEO Integration', 'smartpics'),
                'description' => __('Integration settings for SEO plugins and schema markup.', 'smartpics')
            ),
            'geotargeting' => array(
                'title' => __('Geotargeting', 'smartpics'),
                'description' => __('Google My Business integration for location-aware content.', 'smartpics')
            ),
            'performance' => array(
                'title' => __('Performance & Caching', 'smartpics'),
                'description' => __('Optimize performance with caching and similarity detection.', 'smartpics')
            ),
            'advanced' => array(
                'title' => __('Advanced Settings', 'smartpics'),
                'description' => __('Advanced configuration options for power users.', 'smartpics')
            )
        );
    }
    
    private function setup_settings_fields() {
        $this->settings_fields = array(
            // AI Providers Section
            'ai_providers' => array(
                'primary_ai_provider' => array(
                    'title' => __('Primary AI Provider', 'smartpics'),
                    'type' => 'select',
                    'options' => array(
                        'vertex_ai' => __('Google Vertex AI (Gemini Pro Vision)', 'smartpics'),
                        'openai' => __('OpenAI GPT-4 Vision', 'smartpics'),
                        'claude' => __('Anthropic Claude 3 Vision', 'smartpics')
                    ),
                    'default' => 'vertex_ai',
                    'description' => __('Select your primary AI provider for image analysis.', 'smartpics')
                ),
                'fallback_providers' => array(
                    'title' => __('Fallback Providers', 'smartpics'),
                    'type' => 'multiselect',
                    'options' => array(
                        'vertex_ai' => __('Google Vertex AI', 'smartpics'),
                        'openai' => __('OpenAI GPT-4V', 'smartpics'),
                        'claude' => __('Anthropic Claude 3', 'smartpics')
                    ),
                    'default' => array('openai', 'claude'),
                    'description' => __('Select fallback providers in case the primary provider fails.', 'smartpics')
                ),
                'vertex_ai_project_id' => array(
                    'title' => __('Vertex AI Project ID', 'smartpics'),
                    'type' => 'text',
                    'description' => __('Your Google Cloud Project ID for Vertex AI access.', 'smartpics')
                ),
                'vertex_ai_location' => array(
                    'title' => __('Vertex AI Location', 'smartpics'),
                    'type' => 'select',
                    'options' => array(
                        'us-central1' => __('US Central (Iowa)', 'smartpics'),
                        'us-east1' => __('US East (South Carolina)', 'smartpics'),
                        'us-west1' => __('US West (Oregon)', 'smartpics'),
                        'europe-west1' => __('Europe West (Belgium)', 'smartpics'),
                        'asia-northeast1' => __('Asia Northeast (Tokyo)', 'smartpics')
                    ),
                    'default' => 'us-central1',
                    'description' => __('Select the region for your Vertex AI API calls.', 'smartpics')
                ),
                'vertex_ai_api_key' => array(
                    'title' => __('Vertex AI API Key', 'smartpics'),
                    'type' => 'password',
                    'description' => __('Your Google Cloud API key with Vertex AI permissions.', 'smartpics')
                ),
                'openai_api_key' => array(
                    'title' => __('OpenAI API Key', 'smartpics'),
                    'type' => 'password',
                    'description' => __('Your OpenAI API key for GPT-4 Vision access.', 'smartpics')
                ),
                'claude_api_key' => array(
                    'title' => __('Anthropic API Key', 'smartpics'),
                    'type' => 'password',
                    'description' => __('Your Anthropic API key for Claude 3 Vision access.', 'smartpics')
                )
            ),
            
            // Cloudflare Section
            'cloudflare' => array(
                'cloudflare_account_id' => array(
                    'title' => __('Cloudflare Account ID', 'smartpics'),
                    'type' => 'text',
                    'description' => __('Your Cloudflare account ID for R2 and Workers access.', 'smartpics')
                ),
                'cloudflare_api_token' => array(
                    'title' => __('Cloudflare API Token', 'smartpics'),
                    'type' => 'password',
                    'description' => __('API token with permissions for R2 and Workers.', 'smartpics')
                ),
                'r2_bucket_name' => array(
                    'title' => __('R2 Bucket Name', 'smartpics'),
                    'type' => 'text',
                    'description' => __('Name of your R2 bucket for image storage.', 'smartpics')
                ),
                'r2_access_key_id' => array(
                    'title' => __('R2 Access Key ID', 'smartpics'),
                    'type' => 'text',
                    'description' => __('R2 access key ID for S3-compatible API access.', 'smartpics')
                ),
                'r2_secret_access_key' => array(
                    'title' => __('R2 Secret Access Key', 'smartpics'),
                    'type' => 'password',
                    'description' => __('R2 secret access key for S3-compatible API access.', 'smartpics')
                ),
                'workers_endpoint' => array(
                    'title' => __('Workers API Endpoint', 'smartpics'),
                    'type' => 'url',
                    'description' => __('URL endpoint for your Cloudflare Worker.', 'smartpics')
                )
            ),
            
            // Content Analysis Section
            'content_analysis' => array(
                'enable_content_analysis' => array(
                    'title' => __('Enable Content Analysis', 'smartpics'),
                    'type' => 'checkbox',
                    'default' => true,
                    'description' => __('Analyze surrounding content to improve alt text relevance.', 'smartpics')
                ),
                'content_analysis_depth' => array(
                    'title' => __('Content Analysis Depth', 'smartpics'),
                    'type' => 'select',
                    'options' => array(
                        'paragraph' => __('Current paragraph only', 'smartpics'),
                        'section' => __('Current section', 'smartpics'),
                        'full_page' => __('Full page content', 'smartpics')
                    ),
                    'default' => 'section',
                    'description' => __('How much surrounding content to analyze for context.', 'smartpics')
                ),
                'topic_modeling_enabled' => array(
                    'title' => __('Enable Topic Modeling', 'smartpics'),
                    'type' => 'checkbox',
                    'default' => true,
                    'description' => __('Use AI to identify page topics for better context.', 'smartpics')
                ),
                'sentiment_analysis' => array(
                    'title' => __('Enable Sentiment Analysis', 'smartpics'),
                    'type' => 'checkbox',
                    'default' => false,
                    'description' => __('Analyze content sentiment to match alt text tone.', 'smartpics')
                )
            ),
            
            // SEO Integration Section
            'seo_integration' => array(
                'enable_schema_generation' => array(
                    'title' => __('Enable Schema Markup', 'smartpics'),
                    'type' => 'checkbox',
                    'default' => true,
                    'description' => __('Automatically generate JSON-LD schema markup for images.', 'smartpics')
                ),
                'schema_types' => array(
                    'title' => __('Schema Types', 'smartpics'),
                    'type' => 'multiselect',
                    'options' => array(
                        'ImageObject' => __('Image Object', 'smartpics'),
                        'Product' => __('Product Images', 'smartpics'),
                        'Article' => __('Article Images', 'smartpics'),
                        'Organization' => __('Organization Logo', 'smartpics'),
                        'CreativeWork' => __('Creative Work', 'smartpics')
                    ),
                    'default' => array('ImageObject', 'Product', 'Article'),
                    'description' => __('Select which schema types to generate automatically.', 'smartpics')
                ),
                'rankmath_integration' => array(
                    'title' => __('RankMath Integration', 'smartpics'),
                    'type' => 'checkbox',
                    'default' => true,
                    'description' => __('Integrate with RankMath SEO plugin for focus keywords.', 'smartpics')
                ),
                'yoast_integration' => array(
                    'title' => __('Yoast SEO Integration', 'smartpics'),
                    'type' => 'checkbox',
                    'default' => true,
                    'description' => __('Integrate with Yoast SEO plugin for focus keywords.', 'smartpics')
                )
            ),
            
            // Geotargeting Section
            'geotargeting' => array(
                'enable_geotargeting' => array(
                    'title' => __('Enable Geotargeting', 'smartpics'),
                    'type' => 'checkbox',
                    'default' => false,
                    'description' => __('Enable location-aware content generation using Google My Business.', 'smartpics')
                ),
                'gmb_api_key' => array(
                    'title' => __('Google My Business API Key', 'smartpics'),
                    'type' => 'password',
                    'description' => __('API key for Google My Business integration.', 'smartpics')
                ),
                'vector_db_provider' => array(
                    'title' => __('Vector Database Provider', 'smartpics'),
                    'type' => 'select',
                    'options' => array(
                        'pinecone' => __('Pinecone', 'smartpics'),
                        'milvus' => __('Milvus', 'smartpics'),
                        'weaviate' => __('Weaviate', 'smartpics')
                    ),
                    'default' => 'pinecone',
                    'description' => __('Choose your vector database provider for geotargeting.', 'smartpics')
                ),
                'vector_db_url' => array(
                    'title' => __('Vector Database URL', 'smartpics'),
                    'type' => 'url',
                    'description' => __('Connection URL for your vector database.', 'smartpics')
                ),
                'vector_db_api_key' => array(
                    'title' => __('Vector Database API Key', 'smartpics'),
                    'type' => 'password',
                    'description' => __('API key for vector database access.', 'smartpics')
                )
            ),
            
            // Performance Section
            'performance' => array(
                'enable_caching' => array(
                    'title' => __('Enable AI Response Caching', 'smartpics'),
                    'type' => 'checkbox',
                    'default' => true,
                    'description' => __('Cache AI responses to reduce costs and improve performance.', 'smartpics')
                ),
                'cache_duration' => array(
                    'title' => __('Cache Duration (days)', 'smartpics'),
                    'type' => 'number',
                    'default' => 30,
                    'min' => 1,
                    'max' => 365,
                    'description' => __('How long to keep cached responses (1-365 days).', 'smartpics')
                ),
                'enable_similarity_detection' => array(
                    'title' => __('Enable Similarity Detection', 'smartpics'),
                    'type' => 'checkbox',
                    'default' => true,
                    'description' => __('Use perceptual hashing to detect similar images and reuse alt text.', 'smartpics')
                ),
                'similarity_threshold' => array(
                    'title' => __('Similarity Threshold', 'smartpics'),
                    'type' => 'range',
                    'min' => 0.5,
                    'max' => 1.0,
                    'step' => 0.05,
                    'default' => 0.85,
                    'description' => __('How similar images need to be to reuse alt text (0.5-1.0).', 'smartpics')
                ),
                'batch_size' => array(
                    'title' => __('Batch Processing Size', 'smartpics'),
                    'type' => 'number',
                    'default' => 10,
                    'min' => 1,
                    'max' => 50,
                    'description' => __('Number of images to process simultaneously (1-50).', 'smartpics')
                )
            ),
            
            // Advanced Section
            'advanced' => array(
                'auto_process' => array(
                    'title' => __('Auto-Process New Images', 'smartpics'),
                    'type' => 'checkbox',
                    'default' => true,
                    'description' => __('Automatically process new images when uploaded.', 'smartpics')
                ),
                'rate_limit' => array(
                    'title' => __('API Rate Limit (requests/hour)', 'smartpics'),
                    'type' => 'number',
                    'default' => 100,
                    'min' => 10,
                    'max' => 1000,
                    'description' => __('Maximum API requests per hour across all providers.', 'smartpics')
                ),
                'debug_mode' => array(
                    'title' => __('Debug Mode', 'smartpics'),
                    'type' => 'checkbox',
                    'default' => false,
                    'description' => __('Enable detailed logging for troubleshooting.', 'smartpics')
                ),
                'custom_prompts' => array(
                    'title' => __('Custom AI Prompts', 'smartpics'),
                    'type' => 'textarea',
                    'rows' => 5,
                    'description' => __('Custom prompts for AI providers (JSON format). Leave empty for defaults.', 'smartpics')
                )
            )
        );
    }
    
    private function register_settings_sections() {
        foreach ($this->settings_sections as $section_id => $section) {
            add_settings_section(
                $section_id,
                $section['title'],
                array($this, 'render_section_description'),
                'smartpics-settings'
            );
        }
    }
    
    private function register_settings_fields() {
        foreach ($this->settings_fields as $section_id => $fields) {
            foreach ($fields as $field_id => $field) {
                add_settings_field(
                    $field_id,
                    $field['title'],
                    array($this, 'render_field'),
                    'smartpics-settings',
                    $section_id,
                    array_merge($field, array('field_id' => $field_id, 'section_id' => $section_id))
                );
            }
        }
    }
    
    public function render_section_description($args) {
        $section_id = $args['id'];
        if (isset($this->settings_sections[$section_id]['description'])) {
            echo '<p>' . esc_html($this->settings_sections[$section_id]['description']) . '</p>';
        }
    }
    
    public function render_field($args) {
        $settings = get_option('smartpics_settings', array());
        $field_id = $args['field_id'];
        $value = isset($settings[$field_id]) ? $settings[$field_id] : (isset($args['default']) ? $args['default'] : '');
        $name = "smartpics_settings[$field_id]";
        
        switch ($args['type']) {
            case 'text':
            case 'url':
                echo "<input type='{$args['type']}' name='$name' value='" . esc_attr($value) . "' class='regular-text' />";
                break;
                
            case 'password':
                $display_value = !empty($value) ? str_repeat('*', 12) : '';
                echo "<input type='password' name='$name' value='" . esc_attr($display_value) . "' class='regular-text' />";
                break;
                
            case 'number':
            case 'range':
                $min = isset($args['min']) ? "min='{$args['min']}'" : '';
                $max = isset($args['max']) ? "max='{$args['max']}'" : '';
                $step = isset($args['step']) ? "step='{$args['step']}'" : '';
                echo "<input type='{$args['type']}' name='$name' value='" . esc_attr($value) . "' $min $max $step />";
                if ($args['type'] === 'range') {
                    echo "<span class='range-value'>$value</span>";
                }
                break;
                
            case 'checkbox':
                $checked = checked($value, true, false);
                echo "<input type='checkbox' name='$name' value='1' $checked />";
                break;
                
            case 'select':
                echo "<select name='$name'>";
                foreach ($args['options'] as $option_value => $option_label) {
                    $selected = selected($value, $option_value, false);
                    echo "<option value='" . esc_attr($option_value) . "' $selected>" . esc_html($option_label) . "</option>";
                }
                echo "</select>";
                break;
                
            case 'multiselect':
                $value = is_array($value) ? $value : array();
                echo "<select name='{$name}[]' multiple size='4' style='height: auto;'>";
                foreach ($args['options'] as $option_value => $option_label) {
                    $selected = in_array($option_value, $value) ? 'selected' : '';
                    echo "<option value='" . esc_attr($option_value) . "' $selected>" . esc_html($option_label) . "</option>";
                }
                echo "</select>";
                break;
                
            case 'textarea':
                $rows = isset($args['rows']) ? $args['rows'] : 3;
                echo "<textarea name='$name' rows='$rows' class='large-text'>" . esc_textarea($value) . "</textarea>";
                break;
        }
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
        
        if ($args['type'] === 'password' && in_array($field_id, array('vertex_ai_api_key', 'openai_api_key', 'claude_api_key'))) {
            echo '<button type="button" class="button smartpics-test-connection" data-provider="' . esc_attr(str_replace('_api_key', '', $field_id)) . '">';
            echo __('Test Connection', 'smartpics');
            echo '</button>';
            echo '<span class="smartpics-connection-status"></span>';
        }
    }
    
    public function sanitize_settings($input) {
        $sanitized = array();
        $settings = get_option('smartpics_settings', array());
        
        foreach ($this->settings_fields as $section_id => $fields) {
            foreach ($fields as $field_id => $field) {
                if (!isset($input[$field_id])) {
                    if ($field['type'] === 'checkbox') {
                        $sanitized[$field_id] = false;
                    } elseif ($field['type'] === 'multiselect') {
                        $sanitized[$field_id] = array();
                    } else {
                        $sanitized[$field_id] = isset($settings[$field_id]) ? $settings[$field_id] : '';
                    }
                    continue;
                }
                
                $value = $input[$field_id];
                
                switch ($field['type']) {
                    case 'text':
                    case 'url':
                        $sanitized[$field_id] = sanitize_text_field($value);
                        break;
                        
                    case 'password':
                        if (strpos($value, '*') !== false) {
                            $sanitized[$field_id] = isset($settings[$field_id]) ? $settings[$field_id] : '';
                        } else {
                            $sanitized[$field_id] = sanitize_text_field($value);
                        }
                        break;
                        
                    case 'number':
                    case 'range':
                        $sanitized[$field_id] = floatval($value);
                        if (isset($field['min'])) {
                            $sanitized[$field_id] = max($field['min'], $sanitized[$field_id]);
                        }
                        if (isset($field['max'])) {
                            $sanitized[$field_id] = min($field['max'], $sanitized[$field_id]);
                        }
                        break;
                        
                    case 'checkbox':
                        $sanitized[$field_id] = (bool) $value;
                        break;
                        
                    case 'select':
                        $sanitized[$field_id] = sanitize_text_field($value);
                        break;
                        
                    case 'multiselect':
                        $sanitized[$field_id] = is_array($value) ? array_map('sanitize_text_field', $value) : array();
                        break;
                        
                    case 'textarea':
                        $sanitized[$field_id] = sanitize_textarea_field($value);
                        break;
                        
                    default:
                        $sanitized[$field_id] = sanitize_text_field($value);
                        break;
                }
            }
        }
        
        return $sanitized;
    }
}