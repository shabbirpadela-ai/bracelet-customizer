<?php
/**
 * Product Types Class
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle custom WooCommerce product types for bracelets and charms
 */
class Bracelet_Customizer_Product_Types {
    
    /**
     * Initialize product types
     */
    public function __construct() {
        $this->init_hooks();
        $this->include_product_classes();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register product types
        add_action('init', [$this, 'register_product_types'], 20);
        
        // Add product type to dropdown
        add_filter('product_type_selector', [$this, 'add_product_type_selector']);
        
        // Add product data tabs
        add_filter('woocommerce_product_data_tabs', [$this, 'add_product_data_tabs']);
        
        // Add product data panels
        add_action('woocommerce_product_data_panels', [$this, 'add_product_data_panels']);
        
        // Save product data
        add_action('woocommerce_process_product_meta', [$this, 'save_product_data']);
        
        // Add product type specific JavaScript
        add_action('admin_footer', [$this, 'add_product_type_js']);
        
        // Add custom handling for checkbox default values
        add_action('admin_head', [$this, 'add_checkbox_default_handling']);
        
        // Filter product class
        add_filter('woocommerce_product_class', [$this, 'woocommerce_product_class'], 10, 2);
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Get charm categories for select field options
     * @return array Array of category slug => label pairs
     */
    private function get_charm_categories_for_select() {
        $default_categories = [
            'bestsellers' => __('Bestsellers', 'bracelet-customizer'),
            'new_drops' => __('New Drops & Favs', 'bracelet-customizer'),
            'personalize' => __('Personalize it', 'bracelet-customizer'),
            'by_vibe' => __('By Vibe', 'bracelet-customizer')
        ];
        
        // Get custom categories from WordPress options
        $custom_categories = get_option('bracelet_customizer_charm_categories', []);
        
        // Merge default and custom categories
        $categories = array_merge($default_categories, $custom_categories);
        
        return $categories;
    }
    
    /**
     * Include custom product classes
     */
    private function include_product_classes() {
        require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/product-types/class-wc-product-standard-bracelet.php';
        require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/product-types/class-wc-product-charm.php';
        require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/product-types/class-wc-product-bracelet-collabs.php';
        require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/product-types/class-wc-product-tiny-words.php';
        require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/product-types/class-wc-product-bracelet-no-words.php';
        
        // Include debug script
        if (defined('WP_DEBUG') && WP_DEBUG) {
            require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/debug-customizable-meta.php';
        }
    }
    
    /**
     * Register custom product types
     */
    public function register_product_types() {
        // Register custom product types with WooCommerce
        add_filter('woocommerce_data_stores', [$this, 'register_product_data_stores']);
    }
    
    /**
     * Register product data stores for custom product types
     */
    public function register_product_data_stores($stores) {
        $stores['product-standard_bracelet'] = 'WC_Product_Data_Store_CPT';
        $stores['product-charm'] = 'WC_Product_Data_Store_CPT';
        $stores['product-bracelet_collabs'] = 'WC_Product_Data_Store_CPT';
        $stores['product-tiny_words'] = 'WC_Product_Data_Store_CPT';
        $stores['product-bracelet_no_words'] = 'WC_Product_Data_Store_CPT';
        return $stores;
    }
    
    /**
     * Add product types to the selector dropdown
     */
    public function add_product_type_selector($types) {
        $types['standard_bracelet'] = __('Standard Bracelet', 'bracelet-customizer');
        $types['bracelet_collabs'] = __('Bracelet Collabs', 'bracelet-customizer');
        $types['bracelet_no_words'] = __('Bracelet - No Words', 'bracelet-customizer');
        $types['tiny_words'] = __('Tiny Words', 'bracelet-customizer');
        $types['charm'] = __('Charm', 'bracelet-customizer');
        
        return $types;
    }
    
    /**
     * Add custom product data tabs
     */
    public function add_product_data_tabs($tabs) {
        $add_show_if = function (&$tab) {
            if (!isset($tab['class'])) $tab['class'] = [];
            if (!in_array('show_if_charm', $tab['class'], true)) {
                $tab['class'][] = 'show_if_charm';
            }
        };
        // Standard Bracelet Configuration Tab
        $tabs['standard_bracelet_config'] = [
            'label' => __('Bracelet Config', 'bracelet-customizer'),
            'target' => 'standard_bracelet_config_data',
            'class' => ['show_if_standard_bracelet'],
            'priority' => 21
        ];

        if (isset($tabs['general'])) {
            $add_show_if($tabs['general']);
        }

        // Charm Configuration Tab
        $tabs['charm_config'] = [
            'label' => __('Charm Config', 'bracelet-customizer'),
            'target' => 'charm_config_data',
            'class' => ['show_if_charm'],
            'priority' => 21
        ];

        
        // Bracelet Collabs Configuration Tab
        $tabs['bracelet_collabs_config'] = [
            'label' => __('Collabs Config', 'bracelet-customizer'),
            'target' => 'bracelet_collabs_config_data',
            'class' => ['show_if_bracelet_collabs'],
            'priority' => 21
        ];
        
        // Tiny Words Configuration Tab
        $tabs['tiny_words_config'] = [
            'label' => __('Tiny Word Config', 'bracelet-customizer'),
            'target' => 'tiny_words_config_data',
            'class' => ['show_if_tiny_words'],
            'priority' => 21
        ];
        
        // Bracelet No Words Configuration Tab
        $tabs['bracelet_no_words_config'] = [
            'label' => __('No Words Config', 'bracelet-customizer'),
            'target' => 'bracelet_no_words_config_data',
            'class' => ['show_if_bracelet_no_words'],
            'priority' => 21
        ];
        
        // Tiny Words Gaps Tab
        $tabs['tiny_words_gaps'] = [
            'label' => __('Gaps (Max 10)', 'bracelet-customizer'),
            'target' => 'tiny_words_gaps_data',
            'class' => ['show_if_tiny_words'],
            'priority' => 22
        ];
        
        return $tabs;
    }
    
    /**
     * Add custom product data panels
     */
    public function add_product_data_panels() {
        global $post;
        
        // Standard Bracelet Configuration Panel
        ?>
        <div id="standard_bracelet_config_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <h4><?php _e('Standard Bracelet Configuration', 'bracelet-customizer'); ?></h4>
                
                <?php
                // Letter Colors Field
                $this->render_letter_colors_field($post->ID);
                ?>
            </div>
        </div>
        
        <!-- Charm Configuration Panel -->
        <div id="charm_config_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <h4><?php _e('Charm Configuration', 'bracelet-customizer'); ?></h4>
                
                <?php
                woocommerce_wp_select([
                    'id' => '_charm_category',
                    'label' => __('Charm Category', 'bracelet-customizer'),
                    'description' => __('Select the category for this charm.', 'bracelet-customizer'),
                    'desc_tip' => true,
                    'options' => $this->get_charm_categories_for_select()
                ]);
                
                woocommerce_wp_checkbox([
                    'id' => '_charm_is_new',
                    'label' => __('New Charm', 'bracelet-customizer'),
                    'description' => __('Mark this charm as new.', 'bracelet-customizer')
                ]);
                
                woocommerce_wp_checkbox([
                    'id' => '_charm_is_bestseller',
                    'label' => __('Bestseller', 'bracelet-customizer'),
                    'description' => __('Mark this charm as a bestseller.', 'bracelet-customizer')
                ]);
                
                // Get current values using consistent meta keys
                $main_charm_image_id = get_post_meta($post->ID, '_product_main_image', true);
                $main_charm_url = get_post_meta($post->ID, '_product_main_url', true);
                
                // Auto-fill URL if image is uploaded but URL is empty
                if ($main_charm_image_id && empty($main_charm_url)) {
                    $main_charm_url = wp_get_attachment_url($main_charm_image_id);
                }
                
                echo '<div class="main-charm-image-group" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">';
                echo '<h4 style="margin: 0 0 10px 0;">' . __('Main Charm Image', 'bracelet-customizer') . '</h4>';
                
                // Image URL field
                woocommerce_wp_text_input([
                    'id' => '_product_main_url',
                    'label' => __('Main Charm Image URL', 'bracelet-customizer'),
                    'description' => __('Image URL (auto-filled when uploaded via WordPress, or enter external CDN/Cloud URL)', 'bracelet-customizer'),
                    'desc_tip' => true,
                    'value' => $main_charm_url,
                    'wrapper_class' => 'form-row form-row-wide',
                    'type' => 'url',
                    'custom_attributes' => [
                        'placeholder' => 'https://example.com/main-charm.webp',
                        'data-auto-fill-field' => '_product_main_image'
                    ]
                ]);
                
                // Image upload field
                $this->render_image_field(
                    $post->ID, 
                    '_product_main_image', 
                    __('Upload Main Charm Image', 'bracelet-customizer')
                );
                
                echo '</div>';
                ?>
            </div>
        </div>
        
        <!-- Bracelet Collabs Configuration Panel -->
        <div id="bracelet_collabs_config_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <h4><?php _e('Bracelet Collabs Configuration', 'bracelet-customizer'); ?></h4>
                
                <?php
                // Letter Colors Field - moved down
                $this->render_letter_colors_field($post->ID);
                
                // Get current values
                $collabs_image_id = get_post_meta($post->ID, '_product_main_image', true);
                $collabs_url = get_post_meta($post->ID, '_product_main_url', true);
                
                // Auto-fill URL if image is uploaded but URL is empty
                if ($collabs_image_id && empty($collabs_url)) {
                    $collabs_url = wp_get_attachment_url($collabs_image_id);
                }
                
                echo '<div class="collabs-image-group" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">';
                echo '<h4 style="margin: 0 0 10px 0;">' . __('Bracelet Collabs Image', 'bracelet-customizer') . '</h4>';
                
                // Image URL field
                woocommerce_wp_text_input([
                    'id' => '_product_main_url',
                    'label' => __('Collabs Image URL', 'bracelet-customizer'),
                    'description' => __('Image URL (auto-filled when uploaded via WordPress, or enter external CDN/Cloud URL)', 'bracelet-customizer'),
                    'desc_tip' => true,
                    'value' => $collabs_url,
                    'wrapper_class' => 'form-row form-row-wide',
                    'type' => 'url',
                    'custom_attributes' => [
                        'placeholder' => 'https://example.com/collabs-bracelet.webp',
                        'data-auto-fill-field' => '_product_main_image'
                    ]
                ]);
                
                // Image upload field
                $this->render_image_field(
                    $post->ID, 
                    '_product_main_image', 
                    __('Upload Collabs Image', 'bracelet-customizer')
                );
                
                echo '</div>';
                ?>
            </div>
        </div>
        
        <!-- Bracelet No Words Configuration Panel -->
        <div id="bracelet_no_words_config_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <h4><?php _e('Bracelet - No Words Configuration', 'bracelet-customizer'); ?></h4>
                
                <?php
                // Get current values
                $no_words_image_id = get_post_meta($post->ID, '_product_main_image', true);
                $no_words_url = get_post_meta($post->ID, '_product_main_url', true);
                
                // Auto-fill URL if image is uploaded but URL is empty
                if ($no_words_image_id && empty($no_words_url)) {
                    $no_words_url = wp_get_attachment_url($no_words_image_id);
                }
                
                echo '<div class="no-words-image-group" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">';
                echo '<h4 style="margin: 0 0 10px 0;">' . __('Bracelet No Words Image', 'bracelet-customizer') . '</h4>';
                
                // Image URL field
                woocommerce_wp_text_input([
                    'id' => '_product_main_url',
                    'label' => __('No Words Image URL', 'bracelet-customizer'),
                    'description' => __('Image URL (auto-filled when uploaded via WordPress, or enter external CDN/Cloud URL)', 'bracelet-customizer'),
                    'desc_tip' => true,
                    'value' => $no_words_url,
                    'wrapper_class' => 'form-row form-row-wide',
                    'type' => 'url',
                    'custom_attributes' => [
                        'placeholder' => 'https://example.com/no-words-bracelet.webp',
                        'data-auto-fill-field' => '_product_main_image'
                    ]
                ]);
                
                // Image upload field
                $this->render_image_field(
                    $post->ID, 
                    '_product_main_image', 
                    __('Upload No Words Image', 'bracelet-customizer')
                );
                
                echo '</div>';
                ?>
            </div>
        </div>
        
        <!-- Tiny Words Configuration Panel -->
        <div id="tiny_words_config_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <h4><?php _e('Tiny Words Configuration', 'bracelet-customizer'); ?></h4>
                
                <?php
                // Bestseller checkbox
                woocommerce_wp_checkbox([
                    'id' => '_tiny_words_is_bestseller',
                    'label' => __('Bestseller', 'bracelet-customizer'),
                    'description' => __('Mark this tiny words bracelet as a bestseller.', 'bracelet-customizer'),
                    'value' => get_post_meta($post->ID, '_tiny_words_is_bestseller', true)
                ]);
                
                // Letter Colors Field
                $this->render_letter_colors_field($post->ID);
                
                // Get current values
                $tiny_words_image_id = get_post_meta($post->ID, '_product_main_image', true);
                $tiny_words_url = get_post_meta($post->ID, '_product_main_url', true);
                
                // Auto-fill URL if image is uploaded but URL is empty
                if ($tiny_words_image_id && empty($tiny_words_url)) {
                    $tiny_words_url = wp_get_attachment_url($tiny_words_image_id);
                }
                
                echo '<div class="tiny-words-image-group" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">';
                echo '<h4 style="margin: 0 0 10px 0;">' . __('Main Tiny Words Image', 'bracelet-customizer') . '</h4>';
                
                // Image URL field
                woocommerce_wp_text_input([
                    'id' => '_product_main_url',
                    'label' => __('Main Image URL', 'bracelet-customizer'),
                    'description' => __('Image URL (auto-filled when uploaded via WordPress, or enter external CDN/Cloud URL)', 'bracelet-customizer'),
                    'desc_tip' => true,
                    'value' => $tiny_words_url,
                    'wrapper_class' => 'form-row form-row-wide',
                    'type' => 'url',
                    'custom_attributes' => [
                        'placeholder' => 'https://example.com/tiny-words-bracelet.webp',
                        'data-auto-fill-field' => '_product_main_image'
                    ]
                ]);
                
                // Image upload field
                $this->render_image_field(
                    $post->ID, 
                    '_product_main_image', 
                    __('Upload Main Image', 'bracelet-customizer')
                );
                
                echo '</div>';
                ?>
            </div>
        </div>
        
        <!-- Tiny Words Gaps Panel -->
        <div id="tiny_words_gaps_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <h4><?php _e('Tiny Words Gap Images (Maximum 10 Characters)', 'bracelet-customizer'); ?></h4>
                <p class="description"><?php _e('Upload gap images for different character counts. Tiny words bracelets support up to 10 characters maximum.', 'bracelet-customizer'); ?></p>
                
                <?php
                // Create gap image fields for 1-10 characters following Standard Bracelet pattern
                for ($i = 1; $i <= 10; $i++) {
                    echo '<div class="gap-image-group" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">';
                    echo '<h4 style="margin: 0 0 10px 0;">' . sprintf(__('GAP %d - %d Character Gap', 'bracelet-customizer'), $i, $i) . '</h4>';
                    
                    // Get current values
                    $gap_image_id = get_post_meta($post->ID, "_tiny_words_gap_image_{$i}char", true);
                    $gap_url = get_post_meta($post->ID, "_tiny_words_gap_url_{$i}char", true);
                    
                    // Auto-fill URL if image is uploaded but URL is empty
                    if ($gap_image_id && empty($gap_url)) {
                        $gap_url = wp_get_attachment_url($gap_image_id);
                    }
                    
                    // Image URL field
                    woocommerce_wp_text_input([
                        'id' => "_tiny_words_gap_url_{$i}char",
                        'label' => sprintf(__('GAP %d Image URL', 'bracelet-customizer'), $i),
                        'description' => __('Image URL (auto-filled when uploaded via WordPress, or enter external CDN/Cloud URL)', 'bracelet-customizer'),
                        'desc_tip' => true,
                        'value' => $gap_url,
                        'wrapper_class' => 'form-row form-row-wide',
                        'type' => 'url',
                        'custom_attributes' => [
                            'placeholder' => 'https://example.com/tiny-words-' . $i . 'char.webp',
                            'data-auto-fill-field' => "_tiny_words_gap_image_{$i}char"
                        ]
                    ]);
                    
                    // Image upload field
                    $this->render_image_field(
                        $post->ID, 
                        "_tiny_words_gap_image_{$i}char", 
                        sprintf(__('Upload Image for GAP %d', 'bracelet-customizer'), $i)
                    );
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render letter colors Select2 field
     */
    private function render_letter_colors_field($product_id) {
        // Get plugin settings to retrieve available letter colors
        $settings = get_option('bracelet_customizer_settings', []);
        $available_colors = isset($settings['letter_colors']) ? $settings['letter_colors'] : [];
        
        // Use consistent meta key for all product types
        $meta_key  = '_product_letter_colors';
        $field_name = $field_id = '_product_letter_colors';

        // Did this meta ever exist?
        $meta_exists = metadata_exists( 'post', $product_id, $meta_key );

        // Read current selection (null if never saved)
        $selected_colors = $meta_exists ? get_post_meta( $product_id, $meta_key, true ) : null;

        // Normalize to array when value exists
        if ( $selected_colors !== null ) {
            if ( ! is_array( $selected_colors ) ) {
                $selected_colors = $selected_colors !== '' ? array( $selected_colors ) : array();
            }
        }

        $selected_colors = array_values( array_filter( $selected_colors ?: array(), 'strlen' ) );

        // Only for brand-new (never-saved) products, default to all enabled colors
        if ( null === $selected_colors ) {
            $selected_colors = array();
            foreach ( $available_colors as $color_id => $color_data ) {
                if ( ! empty( $color_data['enabled'] ) ) {
                    $selected_colors[] = $color_id;
                }
            }
        }
        
        echo '<div class="letter-colors-field" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">';
        echo '<h4 style="margin: 0 0 10px 0;">' . __( 'Letter Colors', 'bracelet-customizer' ) . '</h4>';
        
        if ( empty( $available_colors ) ) {
            echo '<p style="color: #d63638;">' . __( 'No letter colors configured. Please configure letter colors in the plugin settings first.', 'bracelet-customizer' ) . '</p>';
            echo '<a href="' . esc_url( admin_url( 'admin.php?page=bracelet-customizer-settings&tab=letter-colors' ) ) . '" class="button">' . __( 'Configure Letter Colors', 'bracelet-customizer' ) . '</a>';
        } else {
            echo '<label for="' . esc_attr( $field_id ) . '">' . __( 'Available Letter Colors:', 'bracelet-customizer' ) . '</label>';
            echo '<br><span class="description">' . __( 'Select which letter colors are available for this product. Add/remove letter colors in settings.', 'bracelet-customizer' ) . '</span>';
            
            // Add a hidden field to ensure POST field is always present (even when empty)
            echo '<input type="hidden" name="' . esc_attr( $field_name ) . '_submitted" value="1" />';
            
            echo '<select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '[]" multiple="multiple" class="wc-enhanced-select" style="width: 100%; margin-top: 10px;">';
            
            foreach ( $available_colors as $color_id => $color_data ) {
                if ( ! empty( $color_data['enabled'] ) ) {
                    $selected = in_array( $color_id, $selected_colors, true ) ? 'selected="selected"' : '';
                    $price_text = '';
                    if ( isset( $color_data['price'] ) && $color_data['price'] > 0 ) {
                        $price_text = ' (+' . wc_price( $color_data['price'] ) . ')';
                    }
                    
                    echo '<option value="' . esc_attr( $color_id ) . '" ' . esc_html( $selected ) . '>';
                    echo esc_html( $color_data['name'] ) . wp_kses_post( $price_text );
                    echo '</option>';
                }
            }
            
            echo '</select>';
            
            // Add JavaScript to initialize Select2 properly
            echo '<script type="text/javascript">
            jQuery(document).ready(function($) {
                var selectField = $("#' . esc_js( $field_id ) . '");
                
                // Initialize Select2
                $(".letter-colors-field select[name=\'_product_letter_colors[]\']")
                .not(".select2-hidden-accessible")
                .select2({
                    placeholder: "' . esc_js( __( 'Select letter colors...', 'bracelet-customizer' ) ) . '",
                    allowClear: true,
                    width: "100%"
                });

            });
            </script>';
        }
        
        echo '</div>';
    }
    
    /**
     * Save custom product data
     */
    public function save_product_data( $post_id ) {
        // Verify nonce and check permissions
        if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) {
            return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Save charm data
        if ( isset( $_POST['_charm_category'] ) ) {
            update_post_meta( $post_id, '_charm_category', sanitize_text_field( wp_unslash( $_POST['_charm_category'] ) ) );
        }
        
        if ( isset( $_POST['_charm_is_new'] ) ) {
            update_post_meta( $post_id, '_charm_is_new', 'yes' );
        } else {
            update_post_meta( $post_id, '_charm_is_new', 'no' );
        }
        
        if (isset($_POST['_charm_is_bestseller'])) {
            update_post_meta($post_id, '_charm_is_bestseller', 'yes');
        } else {
            update_post_meta($post_id, '_charm_is_bestseller', 'no');
        }
        
        if (isset($_POST['_charm_base_image'])) {
            update_post_meta($post_id, '_charm_base_image', sanitize_url($_POST['_charm_base_image']));
        }
        
        if (isset($_POST['_charm_description'])) {
            update_post_meta($post_id, '_charm_description', sanitize_textarea_field($_POST['_charm_description']));
        }
        
        if (isset($_POST['_charm_tags'])) {
            update_post_meta($post_id, '_charm_tags', sanitize_text_field($_POST['_charm_tags']));
        }
        
        // Save main charm image and URL
        if (isset($_POST['_product_main_image'])) {
            update_post_meta($post_id, '_product_main_image', sanitize_text_field($_POST['_product_main_image']));
        }
        
        if (isset($_POST['_product_main_url'])) {
            update_post_meta($post_id, '_product_main_url', esc_url_raw($_POST['_product_main_url']));
        }
        
        // Auto-fill URL when main charm image is uploaded
        $main_charm_image_id = isset($_POST['_product_main_image']) ? $_POST['_product_main_image'] : '';
        $main_charm_url = isset($_POST['_product_main_url']) ? $_POST['_product_main_url'] : '';
        
        if ($main_charm_image_id && empty($main_charm_url)) {
            $auto_url = wp_get_attachment_url($main_charm_image_id);
            if ($auto_url) {
                update_post_meta($post_id, '_product_main_url', $auto_url);
            }
        }
        
        // Save Bracelet Collabs data
        if (isset($_POST['_product_main_image'])) {
            update_post_meta($post_id, '_product_main_image', sanitize_text_field($_POST['_product_main_image']));
        }
        
        if (isset($_POST['_product_main_url'])) {
            update_post_meta($post_id, '_product_main_url', esc_url_raw($_POST['_product_main_url']));
        }
        
        // Auto-fill URL when collabs image is uploaded
        $collabs_image_id = isset($_POST['_product_main_image']) ? $_POST['_product_main_image'] : '';
        $collabs_url = isset($_POST['_product_main_url']) ? $_POST['_product_main_url'] : '';
        
        if ($collabs_image_id && empty($collabs_url)) {
            $auto_url = wp_get_attachment_url($collabs_image_id);
            if ($auto_url) {
                update_post_meta($post_id, '_product_main_url', $auto_url);
            }
        }
        
        // Save position images
        $position_images = [];
        for ($i = 1; $i <= 9; $i++) {
            if (isset($_POST["_charm_position_image_{$i}"])) {
                $position_images[$i] = sanitize_url($_POST["_charm_position_image_{$i}"]);
            }
        }
        update_post_meta($post_id, '_charm_position_images', $position_images);
        
        // Save product letter colors - handle both selection and removal
        // Save product letter colors (supports clearing all selections)
        // Save product letter colors — only if the active panel submitted this field
        if (isset($_POST['_product_letter_colors_submitted'])) {
            $selected = isset($_POST['_product_letter_colors']) ? (array) $_POST['_product_letter_colors'] : [];
            $selected = array_values(array_filter(array_map('sanitize_text_field', $selected), 'strlen'));

            if (!empty($selected)) {
                update_post_meta($post_id, '_product_letter_colors', $selected);
            } else {
                // User cleared all selections
                delete_post_meta($post_id, '_product_letter_colors');
            }
        }
        // If the hidden marker is absent, do nothing (prevents hidden panels from clobbering).

        // If the hidden "_submitted" flag isn't present, do nothing (field wasn't rendered/submitted).

        // Note: If hidden field is not in POST, we don't modify the meta (form wasn't submitted with letter colors field)
        
        // Save customizable checkbox for all bracelet product types
        if (isset($_POST['_bracelet_customizable'])) {
            update_post_meta($post_id, '_bracelet_customizable', 'yes');
        } else {
            // Only set to 'no' if it was previously saved - for new products, leave as default 'yes'
            $existing_value = get_post_meta($post_id, '_bracelet_customizable', true);
            if ($existing_value !== '') {
                // Meta exists, so user unchecked it
                update_post_meta($post_id, '_bracelet_customizable', 'no');
            } else {
                // Meta doesn't exist, set default value based on product type
                $product_type = isset($_POST['product-type']) ? $_POST['product-type'] : get_post_meta($post_id, '_product_type', true);
                if (in_array($product_type, ['standard_bracelet', 'bracelet_collabs', 'bracelet_no_words', 'tiny_words'])) {
                    update_post_meta($post_id, '_bracelet_customizable', 'yes');
                }
            }
        }
        
        // Ensure customizable meta is set for bracelet product types when first switching to them
        $current_product_type = isset($_POST['product-type']) ? $_POST['product-type'] : get_post_meta($post_id, '_product_type', true);
        if (in_array($current_product_type, ['standard_bracelet', 'bracelet_collabs', 'bracelet_no_words', 'tiny_words'])) {
            $existing_customizable = get_post_meta($post_id, '_bracelet_customizable', true);
            if ($existing_customizable === '') {
                // First time saving this product type, set default value
                update_post_meta($post_id, '_bracelet_customizable', 'yes');
            }
        }
        
        // Save Bracelet No Words data
        if (isset($_POST['_product_main_image'])) {
            update_post_meta($post_id, '_product_main_image', sanitize_text_field($_POST['_product_main_image']));
        }
        
        if (isset($_POST['_product_main_url'])) {
            update_post_meta($post_id, '_product_main_url', esc_url_raw($_POST['_product_main_url']));
        }
        
        // Auto-fill URL when no words image is uploaded
        $no_words_image_id = isset($_POST['_product_main_image']) ? $_POST['_product_main_image'] : '';
        $no_words_url = isset($_POST['_product_main_url']) ? $_POST['_product_main_url'] : '';
        
        if ($no_words_image_id && empty($no_words_url)) {
            $auto_url = wp_get_attachment_url($no_words_image_id);
            if ($auto_url) {
                update_post_meta($post_id, '_product_main_url', $auto_url);
            }
        }
        
        // Save Tiny Words data
        
        if (isset($_POST['_tiny_words_is_bestseller'])) {
            update_post_meta($post_id, '_tiny_words_is_bestseller', 'yes');
        } else {
            update_post_meta($post_id, '_tiny_words_is_bestseller', 'no');
        }
        
        if (isset($_POST['_product_main_image'])) {
            update_post_meta($post_id, '_product_main_image', sanitize_text_field($_POST['_product_main_image']));
        }
        
        if (isset($_POST['_product_main_url'])) {
            update_post_meta($post_id, '_product_main_url', esc_url_raw($_POST['_product_main_url']));
        }
        
        // Auto-fill URL when tiny words image is uploaded
        $tiny_words_image_id = isset($_POST['_product_main_image']) ? $_POST['_product_main_image'] : '';
        $tiny_words_url = isset($_POST['_product_main_url']) ? $_POST['_product_main_url'] : '';
        
        if ($tiny_words_image_id && empty($tiny_words_url)) {
            $auto_url = wp_get_attachment_url($tiny_words_image_id);
            if ($auto_url) {
                update_post_meta($post_id, '_product_main_url', $auto_url);
            }
        }
        
        
        // Save tiny words gap images and URLs (1-10 characters) following Standard Bracelet pattern
        for ($i = 1; $i <= 10; $i++) {
            // Save gap image ID
            if (isset($_POST["_tiny_words_gap_image_{$i}char"])) {
                update_post_meta($post_id, "_tiny_words_gap_image_{$i}char", sanitize_text_field($_POST["_tiny_words_gap_image_{$i}char"]));
            }
            
            // Save gap URL
            if (isset($_POST["_tiny_words_gap_url_{$i}char"])) {
                update_post_meta($post_id, "_tiny_words_gap_url_{$i}char", esc_url_raw($_POST["_tiny_words_gap_url_{$i}char"]));
            }
            
            // Auto-fill URL when gap image is uploaded
            $gap_image_id = isset($_POST["_tiny_words_gap_image_{$i}char"]) ? $_POST["_tiny_words_gap_image_{$i}char"] : '';
            $gap_url = isset($_POST["_tiny_words_gap_url_{$i}char"]) ? $_POST["_tiny_words_gap_url_{$i}char"] : '';
            
            if ($gap_image_id && empty($gap_url)) {
                $auto_url = wp_get_attachment_url($gap_image_id);
                if ($auto_url) {
                    update_post_meta($post_id, "_tiny_words_gap_url_{$i}char", $auto_url);
                }
            }
        }
    }
    
    /**
     * Render an image upload field
     */
    private function render_image_field($product_id, $meta_key, $label) {
        $image_id = get_post_meta($product_id, $meta_key, true);
        $uploaded_image_url = $image_id ? wp_get_attachment_url($image_id) : '';
        
        // Check for external URL using consistent meta keys
        $external_url = '';
        if ($meta_key === '_product_main_image') {
            $external_url = get_post_meta($product_id, '_product_main_url', true);
        } elseif (strpos($meta_key, '_tiny_words_gap_image_') !== false) {
            // Get the corresponding URL field for tiny words gap images
            $url_meta_key = str_replace('_tiny_words_gap_image_', '_tiny_words_gap_url_', $meta_key);
            $external_url = get_post_meta($product_id, $url_meta_key, true);
        }
        
        // Determine which image to show (external URL takes precedence)
        $display_image_url = '';
        $image_source = '';
        if (!empty($external_url)) {
            $display_image_url = $external_url;
            $image_source = 'external';
        } elseif ($uploaded_image_url) {
            $display_image_url = $uploaded_image_url;
            $image_source = 'uploaded';
        }
        
        echo '<div class="form-field ' . esc_attr($meta_key) . '_field">';
        echo '<label for="' . esc_attr($meta_key) . '">' . esc_html($label) . '</label>';
        echo '<div class="image-upload-container">';
        
        echo '<input type="hidden" id="' . esc_attr($meta_key) . '" name="' . esc_attr($meta_key) . '" value="' . esc_attr($image_id) . '" />';
        
        echo '<div class="image-preview" style="margin-bottom: 10px;">';
        if ($display_image_url) {
            echo '<img src="' . esc_url($display_image_url) . '" style="max-width: 150px; max-height: 150px; display: block;" />';
            if ($image_source === 'external') {
                echo '<p style="font-size: 11px; color: #666; margin: 5px 0 0 0;">External URL Image</p>';
            } elseif ($image_source === 'uploaded') {
                echo '<p style="font-size: 11px; color: #666; margin: 5px 0 0 0;">WordPress Upload</p>';
            }
        } else {
            echo '<div style="width: 150px; height: 150px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #999;">No image selected</div>';
        }
        echo '</div>';
        
        echo '<button type="button" class="button upload-image-button" data-field="' . esc_attr($meta_key) . '">' . __('Upload Image', 'bracelet-customizer') . '</button>';
        
        if ($image_id || $external_url) {
            echo ' <button type="button" class="button remove-image-button" data-field="' . esc_attr($meta_key) . '">' . __('Remove', 'bracelet-customizer') . '</button>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($hook === 'post.php' && $post_type === 'product' || $hook === 'post-new.php' && $post_type === 'product') {
            wp_enqueue_media();
        }
    }
    
    /**
     * Add custom handling for checkbox default values
     */
    public function add_checkbox_default_handling() {
        global $post;
        
        if (!$post || $post->post_type !== 'product') {
            return;
        }
        
        $product_type = get_post_meta($post->ID, '_product_type', true);
        $customizable_value = get_post_meta($post->ID, '_bracelet_customizable', true);
        
        // If this is a bracelet product type and no meta value exists, set the checkbox as checked
        if (in_array($product_type, ['standard_bracelet', 'bracelet_collabs', 'bracelet_no_words', 'tiny_words']) && $customizable_value === '') {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#_bracelet_customizable').prop('checked', true);
            });
            </script>
            <?php
        }
    }
    
    /**
     * Add JavaScript for product type functionality
     */
    public function add_product_type_js() {
        global $post;
        
        if (!$post || $post->post_type !== 'product') {
            return;
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {

			// Show/hide tabs based on product type
			function toggleProductTypeTabs() {
				var productType = $('#product-type').val();
                // Disable all duplicates first
                $('.letter-colors-field select[name="_product_letter_colors[]"]').prop('disabled', true);
                $('.letter-colors-field input[name="_product_letter_colors_submitted"]').prop('disabled', true);

                // Enable only the visible panel’s controls
                $('.woocommerce_options_panel:visible .letter-colors-field select[name="_product_letter_colors[]"]').prop('disabled', false);
                $('.woocommerce_options_panel:visible .letter-colors-field input[name="_product_letter_colors_submitted"]').prop('disabled', false);

				
				// Let WooCommerce handle tab visibility via show_if_* classes naturally
				// No manual hiding needed - WooCommerce will show/hide based on product type
				
				// For all custom product types, show general tab fields (like simple products)
				if (productType === 'standard_bracelet' || productType === 'charm' || productType === 'bracelet_collabs' || productType === 'bracelet_no_words' || productType === 'tiny_words') {
					$('.general_options').show();
					$('.show_if_simple').show();
					$('.pricing').show();
					$('._regular_price_field, ._sale_price_field').show();
					$('.sale_price_dates_fields').show();
					$('li.general_tab').show();
					
					// Hide Virtual and Downloadable checkboxes for custom product types
					$('label[for="_virtual"], label[for="_downloadable"]').hide();
					$('._virtual_field, ._downloadable_field').hide();
					$('.show_if_virtual, .show_if_downloadable').hide();
				}
				
				// Let WooCommerce handle all tab visibility via show_if_* classes naturally
				// No manual tab showing/hiding needed - WooCommerce will handle this automatically
			}
            
            // Initial toggle
            toggleProductTypeTabs();
            
            // Toggle on product type change
            $('#product-type').on('change', toggleProductTypeTabs);
            
            // Image upload functionality
            $(document).on('click', '.upload-image-button', function(e) {
                e.preventDefault();
                var button = $(this);
                var fieldId = button.data('field');
                
                var mediaUploader = wp.media({
                    title: '<?php _e('Choose Image', 'bracelet-customizer'); ?>',
                    button: {
                        text: '<?php _e('Use this image', 'bracelet-customizer'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#' + fieldId).val(attachment.id);
                    
                    // Update preview
                    var preview = button.siblings('.image-upload-container').find('.image-preview');
                    preview.html('<img src="' + attachment.url + '" style="max-width: 150px; max-height: 150px; display: block;" /><p style="font-size: 11px; color: #666; margin: 5px 0 0 0;">WordPress Upload</p>');
                    
                    // Auto-fill URL field if exists
                    var urlField = $('#' + fieldId.replace('_image', '_url'));
                    if (urlField.length && !urlField.val()) {
                        urlField.val(attachment.url);
                    }
                    
                    // Show remove button
                    if (!button.siblings('.remove-image-button').length) {
                        button.after(' <button type="button" class="button remove-image-button" data-field="' + fieldId + '"><?php _e('Remove', 'bracelet-customizer'); ?></button>');
                    }
                });
                
                mediaUploader.open();
            });
            
            // Image remove functionality
            $(document).on('click', '.remove-image-button', function(e) {
                e.preventDefault();
                var button = $(this);
                var fieldId = button.data('field');
                
                $('#' + fieldId).val('');
                
                // Update preview
                var preview = button.siblings('.image-upload-container').find('.image-preview');
                preview.html('<div style="width: 150px; height: 150px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #999;">No image selected</div>');
                
                // Remove this button
                button.remove();
            });
            
            // Bulk upload functionality for charms
            $('#bulk-upload-position-images').on('click', function(e) {
                e.preventDefault();
                var mediaUploader = wp.media({
                    title: '<?php _e('Select Position Images', 'bracelet-customizer'); ?>',
                    button: {
                        text: '<?php _e('Use Images', 'bracelet-customizer'); ?>'
                    },
                    multiple: true
                });
                
                mediaUploader.on('select', function() {
                    var selection = mediaUploader.state().get('selection');
                    var images = selection.toJSON();
                    
                    // Auto-assign images based on order
                    images.forEach(function(image, index) {
                        var position = index + 1; // Start from position 1
                        if (position <= 9) {
                            $('#_charm_position_image_' + position).val(image.url);
                        }
                    });
                });
                
                mediaUploader.open();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Filter product class for custom product types
     */
    public function woocommerce_product_class($classname, $product_type) {
        if ($product_type === 'standard_bracelet') {
            return 'WC_Product_Standard_Bracelet';
        } elseif ($product_type === 'bracelet_collabs') {
            return 'WC_Product_Bracelet_Collabs';
        } elseif ($product_type === 'bracelet_no_words') {
            return 'WC_Product_Bracelet_No_Words';
        } elseif ($product_type === 'tiny_words') {
            return 'WC_Product_Tiny_Words';
        } elseif ($product_type === 'charm') {
            return 'WC_Product_Charm';
        }
        return $classname;
    }
    
    /**
     * Get bracelet products for customizer
     */
    public static function get_bracelet_products($args = []) {
        // Check if we have WooCommerce, otherwise return hardcoded data
        if (!class_exists('WooCommerce') || !function_exists('wc_get_product')) {
            return self::get_hardcoded_bracelet_products($args);
        }
        
        $default_args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_product_type',
                    'value' => 'standard_bracelet'
                ]
            ]
        ];
        
        $args = wp_parse_args($args, $default_args);
        
        $products = get_posts($args);
        
        // If no custom products found, return hardcoded data
        if (empty($products)) {
            return self::get_hardcoded_bracelet_products($args);
        }
        
        $bracelet_data = [];
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if (!$product) continue;
            
            $bracelet_data[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'image' => wp_get_attachment_url($product->get_image_id()),
                'base_image' => get_post_meta($product->get_id(), '_bracelet_base_image', true),
                'gap_images' => get_post_meta($product->get_id(), '_bracelet_gap_images', true) ?: [],
                'category' => get_post_meta($product->get_id(), '_bracelet_style_category', true),
                'is_bestseller' => get_post_meta($product->get_id(), '_bracelet_is_bestseller', true) === 'yes',
                'customizable' => get_post_meta($product->get_id(), '_bracelet_customizable', true) === 'yes',
                'available_sizes' => explode("\n", get_post_meta($product->get_id(), '_bracelet_available_sizes', true) ?: "XS\nS/M\nM/L\nL/XL")
            ];
        }
        
        return $bracelet_data;
    }
    
    /**
     * Get charm products for customizer
     */
    public static function get_charm_products($args = []) {
        // Check if we have WooCommerce, otherwise return hardcoded data
        if (!class_exists('WooCommerce') || !function_exists('wc_get_product')) {
            return self::get_hardcoded_charm_products($args);
        }
        
        $default_args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_product_type',
                    'value' => 'charm'
                ]
            ]
        ];
        
