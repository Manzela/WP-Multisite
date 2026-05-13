/**
 * This script is used to handle the logic of the variable product.
 * It is used to update the price and the add to Cart button when a variation is selected.
 * It also updates the available options for the attributes.
 * variable-product.blade.php is used to display the variable product.
 * Author: Edward Ziadeh
 * Date: 2024-11-04
 */

jQuery(document).ready(function($) {
    const productContainer = $('.variable-product');
    if (!productContainer.length) {
        //console.log('No product container found.');
        return;
    }

    const variationsData = productContainer.attr('data-variations');
    let variations;
    try {
        variations = JSON.parse(variationsData);
       // console.log('Variations data:', variations);
    } catch (error) {
        //console.error('Error parsing variations data:', error);
        return;
    }

    const addToCartButton = productContainer.find('.add-to-cart-button');
    const priceContainer = productContainer.find('.variation-price');
    const stockStatusContainer = productContainer.find('.stock-status-badge');
    const selectedAttributes = {};

    function areAllAttributesSelected() {
        const requiredAttributes = $('.variation-wrapper').length;
        const selectedCount = Object.keys(selectedAttributes).length;
        return requiredAttributes === selectedCount;
    }

    function updateAddToCartButton(matchingVariation) {
        const allSelected = areAllAttributesSelected();
        const isInvalid = !allSelected || !matchingVariation;

        // Disable button if not all attributes selected or variation is out of stock
        addToCartButton.prop('disabled', isInvalid);
        addToCartButton.toggleClass('opacity-50 cursor-not-allowed', isInvalid);

        // Update button text based on status
        addToCartButton.text(getTranslation(
            isInvalid ? 'Select options' : 'Add to cart', 'woocommerce'));
    }

    function updatePriceAndStock(matchingVariation) {
        if(matchingVariation) { // variation is defined (with price)
            priceContainer.html(matchingVariation.display_price);

            if(matchingVariation.is_in_stock) {
                // Show stock quantity if available
                if (matchingVariation.stock_quantity) {
                    stockStatusContainer.text(
                        `${getTranslation('In stock', 'woocommerce')} (${matchingVariation.stock_quantity} ${getTranslation('Units in stock', 'woocommerce')})`
                    );
                }
                else { // in-stock without quantity
                stockStatusContainer.removeClass('hidden bg-red-100 text-red-800')
                    .addClass('bg-green-100 text-green-800')
                    .text(getTranslation('In stock', 'woocommerce'));
                }
                return;
            }
        }
        // fallbak to out-of-stock
        stockStatusContainer.removeClass('hidden bg-green-100 text-green-800')
            .addClass('bg-red-100 text-red-800')
            .text(getTranslation('Out of stock', 'woocommerce'));
        priceContainer.html('');
    }

    function updateVariationSku(matchingVariationForSku) {
        const skuElement = $('#sku_inside_desc');
        const variationSku = variations_skus[matchingVariationForSku.variation_id];
        
        if (skuElement && variationSku) {
            skuElement.text(variationSku);

            // Update URL with SKU
            const currentPath = window.location.pathname;
            const pathParts = currentPath.split('/').filter(Boolean);
                
            // Remove existing SKU if present
            if (pathParts.length > 2) {
                pathParts.pop();
            }
                
            // Add new SKU
            const newUrl = '/' + pathParts.join('/') + '/' + variationSku;
            // Use replaceState instead of pushState to avoid growing browser history
            window.history.replaceState({}, '', newUrl);
        }
    }

    function disableUnavailableAttributes() {
        $('.variation-wrapper').each(function() {
            const wrapper = $(this);
            const attributeName = wrapper.data('attribute');

            wrapper.find('button').each(function() {
                const value = $(this).data('value');
                const isAvailable = variations.some(variation => 
                    variation.attributes[`attribute_${attributeName}`] === value &&
                    Object.entries(selectedAttributes).every(([attr, val]) => 
                        attr === attributeName || variation.attributes[`attribute_${attr}`] === val
                    )
                );

                $(this).prop('disabled', !isAvailable);
                $(this).toggleClass('opacity-50 cursor-not-allowed', !isAvailable);
            });
        });
    }

    function updateSelectedValue(wrapper, label) {
        const selectedValueEl = wrapper.find('.selected-value');
        selectedValueEl.text(label || '');
    }

    function showSelectionError(wrapper) {
        wrapper.find('.selected-value')
            .text('Please select an option')
            .addClass('text-red-500')
            .removeClass('text-gray-500');
        
        setTimeout(() => {
            wrapper.find('.selected-value')
                .removeClass('text-red-500')
                .addClass('text-gray-500');
        }, 2000);
    }

    function updateVariationImage(matchingVariation) {
        if (!matchingVariation || !matchingVariation.image) return;

        // Dispatch custom event with variation data
        const event = new CustomEvent('variation_selected', {
            detail: matchingVariation
        });
        document.dispatchEvent(event);
    }

    $('.variation-wrapper button').click(function(e) {
        e.preventDefault();
        const $this = $(this);
        
        if ($this.prop('disabled')) {
            return;
        }

        const wrapper = $this.closest('.variation-wrapper');
        const attributeName = wrapper.data('attribute');
        const value = $this.data('value');
        const label = $this.data('label');

        // Check if button is already selected
        const isCurrentlySelected = $this.hasClass('selected-variation');

        // Remove selection from all buttons in this wrapper
        wrapper.find('button').each(function() {
            const $btn = $(this);
            $btn.removeClass('selected-variation');
            $btn.css('borderColor', '');
            $btn.find('.selected-check').removeClass('opacity-100').addClass('opacity-0');
        });

        // If button wasn't selected before, select it. If it was, leave it unselected
        if (!isCurrentlySelected) {
            selectedAttributes[attributeName] = value;
            $this.addClass('selected-variation');
            $this.css('borderColor', 'var(--color-primary)');
            $this.find('.selected-check').removeClass('opacity-0').addClass('opacity-100');
            updateSelectedValue(wrapper, label);
        } else {
            // Remove the attribute from selectedAttributes
            delete selectedAttributes[attributeName];
            updateSelectedValue(wrapper, '');
        }

        // Find matching variation
        const matchingVariation = variations.find(variation => 
            Object.entries(selectedAttributes).every(([attr, val]) => 
                variation.attributes[`attribute_${attr}`] === val
            )
        );
        // Find matching variation for SKU update (including out of stock)
        const matchingVariationForSku = variations.find(variation => 
            // Only check if selected attributes match the variation attributes
            Object.entries(selectedAttributes).every(([attr, val]) => 
                variation.attributes[`attribute_${attr}`] === val
            )
        );

        // If no attributes are selected, show original product price and hide stock status
        if (Object.keys(selectedAttributes).length === 0) {
            priceContainer.html(productContainer.find('.variation-price').data('original-price'));
            stockStatusContainer.addClass('hidden');
            
            // Reset SKU to original product SKU
            const originalSku = $('#sku_inside_desc').data('original-sku');
            if (originalSku) {
                $('#sku_inside_desc').text(originalSku);
            }
            // Reset URL to original product URL without SKU
            const pathParts = window.location.pathname.split('/').filter(Boolean);
            if (pathParts.length > 2) {
                pathParts.pop();
                const newUrl = '/' + pathParts.join('/');
                window.history.pushState({}, '', newUrl);
            }
        } else {
            // Update UI with variation data
            updatePriceAndStock(matchingVariation);
            updateVariationSku(matchingVariationForSku);
        }

        // Update other UI elements
        updateAddToCartButton(matchingVariation);
        updateVariationImage(matchingVariation);
        //disableUnavailableAttributes();

        // Add selection animation
        $this.addClass('scale-105');
        setTimeout(() => $this.removeClass('scale-105'), 200);
    });

    // Color swatch hover effects
    $('.variation-button[style*="background-color"]').hover(
        function() {
            const label = $(this).data('label');
            const wrapper = $(this).closest('.variation-wrapper');
            wrapper.find('.color-tooltip').text(label);
        },
        function() {
            const wrapper = $(this).closest('.variation-wrapper');
            const selectedButton = wrapper.find('.selected-variation');
            const label = selectedButton.length ? selectedButton.data('label') : '';
            wrapper.find('.color-tooltip').text(label);
        }
    );

    // Add keyboard navigation
    $('.variation-button').on('keydown', function(e) {
        const $this = $(this);
        const wrapper = $this.closest('.variation-wrapper');
        const buttons = wrapper.find('.variation-button:not(:disabled)');
        const currentIndex = buttons.index($this);

        switch(e.keyCode) {
            case 37: // Left arrow
            case 38: // Up arrow
                e.preventDefault();
                buttons.eq((currentIndex - 1 + buttons.length) % buttons.length).focus();
                break;
            case 39: // Right arrow
            case 40: // Down arrow
                e.preventDefault();
                buttons.eq((currentIndex + 1) % buttons.length).focus();
                break;
        }
    });

    addToCartButton.click(function(e) {
        e.preventDefault();
        
        if ($(this).prop('disabled')) {
            return;
        }

        const matchingVariation = variations.find(variation => 
            Object.entries(selectedAttributes).every(([attr, val]) => 
                variation.attributes[`attribute_${attr}`] === val
            )
        );

        if (!matchingVariation || !matchingVariation.is_in_stock) {
            return;
        }

        const variationId = matchingVariation.variation_id;
        const quantityInput = document.querySelector('.quantity-input');
        const quantity = quantityInput ? parseInt(quantityInput.value) : 1;

        // console.log('Adding to cart:', { 
        //     variationId, 
        //     quantity,
        //     inputValue: quantityInput ? quantityInput.value : 'not found'
        // });

        $.ajax({
            type: 'POST',
            url: wc_add_to_cart_params.ajax_url,
            data: {
                action: 'woocommerce_add_to_cart',
                product_id: variationId,
                quantity: quantity
            },
            success: function(response) {
                if (response.error) {
                    // console.log('Error:', response.message);
                } else {
                    // make sure the variation_id is updated
                    addToCartButton.data('variation_id', variationId);
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, addToCartButton]);
                }
            },
            error: function(response) {
                // console.log('AJAX error:', response);
            }
        });
    });

    // At the start of your script, log the variations data
    //console.log('All Variations:', variations);

    // Quantity button functionality
    document.addEventListener('DOMContentLoaded', function() {
        const quantityButton = document.querySelector('.quantity-button');
        const addToCartButton = document.querySelector('.single_add_to_cart_button');
        
        if (quantityButton && addToCartButton) {
            // Listen for quantity changes
            quantityButton.addEventListener('change', function(e) {
                const newQuantity = e.target.value;
                // console.log('Quantity Input Changed:', newQuantity);
                addToCartButton.setAttribute('data-quantity', newQuantity);
            });

            // Listen for increment/decrement buttons
            quantityButton.querySelectorAll('button').forEach(button => {
                button.addEventListener('click', function() {
                    const input = quantityButton.querySelector('input');
                    const newQuantity = input.value;
                    // console.log('Quantity Button Clicked:', newQuantity);
                    addToCartButton.setAttribute('data-quantity', newQuantity);
                });
            });
        }
    });

    // When a variation is selected
    function updateVariationSelection(matchingVariation) {
        if (matchingVariation) {
            const addToCartBtn = document.querySelector('.add-to-cart-button');
            const currentQuantity = addToCartBtn.getAttribute('data-quantity') || '1';
            // console.log('Variation Selected - Current Quantity:', currentQuantity);
            
            // Your existing variation update code...
        }
    }

    // When a variation is selected and the button becomes enabled
    function enableAddToCartButton(variationId) {
        const addToCartBtn = document.querySelector('.add-to-cart-button');
        if (addToCartBtn) {
            const currentQuantity = addToCartBtn.getAttribute('data-quantity') || '1';
            
            addToCartBtn.disabled = false;
            addToCartBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            addToCartBtn.classList.add('hover:bg-[var(--color-secondary)]');
            addToCartBtn.textContent = getTranslation('Add to cart', 'woocommerce');
            addToCartBtn.setAttribute('data-variation_id', variationId);
            addToCartBtn.setAttribute('data-quantity', currentQuantity);
        }
    }

    // When no variation is selected or variation is not available
    function disableAddToCartButton() {
        const addToCartBtn = document.querySelector('.add-to-cart-button');
        if (addToCartBtn) {
            addToCartBtn.disabled = true;
            addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed');
            addToCartBtn.classList.remove('hover:bg-[var(--color-secondary)]');
            addToCartBtn.textContent = getTranslation('Select options', 'woocommerce');
            addToCartBtn.removeAttribute('data-variation_id');
        }
    }

    // Initialize the variation handling
    document.addEventListener('DOMContentLoaded', function() {
        const variableProduct = document.querySelector('.variable-product');
        
        if (variableProduct) {
            // Your existing variation selection code...

            // When a valid variation is found
            function handleValidVariation(variation) {
                // Your existing variation handling code...
                enableAddToCartButton(variation.variation_id);
            }

            // When no valid variation is found
            function handleNoVariation() {
                // Your existing no variation handling code...
                disableAddToCartButton();
            }
        }
    });

    // Pre-select variation if URL contains SKU
    if (typeof initial_variation_id !== 'undefined') {
        const variation = variations.find(v => v.variation_id === initial_variation_id);
        if (variation) {
            // Select each attribute for this variation
            Object.entries(variation.attributes).forEach(([attr, value]) => {
                const attributeName = attr.replace('attribute_', '');
                const wrapper = $(`.variation-wrapper[data-attribute="${attributeName}"]`);
                const button = wrapper.find(`button[data-value="${value}"]`);
                if (button.length) {
                    button.click();
                }
            });
        }
    }
});
