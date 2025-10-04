<?php
/**
 * Shortcodes
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle plugin shortcodes
 */
class Bracelet_Customizer_Shortcodes {
    
    /**
     * Initialize shortcodes
     */
    public function __construct() {
        $this->register_shortcodes();
    }
    
    /**
     * Register all shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('bracelet_customizer', [$this, 'bracelet_customizer_shortcode']);
        add_shortcode('bracelet_customize_button', [$this, 'customize_button_shortcode']);
    }
    
    /**
     * Bracelet customizer shortcode
     */
    public function bracelet_customizer_shortcode($atts) {
        $atts = shortcode_atts([
            'product_id' => '',
            'width' => '100%',
            'height' => '600px'
        ], $atts);
        
        ob_start();
        ?>
        <div class="bracelet-customizer-embed" style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
            <div id="bracelet-customizer-root" data-product-id="<?php echo esc_attr($atts['product_id']); ?>"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Customize button shortcode
     */
    public function customize_button_shortcode($atts) {
        $atts = shortcode_atts([
            'product_id' => '',
            'text' => __('Customize This Bracelet', 'bracelet-customizer'),
            'class' => 'bracelet-customize-btn'
        ], $atts);
        
        ob_start();
        ?>
        <div class="bracelet-customizer-button-wrapper">
            <button class="<?php echo esc_attr($atts['class']); ?>" data-product-id="<?php echo esc_attr($atts['product_id']); ?>">
                <?php echo esc_html($atts['text']); ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
}