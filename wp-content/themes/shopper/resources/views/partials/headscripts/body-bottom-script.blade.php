@php
    $site_settings = get_option('store_settings', []);
    $scripts = $site_settings['network_scripts'] ?? [];
@endphp

@if(!empty($scripts))
    @foreach($scripts as $script)
        @if(!empty($script['network_script_enabled']) && 
            !empty($script['network_script_code']) && 
            $script['network_script_location'] === 'body_bottom')
            {!! $script['network_script_code'] !!}
        @endif
    @endforeach
@endif
