<?php
/**
 * Admin Class
 *
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle admin-specific functionality
 */
class Bracelet_Customizer_Admin {
    
    /**
     * Initialize admin functionality
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu_items']);
        add_action('admin_notices', [$this, 'show_admin_notices']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_post_save_charm_categories', [$this, 'handle_save_charm_categories']);
    }
    
    /**
     * Add admin menu items
     */
    public function add_admin_menu_items() {
        add_submenu_page(
            'woocommerce',
            __('Charm Categories', 'bracelet-customizer'),
            __('Charm Categories', 'bracelet-customizer'),
            'manage_woocommerce',
            'charm-categories',
            [$this, 'render_charm_categories_page']
        );
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        // Show any necessary admin notices
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Enqueue admin-specific CSS and JS
    }
    
    /**
     * Render charm categories management page
     */
    public function render_charm_categories_page() {
        // Handle form submission
        if (isset($_GET['message']) && $_GET['message'] === 'updated') {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Charm categories updated successfully.', 'bracelet-customizer') . '</p></div>';
        }
        
        $categories = get_option('bracelet_customizer_charm_categories', []);
        ?>
        <div class="wrap">
            <h1><?php _e('Manage Charm Categories', 'bracelet-customizer'); ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('save_charm_categories', 'charm_categories_nonce'); ?>
                <input type="hidden" name="action" value="save_charm_categories">
                
                <table class="form-table" id="charm-categories-table">
                    <thead>
                        <tr>
                            <th><?php _e('Category Slug', 'bracelet-customizer'); ?></th>
                            <th><?php _e('Category Name', 'bracelet-customizer'); ?></th>
                            <th><?php _e('Actions', 'bracelet-customizer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $slug => $name): ?>
                        <tr>
                            <td><input type="text" name="categories[<?php echo esc_attr($slug); ?>][slug]" value="<?php echo esc_attr($slug); ?>" readonly class="regular-text"></td>
                            <td><input type="text" name="categories[<?php echo esc_attr($slug); ?>][name]" value="<?php echo esc_attr($name); ?>" class="regular-text"></td>
                            <td><button type="button" class="button remove-category"><?php _e('Remove', 'bracelet-customizer'); ?></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p>
                    <button type="button" class="button" id="add-category"><?php _e('Add New Category', 'bracelet-customizer'); ?></button>
                </p>
                
                <?php submit_button(__('Save Categories', 'bracelet-customizer')); ?>
            </form>
            
            <h2><?php _e('Default Categories', 'bracelet-customizer'); ?></h2>
            <p><?php _e('These are the built-in categories that cannot be removed:', 'bracelet-customizer'); ?></p>
            <ul>
                <li><strong>bestsellers</strong> - <?php _e('Bestsellers', 'bracelet-customizer'); ?></li>
                <li><strong>new_drops</strong> - <?php _e('New Drops & Favs', 'bracelet-customizer'); ?></li>
                <li><strong>personalize</strong> - <?php _e('Personalize it', 'bracelet-customizer'); ?></li>
                <li><strong>by_vibe</strong> - <?php _e('By Vibe', 'bracelet-customizer'); ?></li>
            </ul>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            let categoryCount = <?php echo count($categories); ?>;
            
            $('#add-category').on('click', function() {
                const newRow = $('<tr>' +
                    '<td><input type="text" name="new_categories[' + categoryCount + '][slug]" placeholder="category-slug" class="regular-text" required></td>' +
                    '<td><input type="text" name="new_categories[' + categoryCount + '][name]" placeholder="Category Name" class="regular-text" required></td>' +
                    '<td><button type="button" class="button remove-category">Remove</button></td>' +
                    '</tr>');
                $('#charm-categories-table tbody').append(newRow);
                categoryCount++;
            });
            
            $(document).on('click', '.remove-category', function() {
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle saving charm categories
     */
    public function handle_save_charm_categories() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        if (!wp_verify_nonce($_POST['charm_categories_nonce'], 'save_charm_categories')) {
            wp_die(__('Security check failed.'));
        }
        
        $categories = [];
        
        // Handle existing categories
        if (isset($_POST['categories']) && is_array($_POST['categories'])) {
            foreach ($_POST['categories'] as $category_data) {
                if (!empty($category_data['slug']) && !empty($category_data['name'])) {
                    $slug = sanitize_key($category_data['slug']);
                    $name = sanitize_text_field($category_data['name']);
                    $categories[$slug] = $name;
                }
            }
        }
        
        // Handle new categories
        if (isset($_POST['new_categories']) && is_array($_POST['new_categories'])) {
            foreach ($_POST['new_categories'] as $category_data) {
                if (!empty($category_data['slug']) && !empty($category_data['name'])) {
                    $slug = sanitize_key($category_data['slug']);
                    $name = sanitize_text_field($category_data['name']);
                    $categories[$slug] = $name;
                }
            }
        }
        
        update_option('bracelet_customizer_charm_categories', $categories);
        
        wp_redirect(add_query_arg('message', 'updated', wp_get_referer()));
        exit;
    }
}