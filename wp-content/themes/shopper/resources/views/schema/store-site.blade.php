{{-- Store Site Schema: 4 separate JSON-LD blocks --}}
{{-- All data pre-built in SchemaServiceProvider — zero WP function calls here --}}

<!-- 1. LocalBusiness -->
<script type="application/ld+json">
{!! json_encode($localBusinessSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

<!-- 2. WebSite (SearchAction) -->
<script type="application/ld+json">
{!! json_encode($websiteSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

<!-- 3. BreadcrumbList -->
<script type="application/ld+json">
{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

<!-- 4. WebPage -->
<script type="application/ld+json">
{!! json_encode($webPageSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>