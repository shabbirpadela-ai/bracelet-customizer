<?php
/**
 * Settings Class
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle plugin settings and configuration
 */
class Bracelet_Customizer_Settings {
    
    /**
     * Settings option name
     *
     * @var string
     */
    private $option_name = 'bracelet_customizer_settings';
    
    /**
     * Settings page slug
     *
     * @var string
     */
    private $page_slug = 'bracelet-customizer-settings';
    
    /**
     * Default settings
     *
     * @var array
     */
    private $defaults = [];
    
    /**
     * Initialize settings
     */
    public function __construct() {
        $this->set_defaults();
        $this->init_hooks();
    }
    
    /**
     * Set default settings
     */
    private function set_defaults() {
        $this->defaults = [
            'max_word_length' => 13,
            'min_word_length' => 2,
            'allowed_characters' => 'a-zA-Z0-9:)\<3!#&:\s',
            'button_colors' => [
                'primary' => '#4F46E5',
                'secondary' => '#FFB6C1',
                'accent' => '#10B981'
            ],
            'button_labels' => [
                'next' => __('NEXT', 'bracelet-customizer'),
                'review' => __('REVIEW', 'bracelet-customizer'),
                'add_to_cart' => __('ADD TO CART', 'bracelet-customizer'),
                'customize' => __('Customize This Bracelet', 'bracelet-customizer'),
                'back' => __('BACK', 'bracelet-customizer')
            ],
            'letter_colors' => [
                'white' => [
                    'name' => __('White', 'bracelet-customizer'),
                    'price' => 0,
                    'enabled' => true
                ],
                'pink' => [
                    'name' => __('Pink', 'bracelet-customizer'),
                    'price' => 0,
                    'enabled' => true
                ],
                'black' => [
                    'name' => __('Black', 'bracelet-customizer'),
                    'price' => 0,
                    'enabled' => true
                ],
                'gold' => [
                    'name' => __('Gold', 'bracelet-customizer'),
                    'price' => 15,
                    'enabled' => true
                ]
            ],
            'trending_words' => [
                'LET THEM',
                'STRENGTH',
                'YOU GOT THIS',
                'BLESSED',
                'FEARLESS',
                'WARRIOR'
            ],
            'charm_categories' => [
                'All',
                'Bestsellers',
                'New Drops & Favs',
                'Personalize it'
            ],
            'bracelet_categories' => [
                'All',
                'Standard',
                'Collabs',
                'Limited Edition',
                'Engraving',
                'Tiny Words'
            ],
            'ui_settings' => [
                'customizer_page_id' => 0,
                'enable_animations' => true
            ],
            'enable_modal' => false,
            'api_settings' => [
                'enable_rest_api' => true,
                'enable_caching' => true,
                'cache_duration' => 3600,
                'enable_cors' => false
            ],
            'advanced_settings' => [
                'enable_debug' => false,
                'enable_analytics' => true,
                'enable_error_logging' => true,
                'cleanup_on_uninstall' => false
            ]
        ];
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_filter('wp_redirect', [$this, 'preserve_tab_on_redirect'], 10, 2);
        add_action('wp_ajax_bracelet_customizer_reset_settings', [$this, 'reset_settings']);
        add_action('wp_ajax_bracelet_customizer_export_settings', [$this, 'export_settings']);
        add_action('wp_ajax_bracelet_customizer_import_settings', [$this, 'import_settings']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add main menu under WooCommerce
        add_submenu_page(
            'woocommerce',
            __('Bracelet Customizer', 'bracelet-customizer'),
            __('Bracelet Customizer', 'bracelet-customizer'),
            'manage_woocommerce',
            $this->page_slug,
            [$this, 'render_settings_page']
        );
        
        // Add direct menu for easier access
        add_menu_page(
            __('Bracelet Customizer', 'bracelet-customizer'),
            __('Bracelet Customizer', 'bracelet-customizer'),
            'manage_woocommerce',
            $this->page_slug,
            [$this, 'render_settings_page'],
            'dashicons-admin-customizer',
            30
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'bracelet_customizer_settings_group',
            $this->option_name,
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->defaults
            ]
        );
        
        // Register sections for each tab with unique page identifiers
        $this->register_settings_sections();
        
        // Add settings fields for each tab
        $this->add_all_settings_fields();
    }
    
