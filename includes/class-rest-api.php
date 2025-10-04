<?php
/**
 * REST API Class
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle REST API endpoints for the React app
 */
class Bracelet_Customizer_Rest_API {
    
    /**
     * API namespace
     */
    const NAMESPACE = 'bracelet-customizer/v1';
    
    /**
     * Initialize REST API
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('rest_api_init', [$this, 'register_routes']);
        
        // AJAX hooks for screenshot upload
        add_action('wp_ajax_upload_preview_image', [$this, 'ajax_upload_preview_image']);
        add_action('wp_ajax_nopriv_upload_preview_image', [$this, 'ajax_upload_preview_image']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Get bracelets
        register_rest_route(self::NAMESPACE, '/bracelets', [
            'methods' => 'GET',
            'callback' => [$this, 'get_bracelets'],
            'permission_callback' => '__return_true'
        ]);
        
        // Get charms
        register_rest_route(self::NAMESPACE, '/charms', [
            'methods' => 'GET',
            'callback' => [$this, 'get_charms'],
            'permission_callback' => '__return_true'
        ]);
        
        // Get settings
        register_rest_route(self::NAMESPACE, '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_settings'],
            'permission_callback' => '__return_true'
        ]);
        
        // Save customization
        register_rest_route(self::NAMESPACE, '/customization', [
            'methods' => 'POST',
            'callback' => [$this, 'save_customization'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        // Upload preview image
        register_rest_route(self::NAMESPACE, '/preview-image', [
            'methods' => 'POST',
            'callback' => [$this, 'upload_preview_image'],
            'permission_callback' => [$this, 'check_permission']
        ]);
    }
    
    /**
     * Get bracelets from WooCommerce Standard Bracelet products
     */
    public function get_bracelets($request) {
       try {
            // Get category filter from request
            $category = $request->get_param('category');
            
            if (!class_exists('WooCommerce')) {
                // Fallback to hardcoded data if WooCommerce not available
                $bracelets = Bracelet_Customizer_Product_Types::get_hardcoded_bracelet_products();
                return rest_ensure_response([
                    'success' => true,
                    'data' => $bracelets,
                    'source' => 'fallback'
                ]);
            }
            
            // Query WooCommerce products with customizable bracelets (excluding charm products)
            $args = [
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => '_bracelet_customizable',
                        'value' => 'yes',
                        'compare' => '='
                    ]
                ],
                'tax_query' => [
                    [
                        'taxonomy' => 'product_type',
                        'field' => 'slug',
                        'terms' => 'charm',
                        'operator' => 'NOT IN'
                    ]
                ]
            ];
            
            
            $products = get_posts($args);
            $bracelets = [];
            
            // If no products found, and we have less than expected, use fallback for development/testing
            if (empty($products)) {
                error_log('No customizable bracelet products found in WooCommerce. Using fallback data.');
                $bracelets = Bracelet_Customizer_Product_Types::get_hardcoded_bracelet_products();
                
                return rest_ensure_response([
                    'success' => true,
                    'data' => $bracelets,
                    'source' => 'fallback_no_products',
                    'message' => 'No customizable bracelet products found in WooCommerce database'
                ]);
            }
            
