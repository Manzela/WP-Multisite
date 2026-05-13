@php
    global $product;
    
    if (!$product) {
        return;
    }

    $args = apply_filters(
        'woocommerce_output_related_products_args',
        [
            'posts_per_page' => 12,
            'columns'        => 4,
            'orderby'       => 'rand',
            'order'         => 'desc',
        ]
    );

    // Get related products and ensure unique entries
    $related_product_ids = array_unique(wc_get_related_products($product->get_id(), $args['posts_per_page']));
    $related_products = array_filter(array_map('wc_get_product', $related_product_ids));
    
    // Reset array keys to ensure sequential indexing
    $related_products = array_values($related_products);

    if (empty($related_products)) {
        return;
    }

    $slides_count = count($related_products);
    $hide_all_prices = get_option('store_settings')['hide_all_prices'] ?? false;
@endphp

@if($slides_count > 0)
  <section 
    class="related products mt-8"
    x-data="{
      activeSlide: 0,
      slidesCount: {{ $slides_count }},
      slideWidth: window.innerWidth < 768 ? 220 : 250,
      slidesToShow: window.innerWidth < 768 ? 1 : (window.innerWidth < 1024 ? 3 : 4),
      touchStartX: 0,
      touchEndX: 0,
      direction: '{{ is_rtl() ? 1 : -1 }}',
      
      next() {
        if (this.activeSlide >= this.slidesCount - this.slidesToShow) {
          this.activeSlide = 0;
        } else {
          this.activeSlide++;
        }
        this.updateTransform();
      },
      
      prev() {
        if (this.activeSlide <= 0) {
          this.activeSlide = this.slidesCount - this.slidesToShow;
        } else {
          this.activeSlide--;
        }
        this.updateTransform();
      },
      
      updateTransform() {
        const el = this.$refs.slideTrack;
        if (!el) return;
        el.style.transform = `translateX(${this.activeSlide * (this.slideWidth + 16) * this.direction}px)`;
      },
      
      handleTouchStart(e) {
        this.touchStartX = e.touches[0].clientX;
      },
      
      handleTouchEnd(e) {
        this.touchEndX = e.changedTouches[0].clientX;
        this.handleSwipe();
      },
      
      handleSwipe() {
        const swipeDistance = this.touchStartX - this.touchEndX;
        const minSwipeDistance = 50; // Minimum distance for swipe to register
        
        if (Math.abs(swipeDistance) > minSwipeDistance) {
          if (swipeDistance > 0) {
            this.prev(); // Swipe right to left
          } else {
            this.next(); // Swipe left to right
          }
        }
      },
      
      init() {
        this.updateTransform();
      }
    }"
    x-init="init()"
    @resize.window="
      slideWidth = window.innerWidth < 768 ? 220 : 250;
      slidesToShow = window.innerWidth < 768 ? 1 : (window.innerWidth < 1024 ? 3 : 4);
      init();
    "
  >
    <div class="relative">
      <h2 class="text-2xl font-bold mb-6">
        @php
        $store_name = get_option('store_settings')['seo']['store_name'] ?? '';
        $translated_string = esc_html__('Related products', 'woocommerce'); // default
        
        if(!empty($store_name))
            $translated_string = sprintf(__('Customers Also Bought', 'sage') . ' ' 
            . sprintf(__('at %s', 'sage'), $store_name));

        echo esc_html($translated_string);

        @endphp
      </h2>

      {{-- Carousel Container --}}
      <div 
        class="overflow-hidden mx-0 lg:mx-10" 
        dir="{{ is_rtl() ? 'rtl' : 'ltr' }}"
        @touchstart="handleTouchStart"
        @touchend="handleTouchEnd"
      >
        <div 
          x-ref="slideTrack"
          class="flex gap-4 transition-transform duration-300 ease-in-out"
        >
          @foreach($related_products as $related_product)
            @php
              // Calculate discount percentage
              $regular_price = (float) $related_product->get_regular_price();
              $sale_price = (float) $related_product->get_sale_price();
              $discount_percentage = 0;

              if ($regular_price > 0 && $sale_price > 0) {
                  $discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
              }
            @endphp

            @php
                // Apply custom image styling
                $image_style_option = get_option('store_settings')['product_image_style'] ?? 'contain-white';
                $related_image_object_class = $image_style_option === 'contain-white' ? 'object-contain' : 'object-cover';
                $related_image_background_class = $image_style_option === 'contain-white' ? 'bg-white' : 'bg-gray-200';
              @endphp

            <div 
              {{ wc_product_class("w-[220px] md:w-[250px] flex-shrink-0 group relative flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white", $related_product) }}
            >
              <div class="aspect-h-4 aspect-w-3 {{ $related_image_background_class }} sm:aspect-none group-hover:opacity-75 sm:h-96">
                @php
                  $image_id = $related_product->get_image_id();
                  $product_blog_id = get_post_meta($related_product->get_id(), '_product_blog_id', true);
                  
                  // If no image ID and product is from another site, try to get image from original site
                  if (!$image_id && $product_blog_id && $product_blog_id != get_current_blog_id()) {
                    switch_to_blog($product_blog_id);
                    $image_id = $related_product->get_image_id();
                    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : '';
                    restore_current_blog();
                  } else {
                    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : '';
                  }
                  
                  // Check for external image URL if no image is found
                  if (empty($image_url)) {
                    $external_image_url = get_post_meta($related_product->get_id(), '_external_image_url', true);
                    $image_url = $external_image_url ?: wc_placeholder_img_src('woocommerce_thumbnail');
                  } else {
                    $image_url = $image_url ?: wc_placeholder_img_src('woocommerce_thumbnail');
                  }
                @endphp                
                <img 
                  src="{{ $image_url }}"
                  alt="{{ $related_product->get_name() }}"
                  class="h-full w-full {{ $related_image_object_class }} object-center sm:h-full sm:w-full"
                  loading="lazy"
                />
              </div>
              <div class="flex flex-1 flex-col space-y-2 p-4">
                <h3 class="text-sm font-medium text-gray-900">
                  <a href="{{ $related_product->get_permalink() }}">
                    <span aria-hidden="true" class="absolute inset-0"></span>
                    {{ $related_product->get_name() }}
                  </a>
                </h3>
                <div class="flex flex-1 flex-col justify-end">
                  @if ($discount_percentage > 0 && !$hide_all_prices)
                    <p class="text-sm italic text-gray-500">
                      {{ __('You saved:', 'sage') }} {!! $discount_percentage !!}%
                    </p>
                  @endif
                  @if(!$hide_all_prices)
                    <p class="product-price text-base font-medium {{ $discount_percentage > 0 ? 'text-red-500' : 'text-gray-900' }}">
                      {!! $related_product->get_price_html() !!}
                    </p>
                  @endif

                  @if($related_product->is_type('variable'))
                    @php
                      $attributes = $related_product->get_variation_attributes();
                      $color_attribute = isset($attributes['pa_color']) ? $attributes['pa_color'] : (isset($attributes['color']) ? $attributes['color'] : null);
                    @endphp
                    
                    @if($color_attribute)
                      <div class="flex gap-1 mt-2">
                        @foreach($color_attribute as $color)
                          @php
                            $color_term = get_term_by('slug', $color, 'pa_color');
                          @endphp
                          <div 
                            class="w-4 h-4 rounded-full border border-gray-200" 
                            style="background-color: #{{ $color_term->slug }};"
                            title="{{ $color_term->name }}"
                          ></div>
                        @endforeach
                      </div>
                    @endif
                  @endif
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>

      {{-- Navigation Buttons - Now visible on all screens --}}
      <button 
        @click="{{ is_rtl() ? 'next' : 'prev' }}" 
        class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-white rounded-full p-2 shadow-lg hover:bg-gray-50 focus:outline-none transform translate-x-4 lg:-translate-x-5"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
      </button>

      <button 
        @click="{{ is_rtl()? 'prev' : 'next' }}" 
        class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-white rounded-full p-2 shadow-lg hover:bg-gray-50 focus:outline-none transform -translate-x-4 lg:translate-x-5"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
      </button>
    </div>
  </section>
@endif