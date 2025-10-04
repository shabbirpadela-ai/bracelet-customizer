<?php
/**
 * WooCommerce Integration Class
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle WooCommerce integration functionality
 */
class Bracelet_Customizer_WooCommerce {
    
    /**
     * Initialize WooCommerce integration
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Product page hooks
        add_action('woocommerce_single_product_summary', [$this, 'add_customize_button'], 35);
        add_action('flatsome_custom_single_product_1', [$this, 'add_customize_button']);
        add_action('wp_head', [$this, 'add_redirect_script']);
        
        // Cart hooks
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_customization_to_cart'], 10, 3);
        add_filter('woocommerce_get_cart_item_from_session', [$this, 'get_cart_item_from_session'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'display_customization_in_cart'], 10, 2);
        add_action('woocommerce_cart_item_name', [$this, 'add_customization_to_cart_item'], 10, 3);
        
        // Price modification hooks
        add_action('woocommerce_before_calculate_totals', [$this, 'modify_cart_item_price']);
        
        // Cart image hooks
        add_filter('woocommerce_cart_item_thumbnail', [$this, 'custom_cart_item_thumbnail'], 10, 3);
        add_filter('woocommerce_admin_order_item_thumbnail', [$this, 'custom_order_item_thumbnail'], 10, 3);
        
        // Checkout and email image hooks
        add_filter('woocommerce_order_item_thumbnail', [$this, 'custom_order_item_thumbnail'], 10, 3);
        add_filter('woocommerce_email_order_item_thumbnail', [$this, 'custom_email_order_item_thumbnail'], 10, 3);
        
        // Hide internal meta keys from order display
        add_filter('woocommerce_hidden_order_itemmeta', [$this, 'hide_internal_order_meta']);
        
        // Add preview link to cart item names
        add_filter('woocommerce_cart_item_name', [$this, 'customize_preview_cart_item_name'], 10, 3);
        
        // Order hooks
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_customization_to_order_item'], 10, 4);
        add_action('woocommerce_order_item_meta_end', [$this, 'display_customization_in_order'], 10, 3);
        
        // Admin order hooks
        add_action('woocommerce_admin_order_item_headers', [$this, 'add_customization_column_header']);
        add_action('woocommerce_admin_order_item_values', [$this, 'add_customization_column_content'], 10, 3);
        
        // AJAX hooks
        add_action('wp_ajax_add_custom_bracelet_to_cart', [$this, 'ajax_add_to_cart']);
        add_action('wp_ajax_nopriv_add_custom_bracelet_to_cart', [$this, 'ajax_add_to_cart']);
        
        // New AJAX hooks for React app
        add_action('wp_ajax_bracelet_add_to_cart', [$this, 'ajax_add_to_cart_v2']);
        add_action('wp_ajax_nopriv_bracelet_add_to_cart', [$this, 'ajax_add_to_cart_v2']);
    }
    
    /**
     * Add customize button to product page
     */
    public function add_customize_button() {
        global $product;
        
        if (!$product || !$this->is_customizable_product($product)) {
            return;
        }
        
        $settings = get_option('bracelet_customizer_settings', []);
        $button_text = $settings['button_labels']['customize'] ?? __('Customize This Bracelet', 'bracelet-customizer');
        
        ?>
        <div class="bracelet-customizer-button-wrapper">
            <button class="bracelet-customize-btn" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                <?php echo esc_html($button_text); ?>
            </button>
        </div>
        <?php
    }
    
    /**
     * Add redirect script for handling product context
     */
    public function add_redirect_script() {
        ?>
        <script type="text/javascript">
            // Store referring product page URL for customizer close button
            if (document.location.href.includes('product_id=')) {
                const urlParams = new URLSearchParams(window.location.search);
                const productId = urlParams.get('product_id');
                if (productId) {
                    // Store the referring product URL in session storage
                    const referrer = document.referrer;
                    if (referrer) {
                        sessionStorage.setItem('bracelet_customizer_referrer', referrer);
                    }
                }
            }
            
            // Handle close button on customizer page
            window.closeBraceletCustomizer = function() {
                const referrer = sessionStorage.getItem('bracelet_customizer_referrer');
                if (referrer) {
                    window.location.href = referrer;
                } else {
                    window.history.back();
                }
            };
        </script>
        <?php
    }
    
