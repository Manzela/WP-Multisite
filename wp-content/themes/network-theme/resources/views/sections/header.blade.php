<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (window.innerWidth < 768) {
      var burgerBtn = document.getElementById('burger-menu-button');
      if (burgerBtn) {
        burgerBtn.addEventListener('click', function () {
          document.documentElement.classList.toggle('fixed');
        });
      }
    }
  });
</script>

@include('sections.sticky-notice')

@if(is_checkout())
  <!-- Custom checkout header -->
  <header class="bg-white shadow z-50">
    <div class="mx-auto max-w-7xl px-2 sm:px-4 lg:divide-y lg:divide-gray-200 lg:px-8">
      <div class="relative flex h-16 justify-between">
        <!-- Store logo -->
        <div class="relative w-full md:w-fit justify-center md:justify-start flex px-2 lg:px-0 z-50">
          <div class="flex flex-shrink-0 items-center">
            @if (has_custom_logo())
              <a href="{{ home_url('/') }}">
                <img class="h-8 w-auto" src="{{ $getLogoURL }}" alt="{{ get_bloginfo('name') }}">
              </a>
            @else

              <a href="{{ home_url('/') }}" class="text-2xl font-bold text-primary">{!! get_bloginfo('name') !!}</a>

            @endif
          </div>
        </div>

        <div class="relative flex items-center z-50 {{ is_rtl() ? 'ml-2' : 'mr-2' }}">

          <!-- Mini-cart -->
          <a href="{{ wc_get_cart_url() }}" class="relative flex items-center gap-1 hover:text-gray-500">
            @svg('cart', 'w-6 h-6')
            <span
              class="cart-count absolute mb-4 {{ is_rtl() ? 'mr-3' : 'ml-3' }} bg-[red] text-white text-sm rounded-full h-5 w-5 flex items-center justify-center {{ WC()->cart->get_cart_contents_count() === 0 ? 'hidden' : '' }}">
              {{ WC()->cart->get_cart_contents_count() }}
            </span>
          </a>

        </div>
      </div>
    </div>
  </header>

