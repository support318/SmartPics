<?php

if (!defined('ABSPATH')) {
    exit;
}

class AIALT_AI_Providers {
    
    private $settings;
    private $providers = array();
    
    public function __construct() {
        $this->settings = get_option('aialt_settings', array());
        $this->initialize_providers();
    }
    
    private function initialize_providers() {
        $this->providers = array(
            'vertex_ai' => array(
                'name' => 'Google Vertex AI (Gemini Pro Vision)',
                'enabled' => !empty($this->settings['vertex_ai_api_key']),
                'cost_per_1k_tokens' => 0.0025,
                'max_image_size' => 20 * 1024 * 1024, // 20MB
                'supported_formats' => array('jpeg', 'jpg', 'png', 'webp', 'heic', 'heif')
            ),
            'openai' => array(
                'name' => 'OpenAI GPT-4 Vision',
                'enabled' => !empty($this->settings['openai_api_key']),
                'cost_per_1k_tokens' => 0.01,
                'max_image_size' => 20 * 1024 * 1024, // 20MB
                'supported_formats' => array('jpeg', 'jpg', 'png', 'webp', 'gif')
            ),
            'claude' => array(
                'name' => 'Anthropic Claude 3 Vision',
                'enabled' => !empty($this->settings['claude_api_key']),
                'cost_per_1k_tokens' => 0.015,
                'max_image_size' => 5 * 1024 * 1024, // 5MB
                'supported_formats' => array('jpeg', 'jpg', 'png', 'webp')
            )
        );
    }
    
    public function get_available_providers() {
        return array_filter($this->providers, function($provider) {
            return $provider['enabled'];
        });
    }
    
    public function get_provider_priority() {
        $primary = $this->settings['primary_ai_provider'] ?? 'vertex_ai';
        $fallbacks = $this->settings['fallback_providers'] ?? array();
        
        $priority = array($primary);
        
        foreach ($fallbacks as $fallback) {
            if ($fallback !== $primary && isset($this->providers[$fallback]) && $this->providers[$fallback]['enabled']) {
                $priority[] = $fallback;
            }
        }
        
        return $priority;
    }
    
    public function analyze_image($image_path, $context_data = array()) {
        $providers = $this->get_provider_priority();
        $last_error = null;
        
        foreach ($providers as $provider_id) {
            if (!$this->providers[$provider_id]['enabled']) {
                continue;
            }
            
            $start_time = microtime(true);
            
            try {
                $result = $this->call_provider($provider_id, $image_path, $context_data);
                
                if ($result && !is_wp_error($result)) {
                    $response_time = round((microtime(true) - $start_time) * 1000);
                    
                    AIALT_Database::log_provider_request(
                        $provider_id,
                        true,
                        $result['token_usage'] ?? 0,
                        $result['estimated_cost'] ?? 0,
                        $response_time
                    );
                    
                    $result['provider'] = $provider_id;
                    $result['response_time'] = $response_time;
                    
                    return $result;
                }
            } catch (Exception $e) {
                $response_time = round((microtime(true) - $start_time) * 1000);
                $last_error = $e->getMessage();
                
                AIALT_Database::log_provider_request($provider_id, false, 0, 0, $response_time);
                
                error_log("AI Alt Text Generator: {$provider_id} failed - " . $e->getMessage());
                continue;
            }
        }
        
        return new WP_Error('all_providers_failed', 
            sprintf(__('All AI providers failed. Last error: %s', 'ai-alt-text-generator'), $last_error)
        );
    }
    
    private function call_provider($provider_id, $image_path, $context_data = array()) {
        switch ($provider_id) {
            case 'vertex_ai':
                return $this->call_vertex_ai($image_path, $context_data);
            case 'openai':
                return $this->call_openai($image_path, $context_data);
            case 'claude':
                return $this->call_claude($image_path, $context_data);
            default:
                throw new Exception("Unknown provider: $provider_id");
        }
    }
    
    private function call_vertex_ai($image_path, $context_data = array()) {
        $project_id = $this->settings['vertex_ai_project_id'] ?? '';
        $location = $this->settings['vertex_ai_location'] ?? 'us-central1';
        $api_key = $this->settings['vertex_ai_api_key'] ?? '';
        
        if (empty($project_id) || empty($api_key)) {
            throw new Exception('Vertex AI credentials not configured');
        }
        
        $image_data = $this->prepare_image_data($image_path);
        $prompt = $this->build_prompt($context_data);
        
        $endpoint = "https://{$location}-aiplatform.googleapis.com/v1/projects/{$project_id}/locations/{$location}/publishers/google/models/gemini-pro-vision:predict";
        
        $request_body = array(
            'instances' => array(
                array(
                    'prompt' => $prompt,
                    'image' => array(
                        'bytesBase64Encoded' => base64_encode($image_data)
                    )
                )
            ),
            'parameters' => array(
                'temperature' => 0.2,
                'maxOutputTokens' => 256,
                'topP' => 0.8,
                'topK' => 40
            )
        );
        
        $response = wp_remote_post($endpoint, array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($request_body)
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            $error_message = $data['error']['message'] ?? 'Unknown error';
            throw new Exception("API Error: $error_message");
        }
        
        return $this->parse_vertex_ai_response($data, $context_data);
    }
    
