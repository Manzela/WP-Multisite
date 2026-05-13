<?php

/**
 * Theme setup.
 */

namespace App;

use function Roots\bundle;


/**
 * Register the theme assets.
 *
 * @return void
 */
add_action('wp_enqueue_scripts', function () {
    // Ensure jQuery is loaded for plugins/legacy scripts
    wp_enqueue_script('jquery');

    bundle('app')->enqueue();
}, 1);

/**
 * Register the theme assets with the block editor.
 *
 * @return void
 */
add_action('enqueue_block_editor_assets', function () {
    bundle('editor')->enqueue();
}, 100);

/**
 * Performance Optimization: Dequeue unused styles
 */
add_action('wp_enqueue_scripts', function () {
    // --------------------------------------------------------------------------
    // OPTIMIZATION: Debloat Gutenberg & Core Styles
    // --------------------------------------------------------------------------
    // We use Blade/Tailwind. We do not need WP's block library CSS.

    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('wc-blocks-style'); // Safe if using Classic WC Templates

    // REMOVE HUGE INLINE CSS (The "Global Styles" blob)
    wp_dequeue_style('global-styles');

    // Remove "Classic Theme Styles" (SVG filters)
    wp_dequeue_style('classic-theme-styles');
    remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
}, 100);

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {

    /**
     * Make theme available for translation.
     *
     * @link https://developer.wordpress.org/reference/functions/load_theme_textdomain/
     */
    load_theme_textdomain('sage', get_template_directory() . '/languages');

    /**
     * Disable full-site editing support.
     *
     * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => 'Primary Navigation',
    ]);

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');

    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#customize-selective-refresh-widgets
     */
    add_theme_support('customize-selective-refresh-widgets');



    $app = \Roots\app();
    $app->register(\App\Providers\StoreFieldsServiceProvider::class);
    $app->register(\App\Providers\NetworkFieldsServiceProvider::class);
    $app->register(\App\Providers\PermalinkServiceProvider::class);

    // Register Schema & SEO Providers
    $app->register(\App\Providers\SchemaServiceProvider::class);
    $app->register(\App\Providers\ProductSeoServiceProvider::class);

    // Get current blog ID for multisite
    $blog_id = get_current_blog_id();

    // override all-in-one-accessibility plugin default values
    // resize and reposition the accessibility icon
    add_action('init', function () {
        if (function_exists('add_ADAC')) {
            update_option('is_widget_custom_position', '0');
            update_option('position', is_rtl() ? 'middle_left' : 'middle_right');
            update_option('aioa_icon_size', 'aioa-extra-small-icon');
            update_option('aioa_icon_type', 'aioa-icon-type-1');
        }
    });

    // Fix the typo bug in all-in-one-accessibility plugin
    // Replace "aioa_mideel_left" with "aioa_middle_left" class
    add_action('wp_footer', function () {
        ?>
        <script>     // Simple busy wait approach - check every 100ms for up to 6 seconds     let attempts = 0;     const maxAttempts = 60; // 6 seconds (60 * 100ms)
            function checkAndFix() { const button = document.querySelector('#aioa-trigger-button');
            // If button doesn't exist yet, keep checking         if (!button) {             attempts++;             if (attempts < maxAttempts) {                 setTimeout(checkAndFix, 100);             }             return;         }
            // Button exists, now check if it needs fixing         if (button.classList.contains('aioa_mideel_left')) {             button.classList.remove('aioa_mideel_left');             button.classList.add('aioa_middle_left');             return; // Success, stop checking         }
            // Button exists but doesn't need fixing, stop checking         return;     }
            // Start checking after DOM is ready     document.addEventListener('DOMContentLoaded', function () {         setTimeout(checkAndFix, 100);     });
        </script>
        <?php
    });
}, 20);

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => 'Primary',
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => 'Categories',
        'id' => 'sidebar-categories',
    ] + $config);

    register_sidebar([
        'name' => 'Footer',
        'id' => 'sidebar-footer',
    ] + $config);
});


    /**
     * Enable WooCommerce support.
     */
    add_theme_support('woocommerce');

add_theme_support('custom-logo', [
    'height' => 100, // Set your desired height
    'width' => 400, // Set your desired width
    'flex-height' => true, // Allow flexible height
    'flex-width' => true, // Allow flexible width
    'header-text' => ['site-title', 'site-description'], // Elements to hide when logo is displayed
]);


add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);

    // Load Tailwind bundle for both store settings and network store settings
    if (isset($_GET['page']) && in_array($_GET['page'], ['store-settings', 'network-store-settings'])) {
        if (function_exists('Roots\\bundle')) {
            \Roots\bundle('app')->enqueue();
        }
    }

    // Load store-settings specific scripts only for store-settings page
    if (isset($_GET['page']) && $_GET['page'] === 'store-settings') {
        wp_enqueue_style('store-settings-css', get_template_directory_uri() . '/resources/styles/admin/store-settings.css', [], null);
        wp_enqueue_script('store-settings-delivery-js', get_template_directory_uri() . '/resources/scripts/store-settings/delivery.js', ['jquery'], null, true);
        wp_enqueue_script('store-settings-policies-js', get_template_directory_uri() . '/resources/scripts/store-settings/policies.js', ['jquery'], null, true);
        wp_enqueue_script('store-settings-social-js', get_template_directory_uri() . '/resources/scripts/store-settings/social.js', ['jquery'], null, true);
        wp_enqueue_script('store-settings-info-js', get_template_directory_uri() . '/resources/scripts/store-settings/info.js', ['jquery'], null, true);
        wp_enqueue_script('store-settings-general-js', get_template_directory_uri() . '/resources/scripts/store-settings/general.js', ['jquery'], null, true);
        wp_enqueue_script('store-settings-tabs-js', get_template_directory_uri() . '/resources/scripts/store-settings/tabs.js', ['jquery'], null, true);
    }
});

// Add this to ensure permalinks are flushed when needed
add_action('init', function () {
    if (get_option('sage_flush_rewrite_rules', false)) {
        flush_rewrite_rules();
        delete_option('sage_flush_rewrite_rules');
    }
}, 99);

// override woocommerce empty cart page
add_action('template_redirect', function () {
    if (is_cart() && WC()->cart->is_empty()) {
        echo view('woocommerce/cart/cart-empty')->render();
        exit;
    }
});

// Logic moved to mu-plugins/network-core.php
// END [FORCE-HOME-SETTINGS]