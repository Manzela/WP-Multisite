@php
    // Get the Bing Webmaster Tools verification code from network settings or site settings
    $bing_webmaster_code = '';
    
    // First try to get from network settings
    if (function_exists('get_site_option')) {
        $network_settings = get_site_option('network_store_settings', []);
        $bing_webmaster_code = $network_settings['network_bing_webmaster'] ?? '';
    }
    
    // If not found in network settings, try site settings
    if (empty($bing_webmaster_code)) {
        $site_settings = get_option('store_settings', []);
        $bing_webmaster_code = $site_settings['network_bing_webmaster'] ?? '';
    }
@endphp

@if(!empty($bing_webmaster_code))
<!-- Bing Webmaster Tools -->
<meta name="msvalidate.01" content="{{ esc_attr($bing_webmaster_code) }}" />
@endif
