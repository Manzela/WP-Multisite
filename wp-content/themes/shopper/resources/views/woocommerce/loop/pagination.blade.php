@php
if (!defined('ABSPATH')) {
    exit;
}

$total = isset($total) ? $total : wc_get_loop_prop('total_pages');
$current = isset($current) ? $current : wc_get_loop_prop('current_page');
$base = isset($base) ? $base : esc_url_raw(str_replace(999999999, '%#%', remove_query_arg('add-to-cart', get_pagenum_link(999999999, false))));
$format = isset($format) ? $format : '';

if ($total <= 1) {
    return;
}

// Full pagination for desktop
$desktop_links = paginate_links([
    'base' => $base,
    'format' => $format,
    'add_args' => false,
    'current' => max(1, $current),
    'total' => $total,
    'type' => 'array',
    'end_size' => 1,
    'mid_size' => 1,
    'prev_next' => true,
    'prev_text' => '',
    'next_text' => '',
    'show_all' => false,
]);

// Simplified pagination for mobile (just current, prev, next)
$mobile_links = [
    'prev' => $current > 1 ? get_pagenum_link($current - 1) : null,
    'current' => $current,
    'next' => $current < $total ? get_pagenum_link($current + 1) : null,
];
@endphp

@if (!empty($desktop_links))
    <nav class="flex items-center justify-between border-t border-gray-200 px-4 sm:px-0" aria-label="{{ __('Product Pagination', 'woocommerce') }}">
        {{-- Previous Page Link --}}
        <div class="-mt-px flex w-0 flex-1">
            @if ($current > 1)
                <a href="{{ get_pagenum_link($current - 1) }}" class="inline-flex items-center border-t-2 border-transparent pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 {{ is_rtl() ? 'pl-1' : 'pr-1' }}">
                    @svg('pagenationarrow', ['class' => 'h-5 w-5 text-gray-400 ' . (is_rtl() ? 'ml-3 rotate-180' : 'mr-3')])
                    {{ __('Previous', 'woocommerce') }}
                </a>
            @endif
        </div>

        {{-- Desktop Pagination --}}
        <div class="hidden md:flex -mt-px">
            @foreach ($desktop_links as $link)
                @php
                    $isCurrentPage = strpos($link, 'current') !== false;
                    $pageNumber = strip_tags($link);
                    $isDots = strpos($link, 'hellip') !== false;
                    $url = preg_match('/href=["\']([^"\']+)["\']/i', $link, $matches) ? $matches[1] : '#';
                    
                    if (trim($pageNumber) === '') {
                        continue;
                    }
                @endphp

                @if ($isDots)
                    <span class="inline-flex items-center border-t-2 border-transparent px-2 pt-4 text-sm font-medium text-gray-500">...</span>
                @elseif ($url !== '#')
                    <a href="{{ html_entity_decode($url) }}" 
                       class="inline-flex items-center border-t-2 {{ $isCurrentPage ? 'custom-color-primary custom-border-color-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} px-2 pt-4 text-sm font-medium"
                       @if ($isCurrentPage) aria-current="page" @endif>
                        {{ $pageNumber }}
                    </a>
                @else
                    <span class="inline-flex items-center border-t-2 {{ $isCurrentPage ? 'custom-color-primary custom-border-color-primary' : 'border-transparent' }} px-2 pt-4 text-sm font-medium">
                        {{ $pageNumber }}
                    </span>
                @endif
            @endforeach
        </div>

        {{-- Mobile Pagination --}}
        <div class="flex md:hidden -mt-px justify-center">
            <span class="inline-flex items-center border-t-2 custom-color-primary custom-border-color-primary px-2 pt-4 text-sm font-medium">
                {{ $current }} / {{ $total }}
            </span>
        </div>

        {{-- Next Page Link --}}
        <div class="-mt-px flex w-0 flex-1 justify-end">
            @if ($current < $total)
                <a href="{{ get_pagenum_link($current + 1) }}" class="inline-flex items-center border-t-2 border-transparent pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 {{ is_rtl() ? 'pr-1' : 'pl-1' }}">
                    {{ __('Next', 'woocommerce') }}
                    @svg('pagenationarrow', ['class' => 'h-5 w-5 text-gray-400 ' . (is_rtl() ? 'mr-3' : 'ml-3 rotate-180')])
                </a>
            @endif
        </div>
    </nav>
@endif
