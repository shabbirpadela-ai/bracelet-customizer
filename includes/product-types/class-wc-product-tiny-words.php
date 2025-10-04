<?php
/**
 * WooCommerce Tiny Words Bracelet Product Type
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tiny Words Bracelet Product Class
 * Extends WC_Product to add tiny words bracelet-specific functionality
 */
class WC_Product_Tiny_Words extends WC_Product {
    
    /**
     * Product type
     *
     * @var string
     */
    protected $product_type = 'tiny_words';
    
    /**
     * Constructor
     *
     * @param mixed $product Product object or ID
     */
    public function __construct($product = 0) {
        parent::__construct($product);
    }
    
    /**
     * Get product type
     *
     * @return string
     */
    public function get_type() {
        return 'tiny_words';
    }
    
    /**
     * Check if product is virtual (bracelets are physical products)
     *
     * @return bool
     */
    public function is_virtual() {
        return false;
    }
    
    /**
     * Check if product is downloadable (bracelets are not downloadable)
     *
     * @return bool
     */
    public function is_downloadable() {
        return false;
    }
    
    /**
     * Check if product requires shipping (bracelets do)
     *
     * @return bool
     */
    public function needs_shipping() {
        return true;
    }
    
    /**
     * Check if bracelet is customizable
     *
     * @return bool
     */
    public function is_customizable() {
        return $this->get_meta('_bracelet_customizable') === 'yes';
    }
    
    /**
     * Get bracelet style category
     *
     * @return string
     */
    public function get_style_category() {
        return $this->get_meta('_tiny_words_style_category') ?: 'tiny_words';
    }
    
    /**
     * Check if bracelet is a bestseller
     *
     * @return bool
     */
    public function is_bestseller() {
        return $this->get_meta('_tiny_words_is_bestseller') === 'yes';
    }
    
    /**
     * Get base bracelet image URL
     *
     * @return string
     */
    public function get_base_image() {
        return $this->get_meta('_tiny_words_base_image') ?: '';
    }
    
    /**
     * Get gap images for different character counts (max 10 for tiny words)
     *
     * @return array
     */
    public function get_gap_images() {
        $gap_images = [];
        
        // Get gap images from individual meta fields (1-10 characters)
        for ($i = 1; $i <= 10; $i++) {
            $gap_image_id = $this->get_meta("_tiny_words_gap_image_{$i}char");
            $gap_url = $this->get_meta("_tiny_words_gap_url_{$i}char");
            
            // Use URL field if available, otherwise use uploaded image
            if (!empty($gap_url)) {
                $gap_images[$i] = $gap_url;
            } elseif ($gap_image_id) {
                $gap_images[$i] = wp_get_attachment_url($gap_image_id);
            }
        }
        
        return $gap_images;
    }
    
    /**
     * Get gap image for specific character count (max 10)
     *
     * @param int $char_count Number of characters (max 10)
     * @return string Image URL or empty string
     */
    public function get_gap_image($char_count) {
        // Limit to 10 characters maximum for tiny words
        if ($char_count > 10 || $char_count < 1) {
            return '';
        }
        
        // Get from individual meta fields
        $gap_url = $this->get_meta("_tiny_words_gap_url_{$char_count}char");
        if (!empty($gap_url)) {
            return $gap_url;
        }
        
        $gap_image_id = $this->get_meta("_tiny_words_gap_image_{$char_count}char");
        if ($gap_image_id) {
            return wp_get_attachment_url($gap_image_id);
        }
        
        return '';
    }
    
    /**
     * Get available sizes
     *
     * @return array
     */
    public function get_available_sizes() {
        $sizes = $this->get_meta('_tiny_words_available_sizes');
        if (empty($sizes)) {
            return ['XS', 'S/M', 'M/L', 'L/XL']; // Default sizes
        }
        return array_filter(array_map('trim', explode("\n", $sizes)));
    }
    
    /**
     * Calculate price with customization options
     *
     * @param array $customization Customization data
     * @return float Total price
     */
    public function calculate_customized_price($customization = []) {
        $base_price = (float) $this->get_price();
        $additional_price = 0;
        
        // Add letter color pricing
        if (isset($customization['letterColor'])) {
            $settings = get_option('bracelet_customizer_settings', []);
            $letter_colors = isset($settings['letter_colors']) ? $settings['letter_colors'] : [];
            
            if (isset($letter_colors[$customization['letterColor']]['price'])) {
                $additional_price += (float) $letter_colors[$customization['letterColor']]['price'];
            }
        }
        
        // Note: Tiny Words bracelets don't support charms
        
        return $base_price + $additional_price;
    }
    
