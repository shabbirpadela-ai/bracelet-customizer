<?php
/**
 * Debug script to check customizable meta values
 * Add ?debug_customizable=1 to any WordPress admin page to see meta values
 */

// Only run in admin and if debug parameter is set
if (is_admin() && isset($_GET['debug_customizable'])) {
    add_action('admin_notices', function() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        global $wpdb;
        
        // Get all products with bracelet product types
        $products = $wpdb->get_results("
            SELECT p.ID, p.post_title, 
                   pt.meta_value as product_type,
                   bc.meta_value as customizable_value
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pt ON p.ID = pt.post_id AND pt.meta_key = '_product_type'
            LEFT JOIN {$wpdb->postmeta} bc ON p.ID = bc.post_id AND bc.meta_key = '_bracelet_customizable'
            WHERE p.post_type = 'product' 
            AND pt.meta_value IN ('standard_bracelet', 'bracelet_collabs', 'bracelet_no_words', 'tiny_words')
            ORDER BY p.ID
        ");
        
        echo '<div class="notice notice-info">';
        echo '<h3>Bracelet Products Customizable Meta Debug</h3>';
        echo '<table style="width: 100%; border-collapse: collapse;">';
        echo '<thead><tr style="background: #f1f1f1;"><th style="padding: 8px; border: 1px solid #ddd;">ID</th><th style="padding: 8px; border: 1px solid #ddd;">Title</th><th style="padding: 8px; border: 1px solid #ddd;">Product Type</th><th style="padding: 8px; border: 1px solid #ddd;">Customizable Meta</th><th style="padding: 8px; border: 1px solid #ddd;">Status</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($products as $product) {
            $customizable_status = '';
            $row_color = '';
            
            if ($product->customizable_value === null) {
                $customizable_status = '⚠️ NULL (Missing)';
                $row_color = 'background: #fff3cd;';
            } elseif ($product->customizable_value === '') {
                $customizable_status = '⚠️ Empty String';
                $row_color = 'background: #fff3cd;';
            } elseif ($product->customizable_value === 'yes') {
                $customizable_status = '✅ Yes';
                $row_color = 'background: #d1edff;';
            } elseif ($product->customizable_value === 'no') {
                $customizable_status = '❌ No';
                $row_color = 'background: #f8d7da;';
            } else {
                $customizable_status = '❓ Unknown: ' . $product->customizable_value;
                $row_color = 'background: #e2e3e5;';
            }
            
            echo '<tr style="' . $row_color . '">';
            echo '<td style="padding: 8px; border: 1px solid #ddd;">' . $product->ID . '</td>';
            echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($product->post_title) . '</td>';
            echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($product->product_type) . '</td>';
            echo '<td style="padding: 8px; border: 1px solid #ddd;">' . ($product->customizable_value === null ? 'NULL' : "'" . esc_html($product->customizable_value) . "'") . '</td>';
            echo '<td style="padding: 8px; border: 1px solid #ddd;">' . $customizable_status . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '<p><strong>Instructions:</strong> Products showing ⚠️ NULL or Empty String should be updated by editing and saving the product.</p>';
        echo '</div>';
    });
}
?>