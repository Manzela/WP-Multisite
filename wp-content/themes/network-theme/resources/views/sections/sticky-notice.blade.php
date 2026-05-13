@php
    $store_settings = get_option('store_settings', []);
    $store_info = $store_settings['store_info'] ?? [];
    $hours = $store_info['hours'] ?? [];

    // Get current time in store's timezone
    $current_time = current_time('H:i');
    $current_day = strtolower(current_time('l'));
    $is_open = false;

    // Check if store is open using new periods format
    if (isset($hours[$current_day]) && !empty($hours[$current_day]['periods'])) {
        $day_hours = $hours[$current_day];

        // Skip if day is closed
        if (!isset($day_hours['closed']) || !$day_hours['closed']) {
            // Check if current time falls within any period
            foreach ($day_hours['periods'] as $period) {
                if (isset($period['open']) && isset($period['close'])) {
                    if ($current_time >= $period['open'] && $current_time <= $period['close']) {
                        $is_open = true;
                        break;
                    }
                }
            }
        }
    }
    $text_color = wc_light_or_dark($storeOptions['primary_color'] ?? '', '', 'text-white');
@endphp

<div class="sticky top-0 custom-bg-color-primary border-b border-gray-200 py-2 z-[100]">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4 lg:px-8">
        <div class="max-w-full flex justify-between items-center">
            {{-- Store Info (Right Side) --}}
            <div class="flex items-center space-x-3 rtl:space-x-reverse">
                {{-- Store Details --}}
                <div class="flex flex-col">
                    <span class="font-medium text-[13px] md:text-sm {{ $text_color }}">{{ bloginfo('name') }}</span>
                </div>

                {{-- Open/Closed Status --}}
                <div class="flex items-center">
                    <span
                        class="inline-flex items-center px-2 py-0.5 text-[11px] md:text-xs underline font-medium {{ $is_open ? 'text-green-800' : 'text-red-800' }}">
                        @if($is_open)
                            <span
                                class="h-1.5 w-1.5 md:h-2 md:w-2 bg-green-400 rounded-full {{ is_rtl() ? 'ml-1' : 'mr-1' }}"></span>
                            <span class="{{ $text_color }}">
                                {{ __('Open now', 'sage') }}
                            </span>
                        @else
                            <span
                                class="h-1.5 w-1.5 md:h-2 md:w-2 bg-red-400 rounded-full {{ is_rtl() ? 'ml-1' : 'mr-1' }}"></span>
                            <span class="{{ $text_color }}">
                                {{ __('Closed now', 'sage') }}
                            </span>
                        @endif
                    </span>
                </div>
            </div>

            {{-- Left Side Text --}}
            <div class="text-[11px] md:text-sm {{ $text_color }}">
                {{ __('Order now or purchase in store', 'sage') }}
            </div>
        </div>
    </div>
</div>