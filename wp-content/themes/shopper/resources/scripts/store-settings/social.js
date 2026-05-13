/**
 * This script manages the social settings for the store.
 * the script loaded from setup.php
 * 
 * Author: Edward Ziadeh
 * Date: October 30, 2024
 */
(function($) {
	$(document).ready(function() {
		// Single handler for adding social links
		$('#add-social-link').on('click', function(e) {
			e.preventDefault();
			const container = $('#social-links-container');
			const index = container.find('.social-link-entry').length;
			
			// Clone the template
			const template = document.querySelector('#social-link-template');
			const clone = template.content.cloneNode(true);
			
			// Update the index in the cloned elements
			$(clone).find('[name*="INDEX"]').each(function() {
				const newName = $(this).attr('name').replace('INDEX', index);
				$(this).attr('name', newName);
			});
			
			// Append the new entry
			container.append(clone);
		});
        // Remove social link
		$(document).on('click', '.remove-social-link', function(e) {
			e.preventDefault();
			$(this).closest('.social-link-entry').remove();
			
			// Update indices for remaining entries
			$('#social-links-container .social-link-entry').each(function(index) {
				$(this).find('[name*="store_settings[social]"]').each(function() {
					const newName = $(this).attr('name').replace(/\[\d+\]/, '[' + index + ']');
					$(this).attr('name', newName);
				});
			});
		});
	});
})(jQuery);
