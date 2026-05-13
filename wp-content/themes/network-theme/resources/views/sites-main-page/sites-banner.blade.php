@php
    // Get network settings
    $network_options = get_site_option('network_store_settings', []);
    $main_title = $network_options['main_page']['main_title'] ?? '';
    $description = $network_options['main_page']['description'] ?? '';
    $banner_id = $network_options['main_page']['banner'] ?? '';
    $banner_url = $banner_id ? wp_get_attachment_url($banner_id) : '';

    // Get domain name (without www)
    $domain = get_bloginfo('name');

    // Count subdomains (exclude main site)
    $sites = get_sites([
        'public'   => 1,
        'archived' => 0,
        'spam'     => 0,
        'deleted'  => 0,
        'site__not_in' => [1],
    ]);
    $subdomain_count = count($sites);
@endphp

<div class="mx-auto w-full flex flex-col items-center justify-center py-8">
  @if($main_title)
    <h1 class="text-2xl font-bold text-center mb-4">{{ $main_title }}</h1>
  @else
    <h1 class="text-2xl font-bold text-center mb-4">{{ get_bloginfo('name') }} — {{ $subdomain_count }} Lojas</h1>
  @endif

  @if($banner_url)
    <img src="{{ esc_url($banner_url) }}" alt="Banner" class="w-full h-auto mb-4" style="max-height:300px;object-fit:cover;" />
  @endif

  {{-- description --}}
  <div class="text-center text-lg font-bold">
    <div>
      {{ sprintf(__("Find the %s branch closest to you - %d stores across the country", 'sage'), $domain, $subdomain_count) }}
    </div>
    @if($description)
      <div>{{ $description }}</div>
    @endif
  </div>
</div>