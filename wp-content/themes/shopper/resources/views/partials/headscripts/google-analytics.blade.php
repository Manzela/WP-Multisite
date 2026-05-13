@php
    // Get the GA4 property ID from network settings or site settings
    $ga4_property_id = '';
    
    // First try to get from network settings
    if (function_exists('get_site_option')) {
        $network_settings = get_site_option('network_store_settings', []);
        $ga4_property_id = $network_settings['network_ga4_property'] ?? '';
    }
    
    // If not found in network settings, try site settings
    if (empty($ga4_property_id)) {
        $site_settings = get_option('store_settings', []);
        $ga4_property_id = $site_settings['network_ga4_property'] ?? '';
    }
@endphp

@if(!empty($ga4_property_id))
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ esc_attr($ga4_property_id) }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '{{ esc_attr($ga4_property_id) }}');
</script>
@endif 