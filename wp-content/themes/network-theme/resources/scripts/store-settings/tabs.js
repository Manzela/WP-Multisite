/**
 * This script manages the tabs for the store settings.
 * the script loaded from setup.php
 * 
 * Author: Edward Ziadeh
 * Date: October 30, 2024
 */
document.addEventListener('DOMContentLoaded', function() {
	const tabLinks = document.querySelectorAll('.nav-tab');
	const tabContents = document.querySelectorAll('.tab-content');
	const form = document.getElementById('store-settings-form');

	// Function to show a specific tab
	function showTab(tabId) {
		// Hide all tabs
		tabContents.forEach(content => {
			content.style.display = 'none';
		});
		
		// Remove active class from all tab links
		tabLinks.forEach(link => {
			link.classList.remove('nav-tab-active');
		});

		// Show selected tab
		const selectedTab = document.getElementById(tabId);
		if (selectedTab) {
			selectedTab.style.display = 'block';
		}

		// Add active class to selected tab link
		const selectedLink = document.querySelector(`a[href="#${tabId}"]`);
		if (selectedLink) {
			selectedLink.classList.add('nav-tab-active');
		}

		// Update hidden input
		let hiddenInput = form.querySelector('input[name="store_settings_current_tab"]');
		if (!hiddenInput) {
			hiddenInput = document.createElement('input');
			hiddenInput.type = 'hidden';
			hiddenInput.name = 'store_settings_current_tab';
			form.appendChild(hiddenInput);
		}
		hiddenInput.value = tabId;
	}

	// Handle tab clicks
	tabLinks.forEach(tab => {
		tab.addEventListener('click', function(e) {
			e.preventDefault();
			const tabId = this.getAttribute('href').substring(1);
			showTab(tabId);
			
			// Update URL without page reload
			const newUrl = new URL(window.location);
			newUrl.searchParams.set('tab', tabId);
			window.history.pushState({}, '', newUrl);
		});
	});

	// Show initial tab
	const urlParams = new URLSearchParams(window.location.search);
	const currentTab = urlParams.get('tab') || 'general';
	showTab(currentTab);
});
