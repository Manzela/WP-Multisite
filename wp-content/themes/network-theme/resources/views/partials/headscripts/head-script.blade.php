{{-- 1. SEO PROTECTION REMOVED - Handled by ProductSeoServiceProvider --}}

{{-- 2. DYNAMIC SCRIPT INJECTION --}}
@php
    $site_settings = get_option('store_settings', []);
    $scripts = $site_settings['network_scripts'] ?? [];
    $visitor_id = '';

    // Cookie/Session Retrieval
    $referral_data = session('referral_data') ?: ($_COOKIE['referral_data'] ?? '');

    if (!empty($referral_data)) {
        if (is_array($referral_data)) {
            $decoded = $referral_data;
        } else {
            // Attempt standard decode
            $decoded = json_decode($referral_data, true);

            // Handle double-escaped strings (common in WP cookies)
            if (!is_array($decoded) && is_string($referral_data)) {
                $decoded = json_decode(stripslashes($referral_data), true);
            }
        }

        if (is_array($decoded) && !empty($decoded['visitorId'])) {
            $visitor_id = $decoded['visitorId'];
        }
    }
@endphp

@if(!empty($scripts))
    @foreach($scripts as $script)
        @if(
                !empty($script['network_script_enabled']) &&
                !empty($script['network_script_code']) &&
                $script['network_script_location'] === 'head'
            )

            @php
                $script_code = $script['network_script_code'];

                // Dynamic GA User ID Injection
                // Only run replace if we actually have a Visitor ID to insert
                if (!empty($visitor_id) && strpos($script_code, 'gtag(') !== false) {
                    $script_code = str_replace('[visitorid]', $visitor_id, $script_code);
                }
            @endphp

            {!! $script_code !!}
        @endif
    @endforeach
@endif