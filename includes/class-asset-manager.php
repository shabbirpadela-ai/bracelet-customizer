<?php
/**
 * Asset Manager Class
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle asset management for letter blocks and images
 */
class Bracelet_Customizer_Asset_Manager {
    
    /**
     * Initialize asset manager
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_filter('bracelet_customizer_letter_url', [$this, 'get_letter_url'], 10, 3);
        add_action('wp_ajax_bracelet_upload_letter', [$this, 'handle_letter_upload']);
        add_action('wp_ajax_bracelet_upload_charm', [$this, 'handle_charm_upload']);
    }
    
    /**
     * Get letter block URL
     */
    public function get_letter_url($letter, $color = 'white', $context = 'customizer') {
        $settings = get_option('bracelet_customizer_settings', []);
        $source = $settings['letter_source'] ?? 'cloud';
        
        if ($source === 'cloud') {
            return $this->get_cloud_letter_url($letter, $color);
        } else {
            return $this->get_local_letter_url($letter, $color);
        }
    }
    
    /**
     * Get cloud letter URL
     */
    private function get_cloud_letter_url($letter, $color) {
        $settings = get_option('bracelet_customizer_settings', []);
        $base_url = $settings['cloud_base_url'] ?? 'https://res.cloudinary.com/drvnwq9bm/image/upload';
        
        // Convert letter to uppercase and handle special characters
        $letter = strtoupper($letter);
        $letter_code = $this->get_letter_code($letter);
        
        return "{$base_url}/c_scale,w_80,h_80/v1234567890/letters/{$color}/{$letter_code}.png";
    }
    
    /**
     * Get local letter URL
     */
    private function get_local_letter_url($letter, $color) {
        $settings = get_option('bracelet_customizer_settings', []);
        $letter_path = $settings['local_letter_path'] ?? 'bracelet-customizer/letters';
        
        $upload_dir = wp_upload_dir();
        $letter_code = $this->get_letter_code($letter);
        
        $file_path = "{$upload_dir['basedir']}/{$letter_path}/{$color}/{$letter_code}.png";
        $file_url = "{$upload_dir['baseurl']}/{$letter_path}/{$color}/{$letter_code}.png";
        
        if (file_exists($file_path)) {
            return $file_url;
        }
        
        // Fallback to default letter if specific one doesn't exist
        $default_path = "{$upload_dir['basedir']}/{$letter_path}/white/{$letter_code}.png";
        $default_url = "{$upload_dir['baseurl']}/{$letter_path}/white/{$letter_code}.png";
        
        if (file_exists($default_path)) {
            return $default_url;
        }
        
        // Return placeholder if nothing found
        return $this->get_placeholder_letter_url();
    }
    
    /**
     * Get letter code for file naming
     */
    private function get_letter_code($letter) {
        // Handle special characters and numbers
        $codes = [
            'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E',
            'F' => 'F', 'G' => 'G', 'H' => 'H', 'I' => 'I', 'J' => 'J',
            'K' => 'K', 'L' => 'L', 'M' => 'M', 'N' => 'N', 'O' => 'O',
            'P' => 'P', 'Q' => 'Q', 'R' => 'R', 'S' => 'S', 'T' => 'T',
            'U' => 'U', 'V' => 'V', 'W' => 'W', 'X' => 'X', 'Y' => 'Y', 'Z' => 'Z',
            '0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4',
            '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9',
            ' ' => 'SPACE',
            '!' => 'EXCLAMATION',
            '?' => 'QUESTION',
            '&' => 'AMPERSAND',
            '+' => 'PLUS',
            '<3' => 'HEART',
            ':)' => 'SMILE'
        ];
        
        return $codes[$letter] ?? 'UNKNOWN';
    }
    
    /**
     * Get placeholder letter URL
     */
    private function get_placeholder_letter_url() {
        return BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/placeholder-letter.png';
    }
    
    /**
     * Handle letter upload
     */
    public function handle_letter_upload() {
        check_ajax_referer('bracelet_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions.', 'bracelet-customizer'));
        }
        
        // Handle file upload logic here
        wp_send_json_success(['message' => 'Upload functionality coming soon']);
    }
    
    /**
     * Handle charm upload
     */
    public function handle_charm_upload() {
        check_ajax_referer('bracelet_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions.', 'bracelet-customizer'));
        }
        
        // Handle file upload logic here
        wp_send_json_success(['message' => 'Upload functionality coming soon']);
    }
    
    /**
     * Get all available letter colors
     */
    public function get_available_colors() {
        $settings = get_option('bracelet_customizer_settings', []);
        $letter_colors = $settings['letter_colors'] ?? [];
        
        $available_colors = [];
        foreach ($letter_colors as $color_id => $color_data) {
            if (!empty($color_data['enabled'])) {
                $available_colors[$color_id] = $color_data;
            }
        }
        
        return $available_colors;
    }
    
    /**
     * Generate letter preview for admin
     */
    public function generate_letter_preview($word, $color = 'white') {
        $preview_html = '<div class="letter-preview">';
        
        for ($i = 0; $i < strlen($word); $i++) {
            $letter = $word[$i];
            $letter_url = $this->get_letter_url($letter, $color);
            
            $preview_html .= sprintf(
                '<img src="%s" alt="%s" class="letter-block" data-letter="%s" />',
                esc_url($letter_url),
                esc_attr($letter),
                esc_attr($letter)
            );
        }
        
        $preview_html .= '</div>';
        
        return $preview_html;
    }
}