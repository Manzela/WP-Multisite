/**
 * Google Business Profile Sync Handler
 * 
 * This script handles the manual sync functionality for Google Business Profile integration.
 * It provides real-time feedback during the sync process and updates the UI accordingly.
 * 
 * Author: Antigravity
 * Date: 28/04/2025
 */

jQuery(document).ready(function($) {
    const $syncButton = $('#gmb-sync-button');
    const $syncStatus = $('#gmb-sync-status');
    const $autoSyncToggle = $('#gmb_disable_auto_sync');
    
    if (!$syncButton.length || !$syncStatus.length) {
        return;
    }
    
    function updateSyncStatus(message, type = 'info') {
        let statusClass = 'sync-instructions';
        let statusIcon = '';
        
        switch (type) {
            case 'success':
                statusClass = 'sync-success';
                statusIcon = '✅ ';
                break;
            case 'error':
                statusClass = 'sync-error';
                statusIcon = '❌ ';
                break;
            case 'loading':
                statusClass = 'sync-loading';
                statusIcon = '⏳ ';
                break;
        }
        
        $syncStatus.html(`
            <div class="${statusClass}" style="margin: 15px 0; padding: 15px; background-color: ${type === 'error' ? '#f8d7da' : type === 'success' ? '#ecf8f0' : '#f0f6fc'}; border-left: 5px solid ${type === 'error' ? '#d63638' : type === 'success' ? '#46b450' : '#72aee6'}; border-radius: 3px;">
                <p>${statusIcon}${message}</p>
            </div>
        `);
    }
    
    function showUpdates(updates) {
        if (!updates || !Array.isArray(updates) || updates.length === 0) {
            return;
        }
        
        // Filter out non-string items
        const filteredUpdates = updates.filter(update => typeof update === 'string');
        
        if (filteredUpdates.length === 0) {
            return;
        }
        
        const updatesHtml = `
            <div class="last-updates" style="margin-top: 15px;">
                <h4 style="margin-top: 0; margin-bottom: 10px; color: #1d2327;">Latest Updates:</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    ${filteredUpdates.map(update => `<li>${update}</li>`).join('')}
                </ul>
            </div>
        `;
        
        $syncStatus.find('.sync-success').append(updatesHtml);
    }
    
    // Simple function to refresh reviews section
    function refreshReviews() {
        $.ajax({
            url: gmbSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'refresh_reviews_html',
                nonce: gmbSettings.nonce
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $('.gmb-reviews-container').replaceWith(response.data.html);
                }
            }
        });
    }
    
    // Simple function to refresh posts section
    function refreshPostsHTML() {
        $.ajax({
            url: gmbSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'refresh_posts_html',
                nonce: gmbSettings.nonce
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $('.gmb-posts-container').replaceWith(response.data.html);
                }
            }
        });
    }
    
    // Handle automatic sync toggle
    $autoSyncToggle.on('change', function() {
        const isDisabled = $(this).is(':checked');
        
        $.ajax({
            url: gmbSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_gmb_auto_sync',
                disabled: isDisabled ? 1 : 0,
                nonce: gmbSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    // If auto sync is disabled, update the UI to reflect this
                    if (isDisabled) {
                        // If there's a "next update" message, update it
                        $syncStatus.find('.sync-success p:first').text('Automatic sync has been disabled.');
                    } else {
                        // If auto sync is re-enabled, update with "next update" message if there's a last sync time
                        if ($('.last-sync-time').length) {
                            location.reload(); // Simple solution to refresh the next update time
                        }
                    }
                }
            }
        });
    });
    
    $syncButton.on('click', function() {
        const $button = $(this);
        
        $button.prop('disabled', true).text('Syncing...');
        updateSyncStatus('Syncing with Google Business Profile...', 'loading');
        
        // Make AJAX request
        $.ajax({
            url: gmbSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'sync_gmb_ajax',
                nonce: gmbSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show a more contextual message based on the update status
                    let message = response.data.message;
                    
                    updateSyncStatus(message, 'success');
                    
                    // Only show updates if there are actual updates and status is 'updated'
                    if (response.data.status === 'updated' && 
                        response.data.updates && 
                        Array.isArray(response.data.updates) && 
                        response.data.updates.length > 0) {
                        showUpdates(response.data.updates);
                        
                        if (response.data.reviews_updated) {
                            refreshReviews();
                        }

                        // START [GMB-SYNC-FIX] Reload page after sync so all form tabs
                        // re-render with fresh DB values. Without this, the Store Info
                        // tab retains stale/empty fields that overwrite synced data on Save.
                        setTimeout(function() {
                            window.location.href = window.location.pathname + '?page=store-settings&tab=google-business';
                        }, 2000);
                        // END [GMB-SYNC-FIX]
                    }
                    
                    // Update last sync time if available
                    if (response.data.last_sync) {
                        const lastSync = new Date(response.data.last_sync * 1000);
                        const formattedDate = lastSync.toLocaleString();
                        $('.last-sync-time').text(formattedDate);
                        
                        // If auto-sync is not disabled, show the next scheduled update
                        if (!$autoSyncToggle.is(':checked')) {
                            const nextSync = new Date((response.data.last_sync + (24 * 60 * 60)) * 1000);
                            const formattedNextSync = nextSync.toLocaleString();
                            $syncStatus.find('.sync-success p:first')
                                .html(`<strong>Next update is scheduled to:</strong> ${formattedNextSync}`);
                        }
                    }
                } else {
                    updateSyncStatus(response.data.message, 'error');
                }
            },
            error: function() {
                updateSyncStatus('An error occurred while syncing. Please try again.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Sync Now');
            }
        });
    });

    // Add handler for clear reviews button
    $(document).on('click', '#clear-reviews', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to clear all reviews? This cannot be undone.')) {
            return;
        }
        
        $.ajax({
            url: gmbSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'clear_gmb_reviews',
                nonce: gmbSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Refresh the reviews section instead of page reload
                    refreshReviews();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while clearing reviews.');
            }
        });
    });
    
    // Add handler for clear posts button
    $(document).on('click', '#clear-posts', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to clear all Google Business posts? This cannot be undone.')) {
            return;
        }
        
        $.ajax({
            url: gmbSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'clear_gmb_posts',
                nonce: gmbSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Refresh the posts section instead of page reload
                    refreshPostsHTML();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while clearing posts.');
            }
        });
    });

    // Add Fetch Posts button handler
    $(document).on('click', '#fetch-gmb-posts', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $status = $('#gmb-posts-status');
        
        $button.prop('disabled', true).text('Fetching...');
        $status.html('<p>Fetching Google Business posts...</p>');
        
        $.ajax({
            url: gmbSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_gmb_posts_ajax',
                nonce: gmbSettings.nonce
            },
            success: function(response) {
                $button.prop('disabled', false).text('Fetch Google Posts');
                
                if (response.success) {
                    var message = '<p class="success">' + response.data.message + '</p>';
                    
                    if (response.data.status === 'success') {
                        message += '<p>Last fetch: ' + new Date().toLocaleString() + '</p>';
                        message += '<p>Posts fetched: ' + response.data.count + '</p>';
                        refreshPostsHTML();
                    }
                    
                    $status.html(message);
                } else {
                    var errorMessage = '<p class="error" style="color: #d63638;">' + response.data.message + '</p>';
                    
                    // Add detailed error information if available
                    if (response.data.details) {
                        errorMessage += '<div class="error-details" style="margin-top: 10px; padding: 10px; background-color: #f8f8f8; border-left: 3px solid #d63638; font-family: monospace; white-space: pre-wrap; overflow: auto; max-height: 200px; font-size: 12px;">';
                        errorMessage += response.data.details;
                        errorMessage += '</div>';
                    }
                    
                    $status.html(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                $button.prop('disabled', false).text('Fetch Google Posts');
                
                var errorMessage = '<p class="error" style="color: #d63638;">An error occurred. Please try again.</p>';
                
                // Add XHR details if available
                if (xhr && xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.message || response.error) {
                            errorMessage += '<div class="error-details" style="margin-top: 10px; padding: 10px; background-color: #f8f8f8; border-left: 3px solid #d63638; font-family: monospace; white-space: pre-wrap; overflow: auto; max-height: 200px; font-size: 12px;">';
                            errorMessage += response.message || response.error;
                            errorMessage += '</div>';
                        }
                    } catch (e) {
                        // If we can't parse the JSON, show the raw responseText
                        if (xhr.responseText.length < 1000) {
                            errorMessage += '<div class="error-details" style="margin-top: 10px; padding: 10px; background-color: #f8f8f8; border-left: 3px solid #d63638; font-family: monospace; white-space: pre-wrap; overflow: auto; max-height: 200px; font-size: 12px;">';
                            errorMessage += xhr.responseText;
                            errorMessage += '</div>';
                        }
                    }
                }
                
                $status.html(errorMessage);
            }
        });
    });
}); 