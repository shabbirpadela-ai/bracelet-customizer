<?php
/**
 * Database Class
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle database operations for customizations
 */
class Bracelet_Customizer_Database {
    
    /**
     * Initialize database functionality
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_order_customization'], 10, 4);
        add_action('wp_ajax_get_customization', [$this, 'get_customization']);
        add_action('wp_ajax_nopriv_get_customization', [$this, 'get_customization']);
    }
    
    /**
     * Save customization to database
     */
    public function save_customization($session_id, $product_id, $customization_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        
        $result = $wpdb->replace($table_name, [
            'session_id' => sanitize_text_field($session_id),
            'product_id' => intval($product_id),
            'customization_data' => wp_json_encode($customization_data),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]);
        
        return $result !== false;
    }
    
    /**
     * Get customization from database
     */
    public function get_customization_by_session($session_id, $product_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE session_id = %s",
            $session_id
        );
        
        if ($product_id) {
            $sql .= $wpdb->prepare(" AND product_id = %d", $product_id);
        }
        
        $sql .= " ORDER BY updated_at DESC LIMIT 1";
        
        $result = $wpdb->get_row($sql);
        
        if ($result) {
            $result->customization_data = json_decode($result->customization_data, true);
        }
        
        return $result;
    }
    
    /**
     * Get customization via AJAX
     */
    public function get_customization() {
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if (empty($session_id)) {
            wp_send_json_error(['message' => 'Session ID required']);
        }
        
        $customization = $this->get_customization_by_session($session_id, $product_id);
        
        if ($customization) {
            wp_send_json_success($customization);
        } else {
            wp_send_json_error(['message' => 'No customization found']);
        }
    }
    
    /**
     * Save customization to order item
     */
    public function save_order_customization($item, $cart_item_key, $values, $order) {
        if (isset($values['bracelet_customization'])) {
            global $wpdb;
            
            $customization_data = $values['bracelet_customization'];
            $order_item_id = $item->get_id();
            
            // Save to order item meta
            $item->add_meta_data('_bracelet_customization', $customization_data);
            
            // Save to customizations table
            $table_name = $wpdb->prefix . 'order_item_customizations';
            
            $wpdb->insert($table_name, [
                'order_item_id' => $order_item_id,
                'customization_data' => wp_json_encode($customization_data),
                'created_at' => current_time('mysql')
            ]);
        }
    }
    
    /**
     * Get order customization
     */
    public function get_order_customization($order_item_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'order_item_customizations';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE order_item_id = %d",
            $order_item_id
        ));
        
        if ($result) {
            $result->customization_data = json_decode($result->customization_data, true);
        }
        
        return $result;
    }
    
    /**
     * Clean up old customizations
     */
    public function cleanup_old_customizations($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        return $result;
    }
    
    /**
     * Get customization statistics
     */
    public function get_customization_stats($from_date = null, $to_date = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        
        $where_clause = '1=1';
        $params = [];
        
        if ($from_date) {
            $where_clause .= ' AND created_at >= %s';
            $params[] = $from_date;
        }
        
        if ($to_date) {
            $where_clause .= ' AND created_at <= %s';
            $params[] = $to_date;
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_customizations,
                    COUNT(DISTINCT session_id) as unique_sessions,
                    COUNT(DISTINCT product_id) as unique_products
                FROM {$table_name} 
                WHERE {$where_clause}";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Export customizations to CSV
     */
    public function export_customizations_csv($from_date = null, $to_date = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        
        $where_clause = '1=1';
        $params = [];
        
        if ($from_date) {
            $where_clause .= ' AND created_at >= %s';
            $params[] = $from_date;
        }
        
        if ($to_date) {
            $where_clause .= ' AND created_at <= %s';
            $params[] = $to_date;
        }
        
        $sql = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        $results = $wpdb->get_results($sql);
        
        if (empty($results)) {
            return false;
        }
        
        $filename = 'bracelet-customizations-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Write CSV headers
        fputcsv($output, ['ID', 'Session ID', 'Product ID', 'Word', 'Letter Color', 'Charms', 'Created At']);
        
        // Write data rows
        foreach ($results as $row) {
            $customization = json_decode($row->customization_data, true);
            
            fputcsv($output, [
                $row->id,
                $row->session_id,
                $row->product_id,
                $customization['word'] ?? '',
                $customization['letterColor'] ?? '',
                isset($customization['selectedCharms']) ? implode(', ', array_column($customization['selectedCharms'], 'name')) : '',
                $row->created_at
            ]);
        }
        
        fclose($output);
        exit;
    }
}