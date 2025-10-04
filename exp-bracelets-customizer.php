<?php
/**
 * Plugin Name: Bracelet Customizer
 * Plugin URI: https://fiverr.com/expert2014
 * Description: WooCommerce bracelet customization with React interface. Create custom bracelets with words and charms.
 * Version: 3.1.0
 * Author: Nand Lal
 * Author URI: https://gumnotech.com
 * Text Domain: bracelet-customizer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Define plugin constants
define('BRACELET_CUSTOMIZER_VERSION', '3.1.0');
define('BRACELET_CUSTOMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BRACELET_CUSTOMIZER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BRACELET_CUSTOMIZER_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
function bracelet_customizer_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>' . __('Bracelet Customizer', 'bracelet-customizer') . '</strong>: ';
            echo __('This plugin requires WooCommerce to be installed and activated.', 'bracelet-customizer');
            echo ' <a href="' . admin_url('plugin-install.php?s=woocommerce&tab=search&type=term') . '">';
            echo __('Install WooCommerce', 'bracelet-customizer') . '</a></p>';
            echo '</div>';
        });
        return false;
    }
    return true;
}

/**
 * Check PHP version compatibility
 */
function bracelet_customizer_check_php_version() {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error">';
            echo '<p><strong>' . __('Bracelet Customizer', 'bracelet-customizer') . '</strong>: ';
            echo sprintf(
                __('This plugin requires PHP version 7.4 or higher. You are running PHP %s.', 'bracelet-customizer'),
                PHP_VERSION
            );
            echo '</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Include main class and initialize plugin
 */
function bracelet_customizer_init() {
    // Check dependencies
    if (!bracelet_customizer_check_php_version() || !bracelet_customizer_check_woocommerce()) {
        return;
    }

    // Include main class
    require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/class-plugin-main.php';

    // Initialize plugin
    new Bracelet_Customizer_Main();
}

// Initialize on plugins_loaded to ensure WooCommerce is loaded
add_action('plugins_loaded', 'bracelet_customizer_init', 20);
add_action('init', function()
{
    // // Load textdomain early for activation messages
    // load_plugin_textdomain(
    //     'bracelet-customizer',
    //     false,
    //     dirname(BRACELET_CUSTOMIZER_PLUGIN_BASENAME) . '/languages'
    // );
});

/**
 * Plugin activation hook
 */
register_activation_hook(__FILE__, 'bracelet_customizer_activate');
function bracelet_customizer_activate() {
    
    
    // Check dependencies before activation
    if (!bracelet_customizer_check_php_version()) {
        wp_die(__('Bracelet Customizer requires PHP 7.4 or higher.', 'bracelet-customizer'));
    }

    if (!bracelet_customizer_check_woocommerce()) {
        wp_die(__('Bracelet Customizer requires WooCommerce to be installed and activated.', 'bracelet-customizer'));
    }

    // Include main class for activation
    require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/class-plugin-main.php';
    
    // Run activation
    Bracelet_Customizer_Main::activate();
    
    // Set activation redirect flag
    add_option('bracelet_customizer_activation_redirect', true);
}

/**
 * Plugin deactivation hook
 */
register_deactivation_hook(__FILE__, 'bracelet_customizer_deactivate');
function bracelet_customizer_deactivate() {
    // Clear any cached data
    wp_cache_flush();
    
    // Remove activation redirect flag
    delete_option('bracelet_customizer_activation_redirect');
    
    // Clear rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin uninstall - handled by uninstall.php
 */

/**
 * Add plugin action links
 */
add_filter('plugin_action_links_' . BRACELET_CUSTOMIZER_PLUGIN_BASENAME, 'bracelet_customizer_action_links');
function bracelet_customizer_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=bracelet-customizer-settings') . '">' . __('Settings', 'bracelet-customizer') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * Add plugin meta links
 */
add_filter('plugin_row_meta', 'bracelet_customizer_row_meta', 10, 2);
function bracelet_customizer_row_meta($links, $file) {
    if ($file === BRACELET_CUSTOMIZER_PLUGIN_BASENAME) {
        $row_meta = [
            'docs' => '<a href="https://github.com/your-username/bracelet-customizer/wiki" target="_blank">' . __('Documentation', 'bracelet-customizer') . '</a>',
            'support' => '<a href="https://github.com/your-username/bracelet-customizer/issues" target="_blank">' . __('Support', 'bracelet-customizer') . '</a>',
        ];
        return array_merge($links, $row_meta);
    }
    return $links;
}

/**
 * Redirect to settings page on activation
 */
add_action('admin_init', 'bracelet_customizer_activation_redirect');
function bracelet_customizer_activation_redirect() {
    if (get_option('bracelet_customizer_activation_redirect', false)) {
        delete_option('bracelet_customizer_activation_redirect');
        if (!isset($_GET['activate-multi'])) {
            wp_redirect(admin_url('admin.php?page=bracelet-customizer-settings&welcome=1'));
            exit;
        }
    }
}
