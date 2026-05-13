/**
 * This script manages the policies settings for the store.
 * the script loaded from setup.php
 * 
 * Author: Edward Ziadeh
 * Date: October 30, 2024
 */
(function($) {
	$(document).ready(function() {
		// Single handler for adding policies
		$('#add-policy').on('click', function() {
			var index = $('#policies-container .policy-entry').length;
			var editorId = `policy_editor_${index}`;
			
			var policyEntry = `
				<div class="policy-entry">
					<label>${storeSettingsData.translation.title}</label>
					<input type="text" name="store_settings[policies][${index}][title]" value="">

					<label>${storeSettingsData.translation.body}</label>
					<div class="wp-editor-container">
						<textarea id="${editorId}" name="store_settings[policies][${index}][body]"></textarea>
					</div>

					<button type="button" class="remove-policy">${storeSettingsData.translation.remove_policy}</button>
				</div>
			`;
			
			$('#policies-container').append(policyEntry);

			// Initialize new WP editor with full settings
			wp.editor.initialize(editorId, {
				tinymce: {
					wpautop: true,
					plugins: 'charmap colorpicker compat3x directionality fullscreen hr image lists media paste tabfocus textcolor wordpress wpautoresize wpdialogs wpeditimage wpemoji wpgallery wplink wptextpattern wpview',
					toolbar1: 'formatselect bold italic underline strikethrough | bullist numlist | blockquote hr wp_more | alignleft aligncenter alignright | link unlink | fullscreen | wp_adv',
					toolbar2: 'forecolor backcolor | pastetext removeformat charmap | outdent indent | undo redo | wp_help'
				},
				quicktags: true,
				mediaButtons: true,
			});
		});

		// Remove policy handler with editor cleanup
		$(document).on('click', '.remove-policy', function() {
			var $policyEntry = $(this).closest('.policy-entry');
			var editorId = $policyEntry.find('textarea').attr('id');
			
			// Remove TinyMCE instance if it exists
			if (editorId && wp.editor && wp.editor.remove) {
				wp.editor.remove(editorId);
			}
			
			$policyEntry.remove();
		});
	});
})(jQuery);
