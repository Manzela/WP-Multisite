<p class="notice notice-warning" style="height: fit-content; font-size: 1rem; align-content: center;">
    NOTE: Updating these settings will override settings for all sites in the network.
</p>

<table class="form-table" role="presentation">
    <tbody>
        <tr>
            <th scope="row">
                <label for="network_primary_color">Network Primary Color</label>
            </th>
            <td>
                @php
                    $primary_color = $network_options['network_primary_color'] ?? '';
                @endphp
                <input 
                    type="text" 
                    class="color-picker" 
                    id="network_primary_color" 
                    name="network_store_settings[network_primary_color]" 
                    value="{{ esc_attr($primary_color) }}"
                >
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="network_secondary_color">Network Secondary Color</label>
            </th>
            <td>
                @php
                    $secondary_color = $network_options['network_secondary_color'] ?? '';
                @endphp
                <input 
                    type="text" 
                    class="color-picker" 
                    id="network_secondary_color" 
                    name="network_store_settings[network_secondary_color]" 
                    value="{{ esc_attr($secondary_color) }}"
                >
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="network_store_banner">Network Store Banner</label>
            </th>
            <td>
                <div>
                    @php
                        $banner_id = $network_options['network_store_banner'] ?? '';
                        $banner_url = $banner_id ? wp_get_attachment_url($banner_id) : '';
                    @endphp
                    <img 
                        id="network_store_banner_preview" 
                        src="{{ esc_url($banner_url) }}" 
                        style="max-width: 300px; max-height: 300px; display: {{ $banner_url ? 'block' : 'none' }};"
                    >
                    <input 
                        type="hidden" 
                        id="network_store_banner" 
                        name="network_store_settings[network_store_banner]" 
                        value="{{ esc_attr($banner_id) }}"
                    >
                    <button 
                        type="button" 
                        class="button" 
                        id="network_store_banner_button"
                    >Select Banner</button>
                    <button 
                        type="button" 
                        class="button" 
                        id="network_store_banner_remove_button" 
                        style="display: {{ $banner_url ? 'inline-block' : 'none' }};"
                    >Remove Banner</button>
                </div>
                <p class="notice notice-warning" style="height: fit-content; font-size: 1rem; align-content: center;">
                    NOTE: Updating the banner for a large domain (with many sites) may take several minutes, please be patient.
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="network_store_logo">Network Store Logo</label>
            </th>
            <td>
                <div>
                    @php
                        $logo_id = $network_options['network_store_logo'] ?? '';
                        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
                    @endphp
                    <img 
                        id="network_store_logo_preview" 
                        src="{{ esc_url($logo_url) }}" 
                        style="max-width: 100px; max-height: 100px; display: {{ $logo_url ? 'block' : 'none' }};"
                    >
                    <input 
                        type="hidden" 
                        id="network_store_logo" 
                        name="network_store_settings[network_store_logo]" 
                        value="{{ esc_attr($logo_id) }}"
                    >
                    <button 
                        type="button" 
                        class="button" 
                        id="network_store_logo_button"
                    >Select Image</button>
                    <button 
                        type="button" 
                        class="button" 
                        id="network_store_logo_remove_button" 
                        style="display: {{ $logo_url ? 'inline-block' : 'none' }};"
                    >Remove Image</button>
                </div>
                <p class="notice notice-warning" style="height: fit-content; font-size: 1rem; align-content: center;">
                    NOTE: Updating the logo for a large domain (with many sites) may take several minutes, please be patient.
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="network_site_icon">Network Site Icon (favicon)</label>
            </th>
            <td>
                <div>
                    @php
                        $icon_id = $network_options['network_site_icon'] ?? '';
                        $icon_url = $icon_id ? wp_get_attachment_url($icon_id) : '';
                    @endphp
                    <img 
                        id="network_site_icon_preview" 
                        src="{{ esc_url($icon_url) }}" 
                        style="width: 100px; height: 100px; object-fit: contain; display: {{ $icon_url ? 'block' : 'none' }};"
                    >
                    <input 
                        type="hidden" 
                        id="network_site_icon" 
                        name="network_store_settings[network_site_icon]" 
                        value="{{ esc_attr($icon_id) }}"
                    >
                    <button 
                        type="button" 
                        class="button" 
                        id="network_site_icon_button"
                    >Select Site Icon</button>
                    <button 
                        type="button" 
                        class="button" 
                        id="network_site_icon_remove_button" 
                        style="display: {{ $icon_url ? 'inline-block' : 'none' }};"
                    >Remove Site Icon</button>
                    <p class="description">
                        Site Icons should be square and at least 512 × 512 pixels.
                    </p>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="network_buy_externally">Redirect customers to eCommerce link</label>
            </th>
            <td>
                <div style="background-color: #FFEBE8; border: 1px solid #CC0000; padding: 10px; margin: 5px 0;">
                    <input 
                        type="checkbox" 
                        id="network_buy_externally" 
                        name="network_store_settings[network_buy_externally]" 
                        value="1" 
                        {{ isset($network_options['network_buy_externally']) && $network_options['network_buy_externally'] ? 'checked' : '' }}
                    >
                    <label for="network_buy_externally" style="margin-left: 8px; font-weight: bold;"><span style="color: #D63638;">Warning:</span> This will replace the Add-to-cart button logic with an external redirection! (it will also disable all purchasing functionality)</label>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="network_product_image_style">Product Images Style</label>
            </th>
            <td>
                @php
                    $current_style = $network_options['product_image_style'] ?? 'contain-white';
                    // Find the first real subdomain with products
                    $preview_products = [];
                    $preview_blog_id = null;
                    $sites = function_exists('get_sites') ? get_sites() : [];
                    if (!empty($sites)) {
                        foreach ($sites as $site) {
                            if ($site->blog_id == 1) continue; // skip main site
                            switch_to_blog($site->blog_id);
                            if (function_exists('wc_get_products')) {
                                $products = wc_get_products([
                                    'status' => 'publish',
                                    'limit'  => 4,
                                ]);
                                if (!empty($products)) {
                                    $preview_products = $products;
                                    $preview_blog_id = $site->blog_id;
                                    restore_current_blog();
                                    break;
                                }
                            }
                            restore_current_blog();
                        }
                    }
                @endphp
                <div style="margin-bottom:10px;">
                    <label style="display:block; margin-bottom:10px;">
                        <input type="radio" name="network_store_settings[product_image_style]" value="contain-white" {{ checked($current_style, 'contain-white', false) }}>
                        Contain Image (White background)
                    </label>
                    <label style="display:block; margin-bottom:10px;">
                        <input type="radio" name="network_store_settings[product_image_style]" value="cover" {{ checked($current_style, 'cover', false) }}>
                        Filled Image (Cover, Grey background)
                    </label>
                </div>
                <div class="style-previews pointer-events-none">
                    {{-- Cover / Grey background preview --}}
                    <div class="style-preview" data-style="cover" {{ $current_style === 'cover' ? '' : 'style=\"display:none;\"' }}>
                        <h3 class="mb-4 text-lg font-semibold">Cover Preview</h3>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-4 sm:gap-x-6 sm:gap-y-10 lg:grid-cols-4 lg:gap-x-8">
                            @if(!empty($preview_products) && $preview_blog_id)
                                @foreach($preview_products as $preview_product)
                                    @php
                                        switch_to_blog($preview_blog_id);
                                        wc_setup_product_data($preview_product->get_id());
                                        do_action('woocommerce_shop_loop');
                                        // Force style override for preview
                                        $override_filter = function($settings){
                                            $settings['product_image_style'] = 'cover';
                                            return $settings;
                                        };
                                        add_filter('pre_option_store_settings', $override_filter);
                                    @endphp
                                    <div class="relative">
                                        @include('woocommerce.content-product')
                                    </div>
                                    @php remove_filter('pre_option_store_settings', $override_filter); restore_current_blog(); @endphp
                                @endforeach
                                @php switch_to_blog($preview_blog_id); wc_reset_loop(); restore_current_blog(); @endphp
                            @else
                                <p>No products available for preview.</p>
                            @endif
                        </div>
                    </div>
                    {{-- Contain / White background preview --}}
                    <div class="style-preview" data-style="contain-white" {{ $current_style === 'contain-white' ? '' : 'style=\"display:none;\"' }}>
                        <h3 class="mb-4 text-lg font-semibold">Contain Preview</h3>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-4 sm:gap-x-6 sm:gap-y-10 lg:grid-cols-4 lg:gap-x-8">
                            @if(!empty($preview_products) && $preview_blog_id)
                                @foreach($preview_products as $preview_product)
                                    @php
                                        switch_to_blog($preview_blog_id);
                                        wc_setup_product_data($preview_product->get_id());
                                        do_action('woocommerce_shop_loop');
                                        // Force style override for preview
                                        $override_filter = function($settings){
                                            $settings['product_image_style'] = 'contain-white';
                                            return $settings;
                                        };
                                        add_filter('pre_option_store_settings', $override_filter);
                                    @endphp
                                    <div class="relative">
                                        @include('woocommerce.content-product')
                                    </div>
                                    @php remove_filter('pre_option_store_settings', $override_filter); restore_current_blog(); @endphp
                                @endforeach
                                @php switch_to_blog($preview_blog_id); wc_reset_loop(); restore_current_blog(); @endphp
                            @else
                                <p>No products available for preview.</p>
                            @endif
                        </div>
                    </div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const radios = document.querySelectorAll('input[name="network_store_settings[product_image_style]"]');
                        const previews = document.querySelectorAll('.style-preview');
                        function updatePreviews() {
                            const selected = document.querySelector('input[name="network_store_settings[product_image_style]"]:checked');
                            previews.forEach(p => {
                                if (p.dataset.style === selected.value) {
                                    p.style.display = '';
                                } else {
                                    p.style.display = 'none';
                                }
                            });
                        }
                        radios.forEach(r => r.addEventListener('change', updatePreviews));
                        updatePreviews();
                    });
                </script>
            </td>
        </tr>
    </tbody>
