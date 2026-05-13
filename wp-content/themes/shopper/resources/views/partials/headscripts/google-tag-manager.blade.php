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
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{{ esc_attr($gtm_container_id) }}');</script>
<!-- End Google Tag Manager -->
@endif 
