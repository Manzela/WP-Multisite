@php
/**
 * Shop breadcrumb
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/breadcrumb.php.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     2.3.0
 * @see         woocommerce_breadcrumb()
 */

if (!defined('ABSPATH')) {
    exit;
}
@endphp

@if (!empty($breadcrumb))
    <nav id='breadcrumb' class="flex max-w-full my-4" aria-label="Breadcrumb">
        <ol role="list" class="flex flex-wrap overflow-hidden items-center min-w-full">
            @foreach ($breadcrumb as $key => $crumb)
                <li>
                    @if ($key === 0)
                        <div>
                            <a href="{{ esc_url($crumb[1]) }}" class="text-gray-400 hover:text-gray-500">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                    <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6H3a1 1 0 0 1-.707-1.707l7-7Z" clip-rule="evenodd" />
                                </svg>
                                <span class="sr-only">{{ esc_html($crumb[0]) }}</span>
                            </a>
                        </div>
                    @else
                        <div class="flex items-center">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5 shrink-0 text-gray-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                            </svg>
                            @if (!empty($crumb[1]) && count($breadcrumb) !== $key + 1)
                                <a href="{{ esc_url($crumb[1]) }}" class="{{ is_rtl()? 'mr-2 sm:mr-4' : 'ml-2 sm:ml-4' }} text-xs sm:text-sm font-medium text-gray-500 hover:text-gray-700 truncate max-w-[100px] sm:max-w-[200px]">
                                    {{ html_entity_decode(esc_html($crumb[0])) }}
                                </a>
                            @else
                                <span class="{{ is_rtl()? 'mr-2 sm:mr-4' : 'ml-2 sm:ml-4' }} text-xs sm:text-sm font-medium text-gray-500 truncate max-w-[100px] sm:max-w-[200px]" aria-current="page">
                                    {{ html_entity_decode(esc_html($crumb[0])) }}
                                </span>
                            @endif
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif