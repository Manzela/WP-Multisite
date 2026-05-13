<?php



/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our theme. We will simply require it into the script here so that we
| don't have to worry about manually loading any of our classes later on.
|
*/

if (!file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
    wp_die('Error locating autoloader. Please run <code>composer install</code>.');
}

require $composer;

/*
|--------------------------------------------------------------------------
| Register The Bootloader
|--------------------------------------------------------------------------
|
| The first thing we will do is schedule a new Acorn application container
| to boot when WordPress is finished loading the theme. The application
| serves as the "glue" for all the components of Laravel and is
| the IoC container for the system binding all of the various parts.
|
*/

if (!function_exists('\Roots\bootloader')) {
    wp_die(
        'You need to install Acorn to use this theme.',
        '',
        [
            'link_url' => 'https://roots.io/acorn/docs/installation/',
            'link_text' => 'Acorn Docs: Installation',
        ]
    );
}

\Roots\bootloader()->boot();
require_once get_theme_file_path('routes/api.php');

/*
|--------------------------------------------------------------------------
| Register Sage Theme Files
|--------------------------------------------------------------------------
|
| Out of the box, Sage ships with categorically named theme files
| containing common functionality and setup to be bootstrapped with your
| theme. Simply add (or remove) files from the array below to change what
| is registered alongside Sage.
|
*/

/**
 * Load the theme files
 * apiFillter: add product schema data
 */

collect(['setup', 'filters', 'product-custom-tabs', 'seo-image', 'wc-email-customization', 'apiFillter', 'main-page', 'metaTags', 'productSortFilter', 'redirects', 'remove-slugs'])
    ->each(function ($file) {
        if (!locate_template($file = "app/{$file}.php", true, true)) {
            wp_die(
                sprintf('Error locating <code>%s</code> for inclusion.', $file)
            );
        }
    });


