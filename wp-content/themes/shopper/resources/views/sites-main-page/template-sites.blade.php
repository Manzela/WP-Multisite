{{--
Template Name: Sites Directory
--}}

@extends('layouts.app')

@php
  $sites = get_sites([
    'public' => 1,
    'archived' => 0,
    'spam' => 0,
    'deleted' => 0,
    'site__not_in' => [get_main_site_id()]
  ]);

  $days = [
    'sunday' => __('Sunday', 'sage'),
    'monday' => __('Monday', 'sage'),
    'tuesday' => __('Tuesday', 'sage'),
    'wednesday' => __('Wednesday', 'sage'),
    'thursday' => __('Thursday', 'sage'),
    'friday' => __('Friday', 'sage'),
    'saturday' => __('Saturday', 'sage'),
  ];

  // set translated strings in order to use them in js
  $translations = [
    'Read more' => __('Read more', 'woocommerce'),
    'Read less' => __('Read less', 'woocommerce'),
  ];
@endphp

@section('title')
  {!! sprintf(__('%s Stores - Find a nearby branch', 'sage'), get_bloginfo('name')) !!}
@endsection

@section('head')
  <script type="text/javascript">var translations = @json($translations);</script>
@endsection

@section('content')
  <main class="flex-grow">
    <div class="mx-auto max-w-7xl py-8">
      @include('sites-main-page.sites-banner')

      {{-- Search Bar --}}
      <div class="relative max-w-md mx-auto mb-8">
        <input type="text" id="store-search"
          class="w-full px-4 py-2 pr-10 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200"
          placeholder="{{ __('Search for a store', 'sage') }}..." dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
        <div class="absolute {{ is_rtl() ? 'right-[92%]' : 'left-[92%]' }} top-1/2 -translate-y-1/2 text-gray-400">
          @svg('search', 'w-5 h-5 text-gray-500')
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="stores-grid">
        @foreach($sites as $site)
          @php
            $blog_id = $site->blog_id;
            switch_to_blog($blog_id);
            $storeOptions = get_option('store_settings', []);
            $blog_details = get_blog_details($blog_id);

            $formatted_full_address =
              get_option('woocommerce_store_address') .
              ', ' .
              get_option('woocommerce_store_address_2') .
              ', ' .
              get_option('woocommerce_store_city') .
              ', ' .
              (WC()->countries->countries[WC()->countries->get_base_country()] ?? '') .
              ', ' .
              get_option('woocommerce_store_postcode');

            $string = preg_replace('/\s*,\s*/', ', ', $formatted_full_address); // Normalize spaces and commas
            $string = preg_replace('/,+/', ',', $string); // Replace multiple commas with a single comma
            $string = rtrim($string, ', '); // Remove trailing comma and space if exists

            $formatted_full_address = $string;
            $hours = $storeOptions['store_info']['hours'] ?? [];
          @endphp

          <div class="store-card">
            <div class="store-card-content">

              {{-- Store Name --}}
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">
                  {!! html_entity_decode(esc_html($blog_details->blogname)) !!}
                </h2>
              </div>

              {{-- Store Address --}}
              @if (!empty($formatted_full_address))
                <div class="flex items-start gap-2 mb-2">
                  @svg('map', 'w-4 h-4 flex-shrink-0 mt-1')
                  <span class="line-clamp-2 store-woo-address">{{ $formatted_full_address }}</span>
                </div>
              @endif

              {{-- Store Phone --}}
              @if (!empty($storeOptions['store_info']['phone']))
                <div class="flex items-center gap-2 mb-2">
                  @svg('tel', 'w-4 h-4 flex-shrink-0 mt-1')
                  {{-- always use ltr for phone number --}}
                  <a dir="ltr" href="tel:{{ esc_attr($storeOptions['store_info']['phone']) }}"
                    class="hover:text-blue-600 transition-colors store-phone">{{ esc_html($storeOptions['store_info']['phone']) }}</a>
                </div>
              @endif

              {{-- Store Email --}}
              @if (!empty($storeOptions['store_info']['email']))
                <div class="flex items-center gap-2 mb-2">
                  @svg('envelope', 'w-4 h-4 flex-shrink-0 mt-1')
                  <a href="mailto:{{ esc_attr($storeOptions['store_info']['email']) }}"
                    class="hover:text-blue-600 transition-colors store-email"
                    dir="ltr">{{ esc_html($storeOptions['store_info']['email']) }}</a>
                </div>
              @endif

              <div class="space-y-3 text-sm text-gray-600 ">
                <div class="flex items-center gap-2 mt-2">
                  <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    @svg('shop', 'w-4 h-4 ml-1')
                    {{ __('Self-pickup', 'sage') }}
                  </span>

                  {{-- Store Accessibility --}}
                  @if (!empty($storeOptions['accessible']) && $storeOptions['accessible'] === '1')
                    <div class="tooltip" title="{{ esc_attr($storeOptions['accessible_description']) }}">
                      @svg('wheelchair', 'w-5 h-5 flex-shrink-0 mt-1')
                    </div>
                  @endif
                </div>

                {{-- Store Description --}}
                @if (!empty($storeOptions['store_info']['description']))
                  <div class="border-t border-gray-100 pt-2 mt-2">
                    <div class="store-description-wrapper">
                      <div class="store-description collapsed">
                        {!! wp_kses_post($storeOptions['store_info']['description']) !!}</div>
                      <button class="read-more-btn flex items-center gap-2">
                        <span class="read-more-text">
                          {{ __('Read more', 'woocommerce') }}
                        </span>
                        @svg('catarrow', 'w-4 h-4 flex-shrink-0 mt-1 transition-transform rotate-90')
                      </button>
                    </div>
                  </div>
                @endif

                {{-- Store Opening Hours --}}
                @if (!empty($storeOptions['store_info']['hours']))
                  <details class="group mt-2">
                    <summary class="flex items-center gap-2 cursor-pointer list-none">
                      @svg('clock', 'w-4 h-4 flex-shrink-0')
                      <span>{{ __('Opening Hours', 'sage') }}</span>
                      @svg('catarrow', 'w-4 h-4 flex-shrink-0 rotate-90 transition-transform group-open:-rotate-90')
                    </summary>
                    <div class="mt-2 pr-6 border-r border-gray-100">
                      @foreach ($storeOptions['store_info']['hours'] as $day => $time)
                        <div class="flex justify-between text-sm py-1">
                          <span>{{ esc_html($days[$day]) }}:</span>
                          @php
                            if (!empty($time['closed'])) {
                              echo '<span class="text-red-600">' . __('Closed', 'sage') . '</span>';
                            } else {
                              $period_strings = [];
                              foreach ($time['periods'] as $period) {
                                if (isset($period['open']) && isset($period['close'])) {
                                  $period_strings[] = $period['open'] . ' - ' . $period['close'];
                                }
                              }
                              echo '<span class="text-green-600">' . implode(', ', $period_strings) . '</span>';
                            }
                          @endphp
                        </div>
                      @endforeach
                    </div>
                  </details>
                @endif
              </div>

              {{-- Store Social Links --}}
              {{-- NOTE: here we set the gbm_link as part of social links (different from gbm_link on the subdomain's
              footer) --}}
              @php
                $gmb_link = $storeOptions['seo']['gmb_link'] ?? ''; 
              @endphp
              @if (!empty($storeOptions['social']) || !empty($gmb_link))
                <div class="flex justify-center gap-4 mt-4 border-t border-gray-100 pt-4">
                  @if (!empty($storeOptions['social']))
                    @foreach ($storeOptions['social'] as $social)
                      <a href="{{ esc_url($social['url']) }}" target="_blank"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                        @svg('social/' . $social['icon'], 'w-5 h-5 flex-shrink-0 mt-1')
                      </a>
                    @endforeach
                  @endif

                  @if (!empty($gmb_link))
                    <a href="{{ esc_url($gmb_link) }}" target="_blank"
                      class="text-gray-400 hover:text-gray-600 transition-colors">
                      @svg('social/google-my-business', 'w-5 h-5 flex-shrink-0 mt-1')
                    </a>
                  @endif

                </div>
              @endif
            </div>

            @php
              // calculate text colors for both states
              $primary_color = !empty($storeOptions['primary_color']) ? $storeOptions['primary_color'] : '#000000';
              $secondary_color = !empty($storeOptions['secondary_color']) ? $storeOptions['secondary_color'] : '#F3F4F6';
              $text_color = wc_light_or_dark($primary_color, '', 'text-white');
              $text_color_hover = wc_light_or_dark($secondary_color, '', 'text-white');
            @endphp

            {{-- Store Visit Button --}}
            <div
              class="store-card-footer bg-[var(--primary-color)] hover:bg-[var(--secondary-color)] {{ $text_color }} {{ $text_color_hover === 'text-white' ? 'hover:text-white' : '' }}"
              style="--primary-color: {{ esc_attr($primary_color) }}; --secondary-color: {{ esc_attr($secondary_color) }};">
              <a href="{{ get_site_url($blog_id) }}" class="w-full h-full flex items-center justify-center font-medium">
                {{ __('Visit Store', 'woocommerce') }}
              </a>
            </div>
          </div>

          @php
            restore_current_blog();
          @endphp
        @endforeach
      </div>
    </div>
  </main>



  <script>
    document.getElementById('store-search').addEventListener('input', function (e) {
      const searchTerm = e.target.value.toLowerCase();
      const stores = document.querySelectorAll('#stores-grid > div');

      stores.forEach(store => {
        const searchableElements = store.querySelectorAll('h2, .store-description, .store-email, .store-phone, .store-address, .store-woo-address');
        let found = false;

        searchableElements.forEach(element => {
          if (element && element.textContent.toLowerCase().includes(searchTerm)) {
            found = true;
          }
        });

        store.style.display = found ? '' : 'none';
      });
    });

    document.querySelectorAll('.read-more-btn').forEach(button => {
      button.addEventListener('click', function () {
        const description = this.parentElement.querySelector('.store-description');
        const textSpan = this.querySelector('.read-more-text');

        description.classList.toggle('expanded');
        textSpan.textContent = description.classList.contains('expanded') ? translations['Read less'] : translations['Read more'];

        const arrow = this.querySelector('svg');
        if (description.classList.contains('expanded')) {
          arrow.classList.remove('rotate-90');
          arrow.classList.add('-rotate-90');
        } else {
          arrow.classList.remove('-rotate-90');
          arrow.classList.add('rotate-90');
        }
      });
    });
  </script>
@endsection