    /**
     * Get customization display data for cart/order
     *
     * @param array $customization Customization data
     * @return array Display data
     */
    public function get_customization_display($customization = []) {
        $display = [];
        
        if (isset($customization['word']) && !empty($customization['word'])) {
            // Limit to 10 characters for tiny words
            $word = substr(strtoupper($customization['word']), 0, 10);
            $display['Tiny Word'] = $word;
        }
        
        if (isset($customization['letterColor']) && !empty($customization['letterColor'])) {
            $settings = get_option('bracelet_customizer_settings', []);
            $letter_colors = isset($settings['letter_colors']) ? $settings['letter_colors'] : [];
            
            if (isset($letter_colors[$customization['letterColor']]['name'])) {
                $display['Letter Color'] = $letter_colors[$customization['letterColor']]['name'];
            } else {
                $display['Letter Color'] = ucfirst($customization['letterColor']);
            }
        }
        
        if (isset($customization['size']) && !empty($customization['size'])) {
            $display['Size'] = strtoupper($customization['size']);
        }
        
        return $display;
    }
    
    /**
     * Validate customization data
     *
     * @param array $customization Customization data
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_customization($customization = []) {
        $settings = get_option('bracelet_customizer_settings', []);
        
        // Validate word
        if (isset($customization['word'])) {
            $word = trim($customization['word']);
            $min_length = 2; // Minimum 2 characters for tiny words
            $max_length = 10; // Maximum 10 characters for tiny words
            
            if (strlen($word) < $min_length) {
                return new WP_Error('word_too_short', sprintf(__('Tiny word must be at least %d characters long.', 'bracelet-customizer'), $min_length));
            }
            
            if (strlen($word) > $max_length) {
                return new WP_Error('word_too_long', sprintf(__('Tiny word cannot be longer than %d characters.', 'bracelet-customizer'), $max_length));
            }
            
            // Check allowed characters
            $allowed_chars = isset($settings['allowed_characters']) ? $settings['allowed_characters'] : 'a-zA-Z0-9\s';
            if (!preg_match('/^[' . $allowed_chars . ']+$/', $word)) {
                return new WP_Error('invalid_characters', __('Tiny word contains invalid characters.', 'bracelet-customizer'));
            }
        }
        
        // Validate letter color
        if (isset($customization['letterColor'])) {
            $letter_colors = isset($settings['letter_colors']) ? $settings['letter_colors'] : [];
            if (!isset($letter_colors[$customization['letterColor']])) {
                return new WP_Error('invalid_letter_color', __('Invalid letter color selected.', 'bracelet-customizer'));
            }
        }
        
        // Validate size
        if (isset($customization['size'])) {
            $available_sizes = $this->get_available_sizes();
            if (!in_array($customization['size'], $available_sizes)) {
                return new WP_Error('invalid_size', __('Invalid size selected.', 'bracelet-customizer'));
            }
        }
        
        return true;
    }
    
    /**
     * Get product data for customizer API
     *
     * @return array Product data
     */
    public function get_customizer_data() {
        return [
            'id' => $this->get_id(),
            'name' => $this->get_name(),
            'description' => $this->get_description(),
            'short_description' => $this->get_short_description(),
            'price' => $this->get_price(),
            'regular_price' => $this->get_regular_price(),
            'sale_price' => $this->get_sale_price(),
            'currency' => get_woocommerce_currency(),
            'image' => wp_get_attachment_url($this->get_image_id()),
            'gallery' => array_map('wp_get_attachment_url', $this->get_gallery_image_ids()),
            'base_image' => $this->get_base_image(),
            'gap_images' => $this->get_gap_images(),
            'category' => $this->get_style_category(),
            'is_bestseller' => $this->is_bestseller(),
            'is_customizable' => $this->is_customizable(),
            'available_sizes' => $this->get_available_sizes(),
            'stock_status' => $this->get_stock_status(),
            'in_stock' => $this->is_in_stock(),
            'max_word_length' => 10, // Tiny words are limited to 10 characters
            'supports_charms' => false // Tiny words don't support charms
        ];
    }
    
    /**
     * Check if product supports charms (tiny words don't)
     *
     * @return bool
     */
    public function supports_charms() {
        return false;
    }
    
    /**
     * Get maximum word length for tiny words
     *
     * @return int
     */
    public function get_max_word_length() {
        return 10;
    }
}