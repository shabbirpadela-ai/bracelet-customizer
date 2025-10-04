<?php
/**
 * Page Template Class
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle custom page template for bracelet customizer
 */
class Bracelet_Customizer_Page_Template {
    
    /**
     * Initialize page template functionality
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Add custom page template
        add_filter('theme_page_templates', [$this, 'add_page_template']);
        add_filter('page_template', [$this, 'load_page_template']);
        
        // Add template option to page editor
        add_filter('template_include', [$this, 'include_page_template']);
        
        // Block theme compatibility
        add_filter('wp_insert_post_data', [$this, 'override_template_for_block_themes'], 10, 2);
        add_filter('get_page_template', [$this, 'get_page_template_for_block_themes'], 10, 3);
        
        // Add meta box for template selection (if needed)
        add_action('add_meta_boxes', [$this, 'add_template_meta_box']);
        add_action('save_post', [$this, 'save_template_meta']);
        
        // Add body class and admin bar handling
        add_filter('body_class', [$this, 'add_body_class']);
        add_action('wp', [$this, 'maybe_remove_admin_bar']);
    }
    
    /**
     * Add custom page template to the list
     */
    public function add_page_template($templates) {
        $templates['page-bracelet-customizer.php'] = __('Bracelet Customizer Full Canvas', 'bracelet-customizer');
        return $templates;
    }
    