</table>

<div class="card mb-4" style="background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 15px; margin-top: 20px; max-width: 800px;">
    <div class="card-header" style="border-bottom: 1px solid #ccd0d4; padding-bottom: 10px; margin-bottom: 15px;">
        <h3 class="card-title" style="margin: 0; font-size: 1.3em;">Parent Organization Policies (External)</h3>
    </div>
    <div class="card-body">
        
        {{-- 1. Terms --}}
        <div class="form-group mb-3" style="margin-bottom: 15px;">
            <label class="form-label font-weight-bold" style="display: block; font-weight: 600; margin-bottom: 5px;">eCommerce Terms & Conditions Link</label>
            <input type="url" 
                   name="network_store_settings[network_ecom_terms][terms_link]" 
                   class="regular-text" 
                   style="width: 100%;"
                   placeholder="https://www.parent-site.com/terms" 
                   value="{{ $network_options['network_ecom_terms']['terms_link'] ?? '' }}">
            <p class="description">Will render with: <code>rel="noopener noreferrer nofollow"</code></p>
        </div>

        {{-- 2. Refund/Return --}}
        <div class="form-group mb-3" style="margin-bottom: 15px;">
            <label class="form-label font-weight-bold" style="display: block; font-weight: 600; margin-bottom: 5px;">eCommerce Refund & Return Policy Link</label>
            <input type="url" 
                   name="network_store_settings[network_ecom_refund_return][refund_link]" 
                   class="regular-text" 
                   style="width: 100%;"
                   placeholder="https://www.parent-site.com/returns" 
                   value="{{ $network_options['network_ecom_refund_return']['refund_link'] ?? '' }}">
            <p class="description">Will render with: <code>rel="noopener noreferrer nofollow"</code></p>
        </div>

        {{-- 2.1 Structured Return Policy (Schema) --}}
        <div class="form-group mb-3" style="margin-bottom: 15px; background: #f9f9f9; padding: 10px; border-left: 3px solid #007cba;">
            <h4 style="margin-top: 0; margin-bottom: 10px;">Schema.org Return Policy Settings</h4>
            
            {{-- Return Days --}}
            <div style="margin-bottom: 10px;">
                <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 5px;">Return Window (Days)</label>
                <input type="number" 
                       name="network_store_settings[network_ecom_refund_return][merchant_return_days]" 
                       class="regular-text" 
                       style="width: 100px;"
                       placeholder="30" 
                       min="0"
                       value="{{ $network_options['network_ecom_refund_return']['merchant_return_days'] ?? '' }}">
            </div>

            {{-- Return Fees --}}
            <div>
                <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 5px;">Return Fees</label>
                <select name="network_store_settings[network_ecom_refund_return][return_fees]" style="width: 100%; max-width: 400px;">
                    <option value="" {{ selected($network_options['network_ecom_refund_return']['return_fees'] ?? '', '') }}>-- Select Policy --</option>
                    <option value="https://schema.org/FreeReturn" {{ selected($network_options['network_ecom_refund_return']['return_fees'] ?? '', 'https://schema.org/FreeReturn') }}>Free Returns (Merchant Pays)</option>
                    <option value="https://schema.org/ReturnFeesCustomerResponsibility" {{ selected($network_options['network_ecom_refund_return']['return_fees'] ?? '', 'https://schema.org/ReturnFeesCustomerResponsibility') }}>Customer Responsibility (Customer Pays)</option>
                    <option value="https://schema.org/ReturnShippingFees" {{ selected($network_options['network_ecom_refund_return']['return_fees'] ?? '', 'https://schema.org/ReturnShippingFees') }}>Return Shipping Fees</option>
                </select>
            </div>
        </div>

        {{-- 3. Warranty --}}
        <div class="form-group mb-3" style="margin-bottom: 15px;">
            <label class="form-label font-weight-bold" style="display: block; font-weight: 600; margin-bottom: 5px;">eCommerce Warranty Information Link</label>
            <input type="url" 
                   name="network_store_settings[network_ecom_warranty][warranty_link]" 
                   class="regular-text" 
                   style="width: 100%;"
                   placeholder="https://www.parent-site.com/warranty" 
                   value="{{ $network_options['network_ecom_warranty']['warranty_link'] ?? '' }}">
            <p class="description">Will render with: <code>rel="noopener noreferrer nofollow"</code></p>
        </div>

    </div>
