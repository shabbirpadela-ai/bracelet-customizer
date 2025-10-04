<?php
/**
 * Main Plugin Class
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class that handles initialization and coordination
 */
class Bracelet_Customizer_Main {
    
    /**
     * Plugin instance
     *
     * @var Bracelet_Customizer_Main
     */
    private static $instance = null;
    
    /**
     * Plugin version
     *
     * @var string
     */
    public $version = BRACELET_CUSTOMIZER_VERSION;
    
    /**
     * Initialize the plugin
     */
    public function __construct() {
        $this->init_hooks();
        $this->init_includes();
    }
    
    /**
     * Get plugin instance
     *
     * @return Bracelet_Customizer_Main
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'init'], 0);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_init', [$this, 'admin_init']);
        
        // Add body class for customizer pages
        add_filter('body_class', [$this, 'add_body_class']);
        
        // Add customizer data to head
        add_action('wp_head', [$this, 'add_customizer_config']);
    }
    
    /**
     * Include required files and initialize classes
     */
    private function init_includes() {
        // Core includes
        $includes = [
            'class-settings.php',
            'class-product-types.php',
            'class-rest-api.php',
            'class-asset-manager.php',
            'class-asset-integration.php',
            'class-database.php',
            'class-woocommerce-integration.php',
            'class-page-template.php'
        ];
        
        foreach ($includes as $file) {
            $file_path = BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Admin includes
        if (is_admin()) {
            require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'admin/class-admin.php';
            require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'admin/class-product-meta-fields.php';
            require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/debug-cart-thumbnails.php';
        }
        
        // Shortcodes
        require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/class-shortcodes.php';
        
        // Initialize classes
        $this->init_classes();
    }
    
    /**
     * Initialize plugin classes
     */
    private function init_classes() {
        // Core classes
        if (class_exists('Bracelet_Customizer_Settings')) {
            new Bracelet_Customizer_Settings();
        }
        
        if (class_exists('Bracelet_Customizer_Product_Types')) {
            new Bracelet_Customizer_Product_Types();
        }
        
        if (class_exists('Bracelet_Customizer_Rest_API')) {
            new Bracelet_Customizer_Rest_API();
        }
        
        if (class_exists('Bracelet_Customizer_Asset_Manager')) {
            new Bracelet_Customizer_Asset_Manager();
        }
        
        if (class_exists('Bracelet_Customizer_Database')) {
            new Bracelet_Customizer_Database();
        }
        
        if (class_exists('Bracelet_Customizer_WooCommerce')) {
            new Bracelet_Customizer_WooCommerce();
        }
        
        // Admin classes
        if (is_admin() && class_exists('Bracelet_Customizer_Admin')) {
            new Bracelet_Customizer_Admin();
        }
        
        // Initialize shortcodes
        if (class_exists('Bracelet_Customizer_Shortcodes')) {
            new Bracelet_Customizer_Shortcodes();
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load plugin textdomain
        $this->load_textdomain();
        
        // Initialize WooCommerce hooks
        $this->init_woocommerce_hooks();
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Add plugin settings
        $this->register_settings();
    }
    
    /**
     * Load plugin textdomain for translations
     */
    private function load_textdomain() {
        load_plugin_textdomain(
            'bracelet-customizer',
            false,
            dirname(BRACELET_CUSTOMIZER_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Initialize WooCommerce specific hooks
     */
    private function init_woocommerce_hooks() {
        // Add custom product types to WooCommerce
        add_filter('woocommerce_product_class', [$this, 'woocommerce_product_class'], 10, 2);
        
        // Add product type options
        add_filter('product_type_options', [$this, 'product_type_options']);
    }
    
    /**
     * Register plugin settings
     */
    private function register_settings() {
        // This will be handled by the Settings class
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        // Only load on pages that need the customizer
        if ($this->should_load_customizer()) {
            $this->enqueue_react_app();
        }
        
        // Always load basic styles
        wp_enqueue_style(
            'bracelet-customizer-public',
            BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/css/public.css',
            [],
            $this->version
        );
        
        wp_enqueue_script(
            'bracelet-customizer-public',
            BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/js/public.js',
            ['jquery'],
            $this->version,
            true
        );
        
        // Localize script with basic data
        wp_localize_script('bracelet-customizer-public', 'BraceletCustomizerAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bracelet_customizer_nonce'),
            'rest_url' => rest_url('bracelet-customizer/v1/'),
            'rest_nonce' => wp_create_nonce('wp_rest')
        ]);
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Load on plugin settings page
        if (strpos($hook, 'bracelet-customizer') !== false) {
            wp_enqueue_style(
                'bracelet-customizer-admin',
                BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/css/admin.css',
                [],
                $this->version
            );
            
            wp_enqueue_script(
                'bracelet-customizer-admin',
                BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery', 'wp-media'],
                $this->version,
                true
            );
            
            // Enqueue media scripts for image uploads
            wp_enqueue_media();
        }
        
        // Load on product edit pages
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            global $post_type;
            if ($post_type === 'product') {
                wp_enqueue_style(
                    'bracelet-customizer-admin',
                    BRACELET_CUSTOMIZER_PLUGIN_URL . 'assets/css/admin.css',
                    [],
                    $this->version
                );
            }
        }
    }
    
    /**
     * Check if we should load the customizer on this page
     */
    private function should_load_customizer() {
        global $post;
        
        // Load on pages with shortcode
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'bracelet_customizer')) {
            return true;
        }
        
        // 2) Load on single product pages for customizable bracelet products
        if (function_exists('is_product') && is_product()) {
            // Get the current product id from the query (works even before the global $product is set)
            $product_id = get_queried_object_id();
            if (!$product_id && $post instanceof WP_Post) {
                $product_id = (int) $post->ID;
            }

            if ($product_id) {
                // Always get a proper WC_Product object
                $product = wc_get_product($product_id);
                if ($product instanceof WC_Product) {
                    $is_customizable = get_post_meta($product_id, '_bracelet_customizable', true);

                    // Default to true for bracelet product types if meta not set
                    if (
                        $is_customizable === 'yes' ||
                        ($is_customizable === '' && $this->is_bracelet_product_type($product->get_type()))
                    ) {
                        return true;
                    }
                }
            }
        }
        
        // Load on cart/checkout if cart contains bracelet products
        if (is_cart() || is_checkout()) {
            if (WC()->cart && !WC()->cart->is_empty()) {
                foreach (WC()->cart->get_cart() as $cart_item) {
                    if (isset($cart_item['bracelet_customization'])) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if product type is a bracelet product type
     */
    private function is_bracelet_product_type($product_type) {
        $bracelet_types = ['standard_bracelet', 'bracelet_collabs', 'bracelet_no_words', 'tiny_words'];
        return in_array($product_type, $bracelet_types);
    }
    
    /**
     * Enqueue React application
     */
    private function enqueue_react_app() {
        $build_path = BRACELET_CUSTOMIZER_PLUGIN_PATH . 'bracelet-customizer/build';
        $build_url = BRACELET_CUSTOMIZER_PLUGIN_URL . 'bracelet-customizer/build';
        return;
        // Check if build exists
        if (!file_exists($build_path)) {
            return;
        }
        
        // Find the main JS and CSS files
        $manifest_path = $build_path . '/asset-manifest.json';
        if (file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);
            
            if (isset($manifest['files']['main.js'])) {
                wp_enqueue_script(
                    'bracelet-customizer-react',
                    $build_url . $manifest['files']['main.js'],
                    [],
                    $this->version,
                    true
                );
            }
            
            if (isset($manifest['files']['main.css'])) {
                wp_enqueue_style(
                    'bracelet-customizer-react-css',
                    $build_url . $manifest['files']['main.css'],
                    [],
                    $this->version
                );
            }
        } else {
            // Fallback for development
            wp_enqueue_script(
                'bracelet-customizer-react',
                $build_url . '/static/js/main.js',
                [],
                $this->version,
                true
            );
            
            wp_enqueue_style(
                'bracelet-customizer-react-css',
                $build_url . '/static/css/main.css',
                [],
                $this->version
            );
        }
    }
    
    /**
     * Add body class for customizer pages
     */
    public function add_body_class($classes) {
        if ($this->should_load_customizer()) {
            $classes[] = 'bracelet-customizer-page';
        }
        return $classes;
    }
    
    /**
     * Add customizer configuration to head
     */
    public function add_customizer_config() {
        if ($this->should_load_customizer()) {
            $settings = get_option('bracelet_customizer_settings', []);
            // Get customizer page URL
            $customizer_page_url = null;
            if (isset($settings['ui_settings']['customizer_page_id'])) {
                $customizer_page_url = get_permalink($settings['ui_settings']['customizer_page_id']);
            }
            // Fallback: try to get from the page template class
            if (!$customizer_page_url && class_exists('Bracelet_Customizer_Page_Template')) {
                $customizer_page_url = Bracelet_Customizer_Page_Template::get_customizer_page_url();
            }
            
            $config = [
                'apiBase' => rest_url('bracelet-customizer/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'ajaxNonce' => wp_create_nonce('bracelet_customizer_nonce'),
                'pluginUrl' => BRACELET_CUSTOMIZER_PLUGIN_URL,
                'uploadUrl' => wp_upload_dir()['baseurl'],
                'settings' => $settings,
                'customizerPageUrl' => $customizer_page_url,
                'siteName' => get_bloginfo('name'),
                'woocommerce' => [
                    'cartUrl' => wc_get_cart_url(),
                    'checkoutUrl' => wc_get_checkout_url(),
                    'currency' => get_woocommerce_currency(),
                    'currencySymbol' => get_woocommerce_currency_symbol(),
                    'priceDecimals' => wc_get_price_decimals(),
                    'priceDecimalSep' => wc_get_price_decimal_separator(),
                    'priceThousandSep' => wc_get_price_thousand_separator()
                ]
            ];
            
            echo '<script type="text/javascript">';
            echo 'window.BraceletCustomizerConfig = ' . json_encode($config) . ';';
            echo '</script>';
        }
    }
    
    /**
     * Add custom product class for WooCommerce
     */
    public function woocommerce_product_class($classname, $product_type) {
        if ($product_type === 'standard_bracelet') {
            return 'WC_Product_Standard_Bracelet';
        } elseif ($product_type === 'charm') {
            return 'WC_Product_Charm';
        } elseif ($product_type === 'bracelet_collabs') {
            return 'WC_Product_Bracelet_Collabs';
        } elseif ($product_type === 'tiny_words') {
            return 'WC_Product_Tiny_Words';
        }
        return $classname;
    }
    
    /**
     * Add product type options
     */
    public function product_type_options($options) {
        $options['bracelet_customizable'] = [
            'id' => '_bracelet_customizable',
            'wrapper_class' => 'show_if_standard_bracelet show_if_bracelet_collabs show_if_bracelet_no_words show_if_tiny_words',
            'label' => __('Customizable', 'bracelet-customizer'),
            'description' => __('Enable bracelet customization for this product', 'bracelet-customizer'),
            'default' => 'yes'
        ];
        
        return $options;
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default settings
        self::set_default_settings();
        
        // Create sample products
        self::create_sample_products();
        
        // Create upload directories
        self::create_directories();
        
        // Create customizer page with full canvas template
        self::create_customizer_page();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        error_log('Bracelet Customizer activated successfully');
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Customizations table
        $table_name = $wpdb->prefix . 'bracelet_customizations';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            product_id bigint(20) NOT NULL,
            customization_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY product_id (product_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Order item customizations table
        $table_name2 = $wpdb->prefix . 'order_item_customizations';
        $sql2 = "CREATE TABLE IF NOT EXISTS $table_name2 (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_item_id bigint(20) NOT NULL,
            customization_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_item_id (order_item_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
        
        // Log table creation
        error_log('Bracelet Customizer database tables created');
    }
    
    /**
     * Set default plugin settings
     */
    private static function set_default_settings() {
        $default_settings = [
            'letter_source' => 'cloud',
            'cloud_base_url' => 'https://res.cloudinary.com/drvnwq9bm/image/upload',
            'max_word_length' => 13,
            'min_word_length' => 2,
            'allowed_characters' => 'a-zA-Z0-9:)\<3!#&:\s',
            'button_colors' => [
                'primary' => '#4F46E5',
                'secondary' => '#FFB6C1'
            ],
            'button_labels' => [
                'next' => __('NEXT', 'bracelet-customizer'),
                'review' => __('REVIEW', 'bracelet-customizer'),
                'add_to_cart' => __('ADD TO CART', 'bracelet-customizer'),
                'customize' => __('Customize This Bracelet', 'bracelet-customizer')
            ],
            'letter_colors' => [
                'white' => ['name' => __('White', 'bracelet-customizer'), 'price' => 0],
                'pink' => ['name' => __('Pink', 'bracelet-customizer'), 'price' => 0],
                'black' => ['name' => __('Black', 'bracelet-customizer'), 'price' => 0],
                'gold' => ['name' => __('Gold', 'bracelet-customizer'), 'price' => 15]
            ],
            'enable_modal' => false,
            'modal_width' => '90%',
            'modal_height' => '90%'
        ];
        
        // Only set if not already exists
        if (!get_option('bracelet_customizer_settings')) {
            update_option('bracelet_customizer_settings', $default_settings);
        }
        
        // Set version
        update_option('bracelet_customizer_version', BRACELET_CUSTOMIZER_VERSION);
    }
    
    /**
     * Create sample products
     */
    private static function create_sample_products() {
        // Check if sample products already exist
        $existing_bracelets = get_posts([
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => '_bracelet_sample_product',
                    'value' => 'yes'
                ]
            ],
            'posts_per_page' => 1
        ]);
        
        if (!empty($existing_bracelets)) {
            return; // Sample products already exist
        }
        
        // Create sample bracelet product
        $bracelet = new WC_Product_Simple();
        $bracelet->set_name(__('Sample Bluestone Bracelet', 'bracelet-customizer'));
        $bracelet->set_regular_price(25.00);
        $bracelet->set_description(__('A beautiful bluestone bracelet that can be customized with your own words and charms.', 'bracelet-customizer'));
        $bracelet->set_short_description(__('Customizable bluestone bracelet', 'bracelet-customizer'));
        $bracelet->set_manage_stock(false);
        $bracelet->set_stock_status('instock');
        $bracelet->set_catalog_visibility('visible');
        $bracelet->set_status('publish');
        
        $bracelet_id = $bracelet->save();
        
        if ($bracelet_id) {
            // Set as sample product
            update_post_meta($bracelet_id, '_bracelet_sample_product', 'yes');
            
            // Set product type
            wp_set_object_terms($bracelet_id, 'standard_bracelet', 'product_type');
            
            // Add bracelet meta
            update_post_meta($bracelet_id, '_bracelet_style_category', 'standard');
            update_post_meta($bracelet_id, '_bracelet_is_bestseller', 'yes');
            update_post_meta($bracelet_id, '_bracelet_customizable', 'yes');
            
            // Sample gap images (placeholder URLs)
            $gap_images = [];
            for ($i = 2; $i <= 13; $i++) {
                $gap_images[$i] = BRACELET_CUSTOMIZER_PLUGIN_URL . "assets/images/sample-bracelet-{$i}char.jpg";
            }
            update_post_meta($bracelet_id, '_bracelet_gap_images', $gap_images);
            
            error_log("Created sample bracelet product: ID {$bracelet_id}");
        }
        
        // Create charm products from mock data
        self::create_charm_products();
    }
    
    /**
     * Create charm products from mock data
     */
    private static function create_charm_products() {
        // Check if charm products already exist
        $existing_charms = get_posts([
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => '_product_type',
                    'value' => 'charm'
                ]
            ],
            'posts_per_page' => 1
        ]);
        
        if (!empty($existing_charms)) {
            error_log('Charm products already exist, skipping creation');
            return; // Charm products already exist
        }
        
        // Read mock data
        $mock_data_path = BRACELET_CUSTOMIZER_PLUGIN_PATH . 'bracelet-customizer/src/data/mockData.json';
        if (!file_exists($mock_data_path)) {
            error_log('Mock data file not found: ' . $mock_data_path);
            return;
        }
        
        $mock_data = json_decode(file_get_contents($mock_data_path), true);
        if (!$mock_data || !isset($mock_data['charms'])) {
            error_log('Invalid mock data or no charms found');
            return;
        }
        
        $created_count = 0;
        
        foreach ($mock_data['charms'] as $charm_data) {
            // Create charm product
            $charm = new WC_Product_Simple();
            $charm->set_name($charm_data['name']);
            $charm->set_regular_price($charm_data['price']);
            $charm->set_description($charm_data['description'] ?? '');
            $charm->set_short_description($charm_data['description'] ?? '');
            $charm->set_manage_stock(false);
            $charm->set_stock_status('instock');
            $charm->set_catalog_visibility('hidden'); // Hidden from catalog
            $charm->set_status('publish');
            
            $charm_id = $charm->save();
            
            if ($charm_id) {
                // Set product type as charm
                wp_set_object_terms($charm_id, 'charm', 'product_type');
                update_post_meta($charm_id, '_product_type', 'charm');
                
                // Add charm meta data
                update_post_meta($charm_id, '_charm_category', $charm_data['category'] ?? 'bestsellers');
                update_post_meta($charm_id, '_charm_is_new', $charm_data['isNew'] ? 'yes' : 'no');
                update_post_meta($charm_id, '_charm_original_id', $charm_data['id']);
                
                // Set charm as auto-created
                update_post_meta($charm_id, '_charm_auto_created', 'yes');
                
                $created_count++;
                error_log("Created charm product: {$charm_data['name']} (ID: {$charm_id}, Price: {$charm_data['price']})");
            } else {
                error_log("Failed to create charm product: {$charm_data['name']}");
            }
        }
        
        error_log("Created {$created_count} charm products during plugin activation");
    }
    
    /**
     * Create upload directories
     */
    private static function create_directories() {
        $upload_dir = wp_upload_dir();
        $bracelet_dir = $upload_dir['basedir'] . '/bracelet-customizer';
        
        // Create main directory
        if (!file_exists($bracelet_dir)) {
            wp_mkdir_p($bracelet_dir);
        }
        
        // Create subdirectories
        $subdirs = ['letters', 'charms', 'bracelets', 'temp'];
        foreach ($subdirs as $subdir) {
            $dir_path = $bracelet_dir . '/' . $subdir;
            if (!file_exists($dir_path)) {
                wp_mkdir_p($dir_path);
            }
        }
        
        // Create .htaccess to protect uploads
        $htaccess_content = "Options -Indexes\n";
        file_put_contents($bracelet_dir . '/.htaccess', $htaccess_content);
    }
    
    /**
     * Create customizer page with full canvas template
     */
    private static function create_customizer_page() {
        // Ensure the page template class is available
        if (!class_exists('Bracelet_Customizer_Page_Template')) {
            require_once BRACELET_CUSTOMIZER_PLUGIN_PATH . 'includes/class-page-template.php';
        }
        
        // Create the page
        $page_id = Bracelet_Customizer_Page_Template::create_customizer_page();
        
        if ($page_id) {
            error_log('Bracelet Customizer page created successfully with ID: ' . $page_id);
            
            // Update settings to reference this page
            $settings = get_option('bracelet_customizer_settings', []);
            if (!isset($settings['ui_settings'])) {
                $settings['ui_settings'] = [];
            }
            $settings['ui_settings']['customizer_page_id'] = $page_id;
            update_option('bracelet_customizer_settings', $settings);
        } else {
            error_log('Failed to create Bracelet Customizer page');
        }
    }
}