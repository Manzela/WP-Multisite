<table class="form-table" role="presentation">
    <tbody>
        <tr>
            <th scope="row">
                <label for="network_main_title">Main Title</label>
            </th>
            <td>
                <input 
                    type="text" 
                    id="network_main_title" 
                    name="network_store_settings[main_page][main_title]" 
                    value="{{ $network_options['main_page']['main_title'] ?? '' }}"
                    class="regular-text"
                >
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="network_main_description">Description</label>
            </th>
            <td>
                <input 
                    type="text" 
                    id="network_main_description" 
                    name="network_store_settings[main_page][description]" 
                    value="{{ $network_options['main_page']['description'] ?? '' }}"
                    class="regular-text"
                >
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="network_main_banner">Banner</label>
            </th>
            <td>
                <div>
                    @php
                        $banner_id = $network_options['main_page']['banner'] ?? '';
                        $banner_url = $banner_id ? wp_get_attachment_url($banner_id) : '';
                    @endphp
                    <img 
                        id="network_main_banner_preview" 
                        src="{{ esc_url($banner_url) }}" 
                        style="max-width: 300px; max-height: 300px; display: {{ $banner_url ? 'block' : 'none' }};"
                    >
                    <input 
                        type="hidden" 
                        id="network_main_banner" 
                        name="network_store_settings[main_page][banner]" 
                        value="{{ esc_attr($banner_id) }}"
                    >
                    <button 
                        type="button" 
                        class="button" 
                        id="network_main_banner_button"
                    >Select Image</button>
                    <button 
                        type="button" 
                        class="button" 
                        id="network_main_banner_remove_button" 
                        style="display: {{ $banner_url ? 'inline-block' : 'none' }};"
                    >Remove Image</button>
                </div>
                <p class="description">Recommended size: 300px height (banner)</p>
            </td>
        </tr>
    </tbody>
</table>

<script>
jQuery(document).ready(function($) {
    $('#network_main_banner_button').click(function(e) {
        e.preventDefault();
        var frame = wp.media({
            title: 'Select or Upload Banner',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#network_main_banner').val(attachment.id);
            $('#network_main_banner_preview').attr('src', attachment.url).show();
            $('#network_main_banner_remove_button').show();
        });
        frame.open();
    });
    $('#network_main_banner_remove_button').click(function(e) {
        e.preventDefault();
        $('#network_main_banner').val('');
        $('#network_main_banner_preview').attr('src', '').hide();
        $(this).hide();
    });
});
</script> 