    /**
     * Load the custom page template
     */
    public function load_page_template($template) {
        global $post;
        
        if (!$post) {
            return $template;
        }
        
        // Check if this page should use our custom template
        $page_template = get_post_meta($post->ID, '_wp_page_template', true);
        
        if ($page_template === 'page-bracelet-customizer.php') {
            $plugin_template = BRACELET_CUSTOMIZER_PLUGIN_PATH . 'templates/page-bracelet-customizer.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Also check if this page contains the bracelet customizer shortcode
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'bracelet_customizer')) {
            // Check if it's the designated customizer page from settings
            $settings = get_option('bracelet_customizer_settings', []);
            $customizer_page_id = isset($settings['ui_settings']['customizer_page_id']) ? 
                                  $settings['ui_settings']['customizer_page_id'] : null;
            
            if ($customizer_page_id && $post->ID == $customizer_page_id) {
                $plugin_template = BRACELET_CUSTOMIZER_PLUGIN_PATH . 'templates/page-bracelet-customizer.php';
                
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Include the page template
     */
    public function include_page_template($template) {
        global $post;
        
        if (!$post) {
            return $template;
        }
        
        // Check if this is our customizer page
        $page_template = get_post_meta($post->ID, '_wp_page_template', true);
        
        if ($page_template === 'page-bracelet-customizer.php') {
            $plugin_template = BRACELET_CUSTOMIZER_PLUGIN_PATH . 'templates/page-bracelet-customizer.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Add meta box for template selection
     */
    public function add_template_meta_box() {
        add_meta_box(
            'bracelet-customizer-template',
            __('Bracelet Customizer Template', 'bracelet-customizer'),
            [$this, 'template_meta_box_callback'],
            'page',
            'side',
            'default'
        );
    }
    
    /**
     * Meta box callback for template selection
     */
    public function template_meta_box_callback($post) {
        $current_template = get_post_meta($post->ID, '_wp_page_template', true);
        
        echo '<p>' . __('For the best customizer experience, select the Full Canvas template.', 'bracelet-customizer') . '</p>';
        
        if ($current_template === 'page-bracelet-customizer.php') {
            echo '<p style="color: #46b450; font-weight: 600;">✓ ' . __('Full Canvas template is selected', 'bracelet-customizer') . '</p>';
        } else {
            echo '<p style="color: #dc3232;">' . __('Consider using the Full Canvas template for better user experience.', 'bracelet-customizer') . '</p>';
        }
    }
    
    /**
     * Save template meta
     */
    public function save_template_meta($post_id) {
        // This is handled by WordPress core, but we can add custom logic here if needed
    }
    
    /**
     * Create bracelet customizer page on plugin activation
     */
    public static function create_customizer_page() {
        // Check if page already exists
        $existing_page = get_option('bracelet_customizer_page_id');
        
        if ($existing_page && get_post($existing_page)) {
            // Page already exists
            return $existing_page;
        }
        
        // Create new page
        $page_data = [
            'post_title' => __('Bracelet Customizer', 'bracelet-customizer'),
            'post_content' => '[bracelet_customizer]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        ];
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            // Set the custom template
            update_post_meta($page_id, '_wp_page_template', 'page-bracelet-customizer.php');
            
            // Store page ID for plugin settings
            update_option('bracelet_customizer_page_id', $page_id);
            
            // Update plugin settings to use this page
            $settings = get_option('bracelet_customizer_settings', []);
            if (!isset($settings['ui_settings'])) {
                $settings['ui_settings'] = [];
            }
            $settings['ui_settings']['customizer_page_id'] = $page_id;
            update_option('bracelet_customizer_settings', $settings);
            
            return $page_id;
        }
        
        return false;
    }
    
    /**
     * Get the customizer page URL
     */
    public static function get_customizer_page_url() {
        $page_id = get_option('bracelet_customizer_page_id');
        
        if ($page_id && get_post($page_id)) {
            return get_permalink($page_id);
        }
        
        return false;
    }
    
    /**
     * Check if a page is using the customizer template
     */
    public static function is_customizer_page($page_id = null) {
        if (!$page_id) {
            global $post;
            $page_id = $post ? $post->ID : null;
        }
        
        if (!$page_id) {
            return false;
        }
        
        $template = get_post_meta($page_id, '_wp_page_template', true);
        return $template === 'page-bracelet-customizer.php';
    }
    
    /**
     * Add body class for customizer pages
     */
    public function add_body_class($classes) {
        global $post;
        
        if ($post && self::is_customizer_page($post->ID)) {
            $classes[] = 'bracelet-customizer-page';
            $classes[] = 'full-canvas';
        }
        
        return $classes;
    }
    
    /**
     * Remove admin bar on customizer pages for all users
     */
    public function maybe_remove_admin_bar() {
        global $post;
        
        if ($post && self::is_customizer_page($post->ID)) {
            // Force remove admin bar for ALL users on customizer pages
            show_admin_bar(false);
            
            // Add additional CSS to remove admin bar margin
            add_action('wp_head', function() {
                echo '<style>
                    html { margin-top: 0 !important; }
                    body { margin-top: 0 !important; padding-top: 0 !important; }
                    body.admin-bar { margin-top: 0 !important; padding-top: 0 !important; }
                    .admin-bar #wpadminbar { display: none !important; }
                </style>';
            }, 999);
            
            // Remove admin bar class from body
            add_filter('body_class', function($classes) {
                return array_diff($classes, ['admin-bar']);
            });
        }
    }
    
    /**
     * Override template for block themes
     */
    public function override_template_for_block_themes($data, $postarr) {
        // Only process pages
        if (isset($data['post_type']) && $data['post_type'] === 'page') {
            // Check if this is our customizer page
            if (isset($postarr['ID'])) {
                $template = get_post_meta($postarr['ID'], '_wp_page_template', true);
                if ($template === 'page-bracelet-customizer.php') {
                    // Force full-width for block themes
                    $data['post_excerpt'] = 'full-width';
                }
            }
        }
        return $data;
    }
    
    /**
     * Get page template for block themes
     */
    public function get_page_template_for_block_themes($template, $post, $page_template) {
        if ($page_template === 'page-bracelet-customizer.php') {
            $plugin_template = BRACELET_CUSTOMIZER_PLUGIN_PATH . 'templates/page-bracelet-customizer.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }
}

// Initialize page template functionality
add_action('init', function() {
    new Bracelet_Customizer_Page_Template();
});