</div>


<script>
jQuery(document).ready(function($) {
    $('#network_store_logo_button').click(function(e) {
        e.preventDefault();
        
        var frame = wp.media({
            title: 'Select or Upload Network Logo',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#network_store_logo').val(attachment.id);
            $('#network_store_logo_preview').attr('src', attachment.url).show();
            $('#network_store_logo_remove_button').show();
        });

        frame.open();
    });

    $('#network_store_logo_remove_button').click(function(e) {
        e.preventDefault();
        $('#network_store_logo').val('');
        $('#network_store_logo_preview').attr('src', '').hide();
        $(this).hide();
    });

    $('#network_site_icon_button').click(function(e) {
        e.preventDefault();
        
        var frame = wp.media({
            title: 'Select or Upload Site Icon',
            button: {
                text: 'Use this image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            
            // Check if image is at least 512x512
            if (attachment.width < 512 || attachment.height < 512) {
                alert('The image must be at least 512 × 512 pixels.');
                return;
            }

            $('#network_site_icon').val(attachment.id);
            $('#network_site_icon_preview').attr('src', attachment.url).show();
            $('#network_site_icon_remove_button').show();
        });

        frame.open();
    });

    $('#network_site_icon_remove_button').click(function(e) {
        e.preventDefault();
        $('#network_site_icon').val('');
        $('#network_site_icon_preview').attr('src', '').hide();
        $(this).hide();
    });

    $('#network_store_banner_button').click(function(e) {
        e.preventDefault();
        
        var frame = wp.media({
            title: 'Select or Upload Network Banner',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#network_store_banner').val(attachment.id);
            $('#network_store_banner_preview').attr('src', attachment.url).show();
            $('#network_store_banner_remove_button').show();
        });

        frame.open();
    });

    $('#network_store_banner_remove_button').click(function(e) {
        e.preventDefault();
        $('#network_store_banner').val('');
        $('#network_store_banner_preview').attr('src', '').hide();
        $(this).hide();
    });

    // Initialize color pickers
    $('.color-picker').wpColorPicker();
});
</script>
