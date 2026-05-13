{{--
 * This sidebar template is used to display the primary sidebar for the WooCommerce product archive page.
 * It utilizes the WordPress dynamic_sidebar function to pull in widgets assigned to the 'sidebar-primary' area.
 * You can manage the widgets from the WordPress admin under Appearance > Widgets.
 * This sidebar can be customized to include additional information or features as needed.
 --}}
@if(false)
<div class="sidebar-primary bg-white p-6 rounded-lg shadow-md overflow-y-auto h-full sticky top-0 {{ is_rtl()? 'text-right' : 'text-left' }}">
    <h2 class="text-2xl font-bold mb-6 mt-10 text-gray-800">{{ __('Filter', 'woocommerce') }}</h2>

    {{-- Price Filter Widget (Outside main form) --}}
    <div class="widget woocommerce widget_price_filter mb-8" x-data="{ open: true }">
        <h3 @click="open = !open" class="attribute-title text-lg font-semibold mb-4 text-gray-700 cursor-pointer flex justify-between items-center">
            {{ __('Filter by price', 'woocommerce') }}
            @svg('arrowclose', 'w-4 h-4 transition-transform duration-200', ['x-bind:class' => "{'rotate-180': open}"])
        </h3>
        <div x-show="open" class="attribute-content">
            @php
                if (class_exists('WooCommerce')) {
                    the_widget('WC_Widget_Price_Filter', array('title' => ''));
                }
            @endphp
        </div>
    </div>

    {{-- Attribute Filters Form --}}
    <form id="filter-form" action="{{ wc_get_page_permalink('shop') }}" method="get">
        {{-- Preserve price filter values if they exist --}}
        @if(isset($_GET['min_price']))
            <input type="hidden" name="min_price" value="{{ esc_attr($_GET['min_price']) }}">
        @endif
        @if(isset($_GET['max_price']))
            <input type="hidden" name="max_price" value="{{ esc_attr($_GET['max_price']) }}">
        @endif

        {{-- Brand Filter (Custom Taxonomy) --}}
        @php
            $brand_terms = get_terms(['taxonomy' => 'product_brand', 'hide_empty' => false]);
            $current_brand = isset($_GET['product_brand']) ? sanitize_text_field($_GET['product_brand']) : '';
        @endphp
        @if ($brand_terms && !is_wp_error($brand_terms))
            <div class="brand-filter mb-6" x-data="{ open: false, search: '' }">
                <h4 @click="open = !open" class="attribute-title text-md font-medium mb-3 text-gray-600 cursor-pointer flex justify-between items-center">
                    {{ __('Brand', 'sage') }}
                    @svg('arrowclose', 'w-4 h-4 transition-transform duration-200', ['x-bind:class' => "{'rotate-180': open}"])
                </h4>
                <div x-show="open">
                    @if(count($brand_terms) > 10)
                        <div class="mb-3">
                            <input 
                                type="text" 
                                x-model="search"
                                placeholder="{{ __('Search brands', 'sage') . '...' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>
                    @endif
                    <ul class="brand-list space-y-2 max-h-60 overflow-y-auto">
                        @foreach ($brand_terms as $term)
                            <li class="brand-item" x-show="!search || '{{ strtolower(str_replace("'", '׳', $term->name)) }}'.includes(search.toLowerCase())">
                                <label class="inline-flex items-center hover:bg-gray-100 p-2 rounded transition-colors duration-200 w-full">
                                    <input type="radio" 
                                        name="product_brand"
                                        value="{{ esc_attr($term->slug) }}" 
                                        class="form-radio text-blue-500 brand-radio"
                                        @checked($current_brand === $term->slug)>
                                    <span class="{{ is_rtl()? 'mr-2' : 'ml-2' }} text-gray-700">{{ esc_html($term->name) }}</span>
                                    @if($term->count > 0)
                                        <span class="{{ is_rtl()? 'mr-2' : 'ml-2' }} text-gray-500 text-sm">({{ $term->count }})</span>
                                    @endif
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Attribute Filter Widget --}}
        <div class="widget woocommerce widget_layered_nav mb-8">
            @if (class_exists('WooCommerce'))
                @php
                    $attribute_taxonomies = wc_get_attribute_taxonomies();
                @endphp
                @if ($attribute_taxonomies)
                    @foreach ($attribute_taxonomies as $tax)
                        @php
                            $attribute_name = wc_attribute_taxonomy_name($tax->attribute_name);
                            $terms = get_terms(['taxonomy' => $attribute_name, 'hide_empty' => false]);
                            $filter_name = 'filter_' . $tax->attribute_name;
                            $current_filter = isset($_GET[$filter_name]) ? sanitize_text_field($_GET[$filter_name]) : '';
                            $current_filter_array = array_filter(explode(',', $current_filter));
                        @endphp
                        @if ($terms && !is_wp_error($terms))
                            <div class="attribute-filter mb-6" x-data="{ open: false, search: '' }">
                                <h4 @click="open = !open" class="attribute-title text-md font-medium mb-3 text-gray-600 cursor-pointer flex justify-between items-center">
                                    {{ $tax->attribute_label }}
                                    @svg('arrowclose', 'w-4 h-4 transition-transform duration-200', ['x-bind:class' => "{'rotate-180': open}"])
                                </h4>
                                <div x-show="open">
                                    @if(count($terms) > 10)
                                        <div class="mb-3">
                                            <input 
                                                type="text" 
                                                x-model="search"
                                                placeholder="{{ __('Search', 'woocommerce') . '...' }}"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            >
                                        </div>
                                    @endif
                                    <ul class="attribute-list space-y-2 max-h-60 overflow-y-auto">
                                        @foreach ($terms as $term)
                                           {{--  @if($term->count > 0) --}}
                                                <li class="attribute-item" x-show="!search || '  {{ strtolower(str_replace("'", '׳', $term->name)) }}'.includes(search.toLowerCase())">
                                                    <label class="inline-flex items-center hover:bg-gray-100 p-2 rounded transition-colors duration-200 w-full">
                                                        <input type="checkbox" 
                                                            name="{{ $filter_name }}"
                                                            value="{{ urldecode(esc_attr($term->slug)) }}" 
                                                            class="form-checkbox text-blue-500 rounded attribute-checkbox"
                                                            data-attribute="{{ $filter_name }}"
                                                            @checked(in_array($term->slug, $current_filter_array))>
                                                        <span class="{{ is_rtl()? 'mr-2' : 'ml-2' }} text-gray-700">{{ esc_html($term->name) }}</span>
                                                        @if($term->count > 0)
                                                            <span class="{{ is_rtl()? 'mr-2' : 'ml-2' }} text-gray-500 text-sm">({{ $term->count }})</span>
                                                        @endif
                                                    </label>
                                                </li>
                                           {{--  @endif --}}
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif
            @endif
        </div>

        {{-- Apply Filters Button (outside both forms) --}}
        <button type="button" id="apply-all-filters" class="w-full custom-bg-color-primary hover:opacity-50 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                {{ __('Apply', 'woocommerce') }}
        </button>
    </form>

    {{-- Reset Button --}}
    <a href="{{ wc_get_page_permalink('shop') }}" class="w-full hover:opacity-50 custom-color-secondary font-bold py-2 px-4 rounded transition-colors duration-200 text-center block mt-4">
        {{ __('Reset', 'woocommerce') }}
    </a>
</div>
@endif