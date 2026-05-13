<div class="max-w-full">
    <p class="notice notice-info" style="height: fit-content; font-size: 1rem; align-content: center;">
        IMPORTANT!<br>
        1. Please ensure that the following settings are properly set on the network level:<br>
        &nbsp;&nbsp;&nbsp;&nbsp;- Network Primary Color<br>
        &nbsp;&nbsp;&nbsp;&nbsp;- Network Secondary Color<br>
        &nbsp;&nbsp;&nbsp;&nbsp;- Network Logo<br>
        &nbsp;&nbsp;&nbsp;&nbsp;- Network Bing API Key<br>
        <br>
        2. Please ensure that each subdomain is synced with the respective GMB profile.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;Meaning, having proper Street Address, City, Postcode / ZIP, and Coordinates (MOST IMPORTANT).<br>
        <br>
        3. The company display name is defined by the blogname of the main subdomain.<br>
        <br>
        4. Go to Merchantor and complete the "Re-Authenticate" process. (required once per domain - refresh tokens to include new scopes)<br>
        <br>
        5. Please fill in the following fields.<br>
    </p>

    <!-- first section: fields -->
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="gsc_property_name">Property Name on GSC</label>
                </th>
                <td>
                    @php
                        $gsc_property_name = $network_options['gsc_property_name'] ?? '';
                    @endphp
                    <input 
                        type="text" 
                        id="gsc_property_name" 
                        name="network_store_settings[gsc_property_name]" 
                        value="{{ esc_attr($gsc_property_name) }}"
                        class="regular-text"
                        placeholder="e.g., example-tenant-cc.shop or https://example-tenant-cc.shop/"
                    >
                    <p class="description">
                        Enter the exact property name as it appears in Google Search Console
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="is_subdomain">Is subdomain</label>
                </th>
                <td>
                    @php
                        $is_subdomain = $network_options['is_subdomain'] ?? '';
                    @endphp
                    <input 
                        type="checkbox" 
                        id="is_subdomain" 
                        name="network_store_settings[is_subdomain]" 
                        value="1"
                        {{ checked($is_subdomain, 1, false) }}
                    >
                    <p class="description">Check this only in "example-network.shop"</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="launch_date">Launch date</label>
                </th>
                <td>
                    @php
                        $launch_date = $network_options['launch_date'] ?? '';
                    @endphp
                    <input 
                        type="text" 
                        id="launch_date" 
                        name="network_store_settings[launch_date]" 
                        value="{{ esc_attr($launch_date) }}"
                        class="regular-text"
                        placeholder="e.g., July 2024"
                    >
                </td>
            </tr>
        </tbody>
    </table>
    

    <!-- second section: Generate Buttons -->
    <div class="mb-8 p-6 bg-gray-50 border border-gray-300 rounded-lg" id="generate-buttons-section">
        <div class="mt-4">
            <button type="button" class="inline-flex items-center px-4 py-2 mr-3 mb-3 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="generate-company-details">
                Generate Company Details
            </button>
            
            <button type="button" class="inline-flex items-center px-4 py-2 mr-3 mb-3 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="generate-store-locations">
                Generate Store Locations
            </button>

            <button type="button" class="inline-flex items-center px-4 py-2 mr-3 mb-3 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="fetch-total-impressions">
                Fetch Total Impressions
            </button>

            <button type="button" class="inline-flex items-center px-4 py-2 mr-3 mb-3 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="generate-product-discovery">
                Generate Product Discovery
            </button>

            <button type="button" class="inline-flex items-center px-4 py-2 mr-3 mb-3 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="fetch-events">
                Fetch Events
            </button>
        </div>
    </div>

    <!-- third section: JSON Display Area -->
    <div class="p-6 bg-gray-50 border border-gray-300 rounded-lg">
        <div class="mt-4">
            <div id="json-content" class="bg-white border border-gray-300 rounded-md p-4 min-h-[200px] max-h-[500px] overflow-y-auto font-mono text-xs leading-relaxed whitespace-pre-wrap break-words">
                <p class="text-gray-500 italic text-center m-0 py-12">No data generated yet. Click a button above to generate JSON data.</p>
            </div>
            
            <div class="json-actions mt-4 text-right" style="display: none;">
                <button type="button" class="inline-flex items-center px-3 py-2 mr-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="copy-json">Copy JSON</button>
                <button type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="clear-json">Clear</button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Generate Company Details
    $('#generate-company-details').on('click', function() {
        const button = $(this);
        const originalText = button.text();
        
        // Show loading state
        button.prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_company_details',
                nonce: '<?php echo wp_create_nonce("growth_dashboard_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    displayJsonData(response.data);
                } else {
                    showError('Failed to generate company details: ' + response.data);
                }
            },
            error: function() {
                showError('Failed to generate company details. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Generate Store Locations
    $('#generate-store-locations').on('click', function() {
        const button = $(this);
        const originalText = button.text();
        
        // Show loading state
        button.prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_store_locations',
                nonce: '<?php echo wp_create_nonce("growth_dashboard_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    displayJsonData(response.data);
                } else {
                    showError('Failed to generate store locations: ' + response.data);
                }
            },
            error: function() {
                showError('Failed to generate store locations. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });

        // Fetch Total Impressions
        $('#fetch-total-impressions').on('click', function() {
        const button = $(this);
        const originalText = button.text();
        
        // Show loading state
        button.prop('disabled', true).text('Fetching...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_total_impressions',
                nonce: '<?php echo wp_create_nonce("growth_dashboard_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    displayJsonData(response.data);
                } else {
                    showError('Failed to fetch total impressions: ' + response.data);
                }
            },
            error: function() {
                showError('Failed to fetch total impressions. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Generate Product Discovery
    $('#generate-product-discovery').on('click', function() {
        const button = $(this);
        const originalText = button.text();
        
        // Show loading state
        button.prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_product_discovery',
                nonce: '<?php echo wp_create_nonce("growth_dashboard_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    displayJsonData(response.data);
                } else {
                    showError('Failed to generate product discovery data: ' + response.data);
                }
            },
            error: function() {
                showError('Failed to generate product discovery data. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Fetch Events
    $('#fetch-events').on('click', function() {
        const button = $(this);
        const originalText = button.text();
        
        // Show loading state
        button.prop('disabled', true).text('Fetching...');
        
        // Show progress bar
        showProgressBar();
        
        // Start processing day by day
        processEventsDayByDay(0, 30);
        
        function processEventsDayByDay(currentDay, totalDays) {
            if (currentDay >= totalDays) {
                // All done
                hideProgressBar();
                button.prop('disabled', false).text(originalText);
                return;
            }
            
            // Update progress
            updateProgress(currentDay, totalDays);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'fetch_events',
                    nonce: '<?php echo wp_create_nonce("growth_dashboard_nonce"); ?>',
                    current_day: currentDay
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.completed) {
                            // All done, display final results
                            displayJsonData(response.data.events);
                            hideProgressBar();
                            button.prop('disabled', false).text(originalText);
                        } else {
                            // Process next day
                            setTimeout(() => {
                                processEventsDayByDay(response.data.next_day, totalDays);
                            }, 100); // Small delay to prevent overwhelming the server
                        }
                    } else {
                        showError('Failed to fetch events: ' + response.data);
                        hideProgressBar();
                        button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    showError('Failed to fetch events. Please try again.');
                    hideProgressBar();
                    button.prop('disabled', false).text(originalText);
                }
            });
        }
    });

    // Progress bar functions
    function showProgressBar() {
        const progressHtml = `
            <div id="progress-container" class="mb-8 p-6 bg-blue-50 border border-blue-300 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-blue-700">Fetching Events...</span>
                    <span id="progress-text" class="text-sm text-blue-500">0/30 days</span>
                </div>
                <div class="w-full bg-blue-200 rounded-full h-2">
                    <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <div id="progress-details" class="text-xs text-blue-500 mt-1">Processing day 1...</div>
            </div>
        `;
        
        // Insert between the two sections
        $('#generate-buttons-section').after(progressHtml);
    }

    function hideProgressBar() {
        $('#progress-container').remove();
    }

    function updateProgress(currentDay, totalDays) {
        const percentage = ((currentDay + 1) / totalDays) * 100;
        $('#progress-bar').css('width', percentage + '%');
        $('#progress-text').text((currentDay + 1) + '/' + totalDays + ' days');
        $('#progress-details').text(`Processing day ${currentDay + 1}...`);
    }

    // Copy JSON
    $('#copy-json').on('click', function() {
        const jsonContent = $('#json-content').text();
        navigator.clipboard.writeText(jsonContent).then(function() {
            showSuccess('JSON copied to clipboard!');
        }).catch(function() {
            showError('Failed to copy JSON. Please select and copy manually.');
        });
    });

    // Clear JSON
    $('#clear-json').on('click', function() {
        $('#json-content').html('<p class="text-gray-500 italic text-center m-0 py-12">No data generated yet. Click a button above to generate JSON data.</p>');
        $('.json-actions').hide();
    });

    function displayJsonData(data) {
        // Display the exact structure without outer wrapping braces
        const formattedJson = JSON.stringify(data, null, 2);
        // Remove the outer wrapping braces
        const cleanJson = formattedJson.replace(/^\{\s*/, '').replace(/\s*\}$/, '');
        $('#json-content').text(cleanJson);
        $('.json-actions').show();
    }

    function showSuccess(message) {
        // Create a temporary success message
        const successDiv = $('<div class="fixed top-8 right-8 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg z-50">' + message + '</div>');
        $('body').append(successDiv);
        setTimeout(function() {
            successDiv.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    function showError(message) {
        // Create a temporary error message
        const errorDiv = $('<div class="fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-md shadow-lg z-50">' + message + '</div>');
        $('body').append(errorDiv);
        setTimeout(function() {
            errorDiv.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
});
</script>
