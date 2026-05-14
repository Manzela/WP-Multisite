@php
/**
 * Author: Edward Ziadeh
 * Date: 2025-07-21
 * Description: This component is used to display the full product description on the product page.
 * Reviews: using reviews tab from woocommerce_product_tabs filter
 * Policies: using store settings policies
**/

// Alternative: Use a try-catch approach for maximum safety
$product_tabs = [];
try {
    if (function_exists('wc_get_product') && $product && did_action('wp_loaded')) {
        $product_tabs = apply_filters('woocommerce_product_tabs', array());
    }
} catch (Exception $e) {
    // Silently handle any errors
    $product_tabs = [];
}

// Get policies from store settings
$options = get_option('store_settings');
$policies = isset($options['policies']) ? $options['policies'] : [];

@endphp

<div class="product-full space-y-8">
    {{-- Product Description Section --}}
    @if(!empty($product->get_description()))
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-bold text-[var(--color-primary)] flex items-center">
                    {{ __('Description', 'woocommerce') }}
                </h2>
            </div>
            <div class="p-6">
                <div class="prose prose-gray max-w-none text-gray-700 leading-relaxed">
                    {!! apply_filters('the_content', $product->get_description()) !!}
                </div>
            </div>
        </div>
    @endif

    {{-- Product Information Section --}}
    @if(isset($product_tabs['additional_information']) && is_callable($product_tabs['additional_information']['callback']))
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-bold text-[var(--color-primary)] flex items-center">
                    {{ __('Additional information', 'woocommerce') }}
                </h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    {!! do_action('woocommerce_product_additional_information', $product) !!}
                </div>
            </div>
        </div>
    @endif

    {{-- Reviews Section --}}
    {{-- Reviews section (disabled — enable when review moderation is ready)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-bold text-[var(--color-primary)] flex items-center">
                {{ __('Reviews', 'woocommerce') }}
            </h2>
        </div>
        <div class="p-6">
            @php 
                if(isset($product_tabs['reviews']) && is_callable($product_tabs['reviews']['callback'])) {
                    echo call_user_func($product_tabs['reviews']['callback']);
                } else {
                    echo '<p class="text-gray-500 text-center py-8">' . __('No reviews yet.', 'woocommerce') . '</p>';
                }
            @endphp
        </div>
    </div>
    --}}

    {{-- Policies Section --}}
    @if(!empty($policies))
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-bold text-[var(--color-primary)] flex items-center">
                    {{ __('Returns and shipping policies', 'sage') }}
                </h2>
            </div>
            <div class="p-6">
                    @include('woocommerce.partials.policies-accordion', ['policies' => $policies])
            </div>
        </div>
    @endif
</div>