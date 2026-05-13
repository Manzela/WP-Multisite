<p class="notice notice-info" style="height: 2rem; font-size: 1rem; align-content: center;">
    Add custom scripts that will be included across all sites in the network.
</p>

<div id="network-scripts-container">
    @php
        $scripts = $network_options['network_scripts'] ?? [];
        if (empty($scripts)) {
            $scripts = [[]]; // Start with one empty script
        }
    @endphp

    @foreach($scripts as $index => $script)
    <div class="script-item" data-index="{{ $index }}">
        <h3>Script {{ $index + 1 }}</h3>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="network_script_name_{{ $index }}">Script Name</label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="network_script_name_{{ $index }}" 
                            name="network_store_settings[network_scripts][{{ $index }}][network_script_name]" 
                            value="{{ $script['network_script_name'] ?? '' }}"
                            class="regular-text"
                            placeholder="e.g., Google Analytics, Facebook Pixel"
                        >
                        <p class="description">
                            A descriptive name for this script (for reference only).
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="network_script_location_{{ $index }}">Script Location</label>
                    </th>
                    <td>
                        <select 
                            id="network_script_location_{{ $index }}" 
                            name="network_store_settings[network_scripts][{{ $index }}][network_script_location]"
                            class="regular-text"
                        >
                            <option value="head" {{ ($script['network_script_location'] ?? '') === 'head' ? 'selected' : '' }}>
                                &lt;head&gt; section
                            </option>
                            <option value="body_top" {{ ($script['network_script_location'] ?? '') === 'body_top' ? 'selected' : '' }}>
                                &lt;body&gt; section (top)
                            </option>
                            <option value="body_bottom" {{ ($script['network_script_location'] ?? '') === 'body_bottom' ? 'selected' : '' }}>
                                &lt;body&gt; section (bottom)
                            </option>
                        </select>
                        <p class="description">
                            Choose where this script should be placed in the HTML.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="network_script_code_{{ $index }}">Script Code</label>
                    </th>
                    <td>
                        <textarea 
                            id="network_script_code_{{ $index }}" 
                            name="network_store_settings[network_scripts][{{ $index }}][network_script_code]" 
                            rows="8" 
                            cols="80"
                            class="large-text code"
                            placeholder="<script>// Your script code here</script>"
                        >{!! $script['network_script_code'] ?? '' !!}</textarea>
                        <p class="description">
                            Paste your complete script code here (including &lt;script&gt; tags if needed).
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="network_script_enabled_{{ $index }}">Enable Script</label>
                    </th>
                    <td>
                        <input 
                            type="checkbox" 
                            id="network_script_enabled_{{ $index }}" 
                            name="network_store_settings[network_scripts][{{ $index }}][network_script_enabled]" 
                            value="1"
                            {{ ($script['network_script_enabled'] ?? false) ? 'checked' : '' }}
                        >
                        <label for="network_script_enabled_{{ $index }}">Enable this script</label>
                        <p class="description">
                            Uncheck to disable this script without deleting it.
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <p>
            <button type="button" class="button button-secondary remove-script" data-index="{{ $index }}">
                Remove Script {{ $index + 1 }}
            </button>
        </p>
        
        <hr style="margin: 30px 0;">
    </div>
    @endforeach
</div>

<p>
    <button type="button" class="button button-primary" id="add-script">
        Add New Script
    </button>
</p>

<script>
jQuery(document).ready(function($) {
    let scriptIndex = {{ count($scripts) }};
    
    // Add new script
    $('#add-script').click(function() {
        const template = '<div class="script-item" data-index="' + scriptIndex + '">' +
            '<h3>Script ' + (scriptIndex + 1) + '</h3>' +
            '<table class="form-table" role="presentation">' +
                '<tbody>' +
                    '<tr>' +
                        '<th scope="row">' +
                            '<label for="network_script_name_' + scriptIndex + '">Script Name</label>' +
                        '</th>' +
                        '<td>' +
                            '<input ' +
                                'type="text" ' +
                                'id="network_script_name_' + scriptIndex + '" ' +
                                'name="network_store_settings[network_scripts][' + scriptIndex + '][network_script_name]" ' +
                                'value="" ' +
                                'class="regular-text" ' +
                                'placeholder="e.g., Google Analytics, Facebook Pixel"' +
                            '>' +
                            '<p class="description">' +
                                'A descriptive name for this script (for reference only).' +
                            '</p>' +
                        '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th scope="row">' +
                            '<label for="network_script_location_' + scriptIndex + '">Script Location</label>' +
                        '</th>' +
                        '<td>' +
                            '<select ' +
                                'id="network_script_location_' + scriptIndex + '" ' +
                                'name="network_store_settings[network_scripts][' + scriptIndex + '][network_script_location]" ' +
                                'class="regular-text"' +
                            '>' +
                                '<option value="head">&lt;head&gt; section</option>' +
                                '<option value="body_top">&lt;body&gt; section (top)</option>' +
                                '<option value="body_bottom" selected>&lt;body&gt; section (bottom)</option>' +
                            '</select>' +
                            '<p class="description">' +
                                'Choose where this script should be placed in the HTML.' +
                            '</p>' +
                        '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th scope="row">' +
                            '<label for="network_script_code_' + scriptIndex + '">Script Code</label>' +
                        '</th>' +
                        '<td>' +
                            '<textarea ' +
                                'id="network_script_code_' + scriptIndex + '" ' +
                                'name="network_store_settings[network_scripts][' + scriptIndex + '][network_script_code]" ' +
                                'rows="8" ' +
                                'cols="80" ' +
                                'class="large-text code" ' +
                                'placeholder="&lt;script&gt;// Your script code here&lt;/script&gt;"' +
                            '></textarea>' +
                            '<p class="description">' +
                                'Paste your complete script code here (including &lt;script&gt; tags if needed).' +
                            '</p>' +
                        '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th scope="row">' +
                            '<label for="network_script_enabled_' + scriptIndex + '">Enable Script</label>' +
                        '</th>' +
                        '<td>' +
                            '<input ' +
                                'type="checkbox" ' +
                                'id="network_script_enabled_' + scriptIndex + '" ' +
                                'name="network_store_settings[network_scripts][' + scriptIndex + '][network_script_enabled]" ' +
                                'value="1" ' +
                                'checked' +
                            '>' +
                            '<label for="network_script_enabled_' + scriptIndex + '">Enable this script</label>' +
                            '<p class="description">' +
                                'Uncheck to disable this script without deleting it.' +
                            '</p>' +
                        '</td>' +
                    '</tr>' +
                '</tbody>' +
            '</table>' +
            '<p>' +
                '<button type="button" class="button button-secondary remove-script" data-index="' + scriptIndex + '">' +
                    'Remove Script ' + (scriptIndex + 1) +
                '</button>' +
            '</p>' +
            '<hr style="margin: 30px 0;">' +
        '</div>';
        
        $('#network-scripts-container').append(template);
        scriptIndex++;
    });
    
    // Remove script
    $(document).on('click', '.remove-script', function() {
        if (confirm('Are you sure you want to remove this script?')) {
            $(this).closest('.script-item').remove();
            // Renumber remaining scripts
            $('.script-item').each(function(index) {
                $(this).find('h3').text('Script ' + (index + 1));
                $(this).find('.remove-script').text('Remove Script ' + (index + 1));
            });
        }
    });
});
</script>
