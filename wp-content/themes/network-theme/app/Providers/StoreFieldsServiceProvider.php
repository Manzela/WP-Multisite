<?php
/**
/**
 * StoreFieldsServiceProvider
 * 
 * This service provider is responsible for managing the store settings fields 
 * for the WooCommerce plugin. It enqueues necessary scripts and styles for 
 * the admin panel, registers various settings (like delivery options and colors), 
 * and allows dynamic delivery rules based on cities. The settings page is rendered 
 * using Blade templating for cleaner code and includes localization support for translations.
 * 
 * Author: Edward Ziadeh
 * Date: October 30, 2024
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class StoreFieldsServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Enqueue media scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueueMediaScripts']);
    }

    public function enqueueMediaScripts()
    {
        $screen = get_current_screen();
        // Added network settings page check - necessary for editor functionality
        if ($screen->id === 'toplevel_page_store-settings' || $screen->id === 'settings_page_network-store-settings-network') {
            wp_enqueue_editor();
            wp_enqueue_media();

            // Enqueue WordPress Color Picker styles and scripts
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');

            // Enqueue theme-specific styles and scripts
            $css_path = get_template_directory_uri() . '/resources/styles/admin/store-settings.css';
            wp_enqueue_style('store-settings-css', $css_path, ['wp-color-picker'], null);
            wp_enqueue_script('store-settings-js', asset('scripts/store-settings/delivery.js')->uri(), ['jquery', 'wp-color-picker'], null, true);

            // Pass cities data and translation strings to JavaScript
            $cities = include __DIR__ . '/../Data/cities.php';
            wp_localize_script('store-settings-js', 'storeSettingsData', [
                'cities' => $cities,
                'translation' => [
                    'title' => 'Title',
                    'body' => 'Body',
                    'remove_policy' => 'Remove Policy',
                    'active' => 'Active',
                    'minimum_order' => 'Minimum Order',
                    'cities_label' => 'Cities',
                    'shipping_cost' => 'Shipping Cost',
                    'additional_text' => 'Additional Text',
                    'remove_delivery_rule' => 'Remove Delivery Rule',
                    'remove_image' => 'Remove Image',
                    'enable_delivery' => 'Enable Delivery',
                    'untitled_policy' => 'Untitled Policy',
                    'social_url_placeholder' => 'Social Media URL',
                    'social_icon_placeholder' => 'Icon Name',
                    'remove_social' => 'Remove',
                ],
            ]);
        }
    }

    public function boot()
    {
        add_action('admin_menu', [$this, 'addOptionsPage']);
        add_action('admin_init', [$this, 'registerSettings']);

        // NEW: Pre-configure Homepage on Site Creation (Template Rule)
        add_action('wp_initialize_site', [$this, 'on_site_initialization'], 10, 2);

        // Add filter to modify the redirect URL after saving options
        add_filter('wp_redirect', function ($location) {
            if (
                strpos($location, 'options.php') !== false
                && isset($_POST['store_settings_current_tab'])
            ) {
                $tab = sanitize_text_field($_POST['store_settings_current_tab']);
                return admin_url('admin.php?page=store-settings&tab=' . $tab);
            }
            return $location;
        }, 999);


        add_action('update_option_store_settings', function ($old_value, $value) {
            $this->sync_woocommerce_address_and_wordpress_logo($value);
            $this->set_woocommerce_pickup_and_payment_settings($value);
            $this->sync_language_and_currency_with_country($value);
            $this->sync_accessibility_plugin_color($value);
            $this->sync_homepage_settings($value);
        }, 10, 2);

    }

    /**
     * Triggered when a new site is created.
     * Switches to the new blog and attempts to configure the homepage immediately.
     */
    public function on_site_initialization($new_site, $args)
    {
        if ((int) $new_site->blog_id === 1) {
            return;
        }

        switch_to_blog($new_site->blog_id);

        // Pass empty array as value is ignored in the logic
        $this->sync_homepage_settings([]);

        restore_current_blog();
    }

    /**
     * Enforce Homepage Settings based on Site Context.
     * Subsites => "Shop" page.
     * Main Site => Managed elsewhere (setup.php).
     */
    private function sync_homepage_settings($value)
    {
        // Detect subsite context via URL path segment.
        $path = $_SERVER['REQUEST_URI'] ?? '';
        $is_subsite = (strpos($path, '/tiendas/') !== false || strpos($path, '/tienda/') !== false);

        if (!$is_subsite) {
            return;
        }

        $target_id = 0;

        // 1. Try WooCommerce Native
        if (function_exists('wc_get_page_id')) {
            $wc_id = wc_get_page_id('shop');
            if ($wc_id > 0)
                $target_id = $wc_id;
        }

        // 2. Try Page Slug 'shop'
        if (!$target_id) {
            $p = get_page_by_path('shop');
            if ($p)
                $target_id = $p->ID;
        }

        // 3. Try Page Title 'Shop'
        if (!$target_id) {
            $p = get_page_by_title('Shop');
            if ($p)
                $target_id = $p->ID;
        }

        if ($target_id > 0) {
            // Update WordPress Reading Settings
            update_option('show_on_front', 'page');
            update_option('page_on_front', $target_id);

            // Ensure WooCommerce knows about it too
            if (function_exists('WC')) {
                update_option('woocommerce_shop_page_id', $target_id);
            }
        }
    }

    private function sync_woocommerce_address_and_wordpress_logo($value)
    {
        // Sync store info with WooCommerce
        if (isset($value['store_info'])) {
            $store_info = $value['store_info'];

            // Update WooCommerce address fields
            if (isset($store_info['address'])) {
                update_option('woocommerce_store_address', sanitize_text_field($store_info['address']));
            }
            if (isset($store_info['address_2'])) {
                update_option('woocommerce_store_address_2', sanitize_text_field($store_info['address_2']));
            }
            if (isset($store_info['city'])) {
                update_option('woocommerce_store_city', sanitize_text_field($store_info['city']));
            }
            if (isset($store_info['postcode'])) {
                update_option('woocommerce_store_postcode', sanitize_text_field($store_info['postcode']));
            }
            if (isset($store_info['country'])) {
                update_option('woocommerce_default_country', sanitize_text_field($store_info['country']));
            }
        }

        // Sync store logo with WordPress custom logo
        if (isset($value['store_logo'])) {
            $logo_id = absint($value['store_logo']);
            set_theme_mod('custom_logo', $logo_id);
        }
    }

    private function set_woocommerce_pickup_and_payment_settings($store_settings)
    {
        if (!isset($store_settings['store_info'])) {
            return;
        }

        $store_info = $store_settings['store_info'];

        // Remove legacy Local Pickup completely
        delete_option('woocommerce_local_pickup_settings');

        // Remove from shipping zones
        $zones = \WC_Shipping_Zones::get_zones();
        foreach ($zones as $zone) {
            $zone_obj = \WC_Shipping_Zones::get_zone($zone['id']);
            foreach ($zone_obj->get_shipping_methods() as $method) {
                if ($method->id === 'local_pickup') {
                    $zone_obj->delete_shipping_method($method->instance_id);
                }
            }
        }

        $pickup_settings = [
            'enabled' => 'yes',
            'title' => 'Self-pickup at branch',
            'allow_customers' => 'yes'
        ];
        update_option('woocommerce_pickup_location_settings', $pickup_settings);

        $location = [
            'id' => uniqid('loc_'),
            'name' => get_bloginfo('name') ?? '',
            'address' => [
                'address_1' => $store_info['address'] ?? '',
                'address_2' => $store_info['address_2'] ?? '',
                'city' => $store_info['city'] ?? '',
                'postcode' => $store_info['postcode'] ?? '',
                'country' => $store_info['country'] ?? 'IL',
                'state' => ''
            ],
            'enabled' => 'yes'
        ];

        // Save locations in the new format
        $existing_locations = get_option('pickup_location_pickup_locations', []);
        // Check for duplicate addresses and update if found
        $found_index = false;
        foreach ($existing_locations as $index => $existing_location) {
            if (
                $existing_location['address']['address_1'] === $location['address']['address_1'] &&
                $existing_location['address']['city'] === $location['address']['city']
            ) {
                $found_index = $index;
                break;
            }
        }

        // Update existing location or add new one
        if ($found_index !== false) {
            $existing_locations[$found_index] = $location;
        } else {
            $existing_locations[] = $location;
        }
        update_option('pickup_location_pickup_locations', array_values($existing_locations));

        // Enable COD and update its settings
        $cod_settings = get_option('woocommerce_cod_settings', []);
        $cod_settings['enabled'] = 'yes';
        $cod_settings['title'] = __('Pay with cash or credit card', 'sage');
        $cod_settings['description'] = __('Pay with cash or credit card. Coupons and membership benefits can be applied at the register.', 'sage');
        update_option('woocommerce_cod_settings', $cod_settings);
    }

    private function sync_language_and_currency_with_country($value)
    {
        if (!isset($value['store_info']['country'])) {
            return;
        }

        $country = sanitize_text_field($value['store_info']['country']);

        // Get country settings mapping from file
        $countries_settings = include __DIR__ . '/../Data/countries_settings.php';

        // If we have settings for this country
        if (isset($countries_settings[$country])) {
            $settings = $countries_settings[$country];

            // Update WooCommerce currency
            update_option('woocommerce_currency', $settings['currency']);

            // Update site language
            $language = $settings['language'];

            // Special handling for English (default language)
            if ($language === 'en_US') {
                update_option('WPLANG', '');
                return;
            }

            // For other languages, try to install and set
            require_once(ABSPATH . 'wp-admin/includes/translation-install.php');

            // Install language if not already installed
            $installed_languages = get_available_languages();
            if (!in_array($language, $installed_languages)) {
                $result = wp_download_language_pack($language);
                if (!$result) {
                    error_log("Failed to install language pack for: " . $language);
                    return;
                }
            }

            // Update the site language
            update_option('WPLANG', $language);

            // Clear language caches by deleting transients
            delete_site_transient('available_translations');
            delete_transient('_wp_available_languages');

            // Force reload of translations
            load_default_textdomain($language);

            // Schedule a refresh to see changes
            add_action('shutdown', function () {
                wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
                exit;
            });
        }
    }

    // override "all-in-one-accessibility" plugin colors (use primary color from store settings)
    private function sync_accessibility_plugin_color($value)
    {
        // Check if primary color exists in the settings
        $primary_color = $value['primary_color'];

        if (empty($primary_color)) { // use common accesibility color
            $primary_color = '#3399ff';
        }

        // Remove # from the beginning if it exists
        $color_value = ltrim($primary_color, '#');

        // Update the accessibility plugin's color setting
        update_option('highlight_color', $color_value);
    }

    public function addOptionsPage()
    {
        add_menu_page(
            'Store Settings', // Page title
            'Store Settings', // Menu title
            'manage_options', // Capability
            'store-settings', // Menu slug
            [$this, 'renderOptionsPage'], // Callback function
            'dashicons-admin-generic', // Icon
            20 // Position
        );
    }

    public function renderOptionsPage()
    {

        $options = get_option('store_settings');
        $policies = $options['policies'] ?? [];
        $delivery_rules = $options['delivery_rules'] ?? [];
        $social_links = $options['social'] ?? [];     // Render the store-settings Blade file with policies and delivery_rules data
        $store_info = $options['store_info'] ?? [];
        $cities = include __DIR__ . '/../Data/cities.php';
        $social_icons = include __DIR__ . '/../Data/social.php';
        echo view('admin.store-settings', compact('policies', 'delivery_rules', 'options', 'social_links', 'cities', 'store_info', 'social_icons'));
    }

    public function registerSettings()
    {
        register_setting('store_settings', 'store_settings');

        // General Settings Section
        add_settings_section(
            'store_settings_section',
            'General Settings',
            [$this, 'settingsSectionCallback'],
            'store-settings'
        );

        add_settings_field(
            'accessible',
            'Accessible (physically)',
            [$this, 'checkboxFieldCallback'],
            'store-settings',
            'store_settings_section',
            [
                'label_for' => 'accessible',
                'class' => 'store-settings-row',
            ]
        );

        add_settings_field(
            'accessible_description',
            'Description for Accessible',
            [$this, 'textFieldCallback'],
            'store-settings',
            'store_settings_section',
            [
                'label_for' => 'accessible_description',
                'class' => 'store-settings-row',
            ]
        );

        add_settings_field(
            'primary_color',
            'Primary Color',
            [$this, 'colorPickerFieldCallback'],
            'store-settings',
            'store_settings_section',
            [
                'label_for' => 'primary_color',
                'class' => 'store-settings-row',
            ]
        );

        add_settings_field(
            'secondary_color',
            'Secondary Color',
            [$this, 'colorPickerFieldCallback'],
            'store-settings',
            'store_settings_section',
            [
                'label_for' => 'secondary_color',
                'class' => 'store-settings-row',
            ]
        );

        // Store Banner Field
        add_settings_field(
            'store_banner',
            'Store Banner',
            [$this, 'imageFieldCallback'],
            'store-settings',
            'store_settings_section',
            [
                'label_for' => 'store_banner',
                'class' => 'store-settings-row',
                'max_size' => 300, // 300px for banner
            ]
        );

        // store logo field
        add_settings_field(
            'store_logo',
            'Store Logo',
            [$this, 'imageFieldCallback'],
            'store-settings',
            'store_settings_section',
            [
                'label_for' => 'store_logo',
                'class' => 'store-settings-row',
                'max_size' => 100, // 100px for logo
            ]
        );

        // NOTE: No need to actually disable purchasing functionality
        // the cart will be always empty and there is no way to navigate to the cart or checkout pages.
        // even when navigating manually, there is a redirect to the "empty cart" page
        add_settings_field(
            'hide_all_prices',
            'Hide All Prices',
            [$this, 'warningCheckboxFieldCallback'],
            'store-settings',
            'store_settings_section',
            [
                'label_for' => 'hide_all_prices',
                'class' => 'store-settings-row',
                'warning_message' => 'This will disable all purchasing functionality! (cart & checkout)',
            ]
        );

        add_settings_field(
            'buy_externally',
            'Redirect customers to eCommerce link',
            [$this, 'warningCheckboxFieldCallback'],
            'store-settings',
            'store_settings_section',
            [
                'label_for' => 'buy_externally',
                'class' => 'store-settings-row',
                'warning_message' => 'This will replace the Add-to-cart button logic with an external redirection! (it will also disable all purchasing functionality)',
            ]
        );

        // Note: Delivery Rules Section is now managed via Blade and Settings API registration has been removed.
    }

    // Callback methods for settings sections and fields
    public function settingsSectionCallback()
    {
        echo '<p>Configure your store settings below:</p>';
    }

    public function checkboxFieldCallback($args)
    {
        $options = get_option('store_settings');
        $checked = isset($options[$args['label_for']]) ? checked(1, $options[$args['label_for']], false) : '';
        echo "<input type='checkbox' id='{$args['label_for']}' name='store_settings[{$args['label_for']}]' value='1' {$checked} />";
    }

    public function warningCheckboxFieldCallback($args)
    {
        $options = get_option('store_settings');
        $checked = isset($options[$args['label_for']]) ? checked(1, $options[$args['label_for']], false) : '';

        $warning_message = $args['warning_message'];

        echo "<div style='background-color: #FFEBE8; border: 1px solid #CC0000; padding: 10px; margin: 5px 0;'>";
        echo "<input type='checkbox' id='{$args['label_for']}' name='store_settings[{$args['label_for']}]' value='1' {$checked} />";
        echo "<label for='{$args['label_for']}' style='margin-left: 8px; font-weight: bold;'><span style='color: #D63638;'>Warning:</span> " . esc_html($warning_message) . "</label>";
        echo "</div>";
    }

    public function textFieldCallback($args)
    {
        $options = get_option('store_settings');
        $value = isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : '';
        echo "<input type='text' id='{$args['label_for']}' name='store_settings[{$args['label_for']}]' value='{$value}' />";
    }

    public function colorPickerFieldCallback($args)
    {
        $options = get_option('store_settings');
        $color = isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : '';
        echo "<input type='text' class='color-picker' id='{$args['label_for']}' name='store_settings[{$args['label_for']}]' value='{$color}' />";
    }

    public function imageFieldCallback($args)
    {
        $options = get_option('store_settings');
        $image_id = $options[$args['label_for']] ?? '';
        $image_url = $image_id ? wp_get_attachment_url($image_id) : '';

        // Get max size from args, default to 300px if not specified
        $max_size = $args['max_size'] ?? 300;

        ?>
        <div>
            <img id="<?php echo esc_attr($args['label_for']); ?>_preview" src="<?php echo esc_url($image_url); ?>"
                style="max-width: <?php echo intval($max_size); ?>px; max-height: <?php echo intval($max_size); ?>px; display: <?php echo $image_url ? 'block' : 'none'; ?>;">
            <input type="hidden" id="<?php echo esc_attr($args['label_for']); ?>"
                name="store_settings[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo esc_attr($image_id); ?>">
            <button type="button" class="button" id="<?php echo esc_attr($args['label_for']); ?>_button">Select Image</button>
            <button type="button" class="button" id="<?php echo esc_attr($args['label_for']); ?>_remove_button"
                style="display: <?php echo $image_url ? 'inline-block' : 'none'; ?>;">Remove Image</button>
        </div>
        <?php
    }

    public function enqueueAdminScripts()
    {
        // ... other enqueue scripts

        wp_enqueue_script(
            'store-settings-tabs',
            asset('scripts/store-settings/tabs.js')->uri(),
            ['jquery'],
            null,
            true
        );
    }

    public function seoSectionCallback()
    {
        echo '<p>Configure your SEO settings below:</p>';
    }
}