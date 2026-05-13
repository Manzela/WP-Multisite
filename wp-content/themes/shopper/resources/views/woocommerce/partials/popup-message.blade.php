@php
    $network_settings = get_site_option('network_store_settings', []);
    $showPopup = false;
    $title = '';
    $message = '';
    $n_times = 0;

    if($network_settings['popup_message']['enable_popup'] ?? false) { // network popup is active
        if(!($storeOptions['popup_message']['use_local'] ?? false)) { // "use local popup" is unchecked
            // use network popup
            $showPopup = true;
            $title = $network_settings['popup_message']['popup_title'] ?? '';
            $message = $network_settings['popup_message']['popup_message'] ?? '';
            $n_times = $network_settings['popup_message']['n_times'] ?? 0;
            
        } else if($storeOptions['popup_message']['enable_popup'] ?? false) {
            // use local popup
            $showPopup = true;
            $title = $storeOptions['popup_message']['popup_title'] ?? '';
            $message = $storeOptions['popup_message']['popup_message'] ?? '';
            $n_times = $storeOptions['popup_message']['n_times'] ?? 0;
        }
    } else if($storeOptions['popup_message']['enable_popup'] ?? false) {
        // use local popup (network popup is not active)
        $showPopup = true;
        $title = $storeOptions['popup_message']['popup_title'] ?? '';
        $message = $storeOptions['popup_message']['popup_message'] ?? '';
        $n_times = $storeOptions['popup_message']['n_times'] ?? 0;
    }

    // Calculate text color for better contrast
    $primary_color = get_option('store_settings')['primary_color'] ?? '';
    $text_color = wc_light_or_dark($primary_color, '', 'text-white');
@endphp

@if($showPopup)
<div id="popup" 
    class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center"
    data-max-shows="{{ $n_times ?? 0 }}"
    data-show-popup="{{ $showPopup ? 'true' : 'false' }}"
>
    <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full mx-4 min-h-[250px] flex flex-col text-center">
        <div class="flex justify-between items-start mb-4">
            <h3 class="text-lg font-medium text-gray-900 w-full text-center" id="popup-title">{{ $title }}</h3>
            <button type="button" id="close-popup-message" class="text-gray-400 hover:text-gray-500 shrink-0">
                <span class="sr-only">{{ __('Close', 'woocommerce') }}</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    
        <div id="popup-message" class="prose prose-sm max-w-none flex text-center justify-center align-center">
            {!! $message !!}
        </div>
        <div class="flex gap-4 mt-auto">
            <a href="{{ home_url('/') }}" class="flex-1 custom-bg-color-primary hover:opacity-50 {{ $text_color }} font-semibold py-2 px-4 rounded-md transition-colors text-center">
                {{ __('Go to store', 'sage') }}
            </a>
        </div>
    </div>
</div>
@endif