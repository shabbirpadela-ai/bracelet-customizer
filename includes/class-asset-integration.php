<?php
/**
 * WordPress Asset Manager for React Build Integration
 * Generated automatically by build-integration.js
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bracelet_Customizer_Assets {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_customizer_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_customizer_assets']);
    }
    
    /**
     * Enqueue React app assets for the customizer
     */
    public function enqueue_customizer_assets() {
        $plugin_url = defined('BRACELET_CUSTOMIZER_PLUGIN_URL') ? BRACELET_CUSTOMIZER_PLUGIN_URL : plugin_dir_url(dirname(dirname(__FILE__)));
        $plugin_version = defined('BRACELET_CUSTOMIZER_VERSION') ? BRACELET_CUSTOMIZER_VERSION : '2.0.1';
        
        // Enqueue CSS
        wp_enqueue_style(
            'bracelet-customizer-css',
            $plugin_url . 'assets/css/bracelet-customizer.css',
            [],
            $plugin_version
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'bracelet-customizer-js',
            $plugin_url . 'assets/js/bracelet-customizer.js',
            ['wp-element'],
            $plugin_version,
            true
        );
        
        // Add WordPress integration data
        wp_localize_script('bracelet-customizer-js', 'braceletCustomizerData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bracelet_customizer_nonce'),
            'restUrl' => rest_url('bracelet-customizer/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'pluginUrl' => $plugin_url,
            'imagesUrl' => $plugin_url . 'assets/images/',
            'isUserLoggedIn' => is_user_logged_in(),
            'currentUser' => wp_get_current_user()->ID,
            'woocommerceActive' => class_exists('WooCommerce'),
            'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '',
            'checkoutUrl' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : ''
        ]);
        
        // Ensure React and ReactDOM are available
        wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', [], '18.0.0');
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', ['react'], '18.0.0');
    }
    
    /**
     * Initialize the customizer in the DOM
     */
    public static function render_customizer_container() {
        echo '<div id="bracelet-customizer-root"></div>';
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                if (window.React && window.ReactDOM && window.BraceletCustomizer) {
                    const container = document.getElementById("bracelet-customizer-root");
                    if (container) {
                        const root = ReactDOM.createRoot(container);
                        root.render(React.createElement(window.BraceletCustomizer.App));
                    }
                }
            });
        </script>';
    }
}

// Initialize the asset manager
Bracelet_Customizer_Assets::get_instance();
