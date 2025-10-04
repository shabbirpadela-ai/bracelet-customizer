<?php
/**
 * Debug Cart Thumbnails
 * 
 * Add this to functions.php or run as a standalone script to debug cart thumbnails
 */

// Add to admin menu for easy access
add_action('admin_menu', 'bracelet_debug_menu');

function bracelet_debug_menu() {
    add_submenu_page(
        'tools.php',
        'Debug Cart Thumbnails',
        'Debug Cart Thumbnails',
        'manage_options',
        'debug-cart-thumbnails',
        'bracelet_debug_cart_thumbnails_page'
    );
}

function bracelet_debug_cart_thumbnails_page() {
    ?>
    <div class="wrap">
        <h1>Debug Cart Thumbnails</h1>
        
        <h2>Current Cart Items</h2>
        <?php
        if (WC()->cart && !WC()->cart->is_empty()) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Product</th><th>Cart Item Key</th><th>Customization</th><th>Custom Image URL</th></tr></thead>';
            echo '<tbody>';
            
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                echo '<tr>';
                echo '<td>' . $cart_item['data']->get_name() . '</td>';
                echo '<td>' . $cart_item_key . '</td>';
                echo '<td>' . (isset($cart_item['bracelet_customization']) ? 'Yes' : 'No') . '</td>';
                echo '<td>' . (isset($cart_item['custom_image_url']) ? $cart_item['custom_image_url'] : 'No') . '</td>';
                echo '</tr>';
                
                if (isset($cart_item['bracelet_customization'])) {
                    echo '<tr><td colspan="4">';
                    echo '<strong>Customization Data:</strong><br>';
                    echo '<pre>' . print_r($cart_item['bracelet_customization'], true) . '</pre>';
                    echo '</td></tr>';
                }
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>Cart is empty</p>';
        }
        ?>
        
        <h2>Recent Customizations</h2>
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        $customizations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 10");
        
        if ($customizations) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>ID</th><th>Session ID</th><th>Product ID</th><th>Created</th><th>Preview Image</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($customizations as $customization) {
                echo '<tr>';
                echo '<td>' . $customization->id . '</td>';
                echo '<td>' . $customization->session_id . '</td>';
                echo '<td>' . $customization->product_id . '</td>';
                echo '<td>' . $customization->created_at . '</td>';
                
                // Check if preview image exists
                $upload_dir = wp_upload_dir();
                $custom_images_dir = $upload_dir['basedir'] . '/bracelet-customizations/';
                $custom_images_url = $upload_dir['baseurl'] . '/bracelet-customizations/';
                
                $image_pattern = 'preview_' . $customization->id . '_*.png';
                $image_files = glob($custom_images_dir . $image_pattern);
                
                if (!empty($image_files)) {
                    $image_file = basename($image_files[0]);
                    $image_url = $custom_images_url . $image_file;
                    echo '<td><a href="' . $image_url . '" target="_blank">View</a></td>';
                } else {
                    echo '<td>No image</td>';
                }
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>No customizations found</p>';
        }
        ?>
        
        <h2>Upload Directory Info</h2>
        <?php
        $upload_dir = wp_upload_dir();
        $custom_images_dir = $upload_dir['basedir'] . '/bracelet-customizations/';
        $custom_images_url = $upload_dir['baseurl'] . '/bracelet-customizations/';
        
        echo '<p><strong>Upload Directory:</strong> ' . $custom_images_dir . '</p>';
        echo '<p><strong>Directory Exists:</strong> ' . (file_exists($custom_images_dir) ? 'Yes' : 'No') . '</p>';
        echo '<p><strong>Directory Writable:</strong> ' . (is_writable($custom_images_dir) ? 'Yes' : 'No') . '</p>';
        echo '<p><strong>URL Base:</strong> ' . $custom_images_url . '</p>';
        
        if (file_exists($custom_images_dir)) {
            $files = scandir($custom_images_dir);
            $image_files = array_filter($files, function($file) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'png';
            });
            
            echo '<p><strong>Image Files:</strong> ' . count($image_files) . '</p>';
            
            if (!empty($image_files)) {
                echo '<h3>Recent Images</h3>';
                echo '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
                foreach (array_slice($image_files, 0, 10) as $file) {
                    $file_url = $custom_images_url . $file;
                    echo '<div style="border: 1px solid #ccc; padding: 10px;">';
                    echo '<img src="' . $file_url . '" style="max-width: 100px; max-height: 100px;"><br>';
                    echo '<small>' . $file . '</small>';
                    echo '</div>';
                }
                echo '</div>';
            }
        }
        ?>
    </div>
    <?php
}