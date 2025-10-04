<?php
/**
 * WooCommerce Product Type: Bracelet Collabs
 * 
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC Product Bracelet Collabs
 * 
 * Custom WooCommerce product type for Bracelet Collabs products.
 * This extends the simple product type with custom fields for collaboration bracelets.
 */
class WC_Product_Bracelet_Collabs extends WC_Product_Simple {
    
    /**
     * Product type
     * 
     * @var string
     */
    protected $product_type = 'bracelet_collabs';
    
    /**
     * Constructor
     * 
     * @param mixed $product Product to init.
     */
    public function __construct($product = 0) {
        parent::__construct($product);
    }
    
    /**
     * Get the product type
     *
     * @return string
     */
    public function get_type() {
        return 'bracelet_collabs';
    }
    
    /**
     * Returns whether or not the product is sold individually
     * (no quantities)
     *
     * @return bool
     */
    public function is_sold_individually() {
        return apply_filters('woocommerce_is_sold_individually', true, $this);
    }
    
    /**
     * Get collabs main image URL
     *
     * @return string
     */
    public function get_collabs_main_url() {
        return get_post_meta($this->get_id(), '_product_main_url', true);
    }
    
    /**
     * Get collabs main image ID
     *
     * @return int
     */
    public function get_collabs_main_image() {
        return get_post_meta($this->get_id(), '_product_main_image', true);
    }
    
    /**
     * Get the main image URL (prioritize external URL over WordPress upload)
     *
     * @return string
     */
    public function get_main_image_url() {
        $external_url = $this->get_collabs_main_url();
        if (!empty($external_url)) {
            return $external_url;
        }
        
        $image_id = $this->get_collabs_main_image();
        if ($image_id) {
            return wp_get_attachment_url($image_id);
        }
        
        return '';
    }
    
    /**
     * Check if product has required collabs configuration
     *
     * @return bool
     */
    public function has_collabs_configuration() {
        return !empty($this->get_main_image_url());
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
     * Get product data for the customizer API
     *
     * @return array
     */
    public function get_customizer_data() {
        return [
            'id' => $this->get_id(),
            'name' => $this->get_name(),
            'price' => (float) $this->get_price(),
            'image' => $this->get_main_image_url(),
            'category' => 'collabs',
            'type' => 'bracelet_collabs',
            'customizable' => $this->is_customizable(),
            'available_sizes' => ['One Size'], // Collabs typically come in one size
            'description' => $this->get_description(),
            'short_description' => $this->get_short_description()
        ];
    }
    
    /**
     * Returns whether this product is purchasable
     *
     * @return bool
     */
    public function is_purchasable() {
        return apply_filters('woocommerce_is_purchasable', $this->exists() && 
            'publish' === $this->get_status() && 
            $this->has_collabs_configuration(), $this);
    }
    
    /**
     * Get the add to cart button text
     *
     * @return string
     */
    public function add_to_cart_text() {
        if ($this->is_purchasable() && $this->is_in_stock()) {
            $text = __('Add to cart', 'bracelet-customizer');
        } else {
            $text = __('Read more', 'bracelet-customizer');
        }
        
        return apply_filters('woocommerce_product_add_to_cart_text', $text, $this);
    }
    
    /**
     * Get the add to cart button URL
     *
     * @return string
     */
    public function add_to_cart_url() {
        if ($this->is_purchasable() && $this->is_in_stock()) {
            $url = remove_query_arg('added-to-cart', add_query_arg('add-to-cart', $this->get_id()));
        } else {
            $url = get_permalink($this->get_id());
        }
        
        return apply_filters('woocommerce_product_add_to_cart_url', $url, $this);
    }
}