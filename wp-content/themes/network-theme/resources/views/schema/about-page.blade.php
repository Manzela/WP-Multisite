<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'AboutPage',
    'name' => 'About Us',
    'url' => get_site_url() . '/about',
    'description' => $store_settings['store_info']['description'] ?? 'Learn more about our company\'s mission, values, and the team behind our success.',
    'mainEntity' => [
        '@type' => 'Organization',
        'name' => get_option('store_settings')['seo']['store_name'] ?? 'Your Company Name',
        'url' => $store_settings['store_info']['ecommerce_link'] ?? get_site_url(), // [FIX] Use Ecommerce Link
        'logo' => [
            '@type' => 'ImageObject',
            'url' => (function () use ($store_settings) {
                $logoId = $store_settings['store_logo'] ?? '';
                return $logoId ? wp_get_attachment_url($logoId) : (get_site_url() . '/logo.png');
            })()
        ],
        'sameAs' => array_merge(
            $socialLinks ?? [],
            !empty($store_settings['seo']['gmb_link']) ? [$store_settings['seo']['gmb_link']] : []
        ),
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'telephone' => get_option('store_settings')['store_info']['phone'] ?? '+1-123-456-7890',
            'contactType' => 'Customer Support',
            'areaServed' => 'Worldwide',
            'availableLanguage' => [$store_settings['seo']['language'] ?? 'English']
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>