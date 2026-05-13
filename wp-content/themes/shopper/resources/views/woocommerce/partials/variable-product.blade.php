{{--
This template is used to display the variable product. attributes are displayed as buttons and the user can select them.
when a variation is selected the price and the add to cart button are updated.
product.js is used to handle the logic.
Author: Edward Ziadeh
Date: 2024-11-04
--}}

@php
    $product = wc_get_product(get_the_ID());
    $attributes = $product->get_attributes();

    // Get available variations and prepare data for JavaScript
    $available_variations = $product->get_available_variations();
    $variations_data = [];

    // Get currency settings
    $currency_symbol = get_woocommerce_currency_symbol();
    $currency_pos = get_option('woocommerce_currency_pos');

    foreach ($available_variations as $variation) {
        $variation_obj = wc_get_product($variation['variation_id']);

        // Debug log
        error_log('Variation Data: ' . print_r([
            'id' => $variation['variation_id'],
            'is_in_stock' => $variation_obj->is_in_stock(),
            'stock_quantity' => $variation_obj->get_stock_quantity(),
        ], true));

        $variations_data[] = [
            'variation_id' => $variation['variation_id'],
            'attributes' => $variation['attributes'],
            'display_price' => $variation_obj->get_price_html(),
            'is_in_stock' => $variation_obj->is_in_stock(), // Make sure this is boolean
            'stock_quantity' => $variation_obj->get_stock_quantity(),
            'backorders_allowed' => $variation_obj->backorders_allowed(),
            'image' => [
                'full_src' => wp_get_attachment_image_url($variation_obj->get_image_id(), 'full'),
                'src' => wp_get_attachment_image_url($variation_obj->get_image_id(), 'woocommerce_single'),
                'thumb_src' => wp_get_attachment_image_url($variation_obj->get_image_id(), 'thumbnail'),
            ],
        ];
    }
    $has_sale_range = \App\check_product_sale_range($product);
    $formatted_old_range = '';

    // For variable products, check if all variations have the same price (both regular and sale)
    $is_uniform_pricing = false;
    $min_price = $product->get_variation_price('min');
    $max_price = $product->get_variation_price('max');
    $min_regular_price = $product->get_variation_regular_price('min');
    $max_regular_price = $product->get_variation_regular_price('max');

    // If all variations have the same regular price and same sale price, 
    // WooCommerce handles the display like a simple product
    $is_uniform_pricing = ($min_price === $max_price && $min_regular_price === $max_regular_price);

    // Calculate discount percentage for uniform pricing (case 3)
    $discount_percentage = 0;
    if ($is_uniform_pricing && $min_regular_price > 0 && $min_price > 0 && $min_price < $min_regular_price) {
        $discount_percentage = round((($min_regular_price - $min_price) / $min_regular_price) * 100);
    }

    if ($has_sale_range && !$is_uniform_pricing) {
        $min = $product->get_variation_regular_price('min');
        $max = $product->get_variation_regular_price('max');
        $formatted_old_range = $min === $max ? $min : implode('-', [$min, $max]);
    }

    $hide_all_prices = get_option('store_settings')['hide_all_prices'] ?? false;
@endphp