            foreach ($products as $product_post) {
                $product = wc_get_product($product_post->ID);
                if (!$product) continue;
                
                // Determine product type and category
                $product_type = $product->get_type();
                
                // Skip charm products (should already be filtered by tax_query, but double-check)
                if ($product_type === 'charm') continue;
                
                $is_collabs = ($product_type === 'bracelet_collabs');
                $is_tiny_words = ($product_type === 'tiny_words');
                
                // Convert product type to user-friendly category name
                $category = $this->get_product_category($product->get_id());
                /*
                switch ($product_type) {
                    case 'bracelet_collabs':
                        $category = 'Collabs';
                        break;
                    case 'tiny_words':
                        $category = 'Tiny Words';
                        break;
                    case 'bracelet_no_words':
                        $category = 'No Words';
                        break;
                    case 'standard_bracelet':
                        $category = 'Standard';
                        break;
                    default:
                        // Fallback: convert underscores to spaces and title case
                        $category = ucwords(str_replace(['_', '-'], ' ', $product_type));
                        break;
                }
                */
                
                // Get product meta data
                $bracelet_id = get_post_meta($product->get_id(), '_bracelet_id', true) ?: sanitize_title($product->get_name());
                $is_best_seller = get_post_meta($product->get_id(), '_is_best_seller', true) === 'yes';
                
                // Get available sizes from product attributes
                $available_sizes = [];
                $size_attribute = $product->get_attribute('pa_size') ?: $product->get_attribute('size');
                if ($size_attribute) {
                    $available_sizes = array_map('trim', explode('|', $size_attribute));
                } else {
                    // Fallback to default sizes if no attribute is set
                    $available_sizes = ['XS', 'S/M', 'M/L', 'L/XL'];
                }
                
                // Get product-specific letter colors
                $available_letter_colors = [];
                $letter_color_ids = get_post_meta($product->get_id(), '_product_letter_colors', true);
                
                // If product has specific letter colors, get the full color data from settings
                if (is_array($letter_color_ids) && !empty($letter_color_ids)) {
                    $settings = get_option('bracelet_customizer_settings', []);
                    $global_letter_colors = isset($settings['letter_colors']) ? $settings['letter_colors'] : [];
                    
                    foreach ($letter_color_ids as $color_id) {
                        if (isset($global_letter_colors[$color_id])) {
                            $available_letter_colors[] = [
                                'id' => $color_id,
                                'name' => $global_letter_colors[$color_id]['name'],
                                'price' => (float) $global_letter_colors[$color_id]['price'],
                                'color' => $global_letter_colors[$color_id]['color'],
                                'enabled' => !empty($global_letter_colors[$color_id]['enabled'])
                            ];
                        }
                    }
                }
                
                // Get main bracelet image
                $main_image = '';
                // Use consistent main image fields for all product types
                $product_main_url = get_post_meta($product->get_id(), '_product_main_url', true);
                $product_main_image_id = get_post_meta($product->get_id(), '_product_main_image', true);
                
                if (!empty($product_main_url)) {
                    $main_image = $product_main_url;
                } elseif ($product_main_image_id) {
                    $main_image = wp_get_attachment_url($product_main_image_id);
                } else {
                    // Fallback to product featured image
                    $main_image = wp_get_attachment_url($product->get_image_id());
                }
                
                // Get gap images (space images for different character counts)
                // Skip for collabs products as they don't use gap images
                $gap_images = [];
                $gap_data = [];
                if (!$is_collabs) {
                    if ($is_tiny_words) {
                        // Tiny Words: process gap images from individual fields (1-10 characters) like Standard Bracelet
                        for ($i = 1; $i <= 10; $i++) {
                            $gap_image_id = get_post_meta($product->get_id(), "_tiny_words_gap_image_{$i}char", true);
                            $gap_url = get_post_meta($product->get_id(), "_tiny_words_gap_url_{$i}char", true);
                            
                            // Determine the final image URL (URL field takes precedence, auto-filled from upload)
                            $final_image_url = '';
                            if (!empty($gap_url)) {
                                $final_image_url = $gap_url;
                            } elseif ($gap_image_id) {
                                $final_image_url = wp_get_attachment_url($gap_image_id);
                            }
                            
                            if ($final_image_url) {
                                $gap_images[$i] = $final_image_url;
                            }
                            
                            // Store detailed gap data
                            $gap_data[$i] = [
                                'image_url' => $final_image_url,
                                'url_field' => $gap_url ?: '',
                                'uploaded_image_id' => $gap_image_id ?: '',
                                'uploaded_image_url' => $gap_image_id ? wp_get_attachment_url($gap_image_id) : ''
                            ];
                        }
                    } else {
                        // Standard Bracelets: process 2-13 characters
                        for ($i = 2; $i <= 13; $i++) {
                            $gap_image_id = get_post_meta($product->get_id(), "_bracelet_gap_image_{$i}char", true);
                            $gap_url = get_post_meta($product->get_id(), "_bracelet_gap_url_{$i}char", true);
                    
                            // Determine the final image URL (URL field takes precedence, auto-filled from upload)
                            $final_image_url = '';
                            if (!empty($gap_url)) {
                                $final_image_url = $gap_url;
                            } elseif ($gap_image_id) {
                                $final_image_url = wp_get_attachment_url($gap_image_id);
                            }
                            
                            if ($final_image_url) {
                                $gap_images[$i] = $final_image_url;
                            }
                            
                            // Store detailed gap data
                            $gap_data[$i] = [
                                'image_url' => $final_image_url,
                                'url_field' => $gap_url ?: '',
                                'uploaded_image_id' => $gap_image_id ?: '',
                                'uploaded_image_url' => $gap_image_id ? wp_get_attachment_url($gap_image_id) : ''
                            ];
                        }
                    }
                }
                
                // Get main charm image and space stone images
                // Skip for collabs products as they don't use these features
                $main_charm_image = '';
                $main_charm_data = [];
                $space_stone_images = [];
                $space_stone_data = [];
                
                // Only Standard bracelet products should have main charm data
                if ($category === 'Standard') {
                    $main_charm_image_id = get_post_meta($product->get_id(), '_bracelet_overlay_charm_image', true);
                    $main_charm_url = get_post_meta($product->get_id(), '_bracelet_overlay_charm_url', true);
                    
                    // // Determine the final main charm image URL (URL field takes precedence)
                    if (!empty($main_charm_url)) {
                        $main_charm_image = $main_charm_url;
                    } elseif ($main_charm_image_id) {
                        $main_charm_image = wp_get_attachment_url($main_charm_image_id);
                    }
                    
                    // Store detailed main charm data
                    // $main_charm_data = [
                    //     'image_url' => $main_charm_image,
                    //     'url_field' => $main_charm_url ?: '',
                    //     'uploaded_image_id' => $main_charm_image_id ?: '',
                    //     'uploaded_image_url' => $main_charm_image_id ? wp_get_attachment_url($main_charm_image_id) : ''
                    // ];
                    
                    // Get space stone images for all positions and formats
                    for ($position = 1; $position <= 13; $position++) {
                    $position_padded = str_pad($position, 2, '0', STR_PAD_LEFT);
                    $formats = ['O', 'E'];
                    
                    foreach ($formats as $format_code) {
                        $field_key = "_space_stone_pos_{$position_padded}_{$format_code}";
                        $url_field_key = "_space_stone_url_pos_{$position_padded}_{$format_code}";
                        
                        $stone_image_id = get_post_meta($product->get_id(), $field_key, true);
                        $stone_url = get_post_meta($product->get_id(), $url_field_key, true);
                        
                        // Determine the final stone image URL (URL field takes precedence)
                        $final_stone_url = '';
                        if (!empty($stone_url)) {
                            $final_stone_url = $stone_url;
                        } elseif ($stone_image_id) {
                            $final_stone_url = wp_get_attachment_url($stone_image_id);
                        }
                        
                        $stone_key = "{$position_padded}_{$format_code}";
                        if ($final_stone_url) {
                            $space_stone_images[$stone_key] = $final_stone_url;
                        }
                        
                        // Store detailed space stone data
                        $space_stone_data[$stone_key] = [
                            'position' => $position,
                            'position_padded' => $position_padded,
                            'format_code' => $format_code,
                            'image_url' => $final_stone_url,
                            'url_field' => $stone_url ?: '',
                            'uploaded_image_id' => $stone_image_id ?: '',
                            'uploaded_image_url' => $stone_image_id ? wp_get_attachment_url($stone_image_id) : ''
                        ];
                    }
                }
                } // Close if (!$is_collabs) condition for space stone processing
                
                $bracelets[] = [
                    'id' => $bracelet_id,
                    'woocommerce_id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'description' => $product->get_short_description() ?: $product->get_description(),
                    'basePrice' => (float) $product->get_price(),
                    'image' => $main_image,
                    'gapImages' => $gap_images,
                    'gapData' => $gap_data,
                    'mainCharmImage' => $main_charm_image,
                    //'mainCharmData' => $main_charm_data,
                    'spaceStoneImages' => $space_stone_images,
                    'spaceStoneData' => $space_stone_data,
                    'availableSizes' => $available_sizes,
                    'availableLetterColors' => $available_letter_colors,
                    'isBestSeller' => $is_best_seller,
                    'category' => $category,
                    'maxWordLength' => $is_tiny_words ? 10 : 13,
                    'supportsCharms' => !$is_tiny_words && !$is_collabs,
                    'productType' => $product_type,
                    'slug' => $product->get_slug(),
                    'sku' => $product->get_sku()
                ];
            }
            
