<?php
/**
 * Canonical Redirects Handler
 * 
 * This file handles canonical redirects for the site.
 * It uses the redirect_canonical filter to ensure proper trailing slashes for WooCommerce endpoints.
 */

// Add trailing slash redirects for WooCommerce endpoints
add_filter('redirect_canonical', function($redirect_url, $requested_url) {
    // Skip if the url is domain.shop/index.html
    // used by Merchantor plugin to verify the domain
    if (strpos($requested_url, '/index.html') !== false) {
        return $redirect_url;
    }

    // Define WooCommerce endpoints that should have trailing slashes
    $endpoints = ['my-account', 'cart', 'checkout'];

    foreach ($endpoints as $endpoint) {
        if (preg_match('#/' . $endpoint . '$#', $requested_url)) {
            // Add trailing slash
            return trailingslashit($requested_url);
        }
    }

    return $redirect_url;
}, 10, 2);