{{-- Category Schema: 5 separate JSON-LD blocks --}}
{{-- All data pre-built in SchemaServiceProvider — zero WP function calls here --}}

<!-- 1. CollectionPage with ItemList -->
<script type="application/ld+json">
{!! json_encode($collectionPageSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

<!-- 2. LocalBusiness (with parentOrganization) -->
<script type="application/ld+json">
{!! json_encode($localBusinessSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

<!-- 3. BreadcrumbList -->
<script type="application/ld+json">
{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

<!-- 4. OfferCatalog -->
<script type="application/ld+json">
{!! json_encode($offerCatalogSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

<!-- 5. WebSite -->
<script type="application/ld+json">
{!! json_encode($websiteSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>