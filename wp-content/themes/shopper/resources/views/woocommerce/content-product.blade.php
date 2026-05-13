@php
    global $product;
    // Calculate discount percentage
    $regular_price = (float) $product->get_regular_price();
    $sale_price = (float) $product->get_sale_price();
    $discount_percentage = 0;

    if ($regular_price > 0 && $sale_price > 0) {
        $discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
    }

    // Check if a variable product has a different price-range because some variation is on sale
    $has_sale_range = \App\check_product_sale_range($product);
    $formatted_old_range = '';

    // For variable products, check if all variations have the same price (both regular and sale)
    $is_uniform_pricing = false;
    if ($product->is_type('variable')) {
        $min_price = $product->get_variation_price('min');
        $max_price = $product->get_variation_price('max');
        $min_regular_price = $product->get_variation_regular_price('min');
        $max_regular_price = $product->get_variation_regular_price('max');

        // If all variations have the same regular price and same sale price, 
        // WooCommerce handles the display like a simple product
        $is_uniform_pricing = ($min_price === $max_price && $min_regular_price === $max_regular_price);

        // For uniform pricing, calculate discount percentage using variation prices
        if ($is_uniform_pricing && $min_regular_price > 0 && $min_price > 0 && $min_price < $min_regular_price) {
            $discount_percentage = round((($min_regular_price - $min_price) / $min_regular_price) * 100);
        }
    }

    if ($has_sale_range && !$is_uniform_pricing) {
        $min = $product->get_variation_regular_price('min');
        $max = $product->get_variation_regular_price('max');

        $formatted_old_range = $min === $max ? $min : implode('-', [$min, $max]);
    }
    $text_direction_class = is_rtl() ? 'text-right' : 'text-left';

    $hide_all_prices = get_option('store_settings')['hide_all_prices'] ?? false;
    $image_style_option = get_option('store_settings')['product_image_style'] ?? 'contain-white';
    $use_contain_style = $image_style_option === 'contain-white';
@endphp

<div {{ wc_product_class("group relative flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white {$text_direction_class}", $product) }}>
    <div
        class="aspect-h-4 aspect-w-3 sm:aspect-none group-hover:opacity-75 sm:h-96 {{ $use_contain_style ? 'bg-white' : 'bg-gray-200' }}">
        {!! woocommerce_get_product_thumbnail('woocommerce_thumbnail', ['class' => 'h-full w-full object-center sm:h-full sm:w-full ' . ($use_contain_style ? 'object-contain' : 'object-cover')]) !!}
    </div>
    <div class="flex flex-1 flex-col space-y-2 p-4">
        <h3 class="text-sm font-medium text-gray-900">
            <a href="{{ get_permalink() }}">
                <span aria-hidden="true" class="absolute inset-0"></span>
                {{ $product->get_name() }}
            </a>
        </h3>
        <div class="flex flex-1 flex-col justify-end">
            @if ($discount_percentage > 0 && !$hide_all_prices)
                <p class="text-sm italic text-gray-500">
                    {{ __('You saved:', 'sage') }} {!! $discount_percentage !!}%
                </p>
            @endif
            @if(!$hide_all_prices)
                <p class="product-price text-base font-medium">
                    <span
                        class="{{ $discount_percentage > 0 ? 'text-red-500' : (($has_sale_range && !$is_uniform_pricing) ? 'text-red-500 underline' : 'custom-color-secondary') }}">
                        {!! $product->get_price_html() !!}
                    </span>
                    {{-- NOTE:
                    when all variations have the same regular price AND the same sale price ($is_uniform_pricing),
                    woocommerce treats it as a simple product. therefore, the following "del" element should be
                    removed since now it comes directly from woocommerce. (already included in "get_price_html()")
                    --}}
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
                </p>
            @endif

            @if($product->is_type('variable'))
                @php
                    $attributes = $product->get_variation_attributes();
                    $color_attribute = isset($attributes['pa_color']) ? $attributes['pa_color'] : (isset($attributes['color']) ? $attributes['color'] : null);
                @endphp

                @if($color_attribute)
                    <div id="color-attributes" class="flex gap-1 mt-2">
                        @foreach($color_attribute as $color)
                            @php
                                $color_term = get_term_by('slug', $color, 'pa_color');
                            @endphp
                            <div class="w-4 h-4 rounded-full border border-gray-200"
                                style="background-color: #{{ $color_term->slug }};" title="{{ $color_term->name }}"></div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>