{{-- Simple Product Schema: 3 separate JSON-LD blocks --}}
{{-- All data pre-built in SchemaServiceProvider — zero WP function calls here --}}

<!-- 1. Product -->
<script type="application/ld+json">
{!! json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

<!-- 2. LocalBusiness (Seller) -->
<script type="application/ld+json">
{!! json_encode($localBusinessSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

<!-- 3. BreadcrumbList -->
<script type="application/ld+json">
{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>