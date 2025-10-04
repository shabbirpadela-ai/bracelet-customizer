<?php
/**
 * Template Name: Bracelet Customizer Full Canvas
 * 
 * Full canvas page template for bracelet customizer
 * No header, footer, or sidebar - optimized for customizer experience
 * 
 * @package Bracelet_Customizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
    <style>
        /* Full canvas styles - remove all theme styling */
        body {
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif !important;
            box-sizing: border-box !important;
        }
        
        * {
            box-sizing: border-box !important;
        }
        
        body.admin-bar {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        
        /* Hide admin bar if present */
        #wpadminbar {
            display: none !important;
        }
        
        /* Reset any theme containers - Twenty Five specific */
        .site, .site-content, .container, .wrapper, .main, 
        .wp-site-blocks, .wp-block-group, .wp-block-group.alignfull,
        main.wp-block-group, .entry-content {
            max-width: none !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
        }
        
        /* Ensure full height and remove admin bar margin */
        html, body {
            height: 100vh !important;
            min-height: 100vh !important;
            max-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }
        
        /* Force remove admin bar margin */
        html {
            margin-top: 0 !important;
        }
        
        /* Full canvas page content */
        .bracelet-customizer-canvas {
            width: 100vw !important;
            height: 100vh !important;
            min-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            position: relative !important;
            overflow: hidden !important;
            z-index: 999999 !important;
        }
        
        /* Hide any theme elements that might still show - Block theme specific */
        header, footer, .site-header, .site-footer, nav, .navigation, 
        .sidebar, .widget-area, .breadcrumb, .breadcrumbs,
        .wp-block-template-part, .wp-block-navigation, .wp-block-site-title,
        .wp-block-site-logo, .wp-block-post-title, .wp-block-post-featured-image {
            display: none !important;
        }
        
        /* Bracelet customizer specific styles */
        #bracelet-customizer-app {
            width: 100% !important;
            height: 100vh !important;
            min-height: 100vh !important;
            max-height: 100vh !important;
            position: relative !important;
            z-index: 999999 !important;
            overflow: hidden !important;
        }
        
        /* Override any spacing variables from Twenty Five theme */
        :root {
            --wp--preset--spacing--60: 0 !important;
        }
        
        /* Reset block spacing */
        .wp-block-group {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .bracelet-customizer-canvas {
                height: 100vh !important;
                overflow-y: auto !important;
            }
        }
    </style>
</head>

<body <?php body_class('bracelet-customizer-full-canvas'); ?>>
    <div class="bracelet-customizer-canvas">
        <?php
        // Start the Loop
        while (have_posts()) :
            the_post();
            ?>
            <div id="bracelet-customizer-page-<?php the_ID(); ?>" class="bracelet-customizer-page">
                <?php
                // Output the page content which should include the bracelet customizer shortcode
                the_content();
                ?>
            </div>
            <?php
        endwhile;
        ?>
    </div>
    
    <?php wp_footer(); ?>
    
    <script>
        // Ensure no admin bar interference
        document.addEventListener('DOMContentLoaded', function() {
            // Remove admin bar if present
            const adminBar = document.getElementById('wpadminbar');
            if (adminBar) {
                adminBar.style.display = 'none';
            }
            
            // Ensure body and html margin is reset
            document.body.style.marginTop = '0';
            document.body.style.paddingTop = '0';
            document.documentElement.style.marginTop = '0';
            document.documentElement.style.paddingTop = '0';
            document.documentElement.style.height = '100vh';
            document.documentElement.style.maxHeight = '100vh';
            document.documentElement.style.overflow = 'hidden';
            
            // Hide theme header/footer elements that might be dynamically added
            const hideElements = [
                '.wp-block-template-part',
                '.wp-block-navigation',
                '.wp-block-site-title',
                '.wp-block-site-logo',
                '.wp-block-post-title',
                '.wp-block-post-featured-image',
                'header',
                'footer',
                '.site-header',
                '.site-footer'
            ];
            
            hideElements.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    if (!el.closest('.bracelet-customizer-canvas')) {
                        el.style.display = 'none';
                    }
                });
            });
            
            // Reset any theme-imposed spacing on main containers
            const resetElements = [
                '.wp-site-blocks',
                '.wp-block-group',
                'main',
                '.entry-content'
            ];
            
            resetElements.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    if (!el.closest('.bracelet-customizer-canvas')) {
                        el.style.margin = '0';
                        el.style.padding = '0';
                        el.style.maxWidth = 'none';
                        el.style.width = '100%';
                    }
                });
            });
            
            // Handle browser back button for better UX
            window.addEventListener('popstate', function(event) {
                // If user came from a product page, handle gracefully
                const referrer = sessionStorage.getItem('bracelet_customizer_referrer');
                if (referrer && history.length > 1) {
                    // Let the customizer close function handle it
                    if (typeof window.closeBraceletCustomizer === 'function') {
                        window.closeBraceletCustomizer();
                    }
                }
            });
        });
    </script>
</body>
</html>