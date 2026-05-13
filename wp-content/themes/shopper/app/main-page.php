<?php
/**
 * Main page setup and redirect functionality
 */

namespace App;

/**
 * Main page setup and redirect functionality
 */
class MainPage
{
    public function __construct()
    {
        // add_action('template_redirect', [$this, 'redirectToMainPage']);
        $path = $_SERVER['REQUEST_URI'] ?? '';
        $is_root = ($path === '/' || $path === '/index.php');

        $sites_page = get_page_by_title('Sites Directory')->ID ?? 0;
        if (!$sites_page && $is_root) {
            add_action('init', [$this, 'setupMainPage']);
        }
        add_filter('sage/template/template-sites/data', [$this, 'templateData']);
    }

    /**
     * Redirect all non-admin pages to the main page on main site
     */
    public function redirectToMainPage()
    {

        // Skip if we're in admin or if this is not the main site
        if (is_admin() || !is_main_site()) {
            return;
        }
        // Skip if we're already on the homepage
        if (is_front_page() || is_home()) {
            return;
        }

        // Redirect to the root URL
        wp_redirect(home_url('/'));
        exit;
    }

    /**
     * Setup the main page
     */
    public function setupMainPage()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '';
        if ($path !== '/' && $path !== '/index.php') {
            return;
        }

        // Get or create the sites directory page
        $sites_page = get_page_by_path('/');

        if (!$sites_page) {
            // Create the page if it doesn't exist
            $sites_page_id = wp_insert_post([
                'post_title' => 'Sites Directory',
                'post_name' => '',  // Empty for root
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '',
                'page_template' => 'sites-main-page/template-sites.blade.php'
            ]);

            // Set as front page
            update_option('show_on_front', 'page');
            update_option('page_on_front', $sites_page_id);
        } else {
            $sites_page_id = $sites_page->ID;

            // Set as front page if not already
            if (get_option('page_on_front') != $sites_page_id) {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $sites_page_id);
            }
        }

        // Update the template if needed
        if (get_page_template_slug($sites_page_id) !== 'sites-main-page/template-sites.blade.php') {
            update_post_meta($sites_page_id, '_wp_page_template', 'sites-main-page/template-sites.blade.php');
        }
    }

    /**
     * Get store settings for a specific site
     */
    public function get_site_store_settings($blog_id)
    {
        switch_to_blog($blog_id);

        $store_settings = get_option('store_settings', array());

        // Get WooCommerce address
        $address_parts = array(
            get_option('woocommerce_store_address'),
            get_option('woocommerce_store_address_2'),
            get_option('woocommerce_store_city'),
            get_option('woocommerce_store_postcode')
        );

        // Filter out empty parts and join with commas
        $address_parts = array_filter($address_parts);
        $store_settings['woo_address'] = implode(', ', $address_parts);

        // Format store hours in a readable way
        if (!empty($store_settings['store_info']['hours'])) {
            $days_hebrew = array(
                'sunday' => 'ראשון',
                'monday' => 'שני',
                'tuesday' => 'שלישי',
                'wednesday' => 'רביעי',
                'thursday' => 'חמישי',
                'friday' => 'שישי',
                'saturday' => 'שבת'
            );

            $formatted_hours = array();
            foreach ($store_settings['store_info']['hours'] as $day => $hours) {
                if (!empty($hours['closed'])) {
                    $formatted_hours[$days_hebrew[$day]] = 'סגור';
                } else {
                    $formatted_hours[$days_hebrew[$day]] = $hours['open'] . ' - ' . $hours['close'];
                }
            }
            $store_settings['store_info']['formatted_hours'] = $formatted_hours;
        }

        restore_current_blog();
        return $store_settings;
    }

    public function templateData($data)
    {
        $sites = get_sites([
            'public' => 1,
            'archived' => 0,
            'spam' => 0,
            'deleted' => 0,
            'site__not_in' => [get_main_site_id()],
            'number' => 999
        ]);

        return array_merge($data, [
            'sites' => $sites
        ]);
    }
}

// Initialize the MainPage class
new MainPage();
