<?php
/**
 * Plugin Name: Security Hardening - Fingerprint Removal
 * Description: Removes WordPress fingerprints (emoji scripts, oEmbed, WLW manifest, RSD link).
 * Version: 1.0.0
 * Author: Example Engineering
 *
 * REVERSIBILITY: Delete this file or rename to .bak to disable all changes instantly.
 * Added: 2026-02-13
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove WP Emoji scripts and styles.
 * Saves ~25KB per page load and removes a WordPress fingerprint.
 */
add_action('init', function () {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('emoji_svg_url', '__return_false');
    add_filter('tiny_mce_plugins', function ($plugins) {
        return is_array($plugins) ? array_diff($plugins, array('wpemoji')) : array();
    });
    add_filter('wp_resource_hints', function ($urls, $relation_type) {
        if ('dns-prefetch' === $relation_type) {
            $urls = array_filter($urls, function ($url) {
                return false === strpos((string) $url, 'https://s.w.org/images/core/emoji/');
            });
        }
        return $urls;
    }, 10, 2);
});

/**
 * Remove Windows Live Writer manifest link.
 */
remove_action('wp_head', 'wlwmanifest_link');

/**
 * Remove RSD (Really Simple Discovery) link.
 */
remove_action('wp_head', 'rsd_link');

/**
 * Remove oEmbed discovery links.
 */
remove_action('wp_head', 'wp_oembed_add_discovery_links');

/**
 * Remove REST API link from head (reduces fingerprint).
 */
remove_action('wp_head', 'rest_output_link_wp_head', 10);
