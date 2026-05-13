/**
 * This script manages the delivery rules settings for the store.
 * It includes functionalities to add, remove, and toggle delivery rules.
 * the script loaded from setup.php
 * 
 * Author: Edward Ziadeh
 * Date: October 30, 2024
 */

(function ($) {
	$(document).ready(function () {

		const initSelect2 = () => {
			console.log('Initializing Select2');
			$('.city-select, .business-type-select').select2({
				width: '100%',
				placeholder: 'Select options',
				allowClear: true
			});

		};

		initSelect2();

		let clickCount = 0;

		// Toggle rule content
		$(document).on('click', '.toggle-rule', function (e) {
			e.preventDefault();
			const $content = $(this).closest('.delivery-rule').find('.delivery-rule-content');
			const $icon = $(this).find('.dashicons');

			$content.slideToggle(300);
			$icon.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
		});

		$('#add-delivery-rule').off('click').on('click', function () {
			clickCount++;

			var index = $('#delivery-rules-container .delivery-rule').length;
			var editorId = `delivery_additional_text_${index}`;
			var citiesOptions = storeSettingsData.cities.map(city => `<option value="${city}">${city}</option>`).join('');
			var newRule = `
				<div class="delivery-rule">
					<div class="delivery-rule-header">
						<span class="rule-title">${storeSettingsData.translation.delivery_rule} #${index + 1}</span>
						<button type="button" class="toggle-rule">
							<span class="dashicons dashicons-arrow-down-alt2"></span>
						</button>
					</div>
					<div class="delivery-rule-content">
						<label>${storeSettingsData.translation.active}</label>
						<input type="checkbox" name="store_settings[delivery_rules][${index}][active]" value="1">

						<label>${storeSettingsData.translation.minimum_order}</label>
						<input type="number" name="store_settings[delivery_rules][${index}][min_order]" value="">

						<label>${storeSettingsData.translation.cities_label}</label>
						<select class="city-select" name="store_settings[delivery_rules][${index}][city][]" multiple>
							${citiesOptions}
						</select>

						<label>${storeSettingsData.translation.shipping_cost}</label>
						<input type="number" name="store_settings[delivery_rules][${index}][shipping_cost]" value="">

						<label>${storeSettingsData.translation.additional_text}</label>
						<div class="wp-editor-container">
							<textarea id="${editorId}" name="store_settings[delivery_rules][${index}][additional_text]"></textarea>
						</div>

						<button type="button" class="remove-delivery-rule">${storeSettingsData.translation.remove_delivery_rule}</button>
					</div>
				</div>
			`;
			$('#delivery-rules-container').append(newRule);
			initSelect2();

			// Initialize WP Editor
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

		// Remove delivery rule handler
		$(document).on('click', '.remove-delivery-rule', function () {
			var $deliveryRule = $(this).closest('.delivery-rule');
			var editorId = $deliveryRule.find('textarea').attr('id');

			// Remove TinyMCE instance if it exists
			if (editorId && wp.editor && wp.editor.remove) {
				wp.editor.remove(editorId);
			}

			$deliveryRule.remove();
		});
	});
})(jQuery);