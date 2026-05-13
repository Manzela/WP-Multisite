@php
    // Get the GTM container ID from network settings or site settings
    $gtm_container_id = '';
    
    // First try to get from network settings
    if (function_exists('get_site_option')) {
        $network_settings = get_site_option('network_store_settings', []);
        $gtm_container_id = $network_settings['network_gtm_container'] ?? '';
    }
    
    // If not found in network settings, try site settings
    if (empty($gtm_container_id)) {
        $site_settings = get_option('store_settings', []);
        $gtm_container_id = $site_settings['network_gtm_container'] ?? '';
    }
@endphp

@if(!empty($gtm_container_id))
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ esc_attr($gtm_container_id) }}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
@endif 