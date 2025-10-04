<?php
/**
 * WooCommerce Charm Product Type
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Charm Product Class
 * Extends WC_Product to add charm-specific functionality
 */
class WC_Product_Charm extends WC_Product {
    
    /**
     * Product type
     *
     * @var string
     */
    protected $product_type = 'charm';
    
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
        return 'charm';
    }
    
    /**
     * Check if product is virtual (charms are physical products)
     *
     * @return bool
     */
    public function is_virtual() {
        return false;
    }
    
    /**
     * Check if product is downloadable (charms are not downloadable)
     *
     * @return bool
     */
    public function is_downloadable() {
        return false;
    }
    
    /**
     * Check if product requires shipping (charms do when sold separately)
     *
     * @return bool
     */
    public function needs_shipping() {
        return true;
    }
    
    /**
     * Get charm category
     *
     * @return string
     */
    public function get_charm_category() {
        return $this->get_meta('_charm_category') ?: 'bestsellers';
    }
    
    /**
     * Check if charm is new
     *
     * @return bool
     */
    public function is_new() {
        return $this->get_meta('_charm_is_new') === 'yes';
    }
    
    /**
     * Check if charm is a bestseller
     *
     * @return bool
     */
    public function is_bestseller() {
        return $this->get_meta('_charm_is_bestseller') === 'yes';
    }
    
    /**
     * Get base charm image URL
     *
     * @return string
     */
    public function get_base_image() {
        return $this->get_meta('_charm_base_image') ?: '';
    }
    
    /**
     * Get position images for bracelet placement
     *
     * @return array
     */
    public function get_position_images() {
        $position_images = $this->get_meta('_charm_position_images');
        return is_array($position_images) ? $position_images : [];
    }
    
    /**
     * Get position image for specific bracelet position
     *
     * @param int $position Position number (1-9)
     * @return string Image URL or empty string
     */
    public function get_position_image($position) {
        $position_images = $this->get_position_images();
        return isset($position_images[$position]) ? $position_images[$position] : '';
    }
    
    /**
     * Get charm description (different from product description)
     *
     * @return string
     */
    public function get_charm_description() {
        $description = $this->get_meta('_charm_description');
        return !empty($description) ? $description : $this->get_description();
    }
    
    /**
     * Get search tags
     *
     * @return array
     */
    public function get_search_tags() {
        $tags = $this->get_meta('_charm_tags');
        if (empty($tags)) {
            return [];
        }
        return array_filter(array_map('trim', explode(',', $tags)));
    }
    
    /**
     * Check if charm matches search query
     *
     * @param string $query Search query
     * @return bool
     */
    public function matches_search($query) {
        if (empty($query)) {
            return true;
        }
        
        $query = strtolower(trim($query));
        
        // Search in name
        if (strpos(strtolower($this->get_name()), $query) !== false) {
            return true;
        }
        
        // Search in description
        if (strpos(strtolower($this->get_charm_description()), $query) !== false) {
            return true;
        }
        
        // Search in tags
        $tags = $this->get_search_tags();
        foreach ($tags as $tag) {
            if (strpos(strtolower($tag), $query) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if charm belongs to category
     *
     * @param string $category Category to check
     * @return bool
     */
    public function in_category($category) {
        if ($category === 'All' || empty($category)) {
            return true;
        }
        
        $charm_category = $this->get_charm_category();
        
        // Direct category match (for slugs)
        if ($charm_category === $category) {
            return true;
        }
        
        // Get all categories (default + custom)
        $all_categories = $this->get_all_charm_categories();
        
        // Check if category is a display name, convert to slug
        $category_slug = $this->get_category_slug_by_name($category, $all_categories);
        if ($category_slug && $charm_category === $category_slug) {
            return true;
        }
        
        // Special category mappings for default categories
        switch ($category) {
            case 'Bestsellers':
            case 'bestsellers':
                return $this->is_bestseller();
                
            case 'New Drops & Favs':
            case 'new_drops':
                return $this->is_new();
                
            case 'Personalize it':
            case 'personalize':
                return $charm_category === 'personalize';
                
            case 'By Vibe':
            case 'by_vibe':
                return $charm_category === 'by_vibe';
        }
        
        return false;
    }
    
    /**
     * Get all charm categories (default + custom)
     * @return array Array of slug => name pairs
     */
    private function get_all_charm_categories() {
        $default_categories = [
            'bestsellers' => __('Bestsellers', 'bracelet-customizer'),
            'new_drops' => __('New Drops & Favs', 'bracelet-customizer'),
            'personalize' => __('Personalize it', 'bracelet-customizer'),
            'by_vibe' => __('By Vibe', 'bracelet-customizer')
        ];
        
        $custom_categories = get_option('bracelet_customizer_charm_categories', []);
        
        return array_merge($default_categories, $custom_categories);
    }
    
    /**
     * Get category slug by display name
     * @param string $name Category display name
     * @param array $categories All categories
     * @return string|null Category slug or null if not found
     */
    private function get_category_slug_by_name($name, $categories) {
        foreach ($categories as $slug => $display_name) {
            if ($display_name === $name) {
                return $slug;
            }
        }
        return null;
    }
    
    /**
     * Get display data for customizer
     *
     * @return array Display data
     */
    public function get_display_data() {
        $position_images = $this->get_position_images();
        
        return [
            'id' => $this->get_id(),
            'name' => $this->get_name(),
            'price' => $this->get_price(),
            'formatted_price' => wc_price($this->get_price()),
            'description' => $this->get_charm_description(),
            'image' => wp_get_attachment_url($this->get_image_id()),
            'base_image' => $this->get_base_image(),
            'position_images' => $position_images,
            'category' => $this->get_charm_category(),
            'is_new' => $this->is_new(),
            'is_bestseller' => $this->is_bestseller(),
            'tags' => $this->get_search_tags(),
            'stock_status' => $this->get_stock_status(),
            'in_stock' => $this->is_in_stock()
        ];
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
            'description' => $this->get_charm_description(),
            'short_description' => $this->get_short_description(),
            'price' => $this->get_price(),
            'regular_price' => $this->get_regular_price(),
            'sale_price' => $this->get_sale_price(),
            'currency' => get_woocommerce_currency(),
            'image' => wp_get_attachment_url($this->get_image_id()),
            'gallery' => array_map('wp_get_attachment_url', $this->get_gallery_image_ids()),
            'base_image' => $this->get_base_image(),
            'position_images' => $this->get_position_images(),
            'category' => $this->get_charm_category(),
            'is_new' => $this->is_new(),
            'is_bestseller' => $this->is_bestseller(),
            'tags' => $this->get_search_tags(),
            'stock_status' => $this->get_stock_status(),
            'in_stock' => $this->is_in_stock()
        ];
    }
    
    /**
     * Get charm data for specific position
     *
     * @param int $position Bracelet position (1-9)
     * @return array Position-specific data
     */
    public function get_position_data($position) {
        return [
            'id' => $this->get_id(),
            'name' => $this->get_name(),
            'price' => $this->get_price(),
            'image' => $this->get_position_image($position),
            'fallback_image' => $this->get_base_image() ?: wp_get_attachment_url($this->get_image_id()),
            'position' => $position
        ];
    }
    
    /**
     * Validate charm for customization
     *
     * @param array $customization_data Customization data
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_for_customization($customization_data = []) {
        // Check if charm is in stock
        if (!$this->is_in_stock()) {
            return new WP_Error('charm_out_of_stock', sprintf(__('Charm "%s" is currently out of stock.', 'bracelet-customizer'), $this->get_name()));
        }
        
        // Check if position is specified and valid
        if (isset($customization_data['position'])) {
            $position = (int) $customization_data['position'];
            if ($position < 1 || $position > 9) {
                return new WP_Error('invalid_position', __('Invalid charm position. Must be between 1 and 9.', 'bracelet-customizer'));
            }
        }
        
        return true;
    }
    
    /**
     * Get available categories for filtering
     *
     * @return array Available categories
     */
    public static function get_available_categories() {
        return [
            'All' => __('All', 'bracelet-customizer'),
            'Bestsellers' => __('Bestsellers', 'bracelet-customizer'),
            'New Drops & Favs' => __('New Drops & Favs', 'bracelet-customizer'),
            'Personalize it' => __('Personalize it', 'bracelet-customizer'),
            'By Vibe' => __('By Vibe', 'bracelet-customizer')
        ];
    }
    
    /**
     * Get charms by category
     *
     * @param string $category Category name
     * @param int $limit Number of charms to return (-1 for all)
     * @return array Array of charm products
     */
    public static function get_by_category($category = 'All', $limit = -1) {
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => '_product_type',
                    'value' => 'charm'
                ]
            ]
        ];
        
        // Add category-specific filters
        if ($category !== 'All') {
            switch ($category) {
                case 'Bestsellers':
                    $args['meta_query'][] = [
                        'key' => '_charm_is_bestseller',
                        'value' => 'yes'
                    ];
                    break;
                    
                case 'New Drops & Favs':
                    $args['meta_query'][] = [
                        'key' => '_charm_is_new',
                        'value' => 'yes'
                    ];
                    break;
                    
                default:
                    $args['meta_query'][] = [
                        'key' => '_charm_category',
                        'value' => strtolower(str_replace(' ', '_', $category))
                    ];
                    break;
            }
        }
        
        $products = get_posts($args);
        $charms = [];
        
        foreach ($products as $product_post) {
            $charm = new self($product_post->ID);
            if ($charm->in_category($category)) {
                $charms[] = $charm;
            }
        }
        
        return $charms;
    }
}