<div class="variable-product" data-variations='@json($variations_data)' data-product-id="{{ $product->get_id() }}">

    @if(!$hide_all_prices)
        <div class="variation-price text-2xl font-semibold mt-4" data-original-price='{!! $product->get_price_html() !!}'>
            <span
                class="{{ ($has_sale_range && !$is_uniform_pricing) ? 'text-red-500 underline' : 'custom-color-secondary' }}">
                {!! $product->get_price_html() !!}
            </span>
            @if($has_sale_range && !$is_uniform_pricing)
                <br />
                <del aria-hidden="true">
                    <span class="woocommerce-Price-amount amount">
                        <bdi>{{ $formatted_old_range }}&nbsp;
                            <span class="woocommerce-Price-currencySymbol">₪</span>
                        </bdi>
                    </span>
                </del>
            @endif
        </div>
    @endif

    {{-- Stock status display --}}
    @php
        $verified_date = $product->get_date_modified() ? date_i18n('M d, Y', strtotime($product->get_date_modified())) : ($product->get_date_created() ? date_i18n('M d, Y', strtotime($product->get_date_created())) : date_i18n('M d, Y'));
    @endphp
    <div class="stock-status-container mt-2">
        <span class="text-green-600 font-bold">{{ __('Availability Checked', 'sage') }}</span><br>
        <p class="text-xs text-gray-500 mt-1">
            {{ __('Inventory verified:', 'sage') }} {{ $verified_date }}
        </p>
    </div>

    <!-- {{-- Stock status display --}}
    <div class="stock-status mt-2">
        <span class="stock-status-badge hidden inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"></span>
    </div> -->


    <div class="mt-4 mb-6">
        @foreach($attributes as $attribute_slug => $attribute)
            @php
                $decoded_slug = urldecode($attribute_slug);
                $is_color = strpos(strtolower($decoded_slug), 'color') !== false;
            @endphp
            <!-- render only the "used for variations" attributes -->
            @if($attribute['variation'])
                <div class="variation-wrapper mb-6" data-attribute="{{ $attribute_slug }}">
                    <div class="flex justify-between items-center mb-2">
                        <h2 class="text-md font-semibold">
                            {{ wc_attribute_label($decoded_slug) }}
                        </h2>
                        <span class="selected-value text-sm text-gray-500"></span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($attribute->get_options() as $option)
                            @php
                                if ($attribute->is_taxonomy()) {
                                    $term = get_term_by('id', $option, $attribute->get_taxonomy());
                                    $option_name = $term ? $term->name : $option;
                                    $option_value = $term ? $term->slug : $option;
                                } else {
                                    $option_name = $option;
                                    $option_value = sanitize_title($option);
                                }
                            @endphp

                            @if($is_color)
                                <button type="button"
                                    class="variation-button relative w-10 h-10 rounded-full border-2 border-gray-300 
                                                                                                                                                                        hover:border-[var(--color-primary)] hover:scale-110 transition-transform duration-200
                                                                                                                                                                        focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-primary)]"
                                    style="background-color: #{{ esc_attr($option_value) }};" title="{{ esc_html($option_name) }}"
                                    data-attribute="{{ $attribute_slug }}" data-value="{{ $option_value }}"
                                    data-label="{{ $option_name }}">
                                    <span
                                        class="selected-check absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-200">
                                        <svg class="w-6 h-6 text-white drop-shadow-lg" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    <span class="sr-only">{{ $option_name }}</span>
                                </button>
                            @else
                                <button type="button"
                                    class="variation-button min-w-[4rem] px-4 py-2 border-2 border-gray-300 rounded-md text-sm font-medium 
                                                                                                                                                                        hover:border-[var(--color-primary)] hover:bg-gray-50 transition-all duration-200
                                                                                                                                                                        focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-primary)]
                                                                                                                                                                        disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:border-gray-300
                                                                                                                                                        disabled:hover:bg-transparent"
                                    data-attribute="{{ $attribute_slug }}" data-value="{{ $option_value }}"
                                    data-label="{{ $option_name }}">
                                    {{ ($option_name) }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    @php
        $hide_all_prices = get_option('store_settings')['hide_all_prices'] ?? false;
        $buy_externally = get_option('store_settings')['buy_externally'] ?? false;
    @endphp
    @if(!$hide_all_prices && !$buy_externally)
        <div class="flex items-center justify-center mt-4">
            <button
                class="add-to-cart-button add_to_cart_button w-full custom-bg-color-primary hover:opacity-50 text-white py-3 px-6 rounded-md font-semibold 
                                            focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-primary)] opacity-50 cursor-not-allowed {{ is_rtl() ? 'rounded-tl-none rounded-bl-none' : 'rounded-tr-none rounded-br-none' }}"
                type="submit" data-product_id="{{ $product->get_id() }}" data-variation_id="" {{-- will be set by js after
                each selection --}} data-quantity="1" x-data disabled>
                {{__('Select options', 'woocommerce')}}
            </button>
        </div>
    @elseif($buy_externally)
        @php
            $product_id = $product->get_id();
            $source_url = get_post_meta($product_id, '_shopper_source_url', true);
            $ecommerceLink = get_option('store_settings')['store_info']['ecommerce_link'] ?? '';
            $buy_url = !empty($source_url) ? esc_url($source_url) : esc_url($ecommerceLink);

            // Calculate text color for better contrast
            $primary_color = get_option('store_settings')['primary_color'] ?? '';
            $text_color = wc_light_or_dark($primary_color, '', 'text-white');
        @endphp
        <div class="mt-6 w-full">
            <a href="{{ $buy_url }}" target="_blank" rel="noopener noreferrer nofollow" id="buy-online-button"
                class="group w-full flex items-center justify-center py-3 px-6 border border-transparent text-base font-semibold rounded-md {{ $text_color }} custom-bg-color-primary hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 ring-[var(--color-primary)] transition-all shadow-md hover:shadow-lg">

                {{-- 1. The "External" Visual Cue --}}
                <svg class="h-5 w-5 {{ is_rtl()? 'ml-2' : 'mr-2' }} opacity-70 group-hover:opacity-100 transition-opacity" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>

                {{-- 2. The Forensic Copy: Action + Channel + Entity --}}
                <span class="uppercase tracking-wide text-center">
                    {{ sprintf(__('Shop Online at %s', 'sage'), get_bloginfo('name')) }}
                </span>
            </a>

            {{-- 3. The "Trust & Origin" Micro-Copy --}}
            <div class="text-center mt-2">
                <p class="text-[11px] text-gray-500 font-medium">
                    <span class="inline-block w-1.5 h-1.5 custom-bg-color-primary rounded-full mr-1 align-middle"></span>
                    {{ __('Secure transaction through the official website', 'sage') }}
                </p>
            </div>
        </div>
    @endif
</div>