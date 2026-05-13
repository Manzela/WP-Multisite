@php
    $store_settings = get_option('store_settings') ?: [];
    // Safe check for subsite path using MU-Plugin helper
    $is_subsite = function_exists('shopper_is_subsite_path') ? shopper_is_subsite_path() : false;
    $is_main = is_main_site() && !$is_subsite;

    $store_name = $store_settings['seo']['store_name'] ?? get_bloginfo('name');
    $store_city = $store_settings['seo']['city'] ?? __("Local Store", "sage");

    if ($is_main) {
        $store_city = __("Global Network", "sage");
    }
@endphp

<footer class="directory-footer bg-white border-t border-gray-100 mt-auto">
    <div class="container mx-auto px-6 py-10 md:py-16">
        <div class="flex flex-col lg:flex-row justify-between items-center text-sm text-gray-500 gap-8 lg:gap-0">

            {{-- SECTION A: COPYRIGHT --}}
            {{-- Centralized as requested --}}
            <div class="text-center order-2 lg:order-1 w-full lg:w-auto">
                <p>&copy; {{ date('Y') }} <span class="font-semibold text-gray-900">{{ $store_name }}</span>.
                    {{ __('All rights reserved', 'sage') }}.
                </p>
            </div>

            {{-- SECTION B: POLICY NAV --}}
            <nav
                class="flex flex-wrap justify-center lg:justify-end gap-x-8 gap-y-4 order-1 lg:order-2 w-full lg:w-auto">

                {{-- 1. PARENT POLICIES (EXTERNAL - NOFOLLOW STRICT) --}}
                @php $n = get_site_option('network_store_settings'); @endphp

                @if(!empty($n['network_ecom_terms']['terms_link']))
                    <a href="{{ $n['network_ecom_terms']['terms_link'] }}" target="_blank"
                        rel="noopener noreferrer nofollow" class="hover:text-black transition-colors">
                        {{ __('Terms & Conditions', 'sage') }}
                    </a>
                @endif

                @if(!empty($n['network_ecom_refund_return']['refund_link']))
                    <a href="{{ $n['network_ecom_refund_return']['refund_link'] }}" target="_blank"
                        rel="noopener noreferrer nofollow" class="hover:text-black transition-colors">
                        {{ __('Return Policy', 'sage') }}
                    </a>
                @endif

                @if(!empty($n['network_ecom_warranty']['warranty_link']))
                    <a href="{{ $n['network_ecom_warranty']['warranty_link'] }}" target="_blank"
                        rel="noopener noreferrer nofollow" class="hover:text-black transition-colors">
                        {{ __('Warranty Info', 'sage') }}
                    </a>
                @endif

                {{-- 2. LOCAL DIRECTORY POLICIES (INTERNAL) --}}
                <span class="text-gray-300 hidden lg:inline">|</span>

                <a href="/privacy-policy" class="hover:text-black transition-colors" rel="noopener noreferrer nofollow">
                    {{ __('Privacy Policy', 'sage') }}
                </a>

                <a href="/accessibility-policy" class="hover:text-black transition-colors" rel="nofollow">
                    {{ __('Accessibility', 'sage') }}
                </a>

                <a href="/privacy-policy" class="hover:text-black transition-colors" rel="noopener noreferrer nofollow">
                    {{ __('Cookie Policy', 'sage') }}
                </a>

            </nav>
        </div>

        {{-- SECTION C: TRANSPARENCY & ORIGIN STATEMENT --}}
        <div class="border-t border-gray-200 mt-10 pt-8">
            <div
                class="flex flex-col md:flex-row justify-between items-center text-xs text-gray-400 font-mono gap-4 md:gap-0">

                {{-- 1. THE "ORIGIN" STATEMENT --}}
                <div class="text-left">
                    <p class="uppercase tracking-wide font-medium">
                        {{ __('Official Local Directory for', 'sage') }} <span class="text-gray-600 font-bold">
                            {{ $n['main_page']['main_title'] ?? $store_city }}
                        </span>
                    </p>
                    <p class="mt-1 opacity-75">
                        {{ __('Authorized digital listing. Not an e-commerce storefront.', 'sage') }}
                    </p>
                </div>

                {{-- 2. THE "BUILDER" ATTRIBUTION --}}
                <div class="flex items-center gap-2">
                    <span class="opacity-75">{{ __('Platform Architecture by', 'sage') }}</span>
                    <a href="https://example-network.com" target="_blank" rel="noopener noreferrer nofollow"
                        class="font-bold text-gray-500 hover:text-black transition-colors flex items-center gap-1 group">
                        <span
                            class="uppercase tracking-widest group-hover:tracking-[0.2em] transition-all duration-300">Example</span>
                    </a>
                </div>

            </div>
        </div>
    </div>
    @include('woocommerce.partials.popup-message')
</footer>