    private function call_openai($image_path, $context_data = array()) {
        $api_key = $this->settings['openai_api_key'] ?? '';
        
        if (empty($api_key)) {
            throw new Exception('OpenAI API key not configured');
        }
        
        $image_data = $this->prepare_image_data($image_path);
        $prompt = $this->build_prompt($context_data, 'openai');
        
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        
        $request_body = array(
            'model' => 'gpt-4-vision-preview',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'text',
                            'text' => $prompt
                        ),
                        array(
                            'type' => 'image_url',
                            'image_url' => array(
                                'url' => 'data:image/jpeg;base64,' . base64_encode($image_data)
                            )
                        )
                    )
                )
            ),
            'max_tokens' => 256,
            'temperature' => 0.2
        );
        
        $response = wp_remote_post($endpoint, array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($request_body)
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            $error_message = $data['error']['message'] ?? 'Unknown error';
            throw new Exception("API Error: $error_message");
        }
        
        return $this->parse_openai_response($data, $context_data);
    }
    
    private function call_claude($image_path, $context_data = array()) {
        $api_key = $this->settings['claude_api_key'] ?? '';
        
        if (empty($api_key)) {
            throw new Exception('Claude API key not configured');
        }
        
        $image_data = $this->prepare_image_data($image_path);
        $prompt = $this->build_prompt($context_data, 'claude');
        
        $endpoint = 'https://api.anthropic.com/v1/messages';
        
        $request_body = array(
            'model' => 'claude-3-sonnet-20240229',
            'max_tokens' => 256,
            'temperature' => 0.2,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'image',
                            'source' => array(
                                'type' => 'base64',
                                'media_type' => 'image/jpeg',
                                'data' => base64_encode($image_data)
                            )
                        ),
                        array(
                            'type' => 'text',
                            'text' => $prompt
                        )
                    )
                )
            )
        );
        
        $response = wp_remote_post($endpoint, array(
            'timeout' => 60,
            'headers' => array(
                'x-api-key' => $api_key,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ),
            'body' => wp_json_encode($request_body)
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            $error_message = $data['error']['message'] ?? 'Unknown error';
            throw new Exception("API Error: $error_message");
        }
        
        return $this->parse_claude_response($data, $context_data);
    }
    
    private function prepare_image_data($image_path) {
        if (!file_exists($image_path)) {
            throw new Exception("Image file not found: $image_path");
        }
        
        return file_get_contents($image_path);
    }
    
    private function build_prompt($context_data = array(), $provider = 'vertex_ai') {
        $custom_prompts = $this->settings['custom_prompts'] ?? '';
        
        if (!empty($custom_prompts)) {
            $prompts = json_decode($custom_prompts, true);
            if (isset($prompts[$provider])) {
                return $this->replace_prompt_variables($prompts[$provider], $context_data);
            }
        }
        
        $base_prompt = "Analyze this image and generate SEO-optimized metadata in JSON format with the following structure:\n\n";
        $base_prompt .= "{\n";
        $base_prompt .= '  "alt_text": "Concise, descriptive alt text (max 125 characters)",';
        $base_prompt .= '  "caption": "Engaging caption for social media or blog posts",';
        $base_prompt .= '  "title": "SEO-friendly image title",';
        $base_prompt .= '  "confidence": 0.95';
        $base_prompt .= "}\n\n";
        
        $base_prompt .= "Guidelines:\n";
        $base_prompt .= "- Alt text should be descriptive but concise (under 125 characters)\n";
        $base_prompt .= "- Avoid phrases like 'image of', 'picture of', 'photo of'\n";
        $base_prompt .= "- Include relevant keywords naturally\n";
        $base_prompt .= "- Caption should be engaging and informative\n";
        $base_prompt .= "- Title should be SEO-friendly and descriptive\n";
        $base_prompt .= "- Confidence should reflect how certain you are about the analysis (0.0-1.0)\n";
        
        if (!empty($context_data['focus_keyword'])) {
            $base_prompt .= "\nFocus keyword to incorporate naturally: " . $context_data['focus_keyword'];
        }
        
        if (!empty($context_data['page_content'])) {
            $base_prompt .= "\nPage context: " . substr($context_data['page_content'], 0, 500) . "...";
        }
        
        if (!empty($context_data['topics'])) {
            $base_prompt .= "\nPage topics: " . implode(', ', array_slice($context_data['topics'], 0, 5));
        }
        
        if (!empty($context_data['location_data'])) {
            $base_prompt .= "\nBusiness location context: " . $context_data['location_data'];
        }
        
        return $base_prompt;
    }
    
    private function replace_prompt_variables($prompt, $context_data) {
        $replacements = array(
            '{focus_keyword}' => $context_data['focus_keyword'] ?? '',
            '{page_content}' => $context_data['page_content'] ?? '',
            '{topics}' => !empty($context_data['topics']) ? implode(', ', $context_data['topics']) : '',
            '{location_data}' => $context_data['location_data'] ?? ''
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $prompt);
    }
    
    private function parse_vertex_ai_response($data, $context_data) {
        $predictions = $data['predictions'] ?? array();
        
        if (empty($predictions)) {
            throw new Exception('No predictions in response');
        }
        
        $content = $predictions[0]['content'] ?? '';
        $parsed = $this->parse_json_response($content);
        
        $token_usage = $data['metadata']['tokenMetadata']['inputTokenCount']['totalTokens'] ?? 0;
        $token_usage += $data['metadata']['tokenMetadata']['outputTokenCount']['totalTokens'] ?? 0;
        
        $parsed['token_usage'] = $token_usage;
        $parsed['estimated_cost'] = ($token_usage / 1000) * $this->providers['vertex_ai']['cost_per_1k_tokens'];
        
        return $parsed;
    }
    
    private function parse_openai_response($data, $context_data) {
        $choices = $data['choices'] ?? array();
        
        if (empty($choices)) {
            throw new Exception('No choices in response');
        }
        
        $content = $choices[0]['message']['content'] ?? '';
        $parsed = $this->parse_json_response($content);
        
        $token_usage = $data['usage']['total_tokens'] ?? 0;
        $parsed['token_usage'] = $token_usage;
        $parsed['estimated_cost'] = ($token_usage / 1000) * $this->providers['openai']['cost_per_1k_tokens'];
        
        return $parsed;
    }
    
    private function parse_claude_response($data, $context_data) {
        $content_blocks = $data['content'] ?? array();
        
        if (empty($content_blocks)) {
            throw new Exception('No content in response');
        }
        
        $content = $content_blocks[0]['text'] ?? '';
        $parsed = $this->parse_json_response($content);
        
        $token_usage = $data['usage']['input_tokens'] ?? 0;
        $token_usage += $data['usage']['output_tokens'] ?? 0;
        
        $parsed['token_usage'] = $token_usage;
        $parsed['estimated_cost'] = ($token_usage / 1000) * $this->providers['claude']['cost_per_1k_tokens'];
        
        return $parsed;
    }
    
    private function parse_json_response($content) {
        $json_start = strpos($content, '{');
        $json_end = strrpos($content, '}');
        
        if ($json_start === false || $json_end === false) {
            throw new Exception('No JSON found in response');
        }
        
        $json_content = substr($content, $json_start, $json_end - $json_start + 1);
        $parsed = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in response: ' . json_last_error_msg());
        }
        
        $required_fields = array('alt_text', 'caption', 'title');
        foreach ($required_fields as $field) {
            if (empty($parsed[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        $parsed['alt_text'] = substr($parsed['alt_text'], 0, 125);
        $parsed['confidence'] = isset($parsed['confidence']) ? (float) $parsed['confidence'] : 0.8;
        
        return $parsed;
    }
    
    public function test_connection($provider, $api_key = null) {
        if ($api_key) {
            $original_key = $this->settings["{$provider}_api_key"] ?? '';
            $this->settings["{$provider}_api_key"] = $api_key;
        }
        
        try {
            $test_image_path = AIALT_PLUGIN_PATH . 'assets/images/test-image.jpg';
            
            if (!file_exists($test_image_path)) {
                $test_image_data = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gODUK/9sAQwAGBAUGBQQGBgUGBwcGCAoQCgoJCQoUDg0NDhQUExMTExQUFhQUGh0aGhsUFhoYICMgGxoXLiMiFxcXKhceEhwfFBof/8AACwgAAAIABAEiEQMRAD8A5Vv7y5v725ubu6uJ7u7uJZbm6uJGaSSaVy7yyOxLMxYksTySa+o');
                file_put_contents($test_image_path, $test_image_data);
            }
            
            $result = $this->call_provider($provider, $test_image_path, array());
            
            if ($api_key) {
                $this->settings["{$provider}_api_key"] = $original_key;
            }
            
            return !is_wp_error($result) && !empty($result['alt_text']);
            
        } catch (Exception $e) {
            if ($api_key) {
                $this->settings["{$provider}_api_key"] = $original_key;
            }
            return false;
        }
    }
}