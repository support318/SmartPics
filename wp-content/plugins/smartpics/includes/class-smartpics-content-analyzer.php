<?php

if (!defined('ABSPATH')) {
    exit;
}

class SmartPics_Content_Analyzer {
    
    public function analyze_content($post_id) {
        $cached_analysis = SmartPics_Database::get_content_analysis($post_id);
        
        if ($cached_analysis && $this->is_analysis_fresh($cached_analysis)) {
            return array(
                'topics' => maybe_unserialize($cached_analysis->extracted_topics),
                'keywords' => maybe_unserialize($cached_analysis->keywords),
                'sentiment_score' => $cached_analysis->sentiment_score,
                'readability_score' => $cached_analysis->readability_score,
                'embeddings' => maybe_unserialize($cached_analysis->context_embeddings)
            );
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return null;
        }
        
        $content = $this->extract_content($post);
        $analysis = $this->perform_analysis($content);
        
        // Cache the results
        SmartPics_Database::save_content_analysis($post_id, $analysis);
        
        return $analysis;
    }
    
    private function extract_content($post) {
        $content = array(
            'title' => $post->post_title,
            'content' => wp_strip_all_tags($post->post_content),
            'excerpt' => $post->post_excerpt ?: wp_trim_words($post->post_content, 50)
        );
        
        // Extract headings
        preg_match_all('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $post->post_content, $headings);
        $content['headings'] = array_map('strip_tags', $headings[1]);
        
        // Extract meta information
        $content['categories'] = wp_get_post_categories($post->ID, array('fields' => 'names'));
        $content['tags'] = wp_get_post_tags($post->ID, array('fields' => 'names'));
        
        return $content;
    }
    
    private function perform_analysis($content) {
        $analysis = array(
            'topics' => $this->extract_topics($content),
            'keywords' => $this->extract_keywords($content),
            'sentiment_score' => $this->analyze_sentiment($content),
            'readability_score' => $this->calculate_readability($content),
            'embeddings' => array() // Placeholder for future vector embeddings
        );
        
        return $analysis;
    }
    
    private function extract_topics($content) {
        $text = $content['title'] . ' ' . $content['content'] . ' ' . implode(' ', $content['headings']);
        $text = strtolower($text);
        
        // Simple topic extraction using keyword frequency and TF-IDF concepts
        $topics = array();
        
        // Include categories and tags as topics
        $topics = array_merge($topics, $content['categories'], $content['tags']);
        
        // Extract key phrases from content
        $key_phrases = $this->extract_key_phrases($text);
        $topics = array_merge($topics, $key_phrases);
        
        // Remove duplicates and limit
        $topics = array_unique($topics);
        $topics = array_slice($topics, 0, 10);
        
        return array_values($topics);
    }
    
    private function extract_key_phrases($text) {
        // Common stop words
        $stop_words = array(
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with',
            'by', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had',
            'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must',
            'can', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they'
        );
        
        // Remove punctuation and split into words
        $text = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $text);
        $words = preg_split('/\s+/', $text);
        
        // Filter out stop words and short words
        $words = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 3 && !in_array($word, $stop_words);
        });
        
        // Count word frequency
        $word_counts = array_count_values($words);
        arsort($word_counts);
        
        // Get top keywords
        $keywords = array_keys(array_slice($word_counts, 0, 20, true));
        
        // Extract 2-word phrases
        $phrases = array();
        for ($i = 0; $i < count($words) - 1; $i++) {
            $phrase = $words[$i] . ' ' . $words[$i + 1];
            if (strlen($phrase) > 8) {
                $phrases[] = $phrase;
            }
        }
        
        // Count phrase frequency
        $phrase_counts = array_count_values($phrases);
        arsort($phrase_counts);
        
        // Combine keywords and top phrases
        $key_phrases = array_merge(
            array_slice($keywords, 0, 10),
            array_keys(array_slice($phrase_counts, 0, 5, true))
        );
        
        return $key_phrases;
    }
    
    private function extract_keywords($content) {
        $text = $content['content'];
        
        // Use the same logic as extract_key_phrases but focus on single words
        $key_phrases = $this->extract_key_phrases($text);
        
        // Filter for single words only
        $keywords = array_filter($key_phrases, function($phrase) {
            return strpos($phrase, ' ') === false;
        });
        
        return array_values($keywords);
    }
    
    private function analyze_sentiment($content) {
        $text = $content['title'] . ' ' . $content['content'];
        
        // Simple sentiment analysis using positive/negative word lists
        $positive_words = array(
            'good', 'great', 'excellent', 'amazing', 'wonderful', 'fantastic', 'awesome',
            'love', 'like', 'enjoy', 'happy', 'pleased', 'satisfied', 'perfect',
            'best', 'better', 'beautiful', 'nice', 'pleasant', 'positive'
        );
        
        $negative_words = array(
            'bad', 'terrible', 'awful', 'horrible', 'hate', 'dislike', 'angry',
            'sad', 'disappointed', 'frustrated', 'annoyed', 'upset', 'negative',
            'worst', 'worse', 'ugly', 'unpleasant', 'difficult', 'hard'
        );
        
        $text = strtolower($text);
        $words = preg_split('/\s+/', preg_replace('/[^a-zA-Z\s]/', ' ', $text));
        
        $positive_count = 0;
        $negative_count = 0;
        
        foreach ($words as $word) {
            if (in_array($word, $positive_words)) {
                $positive_count++;
            } elseif (in_array($word, $negative_words)) {
                $negative_count++;
            }
        }
        
        $total_sentiment_words = $positive_count + $negative_count;
        
        if ($total_sentiment_words === 0) {
            return 0.5; // Neutral
        }
        
        return $positive_count / $total_sentiment_words;
    }
    
    private function calculate_readability($content) {
        $text = $content['content'];
        
        if (empty($text)) {
            return 0;
        }
        
        // Simple readability score based on sentence length and word complexity
        $sentences = preg_split('/[.!?]+/', $text);
        $words = preg_split('/\s+/', $text);
        $syllables = 0;
        
        foreach ($words as $word) {
            $syllables += $this->count_syllables($word);
        }
        
        $sentence_count = max(1, count($sentences) - 1); // Subtract 1 for empty string after last punctuation
        $word_count = max(1, count($words));
        
        // Simplified Flesch Reading Ease Score
        $avg_sentence_length = $word_count / $sentence_count;
        $avg_syllables_per_word = $syllables / $word_count;
        
        $score = 206.835 - (1.015 * $avg_sentence_length) - (84.6 * $avg_syllables_per_word);
        
        // Normalize to 0-100 scale
        return max(0, min(100, intval($score)));
    }
    
    private function count_syllables($word) {
        $word = strtolower($word);
        $word = preg_replace('/[^a-z]/', '', $word);
        
        if (strlen($word) <= 3) {
            return 1;
        }
        
        $vowels = 'aeiouy';
        $syllable_count = 0;
        $prev_was_vowel = false;
        
        for ($i = 0; $i < strlen($word); $i++) {
            $is_vowel = strpos($vowels, $word[$i]) !== false;
            
            if ($is_vowel && !$prev_was_vowel) {
                $syllable_count++;
            }
            
            $prev_was_vowel = $is_vowel;
        }
        
        // Handle silent 'e'
        if (substr($word, -1) === 'e' && $syllable_count > 1) {
            $syllable_count--;
        }
        
        return max(1, $syllable_count);
    }
    
    private function is_analysis_fresh($cached_analysis) {
        if (!$cached_analysis->updated_at) {
            return false;
        }
        
        $cache_age = time() - strtotime($cached_analysis->updated_at);
        $max_age = 24 * 60 * 60; // 24 hours
        
        return $cache_age < $max_age;
    }
    
    public function get_surrounding_context($post_id, $image_position = null) {
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }
        
        $content = $post->post_content;
        
        if ($image_position === null) {
            // Return first few paragraphs if no specific position
            $paragraphs = explode('</p>', $content);
            return wp_strip_all_tags(implode('</p>', array_slice($paragraphs, 0, 2)));
        }
        
        // Extract context around specific position (future enhancement)
        return wp_strip_all_tags($content);
    }
}