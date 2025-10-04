/**
 * Bracelet Customizer - Public JavaScript
 * Frontend functionality for the bracelet customizer
 */

(function($) {
    'use strict';

    // Global customizer object
    window.BraceletCustomizer = window.BraceletCustomizer || {};

    /**
     * Initialize the customizer
     */
    function initCustomizer() {
        // Initialize modal functionality
        initModal();
        
        // Initialize product page integration
        initProductPageIntegration();
        
        // Initialize cart functionality
        initCartFunctionality();
        
        // Initialize customizer events
        initCustomizerEvents();
        
        console.log('Bracelet Customizer initialized');
    }

    /**
     * Initialize modal functionality
     */
    function initModal() {
        // Handle customize button click
        $(document).on('click', '.bracelet-customize-btn', function(e) {
            e.preventDefault();
            
            const productId = $(this).data('product-id');
            
            // Check if we should use modal or page redirect
            const useModal = window.BraceletCustomizerConfig?.settings?.enable_modal ?? false;
            const customizerPageUrl = getCustomizerPageUrl();
            
            if (!useModal && customizerPageUrl) {
                // Redirect to customizer page
                redirectToCustomizerPage(productId, customizerPageUrl);
            } else {
                // Use modal functionality
                const modal = $('#bracelet-customizer-modal');
                
                if (modal.length) {
                    // Show modal
                    modal.show();
                    
                    // Focus management for accessibility
                    modal.attr('aria-hidden', 'false');
                    modal.find('.bracelet-modal-close').focus();
                    
                    // Prevent body scroll
                    $('body').addClass('modal-open');
                    
                    // Initialize React app if not already done
                    initReactApp(productId);
                    
                    // Track event
                    trackEvent('customizer_opened', { product_id: productId });
                } else {
                    // Fallback to page redirect if modal not found
                    if (customizerPageUrl) {
                        redirectToCustomizerPage(productId, customizerPageUrl);
                    } else {
                        console.error('Bracelet Customizer: No modal found and no customizer page URL configured');
                        alert('Customizer is not properly configured. Please contact support.');
                    }
                }
            }
        });

        // Close modal events
        $(document).on('click', '.bracelet-modal-close', closeModal);
        $(document).on('click', '.bracelet-modal', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Escape key to close modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#bracelet-customizer-modal').is(':visible')) {
                closeModal();
            }
        });
    }

    /**
     * Close modal
     */
    function closeModal() {
        const modal = $('#bracelet-customizer-modal');
        
        modal.hide();
        modal.attr('aria-hidden', 'true');
        $('body').removeClass('modal-open');
        
        // Return focus to trigger button
        $('.bracelet-customize-btn').focus();
        
        // Track event
        trackEvent('customizer_closed');
    }

    /**
     * Initialize product page integration
     */
    function initProductPageIntegration() {
        // Check if we're on a product page with bracelet products
        if ($('body').hasClass('single-product')) {
            const $addToCartForm = $('.single_add_to_cart_form');
            
            if ($addToCartForm.length && $('.bracelet-customize-btn').length) {
                // Modify add to cart behavior for customizable products
                $addToCartForm.on('submit', function(e) {
                    const customizationData = getStoredCustomization();
                    
                    if (customizationData && Object.keys(customizationData).length > 0) {
                        // Add customization data to form
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'bracelet_customization',
                            value: JSON.stringify(customizationData)
                        }).appendTo($addToCartForm);
                    }
                });
            }
        }
    }

    /**
     * Initialize cart functionality
     */
    function initCartFunctionality() {
        // Handle customization display in cart
        $('.woocommerce-cart, .woocommerce-checkout').each(function() {
            displayCartCustomizations();
        });
    }

    /**
     * Display customizations in cart
     */
    function displayCartCustomizations() {
        $('.cart_item').each(function() {
            const $item = $(this);
            const customizationData = $item.data('customization');
            
            if (customizationData) {
                displayCustomizationSummary($item, customizationData);
            }
        });
    }

    /**
     * Display customization summary
     */
    function displayCustomizationSummary($container, customization) {
        if (!customization) return;

        const $summary = $('<div class="bracelet-customization-data"></div>');
        
        // Word customization
        if (customization.word) {
            $summary.append(`
                <dt>Word:</dt>
                <dd>${customization.word}</dd>
            `);
            
            // Letter preview
            if (customization.letterColor) {
                const $preview = $('<div class="bracelet-customization-preview"></div>');
                customization.word.split('').forEach(letter => {
                    $preview.append(`<span class="bracelet-letter-preview">${letter}</span>`);
                });
                $summary.append($preview);
            }
        }
        
        // Letter color
        if (customization.letterColor) {
            $summary.append(`
                <dt>Letter Color:</dt>
                <dd>${customization.letterColor.charAt(0).toUpperCase() + customization.letterColor.slice(1)}</dd>
            `);
        }
        
        // Charms
        if (customization.selectedCharms && customization.selectedCharms.length > 0) {
            const charmNames = customization.selectedCharms.map(charm => charm.name).join(', ');
            $summary.append(`
                <dt>Charms:</dt>
                <dd>${charmNames}</dd>
            `);
        }
        
        // Size
        if (customization.size) {
            $summary.append(`
                <dt>Size:</dt>
                <dd>${customization.size.toUpperCase()}</dd>
            `);
        }
        
        $container.find('.product-name').after($summary);
    }

    /**
     * Initialize customizer events
     */
    function initCustomizerEvents() {
        // Listen for customization complete event from React app
        window.addEventListener('braceletCustomizationComplete', function(event) {
            const customization = event.detail;
            storeCustomization(customization);
            
            // Track completion
            trackEvent('customization_completed', {
                word_length: customization.word ? customization.word.length : 0,
                charm_count: customization.selectedCharms ? customization.selectedCharms.length : 0,
                letter_color: customization.letterColor
            });
        });

        // Listen for add to cart event from React app
        window.addEventListener('braceletAddToCart', function(event) {
            const data = event.detail;
            handleAddToCart(data);
        });

        // Listen for customization change events
        window.addEventListener('braceletCustomizationChanged', function(event) {
            const customization = event.detail;
            storeCustomization(customization);
        });
    }

    /**
     * Initialize React app
     */
    function initReactApp(productId) {
        // React app initialization is handled by the React app itself
        // This function can be used to pass additional data to React
        
        if (window.BraceletCustomizerConfig) {
            window.BraceletCustomizerConfig.productId = productId;
            window.BraceletCustomizerConfig.sessionId = generateSessionId();
        }
    }

    /**
     * Store customization data
     */
    function storeCustomization(customization) {
        try {
            localStorage.setItem('bracelet_customization', JSON.stringify(customization));
            sessionStorage.setItem('bracelet_customization_temp', JSON.stringify(customization));
        } catch (e) {
            console.warn('Could not store customization data:', e);
        }
    }

    /**
     * Get stored customization data
     */
    function getStoredCustomization() {
        try {
            const stored = localStorage.getItem('bracelet_customization') || 
                          sessionStorage.getItem('bracelet_customization_temp');
            return stored ? JSON.parse(stored) : null;
        } catch (e) {
            console.warn('Could not retrieve customization data:', e);
            return null;
        }
    }

    /**
     * Handle add to cart from React app
     */
    function handleAddToCart(data) {
        const { productId, customization, quantity = 1 } = data;
        
        // Show loading state
        showLoading('Adding to cart...');
        
        // Prepare data for AJAX request
        const ajaxData = {
            action: 'add_custom_bracelet_to_cart',
            nonce: BraceletCustomizerAjax.nonce,
            product_id: productId,
            quantity: quantity,
            customization_data: JSON.stringify(customization)
        };
        
        // Send AJAX request
        $.ajax({
            url: BraceletCustomizerAjax.ajaxurl,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    // Close modal
                    closeModal();
                    
                    // Show success message
                    showMessage('Bracelet added to cart!', 'success');
                    
                    // Update cart fragments
                    updateCartFragments();
                    
                    // Optionally redirect to cart
                    if (response.data.cart_url) {
                        window.location.href = response.data.cart_url;
                    }
                    
                    // Track event
                    trackEvent('bracelet_added_to_cart', {
                        product_id: productId,
                        customization: customization
                    });
                } else {
                    showMessage(response.data.message || 'Failed to add bracelet to cart', 'error');
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                showMessage('An error occurred. Please try again.', 'error');
                console.error('Add to cart error:', error);
            }
        });
    }

    /**
     * Update WooCommerce cart fragments
     */
    function updateCartFragments() {
        if (typeof wc_add_to_cart_params !== 'undefined') {
            $(document.body).trigger('wc_fragment_refresh');
        }
    }

    /**
     * Show loading state
     */
    function showLoading(message = 'Loading...') {
        const $loading = $(`
            <div class="bracelet-customizer-loading" id="bracelet-loading">
                <div class="bracelet-loading-spinner"></div>
                ${message}
            </div>
        `);
        
        $('body').append($loading);
    }

    /**
     * Hide loading state
     */
    function hideLoading() {
        $('#bracelet-loading').remove();
    }

    /**
     * Show message to user
     */
    function showMessage(message, type = 'info') {
        // Use WooCommerce notices if available
        if (typeof wc_add_to_cart_params !== 'undefined') {
            $('.woocommerce-notices-wrapper').first().html(`
                <div class="woocommerce-message" role="alert">
                    ${message}
                </div>
            `);
        } else {
            // Fallback to simple alert
            alert(message);
        }
    }

    /**
     * Generate unique session ID
     */
    function generateSessionId() {
        return 'bc_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Get customizer page URL from settings
     */
    function getCustomizerPageUrl() {
        // Try to get from config first (most reliable)
        if (window.BraceletCustomizerConfig?.customizerPageUrl) {
            return window.BraceletCustomizerConfig.customizerPageUrl;
        }
        
        // Fallback: try to construct from page ID in settings
        if (window.BraceletCustomizerConfig?.settings?.ui_settings?.customizer_page_id) {
            const pageId = window.BraceletCustomizerConfig.settings.ui_settings.customizer_page_id;
            const baseUrl = window.location.origin;
            return baseUrl + '/?page_id=' + pageId;
        }
        
        // Fallback: try to find a link to the customizer page in the DOM
        const customizerLink = $('a[href*="bracelet-customizer"], a[href*="customize"]').first().attr('href');
        if (customizerLink) {
            return customizerLink;
        }
        
        // Last resort: construct based on common patterns
        const baseUrl = window.location.origin;
        return baseUrl + '/bracelet-customizer/';
    }

    /**
     * Redirect to customizer page with product context
     */
    function redirectToCustomizerPage(productId, customizerPageUrl) {
        // Store product context for the customizer page
        const productContext = {
            productId: productId,
            referrer: window.location.href,
            timestamp: Date.now()
        };
        
        // Store in session storage for the customizer page to pick up
        try {
            sessionStorage.setItem('bracelet_customizer_product_context', JSON.stringify(productContext));
            sessionStorage.setItem('bracelet_customizer_referrer', window.location.href);
        } catch (e) {
            console.warn('Could not store product context:', e);
        }
        
        // Add product ID to URL if not already there
        let redirectUrl = customizerPageUrl;
        const url = new URL(redirectUrl, window.location.origin);
        
        if (!url.searchParams.get('product_id')) {
            url.searchParams.set('product_id', productId);
        }
        
        // Track event
        trackEvent('customizer_page_redirect', { 
            product_id: productId,
            referrer: window.location.href
        });
        
        // Redirect
        window.location.href = url.toString();
    }

    /**
     * Track events for analytics
     */
    function trackEvent(eventName, data = {}) {
        // Google Analytics 4
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, {
                custom_parameters: data,
                event_category: 'bracelet_customizer'
            });
        }
        
        // Google Analytics Universal
        if (typeof ga !== 'undefined') {
            ga('send', 'event', 'bracelet_customizer', eventName, JSON.stringify(data));
        }
        
        // Facebook Pixel
        if (typeof fbq !== 'undefined') {
            fbq('trackCustom', eventName, data);
        }
        
        // Console log for debugging
        console.log('Event tracked:', eventName, data);
    }

    /**
     * Utility functions for React app communication
     */
    window.BraceletCustomizer.utils = {
        storeCustomization: storeCustomization,
        getStoredCustomization: getStoredCustomization,
        handleAddToCart: handleAddToCart,
        closeModal: closeModal,
        showMessage: showMessage,
        trackEvent: trackEvent
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        initCustomizer();
    });

    // Re-initialize on AJAX complete (for dynamic content)
    $(document).ajaxComplete(function() {
        // Re-initialize modal functionality
        setTimeout(initModal, 100);
    });

})(jQuery);