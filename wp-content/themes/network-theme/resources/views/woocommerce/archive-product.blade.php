{{--
The Template for displaying product archives, including the main shop page which is a post type archive

This template can be overridden by copying it to yourtheme/woocommerce/archive-product.blade.php.

HOWEVER, on occasion WooCommerce will need to update template files and you
(the theme developer) will need to copy the new files to your theme to
maintain compatibility. We try to do this as little as possible, but it does
happen. When this occurs the version of the template file will be bumped and
the readme will list any important changes.

@see https://docs.woocommerce.com/document/template-structure/
@package WooCommerce/Templates
@version 3.4.0
--}}
@php
    use Illuminate\Support\Arr;

    // Retrieve all query parameters, excluding empty values (to be used for active filters)
    $queryParams = array_filter($_GET, function ($value) {
        return !empty($value);
    });
@endphp
@extends('layouts.app')

@section('head')
    @include('partials.meta.store-description')
@endsection

@section('content')
    <!-- Hook before the shop loop -->
    <div x-data="{ openSidebar: false }">
        @php
            do_action('get_header', 'shop');
        @endphp

        @if (woocommerce_product_loop())
            <div class="container mx-auto"> <!-- Tailwind container for the product loop -->
                <div class="bg-white">
                    <div class="mx-auto max-w-2xl px-0 pb-16 sm:px-0 lg:max-w-7xl lg:px-0">
                        @include('sections.store-header-section')
                        {{-- breadcrumbs --}}
                        @php do_action('woocommerce_before_main_content') @endphp

                        {{-- Store Name H1 --}}
                        @if(!is_product_category() && empty($queryParams['s']))
                            <div class="mt-8">
                                <h1 class="text-3xl font-bold text-[var(--color-primary)]">
                                    {!! $storeOptions['seo']['store_name'] ?? get_bloginfo('name') !!}
                                </h1>
                            </div>
                        @endif

                        {{-- A new section for the store description --}}
                        @include('sections.store-about-section')

                        {{-- title for search-results page --}}
                        @if(!empty($queryParams))
                            @foreach ($queryParams as $key => $value)
                                @if($key === 's')
                                    <div class="mb-8">
                                        <span class="flex text-xl mx-auto">
                                            {{ __('Found', 'sage') }} {{ wc_get_loop_prop('total') }} {{ __('results for', 'sage') }}
                                            "{{ esc_html($value) }}":
                                        </span>
                                    </div>
                                @endif
                            @endforeach
                        @endif

                        {{-- title for category page --}}
                        @if(is_product_category())
                            @php
                                $current_category_id = get_queried_object_id();
                                $current_category = get_term_by('id', $current_category_id, 'product_cat');
                                $paged = get_query_var('paged') ? get_query_var('paged') : 1;
                            @endphp
                            <div class="mb-8">
                                <h1 id='category-page' class="text-2xl font-bold">
                                    {{ $current_category->name }}
                                    @if($paged > 1)
                                        <span class="text-gray-600">– {{ __('Page', 'sage') }} {{ $paged }}</span>
                                    @endif
                                </h1>
                            </div>
                        @endif

                        {{-- title for author/brand page --}}
                        @if(isset($queryParams['filter_book-author']))
                            <div class="mb-8">
                                <h1 id='author-page' class="text-2xl font-bold">
                                    {{ __('By', 'sage') }} {{ esc_html($queryParams['filter_book-author']) }}
                                </h1>
                            </div>
                        @elseif(is_tax('product_brand'))
                            @php
                                $brand_term = get_queried_object();
                            @endphp
                            @if($brand_term)
                                <div class="mb-8">
                                    <h1 id='brand-page' class="text-2xl font-bold">
                                        {{ __('By', 'sage') }} {{ $brand_term->name }}
                                    </h1>
                                </div>
                            @endif
                        @endif


                        {{-- filter section --}}
                        <div class="container mx-auto flex items-center justify-between h-16">
                            <!-- Store middle-bar element -->
                            <div class="flex items-center">
                                <button @click="openSidebar = !openSidebar" class="focus:outline-none hidden">
                                    <!-- Button to toggle sidebar -->
                                    <span class="flex items-center">
                                        @svg('filters', 'w-6 h-6 text-gray-600')
                                        <span class="text-gray-600 {{ is_rtl() ? 'mr-2' : 'ml-2' }}">
                                            {{ __('Filter', 'woocommerce') }}
                                        </span>
                                    </span>
                                </button>
                            </div>
                            <div class="flex h-[32px]">
                                {{ do_action('woocommerce_before_shop_loop') }}
                            </div>
                        </div>

                        {{-- Active Filters takes the query params and displays them as a list of filters from the url --}}
                        @if (!empty($queryParams))
                            <div class="query-params mb-4">
                                <ul class="flex flex-wrap gap-2">
                                    @foreach ($queryParams as $key => $value)
                                        <!-- Exclude the 'price' parameter and product-search parameters -->
                                        @if (
                                                $key !== 'max_price' && $key !== 'min_price' && $key !== 'orderby'
                                                && $key !== 's' && ($key !== 'post_type' || $value != 'product') && $key !== 'dgwt_wcas' && $key !== 'paged' && $key !== '_gl'
                                            )
                                            @php
                                                // Convert comma-separated values to an array
                                                $values = is_array($value) ? $value : explode(',', $value);
                                                $text_color = wc_light_or_dark($storeOptions['primary_color'], '', 'text-white');
                                            @endphp
                                            @foreach ($values as $singleValue)
                                                <li
                                                    class="flex items-center custom-bg-color-primary {{ $text_color }} px-3 py-1 rounded-full hover:opacity-50 transition-colors duration-300 cursor-pointer">
                                                    <span class="{{ is_rtl() ? 'ml-2' : 'mr-2' }}">{{ $singleValue }}</span>
                                                    <a
                                                        href="{{ url()->current() . '?' . http_build_query(Arr::except($queryParams, $key)) . '&' . $key . '=' . implode(',', array_diff($values, [$singleValue])) }}">
                                                        &times;
                                                    </a>
                                                </li>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <h2 class="sr-only">Products</h2>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-4 sm:gap-x-6 sm:gap-y-10 lg:grid-cols-4 lg:gap-x-8">
                            <!-- Start Product Loop -->
                            @if (wc_get_loop_prop('total'))
                                <!-- Product Grid -->
                                @while (have_posts())
                                    @php
                                        the_post();
                                        do_action('woocommerce_shop_loop');
                                        $product = wc_get_product(get_the_ID());
                                        $display_rank = get_post_meta($product->get_id(), '_network_display_rank', true);
                                        if (empty($display_rank)) {
                                            $display_rank = 1; // Default value
                                        }
                                    @endphp
                                    {{-- display rank for testing purposes change false to true to see --}}
                                    <div class="relative">
                                        @if($display_rank && false)
                                            <div
                                                class="absolute top-2 {{ is_rtl() ? 'right-2' : 'left-2' }} z-10 bg-[var(--color-primary)] text-white px-2 py-1 rounded-full text-xs">
                                                Rank: {{ $display_rank }}
                                            </div>
                                        @endif
                                        @php
                                            wc_get_template_part('content', 'product');
                                        @endphp
                                    </div>
                                @endwhile
                            @endif
                            <!-- End Product Loop -->
                        </div>
                    </div>
                </div>
                <!-- Pagination with margin -->
                <div class="mt-8"> <!-- Add margin-top for pagination -->
                    {{ do_action('woocommerce_after_shop_loop') }}
                </div>

            </div>
        @else
            <!-- No Products Found -->
            <div class="container mx-auto">
                @php
                    do_action('woocommerce_no_products_found');
                @endphp
            </div>
        @endif

        @php
            do_action('woocommerce_after_main_content');
            do_action('get_sidebar', 'shop');
            do_action('get_footer', 'shop');
        @endphp

        <!-- Sidebar Slider -->
        <div x-show="openSidebar" class="fixed inset-0 bg-gray-800 bg-opacity-50 z-50" @click="openSidebar = false"
            style="display: none;">
            <div class="absolute {{ is_rtl() ? 'right-0' : 'left-0' }} top-0 w-64 bg-white h-full shadow-lg transform transition-transform duration-300"
                x-show="openSidebar" @click.stop x-transition:enter="transform transition-transform duration-300"
                x-transition:enter-start="{{ is_rtl() ? 'translate-x-full' : '-translate-x-full' }}"
                x-transition:enter-end="translate-x-0" x-transition:leave="transform transition-transform duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="{{ is_rtl() ? 'translate-x-full' : '-translate-x-full' }}">
                @if(!$isBot)
                    @include('sections.sidebar')
                @endif
            </div>
        </div>
    </div>

@endsection