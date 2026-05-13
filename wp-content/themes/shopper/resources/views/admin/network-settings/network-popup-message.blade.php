<table class="form-table">
    <tr>
        <th scope="row">
            <label for="network_enable_popup">Enable Network Popup</label>
        </th>
        <td>
            <input 
                type="checkbox" 
                id="network_enable_popup" 
                name="network_store_settings[popup_message][enable_popup]" 
                value="1"
                {{ isset($network_options['popup_message']['enable_popup']) && $network_options['popup_message']['enable_popup'] ? 'checked' : '' }}
            >
        </td>
    </tr>
    
    <tr class="network-n-times-field">
        <th scope="row">
            <label for="network_n_times">
                Show n times per day 
                <span style="color: red">*</span>
            </label>
        </th>
        <td>
            <input 
                type="number" 
                id="network_n_times" 
                name="network_store_settings[popup_message][n_times]" 
                value="{{ $network_options['popup_message']['n_times'] ?? '1' }}"
                min="1"
                required
            >
            <p class="description">Number of times to show the popup per day (per user)</p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="network_popup_title">Popup Title</label>
        </th>
        <td>
            <input 
                type="text"
                id="network_popup_title"
                name="network_store_settings[popup_message][popup_title]"
                value="{{ $network_options['popup_message']['popup_title'] ?? '' }}"
                class="regular-text"
            >
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="network_popup_message">Popup Message</label>
        </th>
        <td>
            @php
                wp_editor(
                    $network_options['popup_message']['popup_message'] ?? '',
                    'network_popup_message',
                    [
                        'textarea_name' => 'network_store_settings[popup_message][popup_message]',
                        'media_buttons' => true,
                        'textarea_rows' => 10,
                    ]
                );
            @endphp
        </td>
    </tr>
</table>


<p class="notice notice-warning" style="height: 2rem; font-size: 1rem; align-content: center;">
    NOTE: Enable network popup will take over the local popup for all sites in the network.
</p>