{{-- Images Preview Tab --}}
<div id="images-preview" class="tab-content {{ request()->get('tab') === 'images-preview' ? 'tab-content-active' : '' }}">
    @php
        $options = get_option('store_settings');
        $current_style = $options['product_image_style'] ?? 'contain-white';
        // Fetch a few sample products for the preview (fallback to empty array if none)
        $preview_products = wc_get_products([
            'status' => 'publish',
            'limit'  => 4,
        ]);
    @endphp

    <div style="margin-bottom:20px;">
        <h2>Product Images Style</h2>
        <p>Choose how product images are displayed on product listings.</p>
    </div>

    <div class="form-field" style="margin-bottom:20px;">
        <label style="display:block; margin-bottom:10px;">
            <input type="radio" name="store_settings[product_image_style]" value="contain-white" {{ checked($current_style, 'contain-white', false) }}>
            Contain Image (White background)
        </label>
        <label style="display:block; margin-bottom:10px;">
            <input type="radio" name="store_settings[product_image_style]" value="cover" {{ checked($current_style, 'cover', false) }}>
            Filled Image (Cover, Grey background)
        </label>
    </div>

    <div class="style-previews pointer-events-none">
        {{-- Cover / Grey background preview --}}
        <div class="style-preview" data-style="cover" {{ $current_style === 'cover' ? '' : 'style="display:none;"' }}>
            <h3 class="mb-4 text-lg font-semibold">Cover Preview</h3>
            <div class="grid grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-4 sm:gap-x-6 sm:gap-y-10 lg:grid-cols-4 lg:gap-x-8">
                @foreach($preview_products as $preview_product)
                    @php
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
                    @php remove_filter('pre_option_store_settings', $override_filter); @endphp
                @endforeach
                @php wc_reset_loop(); @endphp
            </div>
        </div>

        {{-- Contain / White background preview --}}
        <div class="style-preview" data-style="contain-white" {{ $current_style === 'contain-white' ? '' : 'style="display:none;"' }}>
            <h3 class="mb-4 text-lg font-semibold">Contain Preview</h3>
            <div class="grid grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-4 sm:gap-x-6 sm:gap-y-10 lg:grid-cols-4 lg:gap-x-8">
                @foreach($preview_products as $preview_product)
                    @php
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
                    @php remove_filter('pre_option_store_settings', $override_filter); @endphp
                @endforeach
                @php wc_reset_loop(); @endphp
            </div>
        </div>
    </div>

    {{-- Simple JS to toggle preview visibility based on selected option --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const radios = document.querySelectorAll('input[name="store_settings[product_image_style]"]');
            const previews = document.querySelectorAll('.style-preview');
            function updatePreviews() {
                const selected = document.querySelector('input[name="store_settings[product_image_style]"]:checked');
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
</div> 