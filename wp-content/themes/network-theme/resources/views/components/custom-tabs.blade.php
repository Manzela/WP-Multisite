@php
  $product_tabs = apply_filters('woocommerce_product_tabs', array());
@endphp

@if(!empty($product_tabs))
  <div class="woocommerce-tabs wc-tabs-wrapper lg:col-span-5 lg:col-start-8">
    <div class="flex items-center justify-start bg-gray-100 border border-gray-300 p-4 transition-all duration-200 mb-2 mt-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 flex-shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span class="{{ is_rtl()? 'mr-2' : 'ml-2' }}">
        {{ __('The prices displayed are subject to change. The binding price is the price indicated at the checkout in the store at the time of purchase. Errors and omissions excepted.', 'sage') }}
      </span>
    </div>
    
    <div class="flex items-center justify-start bg-gray-100 border border-gray-300 p-4 transition-all duration-200 mb-2 mt-3">
      @svg('shop', 'w-7 h-7 flex-shrink-0 mt-1 wc-block-checkout__shipping-method-option-icon')
      <span class="wc-block-checkout__shipping-method-option-title {{ is_rtl()? 'mr-2' : 'ml-2' }}">
        @php
          $store_name = get_option('store_settings')['seo']['store_name'] ?? '';
          $translated_string = __('Self-pickup only', 'sage'); // default
          
          if(!empty($store_name)) {
            $translated_string = sprintf( __('%s is available', 'woocommerce'), __('Self-pickup', 'sage'));
            $translated_string .= ' ' . sprintf(__('at %s', 'sage'), $store_name);
          }
          $translated_string .= '.';
          $store_phone = get_option('store_settings')['store_info']['phone'] ?? '';
        @endphp
        {{ $translated_string }} {{ __('For up-to-date inventory, it is recommended to check directly with the store.', 'sage')}}
        @if(!empty($store_phone))
          <br>
          {{ __('Contact phone number:', 'sage')}} <span dir="ltr">{{ $store_phone }}</span>
        @endif
      </span>
    </div>
    {{-- custom tab (optional) --}}
    @php
      $custom_tab_html = get_option('store_settings')['store_info']['custom_tab'] ?? '';
    @endphp
    @if(!empty(strip_tags($custom_tab_html)))
      <div class="flex items-center justify-start bg-gray-100 border border-gray-300 p-4 transition-all duration-200 mb-2 mt-3">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 flex-shrink-0 mt-1">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
        </svg>
        <span class="{{ is_rtl()? 'mr-2' : 'ml-2' }}">
          {!! $custom_tab_html !!}
        </span>
      </div>
    @endif
    
    {{-- @foreach($product_tabs as $key => $product_tab)
      @php

        $tab_title = isset($product_tab['title']) ? $product_tab['title'] : $key;
        $title = wp_kses_post(apply_filters('woocommerce_product_' . $key . '_tab_title', $tab_title, $key));

        ob_start();
        if (isset($product_tab['callback'])) {
            call_user_func($product_tab['callback'], $key, $product_tab);
        }
        $content = ob_get_clean();
      @endphp
      
      @if(!(strpos($content, __('No policies created yet.', 'sage')) !== false))
        @php
          echo accordion($title, $content);
        @endphp
      @endif
    @endforeach --}}
    
    @php
      do_action('woocommerce_product_after_tabs');
    @endphp
  </div>
@endif

@php
function accordion($title, $content) {
  if(empty($content)) return null;

  // Demo Tenant: collapse description by default to showcase accordion UX.
  
  return '
  <div x-data="{ isOpen: \'' . esc_html($title) . '\' === \'' . __('Description', 'woocommerce') . '\' && ' . (get_bloginfo('name') !== 'Demo Tenant' ? 'true' : 'false') . ' }"
     class="group rounded-xl overflow-hidden border border-gray-300 border-[50%] transition-all duration-200 mt-4">
      <button @click="isOpen = !isOpen" class="accordion-header w-full flex justify-between items-center p-5 transition-all duration-200 bg-white hover:bg-gray-50">
          <span class="text-lg font-medium flex items-center space-x-4">
              <span>' . esc_html($title) . '</span>
          </span>
          <span class="accordion-icon flex-shrink-0 w-6 h-6 transition-transform duration-300 ' . (is_rtl()? 'ml-4' : 'mr-4') . '" :class="isOpen ? \'rotate-180\' : \'\'">
              <svg class="w-6 h-6 text-gray-400 transition-colors duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                  <path fill-rule="evenodd" d="M12.53 16.28a.75.75 0 0 1-1.06 0l-7.5-7.5a.75.75 0 0 1 1.06-1.06L12 14.69l6.97-6.97a.75.75 0 1 1 1.06 1.06l-7.5 7.5Z" clip-rule="evenodd"></path>
              </svg>
          </span>
      </button>

      <div x-show="isOpen" class="accordion-content border-t ' . (is_rtl()? 'text-right' : 'text-left') . '">
          <div class="p-5 bg-white text-gray-600 leading-relaxed">' . $content . '</div>
      </div>
  </div>';
}
@endphp

