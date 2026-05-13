<header class="bg-white py-2 shadow-md border-b border-gray-200 flex-none">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-center">
            @php
                if (has_custom_logo()) {
                    $custom_logo_id = get_theme_mod('custom_logo');
                    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
                } else {
                    $logo = wp_get_attachment_image_src(get_site_option('network_store_settings')['network_store_logo'] ?? '', 'full');
                }
            @endphp

            @if ($logo)
                <a href="{{ home_url() }}">
                    <img src="{{ esc_url($logo[0]) }}" alt="{{ get_bloginfo('name') }} Logo" class="h-auto max-h-[150px]">
                </a>
            @else
                <h1 class="text-3xl font-bold mb-8 text-center">{!! get_bloginfo('name') !!}</h1>
            @endif
        </div>
    </div>
</header>