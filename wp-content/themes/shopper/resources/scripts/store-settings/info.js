/**
 * This script manages the info settings for the store.
 * the script loaded from setup.php
 * 
 * Author: Edward Ziadeh
 * Date: October 30, 2024
 */
(function($) {
	$(document).ready(function() {
		// Handle closed day toggle
		$('.day-closed-toggle').on('change', function() {
			const periodsContainer = $(this).closest('.day-times').find('.periods-container');
			periodsContainer.toggleClass('hidden', this.checked);
		});

		// Add new period for breaks
		$(document).on('click', '.add-period', function(e) {
			e.preventDefault();
			const dayRow = $(this).closest('.day-row');
			const day = dayRow.data('day');
			const periodsContainer = $(this).closest('.periods-container');
			const existingPeriods = periodsContainer.find('.period-row');
			const newIndex = existingPeriods.length;
			
			const newPeriodHtml = `
				<div class="period-row" data-period-index="${newIndex}">
					<div class="time-inputs">
						<input 
							type="time" 
							name="store_settings[store_info][hours][${day}][periods][${newIndex}][open]" 
							value="17:00"
							class="time-input"
						>
						<span class="time-separator">-</span>
						<input 
							type="time" 
							name="store_settings[store_info][hours][${day}][periods][${newIndex}][close]" 
							value="21:00"
							class="time-input"
						>
						<button type="button" class="button remove-period" title="Remove this period">×</button>
					</div>
				</div>
			`;
			
			$(this).parent().before(newPeriodHtml);
		});

		// Remove period
		$(document).on('click', '.remove-period', function(e) {
			e.preventDefault();
			$(this).closest('.period-row').remove();
			// Re-index remaining periods
			reindexPeriods($(this).closest('.day-row'));
		});

		// Copy Sunday hours to all days
		$('#copy-to-all').on('click', function(e) {
			e.preventDefault();
			const allDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
			copySundayHours(allDays);
		});

		function reindexPeriods(dayRow) {
			const day = dayRow.data('day');
			const periods = dayRow.find('.period-row');
			
			periods.each(function(index) {
				$(this).attr('data-period-index', index);
				$(this).find('input[type="time"]').each(function() {
					const name = $(this).attr('name');
					const newName = name.replace(/\[periods\]\[\d+\]/, `[periods][${index}]`);
					$(this).attr('name', newName);
				});
			});
		}

		function copySundayHours(targetDays) {
			const sundayRow = $('.day-row[data-day="sunday"]');
			const sundayClosed = sundayRow.find('input[name="store_settings[store_info][hours][sunday][closed]"]').prop('checked');
			
			targetDays.forEach(day => {
				const targetRow = $(`.day-row[data-day="${day}"]`);
				
				// Set closed status
				const closedCheckbox = targetRow.find(`input[name="store_settings[store_info][hours][${day}][closed]"]`);
				closedCheckbox.prop('checked', sundayClosed);
				closedCheckbox.trigger('change');
				
				if (!sundayClosed) {
					// Clear existing periods
					targetRow.find('.period-row').remove();
					
					// Copy each period from Sunday
					sundayRow.find('.period-row').each(function(index) {
						const openTime = $(this).find('input[type="time"]').first().val();
						const closeTime = $(this).find('input[type="time"]').last().val();
						
						const periodHtml = `
							<div class="period-row" data-period-index="${index}">
								<div class="time-inputs">
									<input 
										type="time" 
										name="store_settings[store_info][hours][${day}][periods][${index}][open]" 
										value="${openTime}"
										class="time-input"
									>
									<span class="time-separator">-</span>
									<input 
										type="time" 
										name="store_settings[store_info][hours][${day}][periods][${index}][close]" 
										value="${closeTime}"
										class="time-input"
									>
									${index > 0 ? '<button type="button" class="button remove-period" title="Remove this period">×</button>' : ''}
								</div>
							</div>
						`;
						
						targetRow.find('.period-actions').before(periodHtml);
					});
				}
			});
		}
	});
})(jQuery);