    /**
     * Check if product is customizable
     */
    private function is_customizable_product($product) {
        if (!$product) {
            return false;
        }
        
        // Check if product has customizable bracelet product type
        $product_type = $product->get_type();
        $customizable_types = ['standard_bracelet', 'bracelet_collabs', 'tiny_words', 'bracelet_no_words'];
        
        if (!in_array($product_type, $customizable_types)) {
            return false;
        }
        
        // Check customizable meta value
        $customizable_meta = get_post_meta($product->get_id(), '_bracelet_customizable', true);
        
        // For bracelet product types, default to customizable if meta is not set
        // This handles the case where the meta value hasn't been saved yet
        if ($customizable_meta === '') {
            return true; // Default to customizable for bracelet product types
        }
        
        return $customizable_meta === 'yes';
    }
    
    /**
     * Add customization data to cart
     */
    public function add_customization_to_cart($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['bracelet_customization'])) {
            $customization = json_decode(stripslashes($_POST['bracelet_customization']), true);
            
            if ($customization) {
                $cart_item_data['bracelet_customization'] = $customization;
                $cart_item_data['unique_key'] = md5(microtime().rand());
                
                // Note: Custom image URL will be provided by the React app in the future
            }
        }
        
        return $cart_item_data;
    }
    
    /**
     * Get cart item from session
     */
    public function get_cart_item_from_session($item, $values, $key) {
        if (array_key_exists('bracelet_customization', $values)) {
            $item['bracelet_customization'] = $values['bracelet_customization'];
        }
        
        if (array_key_exists('custom_image_url', $values)) {
            $item['custom_image_url'] = $values['custom_image_url'];
        }
        
        if (array_key_exists('preview_image_url', $values)) {
            $item['preview_image_url'] = $values['preview_image_url'];
        }
        
        return $item;
    }
    
    /**
     * Display customization in cart
     */
    public function display_customization_in_cart($item_data, $cart_item) {
        if (isset($cart_item['bracelet_customization'])) {
            $customization = $cart_item['bracelet_customization'];
            
            // Display word only once - prioritize the word field
            $word = $customization['word'] ?? $customization['text'] ?? '';
            if (!empty($word)) {
                $item_data[] = [
                    'key' => __('Words', 'bracelet-customizer'),
                    'value' => strtoupper($word)
                ];
            }
            
            if (isset($customization['letterColor'])) {
                $item_data[] = [
                    'key' => __('Letter Color', 'bracelet-customizer'),
                    'value' => ucfirst($customization['letterColor'])
                ];
            }
            
            if (isset($customization['size'])) {
                $item_data[] = [
                    'key' => __('Size', 'bracelet-customizer'),
                    'value' => strtoupper($customization['size'])
                ];
            }
            
            // Note: Charms section removed since charms are now separate products
        }
        
        // Add preview image URL if available
        if (isset($cart_item['preview_image_url'])) {
            $item_data[] = [
                'key' => __('View Preview', 'bracelet-customizer'),
                'value' => '<a href="' . esc_url($cart_item['preview_image_url']) . '" target="_blank">' . __('View Preview Image', 'bracelet-customizer') . '</a>'
            ];
        }
        
        return $item_data;
    }
    
    /**
     * Modify cart item price based on customization
     */
    public function modify_cart_item_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['bracelet_customization'])) {
                $customization = $cart_item['bracelet_customization'];
                $product = $cart_item['data'];
                $base_price = $product->get_regular_price();
                $additional_price = 0;

                // Add letter color pricing (gold letters cost extra)
                $letter_color = $customization['letter_color'] ?? $customization['letterColor'] ?? 'white';
                if ($letter_color === 'gold') {
                    $additional_price += 15; // Gold letters cost $15 extra
                }

                // Note: Charm pricing removed since charms are now separate products in cart

                // Set the new price only if there are additional costs (e.g., gold letters)
                if ($additional_price > 0) {
                    $new_price = $base_price + $additional_price;
                    $product->set_price($new_price);
                }
            }
            
            // Handle charm pricing from React app data
            if (isset($cart_item['charm_customization'])) {
                $charm_data = $cart_item['charm_customization'];
                $product = $cart_item['data'];
                
                // Use the price from React app charm data instead of WordPress product price
                if (isset($charm_data['price']) && is_numeric($charm_data['price'])) {
                    $product->set_price($charm_data['price']);
                    error_log('Setting charm price from React app data: ' . $charm_data['price'] . ' for charm: ' . ($charm_data['name'] ?? 'Unknown'));
                } else {
                    error_log('No price found in charm_customization data: ' . print_r($charm_data, true));
                }
            }
        }
    }
    
    /**
     * Add customization to cart item name
     */
    public function add_customization_to_cart_item($product_name, $cart_item, $cart_item_key) {
        // Removed duplicate word display - word is now only shown in cart item meta
        return $product_name;
    }
    
    /**
     * Add customization to order item
     */
    public function add_customization_to_order_item($item, $cart_item_key, $values, $order) {
        if (isset($values['bracelet_customization'])) {
            $customization = $values['bracelet_customization'];
            
            // Add as order item meta
            $item->add_meta_data('_bracelet_customization', $customization);
            
            // Save custom image URL if available
            if (isset($values['custom_image_url'])) {
                // Save as hidden meta for internal use
                $item->add_meta_data('_custom_image_url', $values['custom_image_url']);
                
                // Add visible preview link for order display
                $preview_link = sprintf('<a href="%s" target="_blank">%s</a>', 
                    esc_url($values['custom_image_url']), 
                    __('View Preview', 'bracelet-customizer')
                );
                $item->add_meta_data(__('Preview', 'bracelet-customizer'), $preview_link);
            }
            
            // Add individual meta for easy access
            if (isset($customization['word'])) {
                $item->add_meta_data(__('Word', 'bracelet-customizer'), strtoupper($customization['word']));
            }
            
            if (isset($customization['letterColor'])) {
                $item->add_meta_data(__('Letter Color', 'bracelet-customizer'), ucfirst($customization['letterColor']));
            }
            
            if (isset($customization['selectedCharms']) && !empty($customization['selectedCharms'])) {
                $charm_names = array_column($customization['selectedCharms'], 'name');
                $item->add_meta_data(__('Charms', 'bracelet-customizer'), implode(', ', $charm_names));
            }
            
            if (isset($customization['size'])) {
                $item->add_meta_data(__('Size', 'bracelet-customizer'), strtoupper($customization['size']));
            }
        }
    }
    
    /**
     * Display customization in order
     */
    public function display_customization_in_order($item_id, $item, $order) {
        $customization = $item->get_meta('_bracelet_customization');
        
        if ($customization) {
            echo '<div class="bracelet-customization-summary">';
            echo '<h4>' . __('Customization Details', 'bracelet-customizer') . '</h4>';
            
            if (isset($customization['word'])) {
                echo '<p><strong>' . __('Word:', 'bracelet-customizer') . '</strong> ' . strtoupper($customization['word']) . '</p>';
            }
            
            if (isset($customization['letterColor'])) {
                echo '<p><strong>' . __('Letter Color:', 'bracelet-customizer') . '</strong> ' . ucfirst($customization['letterColor']) . '</p>';
            }
            
            if (isset($customization['selectedCharms']) && !empty($customization['selectedCharms'])) {
                echo '<p><strong>' . __('Charms:', 'bracelet-customizer') . '</strong></p>';
                echo '<ul>';
                foreach ($customization['selectedCharms'] as $charm) {
                    echo '<li>' . esc_html($charm['name']) . '</li>';
                }
                echo '</ul>';
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Add customization column header in admin
     */
    public function add_customization_column_header() {
        echo '<th class="item-customization">' . __('Customization', 'bracelet-customizer') . '</th>';
    }
    
    /**
     * Add customization column content in admin
     */
    public function add_customization_column_content($product, $item, $item_id) {
        $customization = $item->get_meta('_bracelet_customization');
        
        echo '<td class="item-customization">';
        if ($customization) {
            if (isset($customization['word'])) {
                echo '<strong>' . strtoupper($customization['word']) . '</strong><br>';
            }
            
            if (isset($customization['letterColor'])) {
                echo '<small>' . ucfirst($customization['letterColor']) . ' letters</small><br>';
            }
            
            if (isset($customization['selectedCharms']) && !empty($customization['selectedCharms'])) {
                echo '<small>' . count($customization['selectedCharms']) . ' charm(s)</small>';
            }
        } else {
            echo '-';
        }
        echo '</td>';
    }
    
    /**
     * AJAX add to cart
     */
    public function ajax_add_to_cart() {
        check_ajax_referer('bracelet_customizer_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']) ?: 1;
        $customization_data = json_decode(stripslashes($_POST['customization_data']), true);
        
        if (!$product_id || !$customization_data) {
            wp_send_json_error(['message' => __('Invalid data provided.', 'bracelet-customizer')]);
        }
        
        // Validate product
        $product = wc_get_product($product_id);
        if (!$product || !$this->is_customizable_product($product)) {
            wp_send_json_error(['message' => __('Product is not customizable.', 'bracelet-customizer')]);
        }
        
        // Validate customization
        $validation = $this->validate_customization($customization_data);
        if (is_wp_error($validation)) {
            wp_send_json_error(['message' => $validation->get_error_message()]);
        }
        
        // Add to cart
        $cart_item_data = ['bracelet_customization' => $customization_data];
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, 0, [], $cart_item_data);
        
        if ($cart_item_key) {
            wp_send_json_success([
                'message' => __('Bracelet added to cart!', 'bracelet-customizer'),
                'cart_url' => wc_get_cart_url()
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to add bracelet to cart.', 'bracelet-customizer')]);
        }
    }
    
    /**
     * AJAX add to cart (v2 for React app)
     */
    public function ajax_add_to_cart_v2() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bracelet_customizer_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'bracelet-customizer')]);
        }
        
        // Get product data
        $product_data = json_decode(stripslashes($_POST['product_data']), true);
        $customization_id = sanitize_text_field($_POST['customization_id']);
        
        // Debug logging
        error_log('Add to cart v2 called with product_data: ' . print_r($product_data, true));
        error_log('Add to cart v2 called with customization_id: ' . $customization_id);
        
        if (!$product_data || !$customization_id) {
            wp_send_json_error(['message' => __('Invalid data provided.', 'bracelet-customizer')]);
        }
        
        $product_id = intval($product_data['product_id']);
        $quantity = intval($product_data['quantity']) ?: 1;
        $variation_data = $product_data['variation_data'] ?? [];
        
        // Get customization from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        $customization = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %s OR session_id = %s ORDER BY created_at DESC LIMIT 1",
            $customization_id,
            $customization_id
        ));
        
        if (!$customization) {
            error_log('Customization not found for ID: ' . $customization_id);
            wp_send_json_error(['message' => __('Customization not found.', 'bracelet-customizer')]);
        }
        
        error_log('Found customization: ' . print_r($customization, true));
        
        $customization_data = json_decode($customization->customization_data, true);
        
        // Validate product
        $product = wc_get_product($product_id);
        if (!$product || !$this->is_customizable_product($product)) {
            wp_send_json_error(['message' => __('Product is not customizable.', 'bracelet-customizer')]);
        }
        
        // Prepare cart item data
        $cart_item_data = [
            'bracelet_customization' => $customization_data,
            'customization_id' => $customization_id,
            'unique_key' => md5($customization_id . microtime())
        ];
        
        // Save custom image URL if provided by React app
        if (isset($product_data['custom_image_url'])) {
            $cart_item_data['custom_image_url'] = $product_data['custom_image_url'];
            $cart_item_data['preview_image_url'] = $product_data['custom_image_url']; // Store for preview link
            error_log('Custom image URL stored in cart data: ' . $product_data['custom_image_url']);
        } else {
            error_log('No custom image URL provided in product_data');
        }
        
        // Add variation data if present
        if (!empty($variation_data)) {
            $cart_item_data['variation_data'] = $variation_data;
        }
        
        $added_items = [];
        
        // Add main bracelet to cart
        $main_cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, 0, [], $cart_item_data);
        
        if (!$main_cart_item_key) {
            error_log('Failed to add main bracelet to cart for product_id: ' . $product_id);
            wp_send_json_error(['message' => __('Failed to add bracelet to cart.', 'bracelet-customizer')]);
        }
        
        $added_items[] = $main_cart_item_key;
        error_log('Successfully added main bracelet to cart with key: ' . $main_cart_item_key);
        
        // Add charms as separate products if selected
        if (isset($customization_data['selectedCharms']) && !empty($customization_data['selectedCharms'])) {
            foreach ($customization_data['selectedCharms'] as $charm) {
                // Use woocommerce_id directly from React app if available
                $charm_product_id = null;
                if (isset($charm['woocommerce_id']) && !empty($charm['woocommerce_id'])) {
                    $charm_product_id = (int) $charm['woocommerce_id'];
                    error_log('Using WordPress product ID from React app: ' . $charm_product_id . ' for charm: ' . $charm['name']);
                } else {
                    // Fallback to lookup method if woocommerce_id not available
                    $charm_product_id = $this->get_charm_product_id($charm);
                    error_log('Looked up charm product ID: ' . ($charm_product_id ?: 'NOT_FOUND') . ' for charm: ' . $charm['name']);
                }
                
                if ($charm_product_id) {
                    $charm_cart_key = WC()->cart->add_to_cart(
                        $charm_product_id, 
                        $quantity, // Use same quantity as main bracelet
                        0, 
                        [], 
                        [
                            'charm_customization' => $charm,
                            'parent_customization_id' => $customization_id,
                            'unique_key' => md5($charm['name'] . $customization_id . microtime())
                        ]
                    );
                    
                    if ($charm_cart_key) {
                        $added_items[] = $charm_cart_key;
                        error_log('Added charm to cart: ' . $charm['name'] . ' (WP ID: ' . $charm_product_id . ') with key: ' . $charm_cart_key);
                    } else {
                        error_log('Failed to add charm to cart: ' . $charm['name'] . ' (WP ID: ' . $charm_product_id . ')');
                    }
                } else {
                    error_log('Charm product not found, skipping: ' . $charm['name'] . ' (no valid product ID)');
                }
            }
        }
        
        // Preview image URL already stored in cart item data above
        
        wp_send_json_success([
            'message' => __('Bracelet and charms added to cart!', 'bracelet-customizer'),
            'cart_url' => wc_get_cart_url(),
            'cart_items' => $added_items,
            'main_item_key' => $main_cart_item_key
        ]);
    }
    
    /**
     * Get charm product ID by charm data (fallback method)
     * This should rarely be used since React app provides woocommerce_id
     */
    private function get_charm_product_id($charm) {
        global $wpdb;
        
        $charm_name = $charm['name'] ?? '';
        
        // Try to find by exact product title
        if ($charm_name) {
            $product_id = $wpdb->get_var($wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE p.post_type = 'product' 
                 AND p.post_status = 'publish'
                 AND pm.meta_key = '_product_type' 
                 AND pm.meta_value = 'charm'
                 AND p.post_title = %s 
                 LIMIT 1",
                $charm_name
            ));
            
            if ($product_id) {
                error_log('Found charm by exact name: ' . $charm_name . ' -> Product ID: ' . $product_id);
                return $product_id;
            }
        }
        
        error_log('Charm product not found for: ' . $charm_name . '. React app should provide woocommerce_id.');
        return null;
    }
    
    
    
    /**
     * Customize cart item name to add preview link for customized bracelets
     */
    public function customize_preview_cart_item_name($product_name, $cart_item, $cart_item_key) {
        // Preview link is now shown in cart meta instead of product title to avoid duplication
        return $product_name;
    }
    
    
    /**
     * Generate custom cart item thumbnail
     */
    public function custom_cart_item_thumbnail($thumbnail, $cart_item, $cart_item_key) {
        if (isset($cart_item['bracelet_customization'])) {
            // Try to use pre-generated image URL first
            $custom_image_url = $cart_item['custom_image_url'] ?? null;
            
            // If not available, generate it now
            if (!$custom_image_url) {
                $custom_image_url = $this->generate_customization_image($cart_item['bracelet_customization'], $cart_item);
            }
            
            if ($custom_image_url) {
                $product_name = $cart_item['data']->get_name();
                $thumbnail = sprintf('<img src="%s" alt="%s" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" style="max-width: 64px; height: auto;">', 
                    esc_url($custom_image_url), 
                    esc_attr($product_name)
                );
            }
        }
        return $thumbnail;
    }
    
    /**
     * Generate custom order item thumbnail
     */
    public function custom_order_item_thumbnail($thumbnail, $item, $order) {
        // Ensure we have proper objects - different WooCommerce contexts pass different parameter types
        
        // Convert order ID to order object if needed
        if (is_numeric($order)) {
            $order_obj = wc_get_order($order);
            if (!$order_obj) {
                return $thumbnail;
            }
            $order = $order_obj;
        }
        
        // Handle different item parameter types
        if (is_numeric($item)) {
            // $item is an item ID, we need to get the order item object
            if (!is_object($order) || !method_exists($order, 'get_items')) {
                return $thumbnail;
            }
            
            $order_item = null;
            foreach ($order->get_items() as $order_item_obj) {
                if ($order_item_obj->get_id() == $item) {
                    $order_item = $order_item_obj;
                    break;
                }
            }
            if (!$order_item) {
                return $thumbnail;
            }
            $item = $order_item;
        }
        
        // Now $item should be a proper order item object
        if (!is_object($item) || !method_exists($item, 'get_meta')) {
            return $thumbnail;
        }
        
        $customization = $item->get_meta('_bracelet_customization');
        if ($customization) {
            // Try to use pre-saved custom image URL first
            $custom_image_url = $item->get_meta('_custom_image_url');
            
            // If not available, generate it now
            if (!$custom_image_url) {
                // Convert order item to cart item format for image generation
                $cart_item = [
                    'data' => $item->get_product(),
                    'bracelet_customization' => $customization
                ];
                
                $custom_image_url = $this->generate_customization_image($customization, $cart_item);
            }
            
            if ($custom_image_url) {
                $product_name = $item->get_name();
                $thumbnail = sprintf('<img src="%s" alt="%s" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" style="max-width: 64px; height: auto;">', 
                    esc_url($custom_image_url), 
                    esc_attr($product_name)
                );
            }
        }
        return $thumbnail;
    }
    
    /**
     * Generate custom email order item thumbnail
     */
    public function custom_email_order_item_thumbnail($thumbnail, $item, $order) {
        // Ensure we have proper objects - different WooCommerce contexts pass different parameter types
        
        // Convert order ID to order object if needed
        if (is_numeric($order)) {
            $order_obj = wc_get_order($order);
            if (!$order_obj) {
                return $thumbnail;
            }
            $order = $order_obj;
        }
        
        // Handle different item parameter types
        if (is_numeric($item)) {
            // $item is an item ID, we need to get the order item object
            if (!is_object($order) || !method_exists($order, 'get_items')) {
                return $thumbnail;
            }
            
            $order_item = null;
            foreach ($order->get_items() as $order_item_obj) {
                if ($order_item_obj->get_id() == $item) {
                    $order_item = $order_item_obj;
                    break;
                }
            }
            if (!$order_item) {
                return $thumbnail;
            }
            $item = $order_item;
        }
        
        // Now $item should be a proper order item object
        if (!is_object($item) || !method_exists($item, 'get_meta')) {
            return $thumbnail;
        }
        
        $customization = $item->get_meta('_bracelet_customization');
        if ($customization) {
            // Try to use pre-saved custom image URL first
            $custom_image_url = $item->get_meta('_custom_image_url');
            
            // If not available, generate it now
            if (!$custom_image_url) {
                // Convert order item to cart item format for image generation
                $cart_item = [
                    'data' => $item->get_product(),
                    'bracelet_customization' => $customization
                ];
                
                $custom_image_url = $this->generate_customization_image($customization, $cart_item);
            }
            
            if ($custom_image_url) {
                $product_name = $item->get_name();
                $thumbnail = sprintf('<img src="%s" alt="%s" style="max-width: 64px; height: auto; border: none;">', 
                    esc_url($custom_image_url), 
                    esc_attr($product_name)
                );
            }
        }
        return $thumbnail;
    }
    
    /**
     * Generate customization preview image URL
     */
    private function generate_customization_image($customization, $cart_item) {
        // For now, check if a custom image URL was saved with the cart item
        if (isset($cart_item['custom_image_url'])) {
            return $cart_item['custom_image_url'];
        }
        
        // If no custom image is available, fall back to product image
        $product = $cart_item['data'];
        if ($product && $product->get_image_id()) {
            return wp_get_attachment_url($product->get_image_id());
        }
        
        return false;
    }
    
    /**
     * Hide internal meta keys from order display
     */
    public function hide_internal_order_meta($hidden_meta_keys) {
        $hidden_meta_keys[] = '_custom_image_url';
        $hidden_meta_keys[] = '_bracelet_customization';
        return $hidden_meta_keys;
    }
    
    /**
     * Validate customization data
     */
    private function validate_customization($customization) {
        $settings = get_option('bracelet_customizer_settings', []);
        
        // Validate word
        if (isset($customization['word'])) {
            $word = trim($customization['word']);
            $min_length = $settings['min_word_length'] ?? 2;
            $max_length = $settings['max_word_length'] ?? 13;
            
            if (strlen($word) < $min_length) {
                return new WP_Error('word_too_short', sprintf(__('Word must be at least %d characters long.', 'bracelet-customizer'), $min_length));
            }
            
            if (strlen($word) > $max_length) {
                return new WP_Error('word_too_long', sprintf(__('Word cannot be longer than %d characters.', 'bracelet-customizer'), $max_length));
            }
        }
        
        // Validate letter color
        if (isset($customization['letterColor'])) {
            $letter_colors = $settings['letter_colors'] ?? [];
            if (!isset($letter_colors[$customization['letterColor']])) {
                return new WP_Error('invalid_letter_color', __('Invalid letter color selected.', 'bracelet-customizer'));
            }
        }
        
        return true;
    }
}