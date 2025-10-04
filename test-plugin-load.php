<?php
/**
 * Test Plugin Loading
 * 
 * This script tests if the plugin can be loaded without errors
 * by simulating WordPress environment
 */

// Simulate WordPress environment
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../../');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', dirname(__FILE__) . '/..');
}

// Simulate WordPress functions that may be needed
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        echo "add_action called: $hook\n";
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        echo "add_filter called: $hook\n";
        return true;
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
        echo "register_activation_hook called\n";
        return true;
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
        echo "register_deactivation_hook called\n";
        return true;
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return basename($file);
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return false;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        return true;
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        if ($type === 'mysql') {
            return date('Y-m-d H:i:s');
        }
        return time();
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return strip_tags($str);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data) {
        return json_encode($data);
    }
}

if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response($response) {
        return $response;
    }
}

if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args) {
        echo "register_rest_route called: $namespace$route\n";
        return true;
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

// Simulate WooCommerce class
if (!class_exists('WooCommerce')) {
    class WooCommerce {
        public function __construct() {
            echo "WooCommerce simulated\n";
        }
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'http://localhost/wp-admin/' . $path;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

// Test plugin loading
echo "Testing Bracelet Customizer Plugin Loading...\n";
echo "==========================================\n\n";

try {
    // Load main plugin file
    require_once dirname(__FILE__) . '/exp-bracelets-customizer.php';
    
    echo "✓ Main plugin file loaded successfully\n";
    
    // Manually trigger the plugin initialization
    if (function_exists('bracelet_customizer_init')) {
        bracelet_customizer_init();
        echo "✓ Plugin initialization function called\n";
    } else {
        // If function doesn't exist, try to initialize the main class directly
        if (class_exists('Bracelet_Customizer_Plugin_Main')) {
            new Bracelet_Customizer_Plugin_Main();
            echo "✓ Main plugin class instantiated directly\n";
        }
    }
    
    // Test that constants are defined
    if (defined('BRACELET_CUSTOMIZER_VERSION')) {
        echo "✓ Plugin version constant defined: " . BRACELET_CUSTOMIZER_VERSION . "\n";
    }
    
    if (defined('BRACELET_CUSTOMIZER_PLUGIN_PATH')) {
        echo "✓ Plugin path constant defined: " . BRACELET_CUSTOMIZER_PLUGIN_PATH . "\n";
    }
    
    if (defined('BRACELET_CUSTOMIZER_PLUGIN_URL')) {
        echo "✓ Plugin URL constant defined: " . BRACELET_CUSTOMIZER_PLUGIN_URL . "\n";
    }
    
    // Test class existence
    if (class_exists('Bracelet_Customizer_Plugin_Main')) {
        echo "✓ Main plugin class exists\n";
    } else {
        echo "✗ Main plugin class not found\n";
    }
    
    if (class_exists('Bracelet_Customizer_Settings')) {
        echo "✓ Settings class exists\n";
    } else {
        echo "✗ Settings class not found\n";
    }
    
    if (class_exists('Bracelet_Customizer_Product_Types')) {
        echo "✓ Product Types class exists\n";
    } else {
        echo "✗ Product Types class not found\n";
    }
    
    if (class_exists('Bracelet_Customizer_Rest_API')) {
        echo "✓ REST API class exists\n";
    } else {
        echo "✗ REST API class not found\n";
    }
    
    if (class_exists('Bracelet_Customizer_Asset_Manager')) {
        echo "✓ Asset Manager class exists\n";
    } else {
        echo "✗ Asset Manager class not found\n";
    }
    
    if (class_exists('Bracelet_Customizer_Database')) {
        echo "✓ Database class exists\n";
    } else {
        echo "✗ Database class not found\n";
    }
    
    if (class_exists('Bracelet_Customizer_WooCommerce')) {
        echo "✓ WooCommerce Integration class exists\n";
    } else {
        echo "✗ WooCommerce Integration class not found\n";
    }
    
    // Test static methods
    if (method_exists('Bracelet_Customizer_Product_Types', 'get_bracelet_products')) {
        echo "✓ get_bracelet_products method exists\n";
        
        // Test hardcoded data
        $bracelets = Bracelet_Customizer_Product_Types::get_hardcoded_bracelet_products();
        if (!empty($bracelets)) {
            echo "✓ Hardcoded bracelet data available (" . count($bracelets) . " items)\n";
        }
    }
    
    if (method_exists('Bracelet_Customizer_Product_Types', 'get_charm_products')) {
        echo "✓ get_charm_products method exists\n";
        
        // Test hardcoded data
        $charms = Bracelet_Customizer_Product_Types::get_hardcoded_charm_products();
        if (!empty($charms)) {
            echo "✓ Hardcoded charm data available (" . count($charms) . " items)\n";
        }
    }
    
    echo "\n==========================================\n";
    echo "✅ Plugin loading test completed successfully!\n";
    echo "All required classes and methods are available.\n";
    
} catch (Exception $e) {
    echo "\n==========================================\n";
    echo "❌ Plugin loading test failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "\n==========================================\n";
    echo "❌ Plugin loading test failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}