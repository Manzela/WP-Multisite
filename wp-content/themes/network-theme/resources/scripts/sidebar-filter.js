/**
 * This script manages the sidebar filter for the store.
 * the script loaded from app.js
 * 
 * Author: Edward Ziadeh
 * Date: October 30, 2024
 */
jQuery(function($) {
    let isSubmitting = false;

    $('#apply-all-filters').on('click', function() {
        if (isSubmitting) return;
        isSubmitting = true;

        try {
            const mainForm = $('#filter-form');
            
            // Handle price filter values
            const minPrice = $('.price_slider_amount #min_price').val();
            const maxPrice = $('.price_slider_amount #max_price').val();
            
            // Handle attribute checkboxes
            const attributeGroups = {};
            
            // Group checkbox values by their filter name
            mainForm.find('input[type="checkbox"]:checked').each(function() {
                const name = $(this).attr('name');
                if (!attributeGroups[name]) {
                    attributeGroups[name] = [];
                }
                attributeGroups[name].push($(this).val());
            });
            
            // Clear existing hidden inputs
            mainForm.find('input[type="hidden"][name^="filter_"]').remove();
            
            // Create new hidden inputs with combined values
            Object.entries(attributeGroups).forEach(([name, values]) => {
                const combinedValue = values.join(',');
                mainForm.append(`<input type="hidden" name="${name}" value="${combinedValue}">`);
            });
            
            // Handle price inputs
            let minPriceInput = mainForm.find('input[name="min_price"]');
            let maxPriceInput = mainForm.find('input[name="max_price"]');
            
            if (minPriceInput.length === 0) {
                mainForm.append('<input type="hidden" name="min_price">');
                minPriceInput = mainForm.find('input[name="min_price"]');
            }
            if (maxPriceInput.length === 0) {
                mainForm.append('<input type="hidden" name="max_price">');
                maxPriceInput = mainForm.find('input[name="max_price"]');
            }
            
            minPriceInput.val(minPrice);
            maxPriceInput.val(maxPrice);
            
            // Remove checkbox inputs before submit
            mainForm.find('input[type="checkbox"]').prop('disabled', true);
            
            // Submit the form
            mainForm[0].submit();
        } catch (error) {
            console.error('Error applying filters:', error);
            isSubmitting = false;
        }
    });

    // Handle form submissions
    $('.price_slider_form, #filter-form').on('submit', function(e) {
        if (isSubmitting) return;
        e.preventDefault();
        $('#apply-all-filters').click();
    });

    // Reset submission flag when leaving page
    $(window).on('unload', function() {
        isSubmitting = false;
    });
});