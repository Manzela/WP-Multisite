@php
    if (empty($reviews))
        return;

    $rating_value = $rating_value ?? 0;
    $review_count = $review_count ?? 0;

    // Fallback if not passed, try to fetch from options
    if (empty($rating_value) || empty($review_count)) {
        $header = get_option('gmb_reviews_header', []);
        $rating_value = $header['rating'] ?? 0;
        $review_count = $header['userRatingCount'] ?? 0;
    }

    // Google Map Link
    $store_settings = get_option('store_settings', []);
    $gmb_link = $store_settings['seo']['gmb_link'] ?? '';

    $star_color = '#Fbbc04'; 
@endphp

<div class="w-full">
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-100 p-4">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-3 border-b border-gray-100 pb-4">
            <div>
                <h2 class="text-xl font-bold mb-1" style="color: {{ $primary_color }};">
                    {{ __('Google Rating', 'sage') }}
                </h2>
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-bold text-gray-900">{{ number_format($rating_value, 1) }}</span>
                    <div class="flex text-yellow-400">
                        @for($i = 0; $i < 5; $i++)
                            @if($i < floor($rating_value))
                                <span>★</span>
                            @elseif($i < ceil($rating_value))
                                <span class="opacity-50">★</span>
                            @else
                                <span class="text-gray-300">★</span>
                            @endif
                        @endfor
                    </div>
                    <span class="text-sm text-gray-500">({{ number_format($review_count) }}
                        {{ __('reviews', 'sage') }})</span>
                </div>
            </div>

            @if(!empty($gmb_link))
                <a href="{{ $gmb_link }}" target="_blank" rel="nofollow noopener"
                    class="text-sm font-medium hover:underline flex items-center gap-1"
                    style="color: {{ $primary_color }};">
                    {{ __('View on Google Maps', 'sage') }}
                </a>
            @endif
        </div>

        {{-- Reviews Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($reviews as $review)
                @php
                    $author = $review['authorAttribution']['displayName'] ?? __('Anonymous', 'sage');
                    $photo = $review['authorAttribution']['photoUri'] ?? '';
                    $text = is_array($review['text'] ?? '') ? ($review['text']['text'] ?? '') : ($review['text'] ?? '');
                    $time = $review['relativePublishTimeDescription'] ?? '';

                    // Simple initial avatar logic
                    $initial = mb_substr($author, 0, 1);
                @endphp
                <div class="border border-gray-100 rounded-lg p-4 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center gap-3 mb-3">
                        @if($photo)
                            <img src="{{ $photo }}" alt="{{ $author }}" class="w-8 h-8 rounded-full bg-gray-100 object-cover">
                        @else
                            <div
                                class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">
                                {{ $initial }}
                            </div>
                        @endif
                        <div>
                            <div class="text-sm font-semibold text-gray-900 line-clamp-1">{{ $author }}</div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-yellow-400">★★★★★</span>
                                <span class="text-xs text-gray-400">{{ $time }}</span>
                            </div>
                        </div>
                    </div>

                    @if($text)
                        <div x-data="{ expanded: false }" class="text-sm text-gray-600">
                            <p class="line-clamp-3" :class="{ 'line-clamp-none': expanded }">
                                {{ $text }}
                            </p>
                            @if(mb_strlen($text) > 100)
                                <button @click="expanded = !expanded"
                                    class="text-xs font-medium mt-1 hover:underline focus:outline-none"
                                    style="color: {{ $primary_color }};">
                                    <span
                                        x-text="expanded ? '{{ __('Read Less', 'sage') }}' : '{{ __('Read More', 'sage') }}'"></span>
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>