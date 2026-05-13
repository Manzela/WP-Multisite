@if(is_shop() && !is_product_category() && !is_search() && !empty($storeOptions['store_info']['description']))
<meta name="description" content="{{ wp_strip_all_tags($storeOptions['store_info']['description']) }}">
@endif 