@else
  <header class="bg-white shadow z-50" x-data="{ mobileMenuOpen: false, userMenuOpen: false }">
    <div class="mx-auto max-w-7xl px-2 sm:px-4 lg:divide-y lg:divide-gray-200 lg:px-8">
      <div class="relative flex h-16 justify-between">
        <!-- Burger menu button -->
        <div id="burger-menu-button" class="relative flex items-center z-50" x-cloak>
          <button @click="if(window.innerWidth < 786)
                    mobileMenuOpen = !mobileMenuOpen;
                    else openSidebarCategories = !openSidebarCategories" type="button"
            class="relative inline-flex items-center justify-center rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary {{ is_rtl() ? '-mr-2' : '-ml-2' }}"
            :aria-expanded="mobileMenuOpen">
            <span class="absolute -inset-0.5"></span>
            <span class="sr-only">Open menu</span>
            <svg class="h-6 w-6" :class="{ 'hidden': mobileMenuOpen, 'block': !mobileMenuOpen }" fill="none"
              viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" x-cloak>
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            <svg class="h-6 w-6 hidden" :class="{ 'block': mobileMenuOpen, 'hidden': !mobileMenuOpen }" fill="none"
              viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" x-cloak>
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Store logo -->
        <div
          class="relative w-full md:w-fit justify-center md:justify-start flex px-2 lg:px-0 {{ is_rtl() ? 'lg:mr-4' : 'lg:ml-4' }} z-50">
          <div class="flex flex-shrink-0 items-center">
            @if (has_custom_logo())
              <a href="{{ home_url('/') }}">
                <img class="h-8 w-auto" src="{{ $getLogoURL }}" alt="{{ get_bloginfo('name') }}">
              </a>
            @else
              <a href="{{ home_url('/') }}" class="text-2xl font-bold text-primary">{!! get_bloginfo('name') !!}</a>
            @endif
          </div>
        </div>

        <!-- Search bar (hidden on mobile, center on desktop) -->
        <div class="hidden lg:flex relative z-0 flex-1 items-center justify-center sm:inset-0">
          <div class="w-full sm:max-w-xs">
            <div class="relative">
              {!! do_shortcode('[fibosearch layout="prix" style="solaris" mobile_overlay="0"]') !!}
            </div>
          </div>
        </div>

        <div class="relative flex items-center z-50 {{ is_rtl() ? 'ml-2' : 'mr-2' }}">

          <!-- Mini-cart -->
          @php
            $store_settings = get_option('store_settings') ?: [];
            $hide_all_prices = $store_settings['hide_all_prices'] ?? false;
            $buy_externally = $store_settings['buy_externally'] ?? false;
          @endphp
          @if(!$hide_all_prices && !$buy_externally)
            <a href="{{ wc_get_cart_url() }}" class="relative flex items-center gap-1 hover:text-gray-500">
              @svg('cart', 'w-6 h-6')
              <span
                class="cart-count absolute mb-4 {{ is_rtl() ? 'mr-3' : 'ml-3' }} bg-[red] text-white text-sm rounded-full h-5 w-5 flex items-center justify-center {{ WC()->cart->get_cart_contents_count() === 0 ? 'hidden' : '' }}">
                {{ WC()->cart->get_cart_contents_count() }}
              </span>
            </a>
          @endif

          <!-- My account dropdown -->
          <!-- TEMPORARY: hidden -->
          <div class="hidden relative flex-shrink-0 {{ is_rtl() ? 'mr-4 -ml-2' : 'ml-4 -mr-2' }}" x-data="{ open: false }"
            @click.away="open = false">
            <div>
              <a class="md:hidden" href="{{ wc_get_account_endpoint_url('dashboard') }}">
                <button
                  class="relative flex rounded-full bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 hover:text-gray-500">
                  <span class="absolute -inset-1.5"></span>
                  <span class="sr-only">{{ __('My account', 'woocommerce') }}</span>
                  @svg('user', 'w-6 h-6')
                </button>
              </a>

              <button @click="open = !open" type="button"
                class="hidden relative md:flex rounded-full bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 hover:text-gray-500"
                id="user-menu-button" :aria-expanded="open">
                <span class="absolute -inset-1.5"></span>
                <span class="sr-only">{{ __('My account', 'woocommerce') }}</span>
                @svg('user', 'w-6 h-6')
              </button>
            </div>

            <div x-show="open" x-transition:enter="transition ease-out duration-100"
              x-transition:enter-start="transform opacity-0 scale-95"
              x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
              x-transition:leave-start="transform opacity-100 scale-100"
              x-transition:leave-end="transform opacity-0 scale-95"
              class="absolute mt-2 w-48 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50 {{ is_rtl() ? 'left-0 origin-top-right' : 'right-0 origin-top-left' }}"
              role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">

              @if (is_user_logged_in())
                <a href="{{ wc_get_account_endpoint_url('dashboard') }}"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('My account', 'woocommerce') }}</a>
                <a href="{{ wc_get_account_endpoint_url('orders') }}"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('My orders', 'sage') }}</a>
                <a href="{{ wp_logout_url(home_url()) }}"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('Logout', 'woocommerce') }}</a>
              @else
                <a href="/my-account"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('Login', 'woocommerce') }}</a>
              @endif
            </div>
          </div>
        </div>
      </div>
      {{-- in order to split again between mobile and desktop: add this element here:
      < x-categories-desktop /> --}}
    </div>

    <!-- Mobile search (visible only on mobile) -->
    <div class="lg:hidden h-[48px] border-t border-gray-100">
      <div class="px-4 py-1">
        {!! do_shortcode('[fibosearch class="w-full" layout="prix" style="solaris" mobile_overlay="0"]') !!}
      </div>
    </div>

    <x-categories-mobile />
    <!-- Mobile menu -->
  </header>
@endif