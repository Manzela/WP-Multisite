{{-- General Settings Tab --}}
<div id="general" class="tab-content" style="display: {{ request()->get('tab', 'general') === 'general' ? 'block' : 'none' }}">
    @php do_settings_sections('store-settings') @endphp
</div>