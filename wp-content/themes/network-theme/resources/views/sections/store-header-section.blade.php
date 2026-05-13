{{-- resources/views/sections/store-header-section.blade.php --}}

@php
    $banner_id = $storeOptions['store_banner'] ?? '';
    $banner_url = wp_get_attachment_url($banner_id);
@endphp
<img src="{{ $banner_url }}" alt="Store image" class="w-full h-full object-cover mt-[2px]" />