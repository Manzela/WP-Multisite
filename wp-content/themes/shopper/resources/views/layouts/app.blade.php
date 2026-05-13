<!doctype html>
<html @php(language_attributes()) class="h-full">

<head>
  <?php 
  $site_icon_32 = get_site_icon_url(32);
$site_icon_192 = get_site_icon_url(192);
if ($site_icon_32): ?>
  <link rel="icon" href="{{ $site_icon_32 }}" sizes="32x32" />
  <link rel="icon" href="{{ $site_icon_192 }}" sizes="192x192" />
  <?php else: ?>
  <link rel="icon" href="{{ network_site_url('/') }}favicon.ico" type="image/x-icon">
  <?php endif; ?>

  @include('partials.headscripts.google-tag-manager')
  @include('partials.headscripts.bing-webmaster-tools')
  @include('partials.headscripts.microsoft-clarity')
  @include('partials.headscripts.head-script')

  <?php
$s_settings = get_option('store_settings') ?: [];
$s_lat = $s_settings['store_info']['latitude'] ?? '';
$s_lng = $s_settings['store_info']['longitude'] ?? '';
$s_name = $s_settings['seo']['store_name'] ?? get_bloginfo('name');
  ?>
  <?php if ($s_lat && $s_lng): ?>
  <meta name="geo.placename" content="{{ $s_name }}" />
  <meta name="geo.position" content="{{ $s_lat }}; {{ $s_lng }}" />
  <meta name="ICBM" content="{{ $s_lat }}, {{ $s_lng }}" />
  <?php endif; ?>

  <style>
    :root {
      --color-primary:
        {{ !empty($s_settings['primary_color']) ? $s_settings['primary_color'] : 'black' }}
      ;
      --color-secondary:
        {{ !empty($s_settings['secondary_color']) ? $s_settings['secondary_color'] : 'gray' }}
      ;
    }

    [x-cloak] {
      display: none !important;
    }

    /* English legal pages */
    body.page-id-104.data-processing-addendum,
    body.page-id-100.master-subscription-agreement,
    body.page-id-105.sub-processors,
    body.page-id-102.terms-and-conditions-vendors,
    body.page-id-3.privacy-policy-en,
    body.page-id-107.trial-agreement,
    body.page-id-114.trial-agreement-us {
      direction: ltr !important;
      text-align: left !important;
    }
  </style>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  @yield('head')
  @php(do_action('get_header'))
  @hasSection('title')
  <title>@yield('title')</title>
  @php(do_action('wp_head'))
  @else
  @php(wp_head())
  @endif
</head>

<body @php(body_class('h-full bg-white'))>
  @php(wp_body_open())
  @include('partials.headscripts.body-top-script')
  {{-- @include('partials.headscripts.google-analytics') remove if not needed --}}
  @include('partials.headscripts.google-tag-manager-noscript')

  <div id="app" x-data="{ mobileMenuOpen: false, openSidebarCategories: false }">
    </a>

    @if (!is_main_site() || strpos($_SERVER['REQUEST_URI'], '/tiendas/') !== false || strpos($_SERVER['REQUEST_URI'], '/tienda/') !== false || strpos($_SERVER['REQUEST_URI'], '/store/') !== false)
      @include('sections.header')
    @else
      @include('sites-main-page.sites-header')
    @endif

    <main id="main" class="main">
      <div class="mx-auto max-w-7xl px-4 pb-4 sm:px-6 lg:px-8">
        {{-- main content section --}}
        @yield('content')
      </div>
      <x-sidebar-categories-wrapper />
    </main>

    @include('sections.footer')
  </div>
  @include('partials.headscripts.body-bottom-script')
  @php(do_action('get_footer'))
  @php(wp_footer())
</body>

</html>