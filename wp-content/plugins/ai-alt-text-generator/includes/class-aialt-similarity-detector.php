<?php

if (!defined('ABSPATH')) {
    exit;
}

class AIALT_Similarity_Detector {
    
    public function generate_hash($image_path) {
        if (!file_exists($image_path)) {
            return false;
        }
        
        // Use perceptual hashing (dHash) for similarity detection
        return $this->generate_dhash($image_path);
    }
    
    private function generate_dhash($image_path) {
        // Create image resource
        $image = $this->create_image_resource($image_path);
        if (!$image) {
            return false;
        }
        
        // Resize to 9x8 for dHash (we need 8x8 pixels for comparison)
        $resized = imagecreatetruecolor(9, 8);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, 9, 8, imagesx($image), imagesy($image));
        
        // Convert to grayscale and calculate hash
        $hash = '';
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $pixel1 = $this->get_grayscale_value($resized, $x, $y);\n                $pixel2 = $this->get_grayscale_value($resized, $x + 1, $y);
                $hash .= ($pixel1 > $pixel2) ? '1' : '0';
            }
        }
        
        // Clean up
        imagedestroy($image);
        imagedestroy($resized);
        
        // Convert binary to hex
        return $this->binary_to_hex($hash);
    }
    
    private function create_image_resource($image_path) {
        $image_info = getimagesize($image_path);
        if (!$image_info) {
            return false;
        }
        
        switch ($image_info[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($image_path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($image_path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($image_path);
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($image_path);
                }
                break;
        }
        
        return false;
    }
    
    private function get_grayscale_value($image, $x, $y) {
        $rgb = imagecolorat($image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        
        // Convert to grayscale using standard luminance formula
        return intval(0.299 * $r + 0.587 * $g + 0.114 * $b);
    }
    
    private function binary_to_hex($binary) {
        $hex = '';
        $chunks = str_split($binary, 4);
        
        foreach ($chunks as $chunk) {
            $decimal = bindec(str_pad($chunk, 4, '0', STR_PAD_LEFT));
            $hex .= dechex($decimal);
        }
        
        return $hex;
    }
    
    public function calculate_similarity($hash1, $hash2) {
        if (strlen($hash1) !== strlen($hash2)) {
            return 0;
        }
        
        $distance = 0;
        $length = strlen($hash1);
        
        for ($i = 0; $i < $length; $i++) {
            if ($hash1[$i] !== $hash2[$i]) {
                $distance++;
            }
        }
        
        // Convert Hamming distance to similarity percentage
        return 1 - ($distance / ($length * 4)); // multiply by 4 because each hex char represents 4 bits
    }
    
    public function find_similar_images($target_hash, $threshold = 0.85) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aialt_image_cache';
        
        // Get all cached hashes for comparison
        $cached_hashes = $wpdb->get_results("
            SELECT id, image_hash, similarity_hash, alt_text, caption, title, ai_provider, confidence_score
            FROM $table_name 
            WHERE expires_at > NOW()
        ");
        
        $similar_images = array();
        
        foreach ($cached_hashes as $cached) {
            $similarity = $this->calculate_similarity($target_hash, $cached->similarity_hash);
            
            if ($similarity >= $threshold) {
                $similar_images[] = array(
                    'id' => $cached->id,
                    'similarity_score' => $similarity,
                    'alt_text' => $cached->alt_text,
                    'caption' => $cached->caption,
                    'title' => $cached->title,
                    'provider' => $cached->ai_provider,
                    'confidence' => $cached->confidence_score
                );
            }
        }
        
        // Sort by similarity score (highest first)
        usort($similar_images, function($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });
        
        return $similar_images;
    }
    
    public function is_duplicate($image_path, $threshold = 0.95) {
        $hash = $this->generate_hash($image_path);
        if (!$hash) {
            return false;
        }
        
        $similar = $this->find_similar_images($hash, $threshold);
        return !empty($similar);
    }
    
    public function get_image_fingerprint($image_path) {
        $hash = $this->generate_hash($image_path);
        if (!$hash) {
            return false;
        }
        
        $image_info = getimagesize($image_path);
        $file_size = filesize($image_path);
        
        return array(
            'perceptual_hash' => $hash,
            'file_hash' => md5_file($image_path),
            'dimensions' => array(
                'width' => $image_info[0] ?? 0,
                'height' => $image_info[1] ?? 0
            ),
            'file_size' => $file_size,
            'mime_type' => $image_info['mime'] ?? '',
            'fingerprint' => hash('sha256', $hash . $file_size . ($image_info[0] ?? 0) . ($image_info[1] ?? 0))
        );
    }
    
    public function batch_analyze_similarity($image_paths, $threshold = 0.85) {
        $results = array();
        $hashes = array();
        
        // Generate hashes for all images
        foreach ($image_paths as $path) {
            $hash = $this->generate_hash($path);
            if ($hash) {
                $hashes[$path] = $hash;
            }
        }
        
        // Compare each image with others
        foreach ($hashes as $path1 => $hash1) {
            $similar_to = array();
            
            foreach ($hashes as $path2 => $hash2) {
                if ($path1 !== $path2) {
                    $similarity = $this->calculate_similarity($hash1, $hash2);
                    if ($similarity >= $threshold) {
                        $similar_to[] = array(
                            'path' => $path2,
                            'similarity' => $similarity
                        );
                    }
                }
            }
            
            $results[$path1] = array(
                'hash' => $hash1,
                'similar_images' => $similar_to,
                'is_unique' => empty($similar_to)
            );
        }
        
        return $results;
    }
}