/**
 * Admin JavaScript for Bracelet Customizer
 * Handles image uploads for product meta fields
 */

jQuery(document).ready(function($) {
    
    // Image upload functionality
    $(document).on('click', '.upload-image-button', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const fieldName = button.data('field');
        const hiddenField = $('#' + fieldName);
        const previewContainer = button.siblings('.image-preview');
        
        // Create media uploader
        const mediaUploader = wp.media({
            title: 'Select Image',
            button: {
                text: 'Use this image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // When image is selected
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            
            // Update hidden field with attachment ID
            hiddenField.val(attachment.id);
            
            // Update preview
            previewContainer.html('<img src="' + attachment.url + '" style="max-width: 150px; max-height: 150px; display: block;" />');
            
            // Auto-fill corresponding URL field for gap images
            const urlFieldSelector = $('input[data-auto-fill-field="' + fieldName + '"]');
            if (urlFieldSelector.length && !urlFieldSelector.val()) {
                urlFieldSelector.val(attachment.url);
            }
            
            // Add remove button if it doesn't exist
            if (!button.siblings('.remove-image-button').length) {
                button.after(' <button type="button" class="button remove-image-button" data-field="' + fieldName + '">Remove</button>');
            }
        });
        
        // Open media uploader
        mediaUploader.open();
    });
    
    // Image removal functionality
    $(document).on('click', '.remove-image-button', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const fieldName = button.data('field');
        const hiddenField = $('#' + fieldName);
        const previewContainer = button.siblings('.image-preview');
        
        // Clear hidden field
        hiddenField.val('');
        
        // Update preview
        previewContainer.html('<div style="width: 150px; height: 150px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #999;">No image selected</div>');
        
        // Clear corresponding URL field for gap images
        const urlFieldSelector = $('input[data-auto-fill-field="' + fieldName + '"]');
        if (urlFieldSelector.length) {
            // Clear the URL field and trigger change to update preview
            urlFieldSelector.val('').trigger('input');
        }
        
        // Remove the remove button
        button.remove();
    });
    
    // Show/hide custom tabs based on product type
    $('select#product-type').on('change', function() {
        const productType = $(this).val();
        const customizerTab = $('.bracelet_customizer_tab');
        
        if (productType === 'standard_bracelet' || productType === 'charm') {
            customizerTab.show();
        } else {
            customizerTab.hide();
        }
    }).trigger('change');
    
    // Initialize visibility on page load
    const initialProductType = $('select#product-type').val();
    const customizerTab = $('.bracelet_customizer_tab');
    
    if (initialProductType === 'standard_bracelet' || initialProductType === 'charm') {
        customizerTab.show();
    } else {
        customizerTab.hide();
    }
    
    // Real-time preview for external URL inputs
    $(document).on('input blur', 'input[data-auto-fill-field]', function() {
        const urlField = $(this);
        const imageFieldName = urlField.attr('data-auto-fill-field');
        const imageUrl = urlField.val().trim();
        const imagePreview = $('input[name="' + imageFieldName + '"]').closest('.image-upload-container').find('.image-preview');
        
        if (imageUrl && isValidImageUrl(imageUrl)) {
            // Show loading state
            imagePreview.html('<div style="width: 150px; height: 150px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #999;">Loading...</div>');
            
            // Test if the image loads
            const testImg = new Image();
            testImg.onload = function() {
                imagePreview.html('<img src="' + imageUrl + '" style="max-width: 150px; max-height: 150px; display: block;" /><p style="font-size: 11px; color: #666; margin: 5px 0 0 0;">External URL Image (Preview)</p>');
                
                // Show remove button if it doesn't exist
                const removeBtn = $('button[data-field="' + imageFieldName + '"].remove-image-button');
                if (!removeBtn.length) {
                    const uploadBtn = $('button[data-field="' + imageFieldName + '"].upload-image-button');
                    uploadBtn.after(' <button type="button" class="button remove-image-button" data-field="' + imageFieldName + '">Remove</button>');
                }
            };
            testImg.onerror = function() {
                imagePreview.html('<div style="width: 150px; height: 150px; border: 2px solid #dc3232; display: flex; align-items: center; justify-content: center; color: #dc3232; font-size: 12px; text-align: center;">Invalid or<br>inaccessible URL</div>');
            };
            testImg.src = imageUrl;
        } else if (!imageUrl) {
            // URL is empty, check if there's an uploaded image
            const hiddenField = $('input[name="' + imageFieldName + '"]');
            const imageId = hiddenField.val();
            
            if (!imageId) {
                imagePreview.html('<div style="width: 150px; height: 150px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #999;">No image selected</div>');
                // Remove the remove button
                $('button[data-field="' + imageFieldName + '"].remove-image-button').remove();
            }
        }
    });
    
    // Helper function to validate image URLs
    function isValidImageUrl(url) {
        try {
            const urlObj = new URL(url);
            const validExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg'];
            const pathname = urlObj.pathname.toLowerCase();
            
            // Check if URL has valid image extension or is from known image services
            return validExtensions.some(ext => pathname.includes(ext)) || 
                   url.includes('cloudinary.com') || 
                   url.includes('amazonaws.com') ||
                   url.includes('wp-content/uploads') ||
                   pathname.includes('image') ||
                   pathname.includes('img');
        } catch (e) {
            return false;
        }
    }
});