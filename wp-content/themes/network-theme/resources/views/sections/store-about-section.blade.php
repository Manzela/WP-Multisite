@php
    $store_settings = get_option('store_settings');
    $enable_delivery = get_option('enable_delivery', false);
    $delivery_rules = get_option('delivery_rules', '');
    $latitude = $store_settings['store_info']['latitude'] ?? '';
    $longitude = $store_settings['store_info']['longitude'] ?? '';
    $network_options = get_option('network_store_settings', []);

    // Extract place ID from GMB link
    $place_id = '';
    if (!empty($gmb)) {
        // Extract CID from GMB URL
        if (preg_match('/cid=(\d+)/', $gmb, $matches)) {
            $place_id = $matches[1];
        }
    }

    // Get store colors
    $storeOptions = get_option('store_settings', []);
    $primary_color = !empty($storeOptions['primary_color']) ? $storeOptions['primary_color'] : '#000000';
    $secondary_color = !empty($storeOptions['secondary_color']) ? $storeOptions['secondary_color'] : '#F3F4F6';
    $text_color = wc_light_or_dark($primary_color, '', 'text-white');
    $text_color_hover = wc_light_or_dark($secondary_color, '', 'text-white');
@endphp

<div class="w-full">
    {{-- Always Visible Content --}}
    <article class="space-y-3">
        {{-- Business Info Section (Always Visible - Minimal) --}}
        <div class="bg-white rounded-lg shadow-sm overflow-hidden pb-4">
            <div class="">
                @if(!empty($store_settings['store_info']['description']))
                    <p class="mt-2 text-gray-600 font-semibold line-clamp-3">
                        {!! $store_settings['store_info']['description'] !!}
                    </p>
                @endif
            </div>

            {{-- Collapse Trigger Button --}}
            <button 
                id="collapse-trigger"
                class="text-md font-medium underline hover:no-underline transition-all duration-200 focus:outline-none"
                style="color: {{ $primary_color }};"
                onclick="toggleCollapse()"
            >
                <span id="button-text">{{ __('Show More Information', 'sage') }}</span>
                <svg id="button-icon" class="inline-block w-4 h-4 ml-2 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            {{-- Collapsible Content --}}
            <div id="collapse-content" class="hidden space-y-3">
                
                {{-- Info Grid: Address, Hours, Reviews, Map --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- 1. Contact Details --}}
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden h-full">
                        <div class="p-4 space-y-4 h-full">
                            @if(!empty($store_settings['store_info']['address']))
                                <div class="flex gap-3">
                                    @svg('about.store', 'h-5 w-5 text-[var(--color-primary)] mt-1')
                                    <div>
                                        <p class="text-gray-500 mb-1">{{ __('Business Address:', 'sage') }}</p>
                                        <p class="text-gray-900">
                                            {{ $store_settings['store_info']['address'] }},
                                            @if(!empty($store_settings['store_info']['address_2']))
                                                {{ $store_settings['store_info']['address_2'] }},
                                            @endif
                                            {{ $store_settings['store_info']['city'] }}
                                            {{ $store_settings['store_info']['postcode'] }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                            
                            @if(!empty($store_settings['store_info']['phone']))
                                <div class="flex gap-3">
                                    @svg('tel', 'h-5 w-5 text-[var(--color-primary)] mt-1')
                                    <div>
                                        <p class="text-gray-500 mb-1">{{ __('Contact phone number:', 'sage') }}</p>
                                        <p class="text-gray-900" dir="ltr">{{ $store_settings['store_info']['phone'] }}</p>
                                    </div>
                                </div>
                            @endif

                            @if(!empty($store_settings['store_info']['email']))
                                <div class="flex gap-3">
                                    @svg('envelope', 'h-5 w-5 text-[var(--color-primary)] mt-1')
                                    <div>
                                        <p class="text-gray-500 mb-1">{{ __('Email:', 'woocommerce') }}</p>
                                        <p class="text-gray-900" dir="ltr">{{ $store_settings['store_info']['email'] }}</p>
                                    </div>
                                </div>
                            @endif

                            @if(!empty($store_settings['accessible']))
                                <div class="flex gap-3">
                                    @svg('wheelchair', 'h-5 w-5 text-[var(--color-primary)] mt-1')
                                    <div>
                                        <p class="text-gray-500 mb-1">{{ __('The store is physically accessible', 'sage') }}</p>
                                        @if(!empty($store_settings['accessible_description']))
                                            <p class="text-gray-900">{{ $store_settings['accessible_description'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @php
                                $gmb_link = $store_settings['seo']['gmb_link'] ?? ''; 
                            @endphp
                            @if (!empty($gmb_link))
                                <div class="flex gap-3">
                                    <a href="{{$gmb_link}}" rel="nofollow" class="flex items-center gap-3">
                                        @svg('social.google-my-business', 'h-5 w-5 mt-1') <p class="text-gray-500">{{__('Visit Our Google Store', 'sage')}}</p>
                                    </a>
                                </div>
                            @endif

                            @if (!empty($store_settings['social']))
                                <div class="flex gap-3">
                                    {{-- IMPORTANT: using invisible icon in order to align the text --}}
                                    @svg('noicon', 'h-5 w-5', ['style' => 'visibility: hidden;'])
                                    <p class="text-gray-500 mb-1">{{ __('Connect with Us', 'sage') }}</p>
                                    <div class="flex gap-4 space-x-reverse">
                                        @foreach ($store_settings['social'] as $social)
                                            <a id='social-icon' data-social="{{$social['icon']}}" href="{{ $social['url'] }}" class="text-gray-400 hover:text-gray-500"
                                                target="_blank" rel="noopener noreferrer nofollow">
                                                <span class="sr-only">{{ $social['icon'] }}</span>
                                                @svg('social.' . $social['icon'], 'h-6 w-6')
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- 2. Opening Hours --}}
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden h-full">
                        <div class="p-4 h-full">
                            <h2 class="text-xl font-bold text-[var(--color-primary)] mb-4">{{ __('Opening Hours', 'sage') }}</h2>
                            @if(!empty($store_settings['store_info']['hours']))
                                <div class="max-w-full">
                                    @foreach($store_settings['store_info']['hours'] as $day => $hours)
                                        <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
                                            <span class="text-gray-500">
                                                @switch($day)
                                                    @case('sunday') {{ __('Sunday', 'sage') }} @break
                                                    @case('monday') {{ __('Monday', 'sage') }} @break
                                                    @case('tuesday') {{ __('Tuesday', 'sage') }} @break
                                                    @case('wednesday') {{ __('Wednesday', 'sage') }} @break
                                                    @case('thursday') {{ __('Thursday', 'sage') }} @break
                                                    @case('friday') {{ __('Friday', 'sage') }} @break
                                                    @case('saturday') {{ __('Saturday', 'sage') }} @break
                                                @endswitch
                                            </span>
                                            <span class="text-gray-900" dir="ltr">
                                                @php
                                                    if (isset($hours['closed']) && $hours['closed']) {
                                                        echo '<span class="text-red-500">' . __('Closed', 'sage') . '</span>';
                                                    } else {
                                                        $period_strings = [];
                                                        foreach ($hours['periods'] as $period) {
                                                            if (isset($period['open']) && isset($period['close'])) {
                                                                $period_strings[] = $period['open'] . ' - ' . $period['close'];
                                                            }
                                                        }
                                                        echo implode(', ', $period_strings);
                                                    }
                                                @endphp
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                
                    {{-- 3. GMB Reviews Widget (Row 2 Col 1) --}}
                    @php
                        $gmb_reviews = \App\Providers\GoogleBusinessServiceProvider::getFilteredReviews(5, 5);
                    @endphp
                    @if(!empty($gmb_reviews))
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-100 h-full">
                           {{-- We wrap specific fields to ensure height match if needed, but the widget itself is self-contained. 
                                Including it directly might result in double borders if the widget has its own.
                                The partial 'reviews-widget' has a 'w-full' wrapper and border/shadow.
                                We should strip the wrapper in the partial or accommodate here.
                                For now, I'll include it directly. --}}
                            @include('partials.reviews-widget', [
                                'reviews' => $gmb_reviews,
                                'primary_color' => $primary_color
                            ])
                        </div>
                    @else
                        {{-- Placeholder or Delivery info if reviews empty, to maintain grid balance? --}}
                        {{-- Delivery Options can go here if Reviews are empty, or just blank --}}
                        @if($enable_delivery)
                             <div class="bg-white rounded-lg shadow-sm p-4 h-full">
                                <h2 class="text-xl font-bold text-[var(--color-primary)] mb-4">{{ __('Delivery & Pickup Options:', 'sage') }}</h2>
                                <div class="prose prose-sm max-w-none text-gray-600">
                                    {!! $delivery_rules !!}
                                </div>
                            </div>
                        @else
                             <div></div> {{-- Empty cell --}}
                        @endif
                    @endif

                    {{-- 4. Map Section (Row 2 Col 2) --}}
                    @if(!empty($latitude) && !empty($longitude))
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden h-full">
                            <div class="p-4 border-b">
                                <h2 class="text-xl font-bold text-[var(--color-primary)]">{{ __('How to get to us?', 'sage') }}</h2>
                            </div>
                            <div class="bg-gray-100 h-full">
                                {{-- OpenStreetMap (Full Height to fill cell) --}}
                                <iframe 
                                    class="w-full h-full min-h-[320px] border-0"
                                    loading="lazy" 
                                    allowfullscreen 
                                    referrerpolicy="no-referrer-when-downgrade"
                                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $longitude - 0.01 }},{{ $latitude - 0.01 }},{{ $longitude + 0.01 }},{{ $latitude + 0.01 }}&layer=mapnik&marker={{ $latitude }},{{ $longitude }}">
                                </iframe>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Delivery Options (If not used in grid above) --}}
                @if($enable_delivery && !empty($gmb_reviews)) 
                    {{-- Only show here if Reviews TOOK the slot above. If Reviews were empty, Delivery is already shown. --}}
                     <div class="bg-white rounded-lg shadow-sm p-4">
                        <h2 class="text-xl font-bold text-[var(--color-primary)] mb-4">{{ __('Delivery & Pickup Options:', 'sage') }}</h2>
                        <div class="prose prose-sm max-w-none text-gray-600">
                            {!! $delivery_rules !!}
                        </div>
                    </div>
                @endif
        </div>
    </article>
</div>

<script>
function toggleCollapse() {
    const content = document.getElementById('collapse-content');
    const button = document.getElementById('collapse-trigger');
    const buttonText = document.getElementById('button-text');
    const buttonIcon = document.getElementById('button-icon');
    
    const primaryColor = '{{ $primary_color }}';
    
    if (content.classList.contains('hidden')) {
        // Show content
        content.classList.remove('hidden');
        content.classList.add('animate-fade-in');
        buttonText.textContent = getTranslation('Show Less Information', 'sage');
        buttonIcon.classList.add('rotate-180');
    } else {
        // Hide content
        content.classList.add('hidden');
        content.classList.remove('animate-fade-in');
        buttonText.textContent = getTranslation('Show More Information', 'sage');
        buttonIcon.classList.remove('rotate-180');
    }
}

// Add custom animation classes if not already present
if (!document.querySelector('style[data-collapse-styles]')) {
    const style = document.createElement('style');
    style.setAttribute('data-collapse-styles', 'true');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    `;
    document.head.appendChild(style);
}
</script>

