/**
 * This script manages the general settings for the store.
 * the script loaded from setup.php
 * 
 * Author: Edward Ziadeh
 * Date: October 30, 2024
 * 
 * Updated: December 25, 2024:
 * - Added store_logo field
 * - Refactored media upload/remove to use a shared handler
 * 
 */
(function($) {
	$(document).ready(function() {
		var banner_frame, logo_frame; // Separate file frames for banner and logo
		var wp_media_post_id = wp.media.model.settings.post.id;
		var set_to_post_id = 0;

		function handleMediaUpload(frame, buttonId, inputId, previewId, removeButtonId, title) {
			$(buttonId).on('click', function(event) {
				event.preventDefault();

				if (frame) {
					frame.uploader.uploader.param('post_id', set_to_post_id);
					frame.open();
					return;
				}

				wp.media.model.settings.post.id = set_to_post_id;

				frame = wp.media({
					title: 'Select a ' + title,
					button: {
						text: 'Use this image',
					},
					multiple: false
				});

				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					$(inputId).val(attachment.id);
					$(previewId).attr('src', attachment.url).show();
					$(removeButtonId).show();
					wp.media.model.settings.post.id = wp_media_post_id;
				});

				frame.open();
			});

			$(removeButtonId).on('click', function(event) {
				event.preventDefault();
				console.log(title + ' remove button clicked');
				$(inputId).val('');
				$(previewId).hide();
				$(this).hide();
			});
		}

		// Initialize media uploaders
		handleMediaUpload(
			banner_frame, 
			'#store_banner_button', 
			'#store_banner', 
			'#store_banner_preview', 
			'#store_banner_remove_button', 
			'banner image'
		);

		handleMediaUpload(
			logo_frame, 
			'#store_logo_button', 
			'#store_logo', 
			'#store_logo_preview', 
			'#store_logo_remove_button', 
			'logo image'
		);

		$(window).on('beforeunload', function() {
			wp.media.model.settings.post.id = wp_media_post_id;
		});

		// Initialize color pickers
		$('.color-picker').wpColorPicker();

		// Example for general.js
		document.addEventListener('DOMContentLoaded', function() {
			const form = document.querySelector('#general-settings-form');
			
			if (form) {
				form.addEventListener('submit', async (e) => {
					e.preventDefault();
					
					try {
						const response = await fetch(form.action, {
							method: 'POST',
							body: new FormData(form),
						});
						
						const data = await response.json();
						
						if (data.success) {
							handleFormSuccess(data);
						}
					} catch (error) {
						console.error('Error saving settings:', error);
					}
				});
			}
		});
	});
})(jQuery);

/**********************************/
/* keep the original code for changes review:
(function($) {
	$(document).ready(function() {
		// Single handler for adding store general settings
		// Media uploader
		var file_frame;
		var wp_media_post_id = wp.media.model.settings.post.id;
		var set_to_post_id = 0;

		$('#store_banner_button').on('click', function(event) {
			event.preventDefault();
			console.log('Store banner button clicked');

			if (file_frame) {
				file_frame.uploader.uploader.param('post_id', set_to_post_id);
				file_frame.open();
				return;
			} else {
				wp.media.model.settings.post.id = set_to_post_id;
			}

			file_frame = wp.media.frames.file_frame = wp.media({
				title: 'Select an image',
				button: {
					text: 'Use this image',
				},
				multiple: false
			});

			file_frame.on('select', function() {
				var attachment = file_frame.state().get('selection').first().toJSON();
				$('#store_banner').val(attachment.id);
				$('#store_banner_preview').attr('src', attachment.url).show();
				$('#store_banner_remove_button').show();
				wp.media.model.settings.post.id = wp_media_post_id;
			});

			file_frame.open();
		});

		$('#store_banner_remove_button').on('click', function(event) {
			event.preventDefault();
			console.log('Store banner remove button clicked');
			$('#store_banner').val('');
			$('#store_banner_preview').hide();
			$(this).hide();
		});

		$(window).on('beforeunload', function() {
			wp.media.model.settings.post.id = wp_media_post_id;
		});

		

		// Initialize color pickers
		$('.color-picker').wpColorPicker();
	});
})(jQuery);
*/