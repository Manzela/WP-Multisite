@php
    // Get the Microsoft Clarity Project ID from network settings or site settings
    $microsoft_clarity_id = '';
    
    // First try to get from network settings
    if (function_exists('get_site_option')) {
        $network_settings = get_site_option('network_store_settings', []);
        $microsoft_clarity_id = $network_settings['network_microsoft_clarity'] ?? '';
    }
    
    // If not found in network settings, try site settings
    if (empty($microsoft_clarity_id)) {
        $site_settings = get_option('store_settings', []);
        $microsoft_clarity_id = $site_settings['network_microsoft_clarity'] ?? '';
    }
@endphp

@if(!empty($microsoft_clarity_id))
<!-- Microsoft Clarity -->
<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "{{ esc_attr($microsoft_clarity_id) }}");
</script>
@endif
