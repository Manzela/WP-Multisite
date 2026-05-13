@extends('layouts.app')

@section('title')
  {{ __('About Us', 'sage') }} - {!! get_bloginfo('name') !!}
@endsection
@include('schema.about-page')
@section('content')

<article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 rtl">
    {{-- Business Info Section --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
        <div class="p-6 border-b">
            <h1 class="text-2xl font-bold text-[var(--color-primary)]">
                {!! $store_settings['store_name'] ?? get_bloginfo('name') !!}
            </h1>
            <p class="mt-2 text-gray-600">
                {!! $store_settings['store_info']['description'] ?? '' !!}
            </p>
        </div>

        {{-- Contact Details --}}
        <div class="p-6 space-y-4">
            @if(!empty($store_settings['store_info']['address']))
                <div class="flex gap-3">
                    @svg('about.store', 'h-5 w-5 text-[var(--color-primary)] mt-1')
                    <div>
                        <p class="text-gray-500 mb-1">{{ __('Business Address:', 'sage') }}</p>
                        <p class="text-gray-900">
                            {{ $store_settings['store_info']['address'] }},
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
        </div>
    </div>

    {{-- Opening Hours --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
        <h2 class="text-xl font-bold text-[var(--color-primary)] mb-4">{{ __('Opening Hours', 'sage') }}</h2>
        @if(!empty($store_settings['store_info']['hours']))
            <div class="max-w-[20rem]">
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

    {{-- Delivery & Pickup Options --}}
    @if($enable_delivery)
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-xl font-bold text-[var(--color-primary)] mb-4">{{ __('Delivery & Pickup Options:', 'sage') }}</h2>
            <div class="prose prose-sm max-w-none text-gray-600">
                {!! $delivery_rules !!}
            </div>
        </div>
    @endif
@php
    $latitude = $store_settings['store_info']['latitude'];
    $longitude = $store_settings['store_info']['longitude'];
    $network_options = get_site_option('network_store_settings', []);
    $api_key = isset($network_options['network_google_places_api_key']) ? $network_options['network_google_places_api_key'] : '';
@endphp
    {{-- Map Section --}}
    @if(!empty($latitude) && !empty($longitude) && !empty($api_key))
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-[var(--color-primary)]">{{ __('How to get to us?', 'sage') }}</h2>
            </div>
            <div class="aspect-w-16 aspect-h-9">
                <iframe 
                    width="600" 
                    height="450" 
                    style="border:0" 
                    loading="lazy" 
                    allowfullscreen 
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps/embed/v1/place?key={{ $api_key }}&q={{ $latitude }},{{ $longitude }}">
                </iframe>
            </div>
        </div>
    @endif

    {{-- Reviews Section // styling is from within the action hook --}}
    @php(do_action('google-business-reviews'))
</article>
@endsection
