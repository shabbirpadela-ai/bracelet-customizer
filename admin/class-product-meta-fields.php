<?php
/**
 * WooCommerce Product Meta Fields for Bracelet Customizer
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for managing product meta fields
 */
class Bracelet_Customizer_Product_Meta_Fields {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('woocommerce_product_data_tabs', [$this, 'add_product_data_tabs']);
        add_action('woocommerce_product_data_panels', [$this, 'add_product_data_panels']);
        add_action('woocommerce_process_product_meta', [$this, 'save_product_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Add custom tabs to product data metabox
     */
    public function add_product_data_tabs($tabs) {
        $tabs['bracelet_customizer'] = [
            'label' => __('Bracelet Customizer', 'bracelet-customizer'),
            'target' => 'bracelet_customizer_product_data',
            'class' => ['show_if_standard_bracelet'],
            'priority' => 21
        ];
        
        $tabs['bracelet_images'] = [
            'label' => __('Bracelets', 'bracelet-customizer'),
            'target' => 'bracelet_images_product_data',
            'class' => ['show_if_standard_bracelet'],
            'priority' => 22
        ];
        
        $tabs['space_stones'] = [
            'label' => __('Spaces', 'bracelet-customizer'),
            'target' => 'space_stones_product_data',
            'class' => ['show_if_standard_bracelet'],
            'priority' => 23
        ];
        
        $tabs['charm_positions'] = [
            'label' => __('Charm Positions', 'bracelet-customizer'),
            'target' => 'charm_positions_product_data',
            'class' => ['show_if_charm'],
            'priority' => 24
        ];
        
        $tabs['charm_pos_nowords'] = [
            'label' => __('Charm Pos - NoWords', 'bracelet-customizer'),
            'target' => 'charm_pos_nowords_product_data',
            'class' => ['show_if_charm'],
            'priority' => 25
        ];
        
        return $tabs;
    }
    
    /**
     * Add custom panels to product data metabox
     */
    public function add_product_data_panels() {
        global $post;
        $product = wc_get_product($post->ID);
        $product_type = $product ? $product->get_type() : '';
        
        // Main Bracelet Customizer Panel
        echo '<div id="bracelet_customizer_product_data" class="panel woocommerce_options_panel" style="padding: 20px;">';
        $this->render_bracelet_basic_fields($post->ID);
        echo '</div>';
        
        // Bracelet Images Panel (Gap Images)
        echo '<div id="bracelet_images_product_data" class="panel woocommerce_options_panel" style="padding: 20px;">';
        
            $this->render_bracelet_images_fields($post->ID);
        
        echo '</div>';
        
        // Space Stones Panel
        echo '<div id="space_stones_product_data" class="panel woocommerce_options_panel" style="padding: 20px;">';
        
        
            $this->render_space_stones_fields($post->ID);
        
        echo '</div>';
        
        // Charm Positions Panel
        echo '<div id="charm_positions_product_data" class="panel woocommerce_options_panel" style="padding: 20px;">';

        $this->render_charm_positions_fields($post->ID);
        
        
        echo '</div>';
        
        // Charm Pos - NoWords Panel
        echo '<div id="charm_pos_nowords_product_data" class="panel woocommerce_options_panel" style="padding: 20px;">';

        $this->render_charm_pos_nowords_fields($post->ID);
        
        
        echo '</div>';
    }
    
    /**
     * Render basic bracelet fields (main customizer tab)
     */
    private function render_bracelet_basic_fields($product_id) {
        echo '<div class="options_group">';
        echo '<h3>' . __('Bracelet Settings', 'bracelet-customizer') . '</h3>';
        
        // Bracelet ID
        woocommerce_wp_text_input([
            'id' => '_bracelet_id',
            'label' => __('Bracelet ID', 'bracelet-customizer'),
            'description' => __('Unique identifier for this bracelet style', 'bracelet-customizer'),
            'desc_tip' => true,
            'value' => get_post_meta($product_id, '_bracelet_id', true)
        ]);
        
        
        // Best Seller
        woocommerce_wp_checkbox([
            'id' => '_is_best_seller',
            'label' => __('Best Seller', 'bracelet-customizer'),
            'description' => __('Mark this bracelet as a best seller', 'bracelet-customizer'),
            'value' => get_post_meta($product_id, '_is_best_seller', true)
        ]);
        
        echo '</div>';
        
        // Main Bracelet Image
        echo '<div class="options_group">';
        echo '<h3>' . __('Main Bracelet Image', 'bracelet-customizer') . '</h3>';

        // Get current values
        $bracelet_image_id = get_post_meta($product_id, '_product_main_image', true);
        $bracelet_url = get_post_meta($product_id, '_product_main_url', true);
        
        // Auto-fill URL if image is uploaded but URL is empty
        if ($bracelet_image_id && empty($bracelet_url)) {
            $bracelet_url = wp_get_attachment_url($bracelet_image_id);
        }
        
        // Image URL field
        woocommerce_wp_text_input([
            'id' => '_product_main_url',
            'label' => __('Main Image URL', 'bracelet-customizer'),
            'description' => __('Image URL (auto-filled when uploaded via WordPress, or enter external CDN/Cloud URL)', 'bracelet-customizer'),
            'desc_tip' => true,
            'value' => $bracelet_url,
            'wrapper_class' => 'form-row form-row-wide',
            'type' => 'url',
            'custom_attributes' => [
                'placeholder' => 'https://example.com/collabs-bracelet.webp',
                'data-auto-fill-field' => '_product_main_image'
            ]
        ]);
        
        // Image upload field
        $this->render_image_field(
            $product_id, 
            '_product_main_image', 
            __('Upload Main Image', 'bracelet-customizer')
        );
        echo '</div>';
        
        // Main Charm Image
        echo '<div class="options_group">';
        echo '<h3>' . __('Base Charm Image', 'bracelet-customizer') . '</h3>';
        echo '<p class="description">' . __('Upload base charm image that will be overlaid on the bracelet preview. Use WordPress media library or enter external CDN/Cloud URL.', 'bracelet-customizer') . '</p>';
        
        // Get current values
        $overlay_charm_image_id = get_post_meta($product_id, '_bracelet_overlay_charm_image', true);
        $overlay_charm_url = get_post_meta($product_id, '_bracelet_overlay_charm_url', true);
        
        // Auto-fill URL if image is uploaded but URL is empty
        if ($overlay_charm_image_id && empty($overlay_charm_url)) {
            $overlay_charm_url = wp_get_attachment_url($overlay_charm_image_id);
        }
        
        echo '<div class="base--charm-image-group" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">';
        echo '<h4 style="margin: 0 0 10px 0;">' . __('Base Charm Overlay Image', 'bracelet-customizer') . '</h4>';
        
        // Image URL field
        woocommerce_wp_text_input([
            'id' => '_bracelet_overlay_charm_url',
            'label' => __('Base Charm Image URL', 'bracelet-customizer'),
            'description' => __('Image URL (auto-filled when uploaded via WordPress, or enter external CDN/Cloud URL)', 'bracelet-customizer'),
            'desc_tip' => true,
            'value' => $overlay_charm_url,
            'wrapper_class' => 'form-row form-row-wide',
            'type' => 'url',
            'custom_attributes' => [
                'placeholder' => 'https://example.com/main-charm.webp',
                'data-auto-fill-field' => '_bracelet_overlay_charm_image'
            ]
        ]);
        
        // Image upload field
        
        $this->render_image_field(
                $product_id, 
                "_bracelet_overlay_charm_image", 
                __('Upload Base charm image', 'bracelet-customizer')
            );
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render bracelet images fields (gap images for different character counts)
     */
    private function render_bracelet_images_fields($product_id) {
        // Gap Images for different character counts
        echo '<div class="options_group">';
        echo '<h3>' . __('Gap Images (Character Count Specific)', 'bracelet-customizer') . '</h3>';
        echo '<p class="description">' . __('Upload bracelet images with letter gaps for different word lengths. Use WordPress media library or enter external CDN/Cloud URLs.', 'bracelet-customizer') . '</p>';
        
        for ($i = 2; $i <= 13; $i++) {
            echo '<div class="gap-image-group" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">';
            echo '<h4 style="margin: 0 0 10px 0;">' . sprintf(__('GAP %d - %d Character Gap', 'bracelet-customizer'), $i, $i) . '</h4>';
            
            // Get current values
            $gap_image_id = get_post_meta($product_id, "_bracelet_gap_image_{$i}char", true);
            $gap_url = get_post_meta($product_id, "_bracelet_gap_url_{$i}char", true);
            
            // Auto-fill URL if image is uploaded but URL is empty
            if ($gap_image_id && empty($gap_url)) {
                $gap_url = wp_get_attachment_url($gap_image_id);
            }
            
            // Image URL field
            woocommerce_wp_text_input([
                'id' => "_bracelet_gap_url_{$i}char",
                'label' => sprintf(__('GAP %d Image URL', 'bracelet-customizer'), $i),
                'description' => __('Image URL (auto-filled when uploaded via WordPress, or enter external CDN/Cloud URL)', 'bracelet-customizer'),
                'desc_tip' => true,
                'value' => $gap_url,
                'wrapper_class' => 'form-row form-row-wide',
                'type' => 'url',
                'custom_attributes' => [
                    'placeholder' => 'https://example.com/image.webp',
                    'data-auto-fill-field' => "_bracelet_gap_image_{$i}char"
                ]
            ]);
            
            // Image upload field (existing functionality)
            $this->render_image_field(
                $product_id, 
                "_bracelet_gap_image_{$i}char", 
                sprintf(__('Upload Image for GAP %d', 'bracelet-customizer'), $i)
            );
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render space stones fields (space stone images for different positions and word length formats)
     */
    private function render_space_stones_fields($product_id) {
        // Space Stone Images for different positions and word length formats
        echo '<div class="options_group">';
        echo '<h3>' . __('Space Stone Images', 'bracelet-customizer') . '</h3>';
        echo '<p class="description">' . __('Upload individual space stone images for each position and word length format. These appear in place of space characters in words. Pattern: {BraceletType}-{Position}-{FormatCode}.png where FormatCode is O (odd word length) or E (even word length).', 'bracelet-customizer') . '</p>';
        
        // Create space stone image fields for all positions and formats
        for ($position = 1; $position <= 13; $position++) {
            $position_padded = str_pad($position, 2, '0', STR_PAD_LEFT);
            
            echo '<div class="space-stone-position-group" style="border: 1px solid #e0e0e0; padding: 15px; margin-bottom: 15px; border-radius: 5px; background-color: #f9f9f9;">';
            echo '<h4 style="margin: 0 0 15px 0; color: #2c3e50;">' . sprintf(__('Position %s', 'bracelet-customizer'), $position_padded) . '</h4>';
            
            // For each position, create fields for both O (odd) and E (even) formats
            $formats = [
                'O' => __('Odd Word Length', 'bracelet-customizer'),
                'E' => __('Even Word Length', 'bracelet-customizer')
            ];
            
            foreach ($formats as $format_code => $format_label) {
                $field_key = "_space_stone_pos_{$position_padded}_{$format_code}";
                $url_field_key = "_space_stone_url_pos_{$position_padded}_{$format_code}";
                
                // Get current values
                $stone_image_id = get_post_meta($product_id, $field_key, true);
                $stone_url = get_post_meta($product_id, $url_field_key, true);
                
                // Auto-fill URL if image is uploaded but URL is empty
                if ($stone_image_id && empty($stone_url)) {
                    $stone_url = wp_get_attachment_url($stone_image_id);
                }
                
                echo '<div class="space-stone-format-group" style="border: 1px solid #ddd; padding: 12px; margin-bottom: 10px; border-radius: 3px; background-color: white;">';
                echo '<h5 style="margin: 0 0 8px 0; color: #34495e;">' . sprintf(__('%s Format (%s)', 'bracelet-customizer'), $format_code, $format_label) . '</h5>';
                
                // Image URL field
                woocommerce_wp_text_input([
                    'id' => $url_field_key,
                    'label' => sprintf(__('Position %s-%s URL', 'bracelet-customizer'), $position_padded, $format_code),
                    'description' => sprintf(__('Stone image URL for position %s, %s word length format', 'bracelet-customizer'), $position_padded, strtolower($format_label)),
                    'desc_tip' => true,
                    'value' => $stone_url,
                    'wrapper_class' => 'form-row form-row-wide',
                    'type' => 'url',
                    'custom_attributes' => [
                        'placeholder' => "https://example.com/stone-{$position_padded}-{$format_code}.webp",
                        'data-auto-fill-field' => $field_key
                    ]
                ]);
                
                // Image upload field
                $this->render_image_field(
                    $product_id, 
                    $field_key, 
                    sprintf(__('Upload Position %s-%s Stone', 'bracelet-customizer'), $position_padded, $format_code)
                );
                
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    
    /**
     * Render charm basic fields (charm customizer tab)
     */
    private function render_charm_basic_fields($product_id) {
        echo '<div class="options_group">';
        echo '<h3>' . __('Charm Settings', 'bracelet-customizer') . '</h3>';
        
        // Charm ID
        woocommerce_wp_text_input([
            'id' => '_charm_id',
            'label' => __('Charm ID', 'bracelet-customizer'),
            'description' => __('Unique identifier for this charm', 'bracelet-customizer'),
            'desc_tip' => true,
            'value' => get_post_meta($product_id, '_charm_id', true)
        ]);
        
        // Category
        woocommerce_wp_select([
            'id' => '_charm_category',
            'label' => __('Category', 'bracelet-customizer'),
            'options' => [
                'bestsellers' => __('Bestsellers', 'bracelet-customizer'),
                'new-drops-favs' => __('New Drops & Favs', 'bracelet-customizer'),
                'personalize-it' => __('Personalize It', 'bracelet-customizer')
            ],
            'value' => get_post_meta($product_id, '_charm_category', true)
        ]);
        
        // Is New
        woocommerce_wp_checkbox([
            'id' => '_is_new',
            'label' => __('New Charm', 'bracelet-customizer'),
            'description' => __('Mark this charm as new', 'bracelet-customizer'),
            'value' => get_post_meta($product_id, '_is_new', true)
        ]);
        
        // Charm Vibe/Description
        woocommerce_wp_textarea_input([
            'id' => '_charm_vibe',
            'label' => __('Charm Vibe', 'bracelet-customizer'),
            'description' => __('Describe the vibe or meaning of this charm', 'bracelet-customizer'),
            'desc_tip' => true,
            'value' => get_post_meta($product_id, '_charm_vibe', true)
        ]);
        
        // Charm Tags
        woocommerce_wp_text_input([
            'id' => '_charm_tags',
            'label' => __('Tags', 'bracelet-customizer'),
            'description' => __('Comma-separated list of tags for filtering', 'bracelet-customizer'),
            'desc_tip' => true,
            'value' => get_post_meta($product_id, '_charm_tags', true)
        ]);
        
        echo '</div>';
        
        // Main Charm Image with URL field
        echo '<div class="options_group">';
        echo '<h3>' . __('Main Charm Image', 'bracelet-customizer') . '</h3>';
        echo '<p class="description">' . __('Upload main charm image. Use WordPress media library or enter external CDN/Cloud URL.', 'bracelet-customizer') . '</p>';
        
        // Get current values
        $main_charm_image_id = get_post_meta($product_id, '_charm_main_image', true);
        $main_charm_url = get_post_meta($product_id, '_charm_main_url', true);
        
        // Auto-fill URL if image is uploaded but URL is empty
        if ($main_charm_image_id && empty($main_charm_url)) {
            $main_charm_url = wp_get_attachment_url($main_charm_image_id);
        }
        
        echo '<div class="main-charm-image-group" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">';
        echo '<h4 style="margin: 0 0 10px 0;">' . __('Main Charm Image', 'bracelet-customizer') . '</h4>';
        
        // Image URL field
        woocommerce_wp_text_input([
            'id' => '_charm_main_url',
            'label' => __('Main Charm Image URL', 'bracelet-customizer'),
            'description' => __('Image URL (auto-filled when uploaded via WordPress, or enter external CDN/Cloud URL)', 'bracelet-customizer'),
            'desc_tip' => true,
            'value' => $main_charm_url,
            'wrapper_class' => 'form-row form-row-wide',
            'type' => 'url',
            'custom_attributes' => [
                'placeholder' => 'https://example.com/main-charm.webp',
                'data-auto-fill-field' => '_charm_main_image'
            ]
        ]);
        
        // Image upload field
        $this->render_image_field(
            $product_id, 
            '_charm_main_image', 
            __('Upload Main Charm Image', 'bracelet-customizer')
        );
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render charm positions fields (charm positions tab)
     */
    private function render_charm_positions_fields($product_id) {
        // Position Images for 9 positions on bracelet with URL options
        echo '<div class="options_group">';
        echo '<h3>' . __('Charm Position Images', 'bracelet-customizer') . '</h3>';
        echo '<p class="description">' . __('Upload charm images for different positions on the bracelet (1-9). Use WordPress media library or enter external CDN/Cloud URLs.', 'bracelet-customizer') . '</p>';
        
        for ($pos = 1; $pos <= 9; $pos++) {
            echo '<div class="position-image-group" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">';
            echo '<h4 style="margin: 0 0 10px 0;">' . sprintf(__('Position %d', 'bracelet-customizer'), $pos) . '</h4>';
            
            // Get current values
            $position_image_id = get_post_meta($product_id, "_charm_position_image_{$pos}", true);
            $position_url = get_post_meta($product_id, "_charm_position_url_{$pos}", true);
            
            // Auto-fill URL if image is uploaded but URL is empty
            if ($position_image_id && empty($position_url)) {
                $position_url = wp_get_attachment_url($position_image_id);
            }
            
            // Image URL field
            woocommerce_wp_text_input([
                'id' => "_charm_position_url_{$pos}",
                'label' => sprintf(__('Position %d Image URL', 'bracelet-customizer'), $pos),
                'description' => sprintf(__('Image URL for position %d (auto-filled when uploaded via WordPress, or enter external CDN/Cloud URL)', 'bracelet-customizer'), $pos),
                'desc_tip' => true,
                'value' => $position_url,
                'wrapper_class' => 'form-row form-row-wide',
                'type' => 'url',
                'custom_attributes' => [
                    'placeholder' => "https://example.com/charm-pos-{$pos}.webp",
                    'data-auto-fill-field' => "_charm_position_image_{$pos}"
                ]
            ]);
            
            // Image upload field
            $this->render_image_field(
                $product_id, 
                "_charm_position_image_{$pos}", 
                sprintf(__('Upload Position %d Image', 'bracelet-customizer'), $pos)
            );
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render charm positions fields for NoWords products (charm pos nowords tab)
     */
    private function render_charm_pos_nowords_fields($product_id) {
        // Position Images for 7 positions on bracelet with URL options for NoWords products
        echo '<div class="options_group">';
        echo '<h3>' . __('Charm Position Images - NoWords', 'bracelet-customizer') . '</h3>';
        echo '<p class="description">' . __('Upload charm images for different positions on the bracelet for NoWords products (1-7). Use WordPress media library or enter external CDN/Cloud URLs.', 'bracelet-customizer') . '</p>';
        
        for ($pos = 1; $pos <= 7; $pos++) {
            echo '<div class="position-image-group" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">';
            echo '<h4 style="margin: 0 0 10px 0;">' . sprintf(__('NoWords Position %d', 'bracelet-customizer'), $pos) . '</h4>';
            
            // Get current values
            $position_image_id = get_post_meta($product_id, "_nowords_charm_position_image_{$pos}", true);
            $position_url = get_post_meta($product_id, "_nowords_charm_position_url_{$pos}", true);
            
            // Auto-fill URL if image is uploaded but URL is empty
            if ($position_image_id && empty($position_url)) {
                $position_url = wp_get_attachment_url($position_image_id);
            }
            
            // Image URL field
            woocommerce_wp_text_input([
                'id' => "_nowords_charm_position_url_{$pos}",
                'label' => sprintf(__('NoWords Position %d Image URL', 'bracelet-customizer'), $pos),
                'description' => sprintf(__('Image URL for NoWords position %d (auto-filled when uploaded via WordPress, or enter external CDN/Cloud URL)', 'bracelet-customizer'), $pos),
                'desc_tip' => true,
                'value' => $position_url,
                'wrapper_class' => 'form-row form-row-wide',
                'type' => 'url',
                'custom_attributes' => [
                    'placeholder' => "https://example.com/charm-nowords-pos-{$pos}.webp",
                    'data-auto-fill-field' => "_nowords_charm_position_image_{$pos}"
                ]
            ]);
            
            // Image upload field
            $this->render_image_field(
                $product_id, 
                "_nowords_charm_position_image_{$pos}", 
                sprintf(__('Upload NoWords Position %d Image', 'bracelet-customizer'), $pos)
            );
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render an image upload field
     */
    private function render_image_field($product_id, $meta_key, $label) {
        $image_id = get_post_meta($product_id, $meta_key, true);
        $uploaded_image_url = $image_id ? wp_get_attachment_url($image_id) : '';
        
        // Check for external URL (for gap images, main charm, and charm positions)
        $external_url = '';
        if (strpos($meta_key, '_bracelet_gap_image_') !== false) {
            // Get the corresponding URL field
            $url_meta_key = str_replace('_bracelet_gap_image_', '_bracelet_gap_url_', $meta_key);
            $external_url = get_post_meta($product_id, $url_meta_key, true);
        } elseif ($meta_key === '_bracelet_main_charm_image') {
            // Get the main charm URL field
            $external_url = get_post_meta($product_id, '_bracelet_main_charm_url', true);
        } elseif ($meta_key === '_charm_main_image') {
            // Get the main charm URL field
            $external_url = get_post_meta($product_id, '_charm_main_url', true);
        } elseif (strpos($meta_key, '_charm_position_image_') !== false) {
            // Get the corresponding URL field for charm positions
            $url_meta_key = str_replace('_charm_position_image_', '_charm_position_url_', $meta_key);
            $external_url = get_post_meta($product_id, $url_meta_key, true);
        } elseif (strpos($meta_key, '_nowords_charm_position_image_') !== false) {
            // Get the corresponding URL field for NoWords charm positions
            $url_meta_key = str_replace('_nowords_charm_position_image_', '_nowords_charm_position_url_', $meta_key);
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
     * Save product meta data
     */
    public function save_product_meta($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) return;
        
        $product_type = $product->get_type();
        
        if ($product_type === 'standard_bracelet') {
            // Save bracelet meta
            $this->save_meta_field($product_id, '_bracelet_id');
            $this->save_meta_field($product_id, '_is_best_seller');
            $this->save_meta_field($product_id, '_bracelet_main_image');
            $this->save_meta_field($product_id, '_product_main_image');
            $this->save_meta_field($product_id, '_product_main_url');
            $this->save_meta_field($product_id, '_bracelet_overlay_charm_image');
            $this->save_meta_field($product_id, '_bracelet_overlay_charm_url');
            
            // Save gap images and URLs
            for ($i = 2; $i <= 13; $i++) {
                $this->save_meta_field($product_id, "_bracelet_gap_image_{$i}char");
                $this->save_meta_field($product_id, "_bracelet_gap_url_{$i}char");
                
                // Auto-fill URL when image is uploaded
                $gap_image_id = isset($_POST["_bracelet_gap_image_{$i}char"]) ? $_POST["_bracelet_gap_image_{$i}char"] : '';
                $gap_url = isset($_POST["_bracelet_gap_url_{$i}char"]) ? $_POST["_bracelet_gap_url_{$i}char"] : '';
                
                if ($gap_image_id && empty($gap_url)) {
                    $auto_url = wp_get_attachment_url($gap_image_id);
                    if ($auto_url) {
                        update_post_meta($product_id, "_bracelet_gap_url_{$i}char", $auto_url);
                    }
                }
            }
            
            // Save main charm image and URL
            $this->save_meta_field($product_id, 'f');
            $this->save_meta_field($product_id, '_bracelet_overlay_charm_url');
            
            // Auto-fill URL when main charm image is uploaded
            $main_charm_image_id = isset($_POST['_bracelet_main_charm_image']) ? $_POST['_bracelet_main_charm_image'] : '';
            $main_charm_url = isset($_POST['_bracelet_main_charm_url']) ? $_POST['_bracelet_main_charm_url'] : '';
            
            if ($main_charm_image_id && empty($main_charm_url)) {
                $auto_url = wp_get_attachment_url($main_charm_image_id);
                if ($auto_url) {
                    update_post_meta($product_id, '_bracelet_main_charm_url', $auto_url);
                }
            }
            
            // Save space stone images and URLs for all positions and formats
            for ($position = 1; $position <= 13; $position++) {
                $position_padded = str_pad($position, 2, '0', STR_PAD_LEFT);
                $formats = ['O', 'E'];
                
                foreach ($formats as $format_code) {
                    $field_key = "_space_stone_pos_{$position_padded}_{$format_code}";
                    $url_field_key = "_space_stone_url_pos_{$position_padded}_{$format_code}";
                    
                    // Save both image and URL fields
                    $this->save_meta_field($product_id, $field_key);
                    $this->save_meta_field($product_id, $url_field_key);
                    
                    // Auto-fill URL when image is uploaded
                    $stone_image_id = isset($_POST[$field_key]) ? $_POST[$field_key] : '';
                    $stone_url = isset($_POST[$url_field_key]) ? $_POST[$url_field_key] : '';
                    
                    if ($stone_image_id && empty($stone_url)) {
                        $auto_url = wp_get_attachment_url($stone_image_id);
                        if ($auto_url) {
                            update_post_meta($product_id, $url_field_key, $auto_url);
                        }
                    }
                }
            }
            
        } elseif ($product_type === 'charm') {
            // Save charm meta
            $this->save_meta_field($product_id, '_charm_id');
            $this->save_meta_field($product_id, '_charm_category');
            $this->save_meta_field($product_id, '_is_new');
            $this->save_meta_field($product_id, '_charm_vibe');
            $this->save_meta_field($product_id, '_charm_tags');
            $this->save_meta_field($product_id, '_product_main_url');
            $this->save_meta_field($product_id, '_product_main_image');
            
            
            // Auto-fill URL when main charm image is uploaded
            $main_charm_image_id = isset($_POST['_product_main_image']) ? $_POST['_product_main_image'] : '';
            $main_charm_url = isset($_POST['_product_main_url']) ? $_POST['_product_main_url'] : '';
            
            if ($main_charm_image_id && empty($main_charm_url)) {
                $auto_url = wp_get_attachment_url($main_charm_image_id);
                if ($auto_url) {
                    update_post_meta($product_id, '_product_main_url', $auto_url);
                }
            }
            
            // Save position images and URLs
            for ($pos = 1; $pos <= 9; $pos++) {
                $this->save_meta_field($product_id, "_charm_position_image_{$pos}");
                $this->save_meta_field($product_id, "_charm_position_url_{$pos}");
                
                // Auto-fill URL when position image is uploaded
                $position_image_id = isset($_POST["_charm_position_image_{$pos}"]) ? $_POST["_charm_position_image_{$pos}"] : '';
                $position_url = isset($_POST["_charm_position_url_{$pos}"]) ? $_POST["_charm_position_url_{$pos}"] : '';
                
                if ($position_image_id && empty($position_url)) {
                    $auto_url = wp_get_attachment_url($position_image_id);
                    if ($auto_url) {
                        update_post_meta($product_id, "_charm_position_url_{$pos}", $auto_url);
                    }
                }
            }
            
            // Save NoWords position images and URLs
            for ($pos = 1; $pos <= 7; $pos++) {
                $this->save_meta_field($product_id, "_nowords_charm_position_image_{$pos}");
                $this->save_meta_field($product_id, "_nowords_charm_position_url_{$pos}");
                
                // Auto-fill URL when NoWords position image is uploaded
                $nowords_position_image_id = isset($_POST["_nowords_charm_position_image_{$pos}"]) ? $_POST["_nowords_charm_position_image_{$pos}"] : '';
                $nowords_position_url = isset($_POST["_nowords_charm_position_url_{$pos}"]) ? $_POST["_nowords_charm_position_url_{$pos}"] : '';
                
                if ($nowords_position_image_id && empty($nowords_position_url)) {
                    $auto_url = wp_get_attachment_url($nowords_position_image_id);
                    if ($auto_url) {
                        update_post_meta($product_id, "_nowords_charm_position_url_{$pos}", $auto_url);
                    }
                }
            }
        }
    }
    
    /**
     * Save individual meta field
     */
    private function save_meta_field($product_id, $meta_key) {
        if (isset($_POST[$meta_key])) {
            // Handle URL fields with proper sanitization
            if (strpos($meta_key, '_url') !== false || strpos($meta_key, '_url_') !== false) {
                $value = esc_url_raw($_POST[$meta_key]);
            } else {
                $value = sanitize_text_field($_POST[$meta_key]);
            }
            update_post_meta($product_id, $meta_key, $value);
        }
    }
    
    /**
     * Enqueue admin scripts and styles for image upload
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($hook === 'post.php' && $post_type === 'product') {
            wp_enqueue_media();
            wp_enqueue_script(
                'bracelet-customizer-admin',
                BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                BRACELET_CUSTOMIZER_VERSION,
                true
            );
        }
    }
}

// Initialize the class
new Bracelet_Customizer_Product_Meta_Fields();