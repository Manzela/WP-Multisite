<div id="popup-message" class="tab-content" style="display: {{ request()->get('tab') === 'popup-message' ? 'block' : 'none' }};">
    <h2>Popup Message Settings</h2>
    
    <table class="form-table">
        <!-- New Network Status Field -->
        <tr>
            <th scope="row">
            </th>
            <td>
                @php
                    $network_settings = get_site_option('network_store_settings', []);
                    $network_popup_enabled = isset($network_settings['popup_message']['enable_popup']) && $network_settings['popup_message']['enable_popup'];
                @endphp

                @if($network_popup_enabled)
                    <div class="mb-4">
                        <p class="notice notice-success" style="height: 2rem; font-size: 1rem; align-content: center;">
                            Network popup is ACTIVE
                        </p>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <input 
                            type="checkbox" 
                            name="store_settings[popup_message][use_local]" 
                            value="1"
                            {{ isset($options['popup_message']['use_local']) && $options['popup_message']['use_local'] ? 'checked' : '' }}
                        >
                        <span class="font-medium switch-label">
                            Use my popup instead
                        </span>
                    </div>

                @endif
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="enable_popup">Enable Popup</label>
            </th>
            <td>
                <input 
                    type="checkbox" 
                    id="enable_popup" 
                    name="store_settings[popup_message][enable_popup]" 
                    value="1"
                    {{ isset($options['popup_message']['enable_popup']) && $options['popup_message']['enable_popup'] ? 'checked' : '' }}
                >
            </td>
        </tr>
        
        <tr class="n-times-field">
            <th scope="row">
                <label for="n_times">
                    Show n times per day 
                    <span style="color: red">*</span>
                </label>
            </th>
            <td>
                <input 
                    type="number" 
                    id="n_times" 
                    name="store_settings[popup_message][n_times]" 
                    value="{{ $options['popup_message']['n_times'] ?? '1' }}"
                    min="1"
                    required
                >
                <p class="description">Number of times to show the popup per day (per user)</p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="popup_title">Popup Title</label>
            </th>
            <td>
                <input 
                    type="text"
                    id="popup_title"
                    name="store_settings[popup_message][popup_title]"
                    value="{{ $options['popup_message']['popup_title'] ?? '' }}"
                    class="regular-text"
                >
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="popup_message">Popup Message</label>
            </th>
            <td>
                @php
                    wp_editor(
                        $options['popup_message']['popup_message'] ?? '',
                        'popup_message',
                        [
                            'textarea_name' => 'store_settings[popup_message][popup_message]',
                            'media_buttons' => true,
                            'textarea_rows' => 10,
                        ]
                    );
                @endphp
            </td>
        </tr>
    </table>
    
    <input type="hidden" name="store_settings_current_tab" value="popup-message">
</div>