    /**
     * Register settings sections for all tabs
     */
    private function register_settings_sections() {
        $sections = [
            'styling' => [
                'id' => 'styling_options',
                'title' => __('Styling Options', 'bracelet-customizer'),
                'page' => $this->page_slug . '_styling'
            ],
            'features' => [
                'id' => 'feature_configuration', 
                'title' => __('Feature Configuration', 'bracelet-customizer'),
                'page' => $this->page_slug . '_features'
            ],
            'letter-colors' => [
                'id' => 'letter_colors',
                'title' => __('Letter Colors', 'bracelet-customizer'),
                'page' => $this->page_slug . '_letter_colors'
            ],
            'interface' => [
                'id' => 'ui_settings',
                'title' => __('User Interface', 'bracelet-customizer'),
                'page' => $this->page_slug . '_interface'
            ],
            'api' => [
                'id' => 'api_settings',
                'title' => __('API Settings', 'bracelet-customizer'),
                'page' => $this->page_slug . '_api'
            ],
            'advanced' => [
                'id' => 'advanced_settings',
                'title' => __('Advanced Settings', 'bracelet-customizer'),
                'page' => $this->page_slug . '_advanced'
            ]
        ];
        
        foreach ($sections as $section) {
            add_settings_section(
                $section['id'],
                $section['title'],
                [$this, 'render_section_description'],
                $section['page']
            );
        }
    }
    
