<?php
    // Temporary line to delete the sync option - REMOVE THIS LINE AFTER TESTING
    // delete_option('gmb_last_sync'); delete_option('gmb_sync_error');
    
    $last_sync = get_option('gmb_last_sync');
    $last_sync_text = $last_sync ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_sync) : 'Never';
    $gmb_error = get_option('gmb_sync_error');
    
    // Calculate next scheduled update (24 hours after last sync)
    $next_sync = $last_sync ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_sync + (24 * 60 * 60)) : 'Not scheduled';
    
    // Check if automatic sync is disabled (default to true/disabled if option not set)
    $auto_sync_disabled = get_option('gmb_disable_auto_sync', true);
    
    // Get last updates
    $last_updates = get_option('gmb_last_updates', []);
    // Filter out any non-string items
    if (is_array($last_updates)) {
        foreach ($last_updates as $key => $value) {
            if (!is_string($value)) {
                unset($last_updates[$key]); 
            }
        }
    }
?>

<div id="google-business" class="tab-content {{ request()->get('tab') === 'google-business' ? 'tab-content-active' : '' }}">
    <div style="border-bottom: 2px solid black;padding-bottom: 10px;margin-bottom: 20px;display:flex;justify-content: space-between;">
        <h2>Google Business Profile</h2>
        <label class="switch">
            <input type="checkbox" name="gmb_disable_auto_sync" id="gmb_disable_auto_sync" {{ $auto_sync_disabled ? 'checked' : '' }}>
            <span class="slider round"></span>
            Disable automatic sync
        </label>
    </div>
    @if(get_option('gmb_last_sync') && !get_option('gmb_sync_error'))
        <span class="notice notice-info" style="display: block; height: 1.5rem; align-content: center;"> {{-- NOTE: using 'style' (instead of tailwind) in order to manipulate the wordpress notice directly --}}
            Store information was last synced with Google My Business on <strong>{{ date_i18n(get_option('date_format') . ' ' . get_option('time_format'), get_option('gmb_last_sync')) }}</strong>
        </span>
    @endif

    <p class="mb-4">
        Connect your store with Google Business Profile to automatically sync your business information.
    </p>
    <p> Update includes:
    <ul class="list-inside list-disc px-4">
        <li>- Store Address: Street, City, Postcode, Country.</li>
        <li>- Store Description</li>
        <li>- Phone Number</li>
        <li>- Opening hours</li>
        <li>- eCommerce link</li>
        <li>- GMB link (location on Google Maps)</li>
        <li>- Coordinates</li>
        <li>- Reviews</li>
    </ul>

    {{-- GMB Sync Status Section --}}
    <div class="gmb-sync-status-section" style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;">
        <h3>Sync Status</h3>
        
        <div id="gmb-sync-status">
            @if($gmb_error && $last_sync)
                <div class="sync-error" style="color: #d63638; margin: 15px 0; padding: 15px; background-color: #f8d7da; border-left: 5px solid #d63638; border-radius: 3px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h4 style="margin-top: 0; color: #d63638;">Error Details:</h4>
                    <p style="margin-bottom: 5px;">{{ $gmb_error }}</p>
                    <p style="font-size: 0.9em; margin-top: 10px;">
                        Ensure your Google Places API key is properly set on the network store settings
                    </p>
                </div>
            @elseif(!$last_sync)
                <div class="sync-instructions" style="margin: 15px 0; padding: 15px; background-color: #f0f6fc; border-left: 5px solid #72aee6; border-radius: 3px;">
                    <p>No synchronization has been performed yet</p>
                </div>
            @else
                <div class="sync-success" style="margin: 15px 0; padding: 15px; background-color: #ecf8f0; border-left: 5px solid #46b450; border-radius: 3px;">
                    <p>
                        @if($auto_sync_disabled)
                            <strong>Automatic sync is disabled.</strong>
                        @else
                            <strong>Next update is scheduled to:</strong> {{ $next_sync }}
                        @endif
                    </p>
                    <p style="font-size: 0.9em; margin-top: 10px;">
                        Your business information has been successfully synchronized with Google Business Profile.
                    </p>
                    
                    @if(!empty($last_updates) && is_array($last_updates))
                        <div class="last-updates" style="margin-top: 15px;">
                            <h4 style="margin-top: 0; margin-bottom: 10px; color: #1d2327;">Latest Updates:</h4>
                            <ul style="margin: 0; padding-left: 20px;">
                                @foreach($last_updates as $update)
                                    <li>{{ $update }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </div>
        
        <div class="sync-actions" style="margin-top: 20px;">
            <button type="button" id="gmb-sync-button" class="button button-primary">
                Sync Now
            </button>
            <p class="description" style="margin-top: 10px;">
                Click to manually sync your store information with Google Business Profile.
            </p>
        </div>
    </div>
    
    {{-- Reviews Section --}}
    <div class="gmb-reviews-section" style="margin-top: 30px;">
        <div style="border-bottom: 2px solid black; padding-bottom: 10px; margin-bottom: 20px;">
            <h2>Google Reviews</h2>
            <p>Reviews appears here only for reference. These exact reviews will be displayed in the "about" page</p>
        </div>
        @php(do_action('google-business-reviews'))
    </div>

    {{-- Posts Section --}}
    <div class="gmb-posts-section" style="margin-top: 30px;">
        <div style="border-bottom: 2px solid black; padding-bottom: 10px; margin-bottom: 20px;">
            <h2>Google Posts</h2>
            <p>Posts appears here only for reference. These exact posts will be displayed in the "about" page</p>
        </div>
        @php(do_action('google-business-posts'))
    </div>

</div> 