            // If no products found, return hardcoded fallback
            if (empty($bracelets)) {
                $bracelets = Bracelet_Customizer_Product_Types::get_hardcoded_bracelet_products();
                $source = 'fallback';
            } else {
                $source = 'woocommerce';
            }
            
            // Debug: Get categories for debugging
            $categories = array_unique(array_column($bracelets, 'category'));
            
            return rest_ensure_response([
                'success' => true,
                'data' => $bracelets,
                'source' => $source,
                'total' => count($bracelets),
                'categories' => $categories,
                'debug' => [
                    'collabs_count' => count(array_filter($bracelets, function($b) { return $b['category'] === 'collabs'; })),
                    'standard_count' => count(array_filter($bracelets, function($b) { return $b['category'] === 'standard'; }))
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Bracelet API Error: ' . $e->getMessage());
            
            // Return fallback data on error
            $bracelets = Bracelet_Customizer_Product_Types::get_hardcoded_bracelet_products();
            return rest_ensure_response([
                'success' => true,
                'data' => $bracelets,
                'source' => 'fallback_error',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get charms from WooCommerce Charm products
     */
    public function get_charms($request) {
        try {
            $category = $request->get_param('category') ?: 'All';
            
            if (!class_exists('WooCommerce')) {
                // Fallback to hardcoded data if WooCommerce not available
                $charms = Bracelet_Customizer_Product_Types::get_hardcoded_charm_products();
                return rest_ensure_response([
                    'success' => true,
                    'data' => $charms,
                    'source' => 'fallback'
                ]);
            }
            
            // Query WooCommerce products with Charm type
            $args = [
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => 'product_type',
                        'field' => 'slug',
                        'terms' => 'charm'
                    ]
                ]
            ];
            
            // Add category filter if specified
            if ($category && $category !== 'All') {
                $args['meta_query'][] = [
                    'key' => '_charm_category',
                    'value' => strtolower(str_replace(' ', '-', $category)),
                    'compare' => '='
                ];
            }
            
            $products = get_posts($args);
            $charms = [];
            $charm_cats = [];
            foreach ($products as $product_post) {
                $product = wc_get_product($product_post->ID);
                if (!$product) continue;
                
                // Get product meta data
                $charm_id = get_post_meta($product->get_id(), '_charm_id', true) ?: sanitize_title($product->get_name());
                $category = get_post_meta($product->get_id(), '_charm_category', true) ?: 'bestsellers';
                if (!isset($charm_cats[$category])) {
                     $charm_cats[$category] = ucwords(str_replace(['_', '-'], ' ', $category));
                }
               
                $is_new = get_post_meta($product->get_id(), '_is_new', true) === 'yes';
                
                // Get main charm image
                $main_image = '';
                // Use consistent main image fields for all product types
                $charm_main_url = get_post_meta($product->get_id(), '_product_main_url', true);
                $charm_main_image_id = get_post_meta($product->get_id(), '_product_main_image', true);
                
                if (!empty($charm_main_url)) {
                    $main_image = $charm_main_url;
                } elseif ($charm_main_image_id) {
                    $main_image = wp_get_attachment_url($charm_main_image_id);
                } else {
                    // Fallback to product featured image
                    $main_image = wp_get_attachment_url($product->get_image_id());
                }
                
                // Get position images (for 9 different positions on bracelet)
                $position_images = [];
                for ($pos = 1; $pos <= 9; $pos++) {
                    $pos_image_url = get_post_meta($product->get_id(), "_charm_position_url_{$pos}", true);
                    if (!empty($pos_image_url)) {
                        $position_images[$pos] = esc_url($pos_image_url);
                    }
                }
                
                // Get NoWords position images (for 7 different positions on bracelet)
                $nowords_position_images = [];
                for ($pos = 1; $pos <= 7; $pos++) {
                    $nowords_pos_image_url = get_post_meta($product->get_id(), "_nowords_charm_position_url_{$pos}", true);
                    if (!empty($nowords_pos_image_url)) {
                        $nowords_position_images[$pos] = esc_url($nowords_pos_image_url);
                    }
                }
                
                // Get charm description/vibe
                $vibe = get_post_meta($product->get_id(), '_charm_vibe', true) ?: '';
                
                // Get charm tags
                $tags = get_post_meta($product->get_id(), '_charm_tags', true);
                if (is_string($tags)) {
                    $tags = array_map('trim', explode(',', $tags));
                }
                if (!is_array($tags)) {
                    $tags = [];
                }
                
                $charms[] = [
                    'id' => $charm_id,
                    'woocommerce_id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'description' => $product->get_short_description() ?: $product->get_description(),
                    'price' => (float) $product->get_price(),
                    'image' => $main_image,
                    'positionImages' => $position_images,
                    'noWordsPositionImages' => $nowords_position_images,
                    'isNew' => $is_new,
                    'category' => $category,
                    'vibe' => $vibe,
                    'tags' => $tags,
                    'slug' => $product->get_slug(),
                    'sku' => $product->get_sku()
                ];
            }
            
            // If no products found, return hardcoded fallback
            if (empty($charms)) {
                $charms = Bracelet_Customizer_Product_Types::get_hardcoded_charm_products();
                $source = 'fallback';
            } else {
                $source = 'woocommerce';
            }
            
            return rest_ensure_response([
                'success' => true,
                'data' => $charms,
                'source' => $source,
                'total' => count($charms),
                'categories' => $charm_cats
            ]);
            
        } catch (Exception $e) {
            error_log('Charm API Error: ' . $e->getMessage());
            
            // Return fallback data on error
            $charms = Bracelet_Customizer_Product_Types::get_hardcoded_charm_products();
            return rest_ensure_response([
                'success' => true,
                'data' => $charms,
                'source' => 'fallback_error',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get settings
     */
    public function get_settings($request) {
        $settings = get_option('bracelet_customizer_settings', []);
        
        // Remove sensitive settings for frontend
        unset($settings['advanced_settings']);
        
        return rest_ensure_response($settings);
    }
    
    /**
     * Save customization
     */
    public function save_customization($request) {
        global $wpdb;
        
        // Try to get JSON params first, fallback to individual params
        $json_params = $request->get_json_params();
        $body_params = $request->get_body_params();
        
        // Merge all possible parameter sources
        $params = array_merge(
            (array) $json_params,
            (array) $body_params,
            $request->get_params()
        );
        
        // Extract session_id, product_id, and customization_data
        $session_id = isset($params['session_id']) ? $params['session_id'] : null;
        $product_id = isset($params['product_id']) ? $params['product_id'] : null;
        $customization_data = isset($params['customization_data']) ? $params['customization_data'] : null;
        
        // If customization_data is not provided as a structured object,
        // build it from flat fields (bracelet_style, word, etc.)
        if (!$customization_data && isset($params['bracelet_style'])) {
            $letter_color_value = $params['letter_color'] ?? $params['letterColor'] ?? 'white';
            $charms_value = $params['selected_charms'] ?? $params['selectedCharms'] ?? [];
            
            $customization_data = [
                'bracelet_style' => $params['bracelet_style'] ?? '',
                'word' => $params['word'] ?? '',
                'letter_color' => $letter_color_value,
                'letterColor' => $letter_color_value, // Ensure both keys exist
                'selected_charms' => $charms_value,
                'selectedCharms' => $charms_value, // Ensure both keys exist
                'size' => $params['size'] ?? 'M/L',
                'quantity' => $params['quantity'] ?? 1
            ];
        }
        
        // If product_id is not provided but we have bracelet_style, try to derive it
        if (!$product_id && isset($params['bracelet_style'])) {
            // For now, use bracelet_style as product identifier
            // In a real implementation, you might want to look up the actual WooCommerce product ID
            $product_id = $params['bracelet_style'];
        }
        
        // Generate session_id if not provided
        if (!$session_id) {
            $session_id = 'session_' . wp_generate_uuid4();
        }
        
        // Validate required data
        if (!$product_id || !$customization_data) {
            return new WP_Error('missing_data', 'Missing required data: product_id and customization_data are required', [
                'status' => 400,
                'received_params' => array_keys($params),
                'session_id' => $session_id,
                'product_id' => $product_id,
                'has_customization_data' => !empty($customization_data)
            ]);
        }
        
        // Ensure customization_data is an array for JSON encoding
        if (is_string($customization_data)) {
            $decoded = json_decode($customization_data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $customization_data = $decoded;
            }
        }
        
        // Normalize letter color keys to ensure both letter_color and letterColor exist
        if (is_array($customization_data)) {
            $letter_color_value = $customization_data['letter_color'] ?? $customization_data['letterColor'] ?? 'white';
            $customization_data['letter_color'] = $letter_color_value;
            $customization_data['letterColor'] = $letter_color_value;
            
            // Also normalize charm keys
            $charms_value = $customization_data['selected_charms'] ?? $customization_data['selectedCharms'] ?? [];
            $customization_data['selected_charms'] = $charms_value;
            $customization_data['selectedCharms'] = $charms_value;
        }
        
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        
        // Create table if it doesn't exist
        $this->ensure_customization_table_exists();
        
        $result = $wpdb->replace($table_name, [
            'session_id' => sanitize_text_field($session_id),
            'product_id' => sanitize_text_field($product_id),
            'customization_data' => wp_json_encode($customization_data),
            'created_at' => current_time('mysql')
        ]);
        
        if ($result === false) {
            return new WP_Error('save_failed', 'Failed to save customization: ' . $wpdb->last_error, ['status' => 500]);
        }
        
        // Get the actual ID from the database
        $saved_id = $wpdb->insert_id;
        
        // If insert_id is 0 (which happens with REPLACE on existing records), 
        // find the record by session_id
        if (!$saved_id) {
            $saved_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE session_id = %s ORDER BY created_at DESC LIMIT 1",
                $session_id
            ));
        }
        
        // Final fallback - use session_id if we still don't have a numeric ID
        $final_id = $saved_id ? $saved_id : $session_id;
        
        return rest_ensure_response([
            'success' => true,
            'session_id' => $session_id,
            'id' => $final_id
        ]);
    }
    
    /**
     * Ensure customization table exists
     */
    private function ensure_customization_table_exists() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            product_id varchar(255) NOT NULL,
            customization_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Upload preview image from React app
     */
    public function upload_preview_image($request) {
        error_log('Upload preview image called');
        
        $customization_id = $request->get_param('customization_id');
        $image_data = $request->get_param('image_data'); // Base64 image data
        
        error_log('Customization ID: ' . $customization_id);
        error_log('Image data length: ' . strlen($image_data ?? ''));
        
        if (!$customization_id || !$image_data) {
            error_log('Missing customization_id or image_data');
            return new WP_Error('missing_data', 'Missing customization_id or image_data', ['status' => 400]);
        }
        
        // Decode base64 image
        $image_parts = explode(';base64,', $image_data);
        if (count($image_parts) < 2) {
            error_log('Invalid image data format');
            return new WP_Error('invalid_image', 'Invalid image data format', ['status' => 400]);
        }
        
        $image_base64 = base64_decode($image_parts[1]);
        if (!$image_base64) {
            error_log('Failed to decode image data');
            return new WP_Error('decode_failed', 'Failed to decode image data', ['status' => 400]);
        }
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $custom_images_dir = $upload_dir['basedir'] . '/bracelet-customizations/';
        $custom_images_url = $upload_dir['baseurl'] . '/bracelet-customizations/';
        
        if (!file_exists($custom_images_dir)) {
            if (!wp_mkdir_p($custom_images_dir)) {
                error_log('Failed to create directory: ' . $custom_images_dir);
                return new WP_Error('dir_failed', 'Failed to create upload directory', ['status' => 500]);
            }
        }
        
        // Save image with timestamp to avoid conflicts
        $timestamp = time();
        $image_filename = 'preview_' . $customization_id . '_' . $timestamp . '.png';
        $image_path = $custom_images_dir . $image_filename;
        $image_url = $custom_images_url . $image_filename;
        
        error_log('Saving image to: ' . $image_path);
        error_log('Image URL will be: ' . $image_url);
        
        $result = file_put_contents($image_path, $image_base64);
        if (!$result) {
            error_log('Failed to save image to filesystem');
            return new WP_Error('save_failed', 'Failed to save image', ['status' => 500]);
        }
        
        // Verify the image was saved correctly
        if (!file_exists($image_path)) {
            error_log('Image file does not exist after save');
            return new WP_Error('verify_failed', 'Image file verification failed', ['status' => 500]);
        }
        
        error_log('Image uploaded successfully: ' . $image_url);
        
        return rest_ensure_response([
            'success' => true,
            'image_url' => $image_url,
            'customization_id' => $customization_id,
            'file_size' => filesize($image_path)
        ]);
    }
    
    /**
     * AJAX handler for screenshot upload
     */
    public function ajax_upload_preview_image() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bracelet_customizer_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed'], 403);
            return;
        }
        
        // Handle file upload from FormData
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $customization_id = sanitize_text_field($_POST['customization_id']);
            
            if (!$customization_id) {
                wp_send_json_error(['message' => 'Missing customization_id'], 400);
                return;
            }
            
            // Convert uploaded file to base64 data URL
            $file_content = file_get_contents($_FILES['image']['tmp_name']);
            $base64_data = 'data:image/png;base64,' . base64_encode($file_content);
            
            // Use the same logic as the REST method
            $image_parts = explode(';base64,', $base64_data);
            if (count($image_parts) < 2) {
                wp_send_json_error(['message' => 'Invalid image data format'], 400);
                return;
            }
            
            $image_base64 = base64_decode($image_parts[1]);
            if (!$image_base64) {
                wp_send_json_error(['message' => 'Failed to decode image data'], 400);
                return;
            }
            
            // Create upload directory
            $upload_dir = wp_upload_dir();
            $custom_images_dir = $upload_dir['basedir'] . '/bracelet-customizations/';
            $custom_images_url = $upload_dir['baseurl'] . '/bracelet-customizations/';
            
            if (!file_exists($custom_images_dir)) {
                if (!wp_mkdir_p($custom_images_dir)) {
                    wp_send_json_error(['message' => 'Failed to create upload directory'], 500);
                    return;
                }
            }
            
            // Save image with timestamp to avoid conflicts
            $timestamp = time();
            $image_filename = 'preview_' . $customization_id . '_' . $timestamp . '.png';
            $image_path = $custom_images_dir . $image_filename;
            $image_url = $custom_images_url . $image_filename;
            
            $result = file_put_contents($image_path, $image_base64);
            if (!$result) {
                wp_send_json_error(['message' => 'Failed to save image'], 500);
                return;
            }
            
            // Verify the image was saved correctly
            if (!file_exists($image_path)) {
                wp_send_json_error(['message' => 'Image file verification failed'], 500);
                return;
            }
            
            wp_send_json_success([
                'image_url' => $image_url,
                'customization_id' => $customization_id,
                'file_size' => filesize($image_path)
            ]);
            
        } else {
            wp_send_json_error(['message' => 'No file uploaded or upload error'], 400);
        }
    }
    
    /**
     * Helper method to get product category for charm position logic
     */
    private function get_product_category($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return 'Standard';
        }
        
        $product_type = $product->get_type();
        
        switch ($product_type) {
            case 'bracelet_collabs':
                $category = 'Collabs';
                break;
            case 'tiny_words':
                $category = 'Tiny Words';
                break;
            case 'bracelet_no_words':
                $category = 'No Words';
                break;
            case 'standard_bracelet':
                $category = 'Standard';
                break;
            default:
                // Fallback: convert underscores to spaces and title case
                $category = ucwords(str_replace(['_', '-'], ' ', $product_type));
                break;
        }

        return $category;
    }
    
    /**
     * Check permission for protected endpoints
     */
    public function check_permission($request) {
        return true; // For now, allow all requests
    }
}