    /**
     * Add all settings fields for all tabs
     */
    private function add_all_settings_fields() {
        // Feature Configuration Fields
        add_settings_field(
            'max_word_length',
            __('Maximum Word Length', 'bracelet-customizer'),
            [$this, 'render_number_field'],
            $this->page_slug . '_features',
            'feature_configuration',
            [
                'field' => 'max_word_length',
                'label' => __('Maximum Word Length', 'bracelet-customizer'),
                'min' => 1,
                'max' => 20,
                'description' => __('Maximum number of characters allowed in custom words.', 'bracelet-customizer')
            ]
        );
        
        add_settings_field(
            'min_word_length',
            __('Minimum Word Length', 'bracelet-customizer'),
            [$this, 'render_number_field'],
            $this->page_slug . '_features',
            'feature_configuration',
            [
                'field' => 'min_word_length',
                'label' => __('Minimum Word Length', 'bracelet-customizer'),
                'min' => 1,
                'max' => 10,
                'description' => __('Minimum number of characters required in custom words.', 'bracelet-customizer')
            ]
        );
        
        add_settings_field(
            'allowed_characters',
            __('Allowed Characters', 'bracelet-customizer'),
            [$this, 'render_text_field'],
            $this->page_slug . '_features',
            'feature_configuration',
            [
                'field' => 'allowed_characters',
                'description' => __('Regular expression pattern for allowed characters.', 'bracelet-customizer'),
                'class' => 'regular-text'
            ]
        );
        
        // Styling Options Fields
        add_settings_field(
            'primary_color',
            __('Primary Button Color', 'bracelet-customizer'),
            [$this, 'render_color_field'],
            $this->page_slug . '_styling',
            'styling_options',
            [
                'field' => 'button_colors.primary',
                'description' => __('Primary button background color.', 'bracelet-customizer')
            ]
        );
        
        add_settings_field(
            'secondary_color',
            __('Secondary Button Color', 'bracelet-customizer'),
            [$this, 'render_color_field'],
            $this->page_slug . '_styling',
            'styling_options',
            [
                'field' => 'button_colors.secondary',
                'description' => __('Secondary button background color.', 'bracelet-customizer')
            ]
        );
        
        // Button Labels
        add_settings_field(
            'customize_button_text',
            __('Customize Button Text', 'bracelet-customizer'),
            [$this, 'render_text_field'],
            $this->page_slug . '_styling',
            'styling_options',
            [
                'field' => 'button_labels.customize',
                'label' => __('Customize Button Text', 'bracelet-customizer'),
                'description' => __('Text for the main customize button on product pages.', 'bracelet-customizer')
            ]
        );
        
        // Letter Colors Configuration
        add_settings_field(
            'letter_colors_config',
            __('Letter Color Options', 'bracelet-customizer'),
            [$this, 'render_letter_colors_field'],
            $this->page_slug . '_letter_colors',
            'letter_colors',
            [
                'field' => 'letter_colors',
                'description' => __('Configure available letter colors and pricing.', 'bracelet-customizer')
            ]
        );
        
        // UI Settings
        add_settings_field(
            'customizer_page',
            __('Customizer Page', 'bracelet-customizer'),
            [$this, 'render_page_select_field'],
            $this->page_slug . '_interface',
            'ui_settings',
            [
                'field' => 'ui_settings.customizer_page_id',
                'description' => __('Select the page where the bracelet customizer will be displayed. Add [bracelet_customizer] shortcode to this page.<br><strong>Use canvas page template for better result</strong> - Select "Bracelet Customizer Full Canvas" template in the page editor for optimal experience.', 'bracelet-customizer')
            ]
        );
        
        add_settings_field(
            'enable_animations',
            __('Enable Animations', 'bracelet-customizer'),
            [$this, 'render_checkbox_field'],
            $this->page_slug . '_interface',
            'ui_settings',
            [
                'field' => 'ui_settings.enable_animations',
                'description' => __('Enable smooth animations and transitions.', 'bracelet-customizer')
            ]
        );
        
        add_settings_field(
            'enable_modal',
            __('Use Modal Mode', 'bracelet-customizer'),
            [$this, 'render_checkbox_field'],
            $this->page_slug . '_interface',
            'ui_settings',
            [
                'field' => 'enable_modal',
                'description' => __('Open customizer in a modal popup instead of redirecting to a separate page. <strong>Recommended:</strong> Leave unchecked for full-page customizer experience.', 'bracelet-customizer')
            ]
        );
        
        
        // API Settings
        add_settings_field(
            'enable_rest_api',
            __('Enable REST API', 'bracelet-customizer'),
            [$this, 'render_checkbox_field'],
            $this->page_slug . '_api',
            'api_settings',
            [
                'field' => 'api_settings.enable_rest_api',
                'label' => __('Enable REST API', 'bracelet-customizer'),
                'description' => __('Enable REST API endpoints for the customizer.', 'bracelet-customizer')
            ]
        );
        
        add_settings_field(
            'enable_caching',
            __('Enable Caching', 'bracelet-customizer'),
            [$this, 'render_checkbox_field'],
            $this->page_slug . '_api',
            'api_settings',
            [
                'field' => 'api_settings.enable_caching',
                'label' => __('Enable Caching', 'bracelet-customizer'),
                'description' => __('Cache API responses for better performance.', 'bracelet-customizer')
            ]
        );
        
        // Advanced Settings
        add_settings_field(
            'enable_debug',
            __('Enable Debug Mode', 'bracelet-customizer'),
            [$this, 'render_checkbox_field'],
            $this->page_slug . '_advanced',
            'advanced_settings',
            [
                'field' => 'advanced_settings.enable_debug',
                'label' => __('Enable Debug Mode', 'bracelet-customizer'),
                'description' => __('Enable debug logging and console output.', 'bracelet-customizer')
            ]
        );
        
        add_settings_field(
            'cleanup_on_uninstall',
            __('Cleanup on Uninstall', 'bracelet-customizer'),
            [$this, 'render_checkbox_field'],
            $this->page_slug . '_advanced',
            'advanced_settings',
            [
                'field' => 'advanced_settings.cleanup_on_uninstall',
                'label' => __('Cleanup on Uninstall', 'bracelet-customizer'),
                'description' => __('Remove all plugin data when uninstalling.', 'bracelet-customizer')
            ]
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bracelet-customizer'));
        }
        
        // Get current tab from URL parameter
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'styling';
        
        // Handle welcome message
        $show_welcome = isset($_GET['welcome']) && $_GET['welcome'] === '1';
        
        // Define available tabs
        $tabs = [
            'styling' => __('Styling', 'bracelet-customizer'),
            'features' => __('Features', 'bracelet-customizer'),
            'letter-colors' => __('Letter Colors', 'bracelet-customizer'),
            'interface' => __('Interface', 'bracelet-customizer'),
            'api' => __('API', 'bracelet-customizer'),
            'advanced' => __('Advanced', 'bracelet-customizer')
        ];
        
        ?>
        <div class="wrap">
            <h1><?php _e('Bracelet Customizer Settings', 'bracelet-customizer'); ?></h1>
            
            <?php if ($show_welcome): ?>
                <div class="notice notice-success is-dismissible">
                    <h3><?php _e('Welcome to Bracelet Customizer!', 'bracelet-customizer'); ?></h3>
                    <p><?php _e('Thank you for installing Bracelet Customizer. The plugin has been activated and sample products have been created.', 'bracelet-customizer'); ?></p>
                    <p>
                        <strong><?php _e('Next steps:', 'bracelet-customizer'); ?></strong>
                    </p>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li><?php _e('Configure your settings below', 'bracelet-customizer'); ?></li>
                        <li><?php _e('Build the React app: Run "npm run build" in the bracelet-customizer directory', 'bracelet-customizer'); ?></li>
                        <li><?php _e('Add the [bracelet_customizer] shortcode to any page', 'bracelet-customizer'); ?></li>
                        <li><?php _e('Create your bracelet and charm products', 'bracelet-customizer'); ?></li>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php settings_errors(); ?>
            
            <!-- WordPress Standard Tab Navigation -->
            <h2 class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab_key => $tab_name): ?>
                    <?php 
                    $tab_url = add_query_arg(['page' => $this->page_slug, 'tab' => $tab_key], admin_url('admin.php'));
                    $active_class = ($current_tab === $tab_key) ? ' nav-tab-active' : '';
                    ?>
                    <a href="<?php echo esc_url($tab_url); ?>" class="nav-tab<?php echo $active_class; ?>">
                        <?php echo esc_html($tab_name); ?>
                    </a>
                <?php endforeach; ?>
            </h2>
            
