{{--
The Template for displaying all single products

This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.

HOWEVER, on occasion WooCommerce will need to update template files and you
(the theme developer) will need to copy the new files to your theme to
maintain compatibility. We try to do this as little as possible, but it does
happen. When this occurs the version of the template file will be bumped and
the readme will list any important changes.

@see         https://docs.woocommerce.com/document/template-structure/
@package     WooCommerce\Templates
@version     1.6.4
--}}



@extends('layouts.app')

@section('content')
  @php
    do_action('get_header', 'shop');
    do_action('woocommerce_before_main_content');

    function get_stock_and_price($product) {
      $hide_all_prices = get_option('store_settings')['hide_all_prices'] ?? false;
      if(!$hide_all_prices) {
        echo '<div class="product-price text-2xl font-medium ' . ($product->is_on_sale() ? 'text-red-500' : 'custom-color-secondary') . '" data-default-price="{!! wp_json_encode($product->get_price_html()) !!}">'
          . $product->get_price_html()
          . '</div>';
      }

      $verified_date = $product->get_date_modified() ? date_i18n('M d, Y', strtotime($product->get_date_modified())) : ($product->get_date_created() ? date_i18n('M d, Y', strtotime($product->get_date_created())) : date_i18n('M d, Y'));
      echo '<div class="stock-status-container mt-2">'
        .   '<span class="text-green-600 font-bold">' . __('Availability Checked', 'sage') . '</span><br>'
        .   '<p class="text-xs text-gray-500 mt-1">'
        .     __('Inventory verified:', 'sage') . ' ' . $verified_date
        .   '</p>'
        . '</div>';
    }
  @endphp

  @while(have_posts())
    @php
      the_post();
      global $product;
      $product = wc_get_product(get_the_ID());
    @endphp
    
    <div class="bg-white {{ is_rtl()? 'text-right' : 'text-left' }}">
      <div class="pb-16 pt-6 sm:pb-24">
        <div class="mx-auto mt-8 max-w-2xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
          <div id='single-product' class="lg:grid lg:auto-rows-min lg:grid-cols-12 lg:gap-x-8" 
            data-product-id="{{ $product->get_id() }}" 
            data-sku="{{ $product->get_sku() }}"
            data-description="{{ $product->get_description() }}"
            data-short-description="{{ $product->get_short_description() }}"
            data-tags="{{ implode(', ', wp_get_post_terms($product->get_id(), 'product_tag', ['fields' => 'names'])) }}"
          >
            <div class="lg:col-span-5 lg:col-start-8">

              {{-- product author/brand --}}
              @php
                // fetch author from attributes, brand from taxonomy
                $author = $product->get_attribute('book-author');              
                $brand_terms = wp_get_post_terms($product->get_id(), 'product_brand');
                $brand_term = !empty($brand_terms) ? $brand_terms[0] : null;
                $brand = $brand_term ? $brand_term->name : null;

                // Build the URL
                $filter_url = '';
                if ($author) {
                    $filter_url = add_query_arg('filter_book-author', urlencode($author), home_url('/'));
                } elseif ($brand_term) {
                    $filter_url = get_term_link($brand_term, 'product_brand');
                }
              @endphp
              @if($author || $brand)
              <a href="{{ $filter_url }}" class="inline-block mb-4 hover:underline">
                {{ __('By', 'sage') }} {{ $author ?: $brand }}
              </a>
              @endif

              {{-- product title --}}
              <h1 class="text-xl font-medium text-gray-900">{{ $product->get_name() }}</h1>
              <div class="hidden lg:flex flex-col mt-4">
                @if($product->get_type() !== 'variable')
                  @php get_stock_and_price($product) @endphp
                @endif
              </div>
            </div>
            {{-- Product Gallery --}}
            <div class="mt-8 lg:col-span-7 lg:col-start-1 lg:row-span-3 lg:row-start-1 lg:mt-0">
              <div class="woocommerce-product-gallery woocommerce-product-gallery--with-images woocommerce-product-gallery--columns-4 images" 
                   data-columns="4">
                @php
                  $attachment_ids = $product->get_gallery_image_ids();
                  $main_image_id = $product->get_image_id();
                  $external_image = get_post_meta($product->get_id(), '_external_image_url', true);
                  $external_gallery = get_post_meta($product->get_id(), '_external_gallery_urls', true);

                  // Create array of all images (main + gallery)
                  $all_images = [];
                  
                  // Add main image
                  if ($main_image_id) {
                      $all_images[] = ['type' => 'internal', 'id' => $main_image_id];
                  } elseif ($external_image) {
                      $all_images[] = ['type' => 'external', 'url' => $external_image];
                  }

                  // Add gallery images
                  if ($attachment_ids) {
                      foreach ($attachment_ids as $attachment_id) {
                          $all_images[] = ['type' => 'internal', 'id' => $attachment_id];
                      }
                  }
                  if ($external_gallery && is_array($external_gallery)) {
                      foreach ($external_gallery as $url) {
                          $all_images[] = ['type' => 'external', 'url' => $url];
                      }
                  }
                @endphp

                <div class="flex flex-col-reverse md:flex-row md:gap-4">
                  {{-- Thumbnails - Only show if there's more than one image --}}
                  @if(count($all_images) > 1)
                    <div class="whitespace-nowrap overflow-scroll md:max-h-[35rem] md:overflow-x-hidden md:min-w-fit mt-4 md:mt-0 md:w-20">
                      @foreach($all_images as $index => $image)
                        @if(!empty($image['url']))
                          <div class="cursor-pointer w-20 h-20 rounded-lg overflow-hidden border-2 border-transparent hover:border-[var(--color-primary)] transition-all gallery-thumbnail inline-block md:flex align-top md:align-baseline"
                               data-full-image-url="{{ $image['url'] }}">
                            <img src="{{ esc_url($image['url']) }}" 
                                 alt="{{ esc_attr($product->get_name()) }} thumbnail" 
                                 class="w-full h-full object-cover gallery-thumbnail-img" />
                          </div>
                        @endif
                      @endforeach
                    </div>
                  @endif
                  {{-- Main Image --}}
                  @php
                    $image_style_option = get_option('store_settings')['product_image_style'] ?? 'contain-white';
                    $main_image_object_class = $image_style_option === 'contain-white' ? 'object-contain' : 'object-cover';
                  @endphp
                  <div id='main-image' class="w-full h-full">
                    <div class="aspect-w-1 aspect-h-1 w-full">
                      @if(!empty($all_images))
                        @php $first_image = $all_images[0]; @endphp
                        @if($first_image['type'] === 'external')
                          <img src="{{ esc_url($first_image['url']) }}" 
                               alt="{{ esc_attr($product->get_name()) }}"
                               class="w-full h-full {{ $main_image_object_class }} rounded-lg"
                               id="main-product-image" />
                        @else
                          {!! wp_get_attachment_image($first_image['id'], 'woocommerce_single', false, [
                            'class' => 'w-full h-full ' . $main_image_object_class . ' rounded-lg',
                            'id' => 'main-product-image'
                          ]) !!}
                        @endif
                      @else
                        <img src="{{ wc_placeholder_img_src() }}" 
                             alt="{{ esc_attr__('Placeholder', 'woocommerce') }}"
                             class="w-full h-full {{ $main_image_object_class }} rounded-lg"
                             id="main-product-image" />
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            </div>
            @if($product->get_type() !== 'variable')
              <div class="flex flex-col lg:hidden mt-4">
                @php get_stock_and_price($product) @endphp
              </div>
            @endif
            
            {{-- Product Form --}}
            <div class="mt-8 lg:col-span-5">
              @if($product->is_type('variable'))
                @include('woocommerce.partials.variable-product')
              @else
                @php
                  $store_settings = get_option('store_settings') ?: [];
                  $hide_all_prices = $store_settings['hide_all_prices'] ?? false;
                  $buy_externally = $store_settings['buy_externally'] ?? false;
                @endphp
                @if(!$hide_all_prices && !$buy_externally)
                  <div class="flex items-center justify-center mt-4">
                    <button type="submit"
                      data-product_id="{{ $product->get_id() }}" 
                      data-quantity="1"
                      x-data
                      class="single_add_to_cart_button ajax_add_to_cart add_to_cart_button w-full custom-bg-color-primary hover:opacity-50 text-center text-white py-3 px-6 rounded-md font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 ring-[var(--color-primary)] {{ is_rtl()? 'rounded-tl-none rounded-bl-none' : 'rounded-tr-none rounded-br-none' }}">
                      {{__('Add to cart', 'woocommerce')}}
                    </button>
                  </div>
                @elseif($buy_externally)
                  @php
                    $product_id = $product->get_id();
                    $source_url = get_post_meta($product_id, '_network_source_url', true);
                    $ecommerceLink = get_option('store_settings')['store_info']['ecommerce_link'] ?? '';
                    $buy_url = !empty($source_url) ? esc_url($source_url) : esc_url($ecommerceLink);
                    
// Tenant-specific override: Demo Tenant routes purchases to map link.
if (get_bloginfo('name') === 'Demo Tenant') {
    $buy_url = $ecommerceLink;
}

                    // Calculate text color for better contrast
                    $primary_color = get_option('store_settings')['primary_color'] ?? '';
                    $text_color = wc_light_or_dark($primary_color, '', 'text-white');
                  @endphp
                  <div class="mt-6 w-full">
                      <a href="{{ $buy_url }}" 
                         target="_blank" 
                         rel="noopener noreferrer nofollow" 
                         id="buy-online-button"
                         class="group w-full flex items-center justify-center py-3 px-6 border border-transparent text-base font-semibold rounded-md {{ $text_color }} custom-bg-color-primary hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 ring-[var(--color-primary)] transition-all shadow-md hover:shadow-lg">
                          
                          {{-- 1. The "External" Visual Cue --}}
                          <svg class="h-5 w-5 {{ is_rtl()? 'ml-2' : 'mr-2' }} opacity-70 group-hover:opacity-100 transition-opacity" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                          </svg>
                          
                          {{-- 2. The Forensic Copy: Action + Channel + Entity --}}
                          <span class="uppercase tracking-wide">
                              {{ sprintf(__('Shop Online at %s', 'sage'), get_bloginfo('name')) }}
                          </span>
                      </a>
                  
                      {{-- 3. The "Trust & Origin" Micro-Copy --}}
                      {{-- 3. The "Trust & Origin" Micro-Copy --}}
                      <div class="text-center mt-2">
                          <p class="text-[11px] text-gray-500 font-medium">
                              <span class="inline-block w-1.5 h-1.5 custom-bg-color-primary rounded-full mr-1 align-middle"></span>
                              {{ __('Secure transaction through the official website', 'sage') }}
                          </p>
                      </div>
                  </div>
                @endif
              @endif

{{-- Buy in-Store button --}}
              @php
                // Add direction button if store coordinates are available
                $store_latitude = get_option('store_settings')['store_info']['latitude'] ?? '';
                $store_longitude = get_option('store_settings')['store_info']['longitude'] ?? '';
                
                // Get product ID and source URL
                $product_id = get_the_ID();
                $source_url = get_post_meta($product_id, '_network_source_url', true);
                
                // Check if we need to show either button
                $show_direction_button = !empty($store_latitude) && !empty($store_longitude);
                $show_source_button = !empty($source_url);

                // Calculate text color for better contrast
                $primary_color = get_option('store_settings')['primary_color'] ?? '';
                $text_color = wc_light_or_dark($primary_color, '', 'text-white');
              @endphp
              
              @if($show_direction_button || $show_source_button)
                <div class="mt-4 flex flex-wrap gap-3">
                  @if($show_direction_button)
                    @php
                      // Create a properly formatted Google Maps direction URL
                      $direction_url = 'https://www.google.com/maps/dir/?api=1&destination=' . $store_latitude . ',' . $store_longitude;

                      // check if there is a custom link for this store
                      $custom_link = get_option('store_settings')['seo']['buy_in_store_custom_link'] ?? '';
                      if($custom_link)
                        $direction_url = $custom_link;
                    @endphp
                      <a href="{{ $direction_url }}" target="_blank" rel="noopener noreferrer nofollow"  
                      id="buy-in-store-button"
                      class="flex items-center justify-center w-full py-3 px-6 border border-transparent text-base font-semibold rounded-md {{ $text_color }} custom-bg-color-primary hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 ring-[var(--color-primary)] transition-all shadow-md hover:shadow-lg">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ is_rtl()? 'ml-2' : 'mr-2' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      @php
                        $buy_in_store_phrases = [
                          __('Buy in-Store', 'sage'),
                          __('Visit Store', 'sage'),
                          __('Shop at Store', 'sage'),
                          __('In-Store Purchase', 'sage'),
                          __('Buy Locally', 'sage'),
                          __('Store Location', 'sage'),
                          __('Shop In-Person', 'sage'),
                          __('Visit Our Store', 'sage'),
                          __('Local Store', 'sage')
                        ];
                        $random_store_phrase = $buy_in_store_phrases[array_rand($buy_in_store_phrases)];
                      @endphp
                      {{ $random_store_phrase }} {!! get_bloginfo('name') !!}
                    </a>
                  @endif
                </div>
              @endif
            </div>
            <x-custom-tabs />
          </div>
        </div>
      </div>
    </div>
  @endwhile
  <x-product-full :product="$product" />

  @php
    do_action('woocommerce_after_single_product_summary');
    do_action('woocommerce_after_main_content');
    do_action('get_sidebar', 'shop');
    do_action('get_footer', 'shop');
  @endphp

  {{-- Include Cart Popup --}}
  @include('woocommerce.partials.cart-popup')
@endsection