        $args = wp_parse_args($args, $default_args);
        
        $products = get_posts($args);
        
        // If no custom products found, return hardcoded data
        if (empty($products)) {
            return self::get_hardcoded_charm_products($args);
        }
        
        $charm_data = [];
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if (!$product) continue;
            
            $charm_data[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'image' => wp_get_attachment_url($product->get_image_id()),
                'base_image' => get_post_meta($product->get_id(), '_charm_base_image', true),
                'position_images' => get_post_meta($product->get_id(), '_charm_position_images', true) ?: [],
                'category' => get_post_meta($product->get_id(), '_charm_category', true),
                'is_new' => get_post_meta($product->get_id(), '_charm_is_new', true) === 'yes',
                'is_bestseller' => get_post_meta($product->get_id(), '_charm_is_bestseller', true) === 'yes',
                'description' => get_post_meta($product->get_id(), '_charm_description', true),
                'tags' => get_post_meta($product->get_id(), '_charm_tags', true)
            ];
        }
        
        return $charm_data;
    }
    
    /**
     * Get hardcoded bracelet products (fallback for when no WooCommerce products exist)
     */
    public static function get_hardcoded_bracelet_products($args = []) {
        $bracelets = [
            [
                'id' => 'gold-plated',
                'name' => 'Gold Plated',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/gold-plated.webp',
                'basePrice' => 0,
                'isBestSeller' => true,
                'category' => 'standard',
                'availableSizes' => ['XS', 'S/M', 'M/L', 'L/XL']
            ],
            [
                'id' => 'bluestone',
                'name' => 'Bluestone',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone.webp',
                'gapImages' => [
                    '2' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-2char.webp',
                    '3' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-3char.webp',
                    '4' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-4char.webp',
                    '5' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-5char.webp',
                    '6' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-6char.webp',
                    '7' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-7char.webp',
                    '8' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-8char.webp',
                    '9' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-9char.webp',
                    '10' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-10char.webp',
                    '11' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-11char.webp',
                    '12' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-12char.webp',
                    '13' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/bluestone-13char.webp'
                ],
                'basePrice' => 0,
                'isBestSeller' => true,
                'category' => 'standard',
                'availableSizes' => ['XS', 'S/M', 'M/L', 'L/XL']
            ],
            [
                'id' => 'amethyst-dreams',
                'name' => 'Amethyst Dreams',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/amethyst-dreams.webp',
                'basePrice' => 5,
                'isBestSeller' => false,
                'category' => 'special',
                'availableSizes' => ['XS', 'S/M', 'M/L', 'L/XL']
            ],
            [
                'id' => 'rose-gold',
                'name' => 'Rose Gold',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/bracelets/rose-gold.webp',
                'basePrice' => 10,
                'isBestSeller' => false,
                'category' => 'special',
                'availableSizes' => ['XS', 'S/M', 'M/L', 'L/XL']
            ]
        ];

        // Apply filters if specified
        if (isset($args['category']) && $args['category'] !== 'All') {
            $bracelets = array_filter($bracelets, function($bracelet) use ($args) {
                return $bracelet['category'] === strtolower($args['category']);
            });
        }

        if (isset($args['bestsellers_only']) && $args['bestsellers_only']) {
            $bracelets = array_filter($bracelets, function($bracelet) {
                return $bracelet['isBestSeller'];
            });
        }

        return array_values($bracelets);
    }
    
    /**
     * Get hardcoded charm products (fallback for when no WooCommerce products exist)
     */
    public static function get_hardcoded_charm_products($args = []) {
        $charms = [
            [
                'id' => 'teacher',
                'name' => '#1 Teacher',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/charms/teacher-banner.jpg',
                'price' => 14,
                'isNew' => true,
                'category' => 'bestsellers'
            ],
            [
                'id' => 'heart',
                'name' => 'Heart',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/charms/apple.jpg',
                'price' => 12,
                'isNew' => false,
                'category' => 'bestsellers'
            ],
            [
                'id' => 'star',
                'name' => 'Star',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/charms/paint-palette.jpg',
                'price' => 10,
                'isNew' => false,
                'category' => 'by-vibe'
            ],
            [
                'id' => 'moon',
                'name' => 'Moon',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/charms/moon.png',
                'price' => 13,
                'isNew' => true,
                'category' => 'new-drops'
            ],
            [
                'id' => 'butterfly',
                'name' => 'Butterfly',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/charms/butterfly.png',
                'price' => 15,
                'isNew' => false,
                'category' => 'by-vibe'
            ],
            [
                'id' => 'anchor',
                'name' => 'Anchor',
                'image' => BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/images/charms/anchor.png',
                'price' => 11,
                'isNew' => false,
                'category' => 'bestsellers'
            ]
        ];

        // Apply filters if specified
        if (isset($args['category']) && $args['category'] !== 'All') {
            $category_map = [
                'Bestsellers' => 'bestsellers',
                'New Drops & Favs' => 'new-drops',
                'By Vibe' => 'by-vibe'
            ];
            
            $filter_category = $category_map[$args['category']] ?? strtolower($args['category']);
            
            $charms = array_filter($charms, function($charm) use ($filter_category) {
                return $charm['category'] === $filter_category;
            });
        }

        if (isset($args['new_only']) && $args['new_only']) {
            $charms = array_filter($charms, function($charm) {
                return $charm['isNew'];
            });
        }

        return array_values($charms);
    }
}