            <form method="post" action="options.php">
                <?php 
                settings_fields('bracelet_customizer_settings_group'); 
                
                // Add hidden field to preserve current tab
                echo '<input type="hidden" name="bracelet_customizer_current_tab" value="' . esc_attr($current_tab) . '" />';
                ?>
                
                <!-- Single Tab Content Area Based on Current Tab -->
                <?php $this->render_current_tab_content($current_tab); ?>
                
                <?php submit_button(__('Save Settings', 'bracelet-customizer')); ?>
                <div class="bracelet-customizer-settings-actions" style="padding:20px;border-top:1px solid #c3c4c7;background:#f6f7f7;display:flex;gap:8px;align-items:center;">
                    <a
                        class="button button-secondary"
                        id="export-settings"
                        href="#"
                    >
                        <?php esc_html_e('Export Settings', 'bracelet-customizer'); ?>
                    </a>

                    <button type="button" class="button button-secondary" id="import-settings">
                        <?php esc_html_e('Import Settings', 'bracelet-customizer'); ?>
                    </button>

                    <button type="button" class="button button-link-delete" id="reset-settings">
                        <?php esc_html_e('Reset to Defaults', 'bracelet-customizer'); ?>
                    </button>
                </div>

                </form>
                
                <input type="file" id="import-file" accept=".json" style="display: none;">
            </div>
        </div>
        <?php
    }
    
    /**
     * Preserve tab parameter on redirect after form submission
     */
    public function preserve_tab_on_redirect($location, $status) {
        // Check if this is a redirect after settings update
        if (strpos($location, 'page=' . $this->page_slug) !== false && 
            strpos($location, 'settings-updated=true') !== false) {
            
            // Get the current tab from POST data
            $current_tab = isset($_POST['bracelet_customizer_current_tab']) ? 
                          sanitize_text_field($_POST['bracelet_customizer_current_tab']) : 'styling';
            
            // Add tab parameter to redirect URL
            $location = add_query_arg('tab', $current_tab, $location);
        }
        
        return $location;
    }
    
    /**
     * Render current tab content
     */
    private function render_current_tab_content($current_tab) {
        $tab_config = [
            'styling' => [
                'title' => __('Styling Options', 'bracelet-customizer'),
                'description' => __('Customize the appearance of buttons and interface elements.', 'bracelet-customizer'),
                'page' => $this->page_slug . '_styling',
                'section' => 'styling_options'
            ],
            'features' => [
                'title' => __('Feature Configuration', 'bracelet-customizer'),
                'description' => __('Configure customizer features and limitations.', 'bracelet-customizer'),
                'page' => $this->page_slug . '_features',
                'section' => 'feature_configuration'
            ],
            'letter-colors' => [
                'title' => __('Letter Colors', 'bracelet-customizer'),
                'description' => __('Manage available letter colors and pricing.', 'bracelet-customizer'),
                'page' => $this->page_slug . '_letter_colors',
                'section' => 'letter_colors'
            ],
            'interface' => [
                'title' => __('User Interface', 'bracelet-customizer'),
                'description' => __('Configure user interface behavior and appearance.', 'bracelet-customizer'),
                'page' => $this->page_slug . '_interface',
                'section' => 'ui_settings'
            ],
            'api' => [
                'title' => __('API Settings', 'bracelet-customizer'),
                'description' => __('Configure REST API and performance settings.', 'bracelet-customizer'),
                'page' => $this->page_slug . '_api',
                'section' => 'api_settings'
            ],
            'advanced' => [
                'title' => __('Advanced Settings', 'bracelet-customizer'),
                'description' => __('Advanced configuration options.', 'bracelet-customizer'),
                'page' => $this->page_slug . '_advanced',
                'section' => 'advanced_settings'
            ]
        ];
        
        // Default to styling if invalid tab
        if (!isset($tab_config[$current_tab])) {
            $current_tab = 'styling';
        }
        
        $config = $tab_config[$current_tab];
        $this->render_tab_content($config['title'], $config['description'], $config['page'], $config['section']);
    }
    
    /**
     * Render individual tab content
     */
    private function render_tab_content($title, $description, $page, $section_id) {
        ?>
        <div class="bracelet-customizer-tab-content">
            <h3><?php echo esc_html($title); ?></h3>
            <p><?php echo esc_html($description); ?></p>
            <table class="form-table" role="presentation">
                <tbody>
                    <?php do_settings_fields($page, $section_id); ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render section description
     */
    public function render_section_description($args) {
        $descriptions = [
            'styling_options' => __('Customize the appearance of buttons and interface elements.', 'bracelet-customizer'),
            'feature_configuration' => __('Configure customizer features and limitations.', 'bracelet-customizer'),
            'letter_colors' => __('Manage available letter colors and pricing.', 'bracelet-customizer'),
            'ui_settings' => __('Configure user interface behavior and appearance.', 'bracelet-customizer'),
            'api_settings' => __('Configure REST API and performance settings.', 'bracelet-customizer'),
            'advanced_settings' => __('Advanced configuration options.', 'bracelet-customizer')
        ];
        
        if (isset($descriptions[$args['id']])) {
            echo '<p>' . esc_html($descriptions[$args['id']]) . '</p>';
        }
    }
    
    /**
     * Render text field
     */
    public function render_text_field($args) {
        $field = $args['field'];
        $value = $this->get_setting($field);
        $class = isset($args['class']) ? $args['class'] : 'regular-text';
        
        echo '<input type="text" id="' . esc_attr($field) . '" name="' . esc_attr($this->get_field_name($field)) . '" value="' . esc_attr($value) . '" class="' . esc_attr($class) . '" />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
        }
    }
    
    /**
     * Render number field
     */
    public function render_number_field($args) {
        $field = $args['field'];
        $value = $this->get_setting($field);
        $min = isset($args['min']) ? $args['min'] : '';
        $max = isset($args['max']) ? $args['max'] : '';
        
        echo '<input type="number" id="' . esc_attr($field) . '" name="' . esc_attr($this->get_field_name($field)) . '" value="' . esc_attr($value) . '" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" class="small-text" />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
        }
    }
    
    /**
     * Render select field
     */
    public function render_select_field($args) {
        $field = $args['field'];
        $value = $this->get_setting($field);
        $options = $args['options'];
        
        echo '<select id="' . esc_attr($field) . '" name="' . esc_attr($this->get_field_name($field)) . '">';
        foreach ($options as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
        }
        echo '</select>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
        }
    }
    
    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args) {
        $field = $args['field'];
        $value = $this->get_setting($field);
        
        echo '<label for="' . esc_attr($field) . '">';
        echo '<input type="checkbox" id="' . esc_attr($field) . '" name="' . esc_attr($this->get_field_name($field)) . '" value="1" ' . checked($value, true, false) . ' />';
        
        if (isset($args['description'])) {
            echo ' ' . wp_kses_post($args['description']);
        }
        echo '</label>';
    }
    
    /**
     * Render color field
     */
    public function render_color_field($args) {
        $field = $args['field'];
        $value = $this->get_setting($field);
        
        echo '<input type="color" id="' . esc_attr($field) . '" name="' . esc_attr($this->get_field_name($field)) . '" value="' . esc_attr($value) . '" class="color-picker" />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
        }
    }
    
    /**
     * Render page select field
     */
    public function render_page_select_field($args) {
        $field = $args['field'];
        $value = $this->get_setting($field);
        
        $pages = get_pages([
            'sort_column' => 'post_title',
            'sort_order' => 'ASC'
        ]);
        
        echo '<select id="' . esc_attr($field) . '" name="' . esc_attr($this->get_field_name($field)) . '">';
        echo '<option value="">' . __('Select a page...', 'bracelet-customizer') . '</option>';
        
        foreach ($pages as $page) {
            echo '<option value="' . esc_attr($page->ID) . '" ' . selected($value, $page->ID, false) . '>' . esc_html($page->post_title) . '</option>';
        }
        
        echo '</select>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
        }
        
    }
    
    /**
     * Render letter colors field
     */
    public function render_letter_colors_field($args) {
        $colors = $this->get_setting('letter_colors');
        
        echo '<div class="letter-colors-config">';
        
        foreach ($colors as $color_id => $color_data) {
            echo '<div class="letter-color-item" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">';
            
            echo '<h4 style="margin-top: 0;">' . esc_html(ucfirst($color_id)) . '</h4>';
            
            // Enabled checkbox
            echo '<label>';
            echo '<input type="checkbox" name="' . esc_attr($this->option_name . '[letter_colors][' . $color_id . '][enabled]') . '" value="1" ' . checked($color_data['enabled'], true, false) . ' />';
            echo ' ' . __('Enabled', 'bracelet-customizer');
            echo '</label><br><br>';
            
            // Name field
            echo '<label>' . __('Display Name:', 'bracelet-customizer') . '</label><br>';
            echo '<input type="text" name="' . esc_attr($this->option_name . '[letter_colors][' . $color_id . '][name]') . '" value="' . esc_attr($color_data['name']) . '" class="regular-text" /><br><br>';
            
            // Price field
            echo '<label>' . __('Additional Price:', 'bracelet-customizer') . '</label><br>';
            echo '<input type="number" name="' . esc_attr($this->option_name . '[letter_colors][' . $color_id . '][price]') . '" value="' . esc_attr($color_data['price']) . '" min="0" step="0.01" class="small-text" /> ' . get_woocommerce_currency_symbol() . '<br><br>';
            
            // Color picker field
            echo '<label>' . __('Color:', 'bracelet-customizer') . '</label><br>';
            $color_value = isset($color_data['color']) ? $color_data['color'] : '#ffffff';
            echo '<input type="text" name="' . esc_attr($this->option_name . '[letter_colors][' . $color_id . '][color]') . '" value="' . esc_attr($color_value) . '" class="color-picker-field" data-default-color="' . esc_attr($color_value) . '" />';
            
            echo '</div>';
        }
        
        echo '</div>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, $this->page_slug) === false) {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Add admin CSS
        wp_add_inline_style('wp-color-picker', '
            .bracelet-customizer-settings-wrapper {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                margin-top: 20px;
            }
            
            .bracelet-customizer-settings-nav {
                border-bottom: 1px solid #c3c4c7;
                background: #f6f7f7;
                padding: 0;
                margin: 0;
            }
            
            .bracelet-customizer-settings-nav .nav-tab-wrapper {
                border-bottom: none;
                margin: 0;
                padding: 12px 20px 0;
            }
            
            .bracelet-customizer-settings-nav .nav-tab {
                border-bottom: 1px solid transparent;
                margin-bottom: -1px;
            }
            
            .bracelet-customizer-settings-nav .nav-tab.nav-tab-active {
                border-bottom: 1px solid #fff;
                background: #fff;
            }
            
            .settings-tab-content {
                padding: 20px;
            }
            
            .settings-tab-content h3 {
                margin-top: 0;
                margin-bottom: 10px;
                color: #1d2327;
            }
            
            .settings-tab-content > p {
                margin-bottom: 20px;
                color: #646970;
            }
            
            .settings-tab-content .form-table {
                margin-top: 0;
            }
            
            .bracelet-customizer-settings-actions {
                padding: 20px;
                border-top: 1px solid #c3c4c7;
                background: #f6f7f7;
                text-align: left;
            }
            
            .bracelet-customizer-settings-actions .button {
                margin-right: 10px;
            }
            
            .letter-colors-config .letter-color-item {
                background: #f9f9f9;
            }
            
            .bracelet-customizer-preview {
                margin-top: 20px;
                padding: 20px;
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
            }
            
            .preview-container {
                margin-top: 15px;
                padding: 20px;
                background: #f6f7f7;
                border-radius: 4px;
                text-align: center;
            }
            
            .preview-btn {
                display: inline-block;
                padding: 12px 24px;
                background: #4F46E5;
                color: #fff;
                text-decoration: none;
                border-radius: 4px;
                font-weight: 600;
                border: none;
                cursor: default;
            }
            
            /* WordPress Standard Tab Styles */
            .bracelet-customizer-tab-content {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-top: none;
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .bracelet-customizer-tab-content h3 {
                margin-top: 0;
                font-size: 20px;
                font-weight: 600;
                color: #1d2327;
            }
            
            .bracelet-customizer-tab-content p {
                color: #646970;
                margin-bottom: 20px;
            }
        ');
        
        wp_add_inline_script('wp-color-picker', '
            jQuery(document).ready(function($) {
                $(".color-picker-field").wpColorPicker();
                
                // Reset settings
                $("#reset-settings").click(function() {
                    if (confirm("' . esc_js(__('Are you sure you want to reset all settings to defaults?', 'bracelet-customizer')) . '")) {
                        $.post(ajaxurl, {
                            action: "bracelet_customizer_reset_settings",
                            nonce: "' . wp_create_nonce('bracelet_customizer_admin') . '"
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            }
                        });
                    }
                });
                
                // Export settings
                $("#export-settings").click(function() {
                    window.location.href = ajaxurl + "?action=bracelet_customizer_export_settings&nonce=' . wp_create_nonce('bracelet_customizer_admin') . '";
                });
                
                // Import settings
                $("#import-settings").click(function() {
                    $("#import-file").click();
                });
                
                $("#import-file").change(function() {
                    var file = this.files[0];
                    if (file) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            try {
                                var settings = JSON.parse(e.target.result);
                                $.post(ajaxurl, {
                                    action: "bracelet_customizer_import_settings",
                                    nonce: "' . wp_create_nonce('bracelet_customizer_admin') . '",
                                    settings: JSON.stringify(settings)
                                }, function(response) {
                                    if (response.success) {
                                        location.reload();
                                    } else {
                                        alert("' . esc_js(__('Import failed. Please check the file format.', 'bracelet-customizer')) . '");
                                    }
                                });
                            } catch (error) {
                                alert("' . esc_js(__('Invalid file format.', 'bracelet-customizer')) . '");
                            }
                        };
                        reader.readAsText(file);
                    }
                });
            });
        ');
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        // Get existing settings to preserve values not in current form submission
        $existing_settings = get_option($this->option_name, $this->defaults);
        $sanitized = $existing_settings;
        
        // Only sanitize and update fields that are actually in the input (from current tab)
        foreach ($input as $key => $value) {
            if (array_key_exists($key, $this->defaults)) {
                $sanitized[$key] = $this->sanitize_field($key, $value, $this->defaults[$key]);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize individual field
     */
    private function sanitize_field($key, $value, $default) {
        switch ($key) {
                
            case 'max_word_length':
            case 'min_word_length':
                return max(1, intval($value));
                
            case 'allowed_characters':
                return sanitize_text_field($value);
                
            case 'button_colors':
                if (is_array($value)) {
                    // Get existing settings to preserve values not in current form submission
                    $existing_settings = get_option($this->option_name, $this->defaults);
                    $existing_colors = isset($existing_settings['button_colors']) ? $existing_settings['button_colors'] : $default;
                    $sanitized = $existing_colors;
                    
                    // Only update submitted values
                    foreach ($value as $color_key => $color_value) {
                        $sanitized[sanitize_key($color_key)] = sanitize_hex_color($color_value);
                    }
                    return $sanitized;
                }
                return $default;
                
            case 'button_labels':
                if (is_array($value)) {
                    // Get existing settings to preserve values not in current form submission
                    $existing_settings = get_option($this->option_name, $this->defaults);
                    $existing_labels = isset($existing_settings['button_labels']) ? $existing_settings['button_labels'] : $default;
                    $sanitized = $existing_labels;
                    
                    // Only update submitted values
                    foreach ($value as $label_key => $label_value) {
                        $sanitized[sanitize_key($label_key)] = sanitize_text_field($label_value);
                    }
                    return $sanitized;
                }
                return $default;
                
            case 'letter_colors':
                if (is_array($value)) {
                    // Get existing settings to preserve values not in current form submission
                    $existing_settings = get_option($this->option_name, $this->defaults);
                    $existing_colors = isset($existing_settings['letter_colors']) ? $existing_settings['letter_colors'] : $default;
                    $sanitized = $existing_colors;
                    
                    // Only update submitted values
                    foreach ($value as $color_id => $color_data) {
                        $sanitized[sanitize_key($color_id)] = [
                            'name' => sanitize_text_field($color_data['name']),
                            'price' => floatval($color_data['price']),
                            'enabled' => !empty($color_data['enabled']),
                            'color' => sanitize_hex_color($color_data['color']) ?: '#ffffff'
                        ];
                    }
                    return $sanitized;
                }
                return $default;
                
            case 'ui_settings':
            case 'api_settings':
            case 'advanced_settings':
                if (is_array($value)) {
                    // Get existing settings to preserve values not in current form submission
                    $existing_settings = get_option($this->option_name, $this->defaults);
                    $existing_nested = isset($existing_settings[$key]) ? $existing_settings[$key] : $default;
                    $sanitized = $existing_nested;
                    
                    // Only update submitted values
                    foreach ($value as $setting_key => $setting_value) {
                        $sanitized[sanitize_key($setting_key)] = isset($default[$setting_key]) && is_bool($default[$setting_key]) ? !empty($setting_value) : sanitize_text_field($setting_value);
                    }
                    return $sanitized;
                }
                return $default;
                
            default:
                if (is_array($value)) {
                    return array_map('sanitize_text_field', $value);
                }
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Convert dot notation field name to array notation for HTML forms
     */
    private function get_field_name($field) {
        if (strpos($field, '.') !== false) {
            $parts = explode('.', $field);
            $name = $this->option_name;
            foreach ($parts as $part) {
                $name .= '[' . $part . ']';
            }
            return $name;
        }
        return $this->option_name . '[' . $field . ']';
    }
    
    /**
     * Get setting value
     */
    public function get_setting($key, $default = null) {
        $settings = get_option($this->option_name, $this->defaults);
        
        // Handle nested keys (e.g., 'button_colors.primary')
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = $settings;
            
            foreach ($keys as $nested_key) {
                if (isset($value[$nested_key])) {
                    $value = $value[$nested_key];
                } else {
                    return $default !== null ? $default : $this->get_default_value($key);
                }
            }
            
            return $value;
        }
        
        return isset($settings[$key]) ? $settings[$key] : ($default !== null ? $default : $this->get_default_value($key));
    }
    
    /**
     * Get default value for a key
     */
    private function get_default_value($key) {
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = $this->defaults;
            
            foreach ($keys as $nested_key) {
                if (isset($value[$nested_key])) {
                    $value = $value[$nested_key];
                } else {
                    return null;
                }
            }
            
            return $value;
        }
        
        return isset($this->defaults[$key]) ? $this->defaults[$key] : null;
    }
    
    /**
     * Get all settings
     */
    public function get_all_settings() {
        return get_option($this->option_name, $this->defaults);
    }
    
    /**
     * Reset settings to defaults
     */
    public function reset_settings() {
        check_ajax_referer('bracelet_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions.', 'bracelet-customizer'));
        }
        
        update_option($this->option_name, $this->defaults);
        wp_send_json_success(__('Settings reset to defaults.', 'bracelet-customizer'));
    }
    
    /**
     * Export settings
     */
    public function export_settings() {
        check_admin_referer('bracelet_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions.', 'bracelet-customizer'));
        }
        
        $settings = $this->get_all_settings();
        $filename = 'bracelet-customizer-settings-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($settings, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Import settings
     */
    public function import_settings() {
        check_ajax_referer('bracelet_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('You do not have sufficient permissions.', 'bracelet-customizer'));
        }
        
        $settings_json = stripslashes($_POST['settings']);
        $settings = json_decode($settings_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Invalid JSON format.', 'bracelet-customizer'));
        }
        
        // Sanitize imported settings
        $sanitized_settings = $this->sanitize_settings($settings);
        
        update_option($this->option_name, $sanitized_settings);
        wp_send_json_success(__('Settings imported successfully.', 'bracelet-customizer'));
    }
}