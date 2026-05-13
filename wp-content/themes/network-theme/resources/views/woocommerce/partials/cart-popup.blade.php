<div id="cart-popup" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full mx-4">
    <div class="flex justify-between items-start mb-4">
      <h3 class="text-lg font-medium text-gray-900">
        {!! sprintf(__('%s has been added to your cart.', 'woocommerce'), __('The product', 'sage')) !!}
      </h3>
      <button type="button" id="close-popup" class="text-gray-400 hover:text-gray-500">
        <span class="sr-only">{{ __('Close', 'woocommerce') }}</span>
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    
    <div class="product-info flex gap-4 mb-6">
      <div class="product-image w-24 h-24 flex-shrink-0 bg-gray-100 rounded-md overflow-hidden">
        {{-- Image will be inserted via JavaScript --}}
      </div>
      <div class="flex-1">
        <h4 class="product-title font-medium text-gray-900 mb-1"></h4>
        <div class="product-variation text-sm text-gray-500 mb-2"></div>
        <div class="product-quantity text-sm text-gray-500 mb-1"></div>
        <div class="product-price font-medium custom-color-primary"></div>
      </div>
    </div>

    <div class="flex gap-4">
      <button type="button" 
              id="continue-shopping"
              class="flex-1 bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:opacity-50 transition-colors">
        {{ __('Continue shopping', 'woocommerce') }}
      </button>
      <a href="{{ wc_get_cart_url() }}" 
         class="flex-1 custom-bg-color-primary hover:opacity-50 text-white py-2 px-4 rounded-md transition-colors text-center">
        {{ __('View cart', 'woocommerce') }}
      </a>
    </div>
  </div>
</div> 