<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class NetworkFieldsServiceProvider extends ServiceProvider
{
    public function register()
    {
        add_action('network_admin_menu', [$this, 'addNetworkAdminMenu']);
        add_action('network_admin_edit_network_store_settings', [$this, 'updateNetworkStoreSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueNetworkAdminScripts']);
    }

    public function boot()
    {
        // Add any boot-specific code here
    }

    public function enqueueNetworkAdminScripts()
    {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'settings_page_network-store-settings-network') {
            // Enqueue WordPress Color Picker styles and scripts
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_media();
        }
    }

    public function addNetworkAdminMenu()
    {
        add_submenu_page(
            'settings.php',
            'Network Store Settings',
            'Network Store Settings',
            'manage_network_options',
            'network-store-settings',
            [$this, 'renderNetworkSettingsPage']
        );
    }

    public function updateNetworkStoreSettings()
    {
        // Security check - verify nonce and capabilities
        if (!isset($_POST['network_store_settings_nonce']) || !wp_verify_nonce($_POST['network_store_settings_nonce'], 'network_store_settings_update')) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_network_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        $network_settings = $_POST['network_store_settings'] ?? [];
        $old_network_settings = get_site_option('network_store_settings', []);

        // Get the current active tab from the form submission
        $current_tab = $_POST['network_store_settings_current_tab'] ?? 'network-general';

        // Check logo and banner changes BEFORE updating network settings
        $logo_changed = ($old_network_settings['network_store_logo'] ?? '') !== ($network_settings['network_store_logo'] ?? '');
        $banner_changed = ($old_network_settings['network_store_banner'] ?? '') !== ($network_settings['network_store_banner'] ?? '');

        // Fix HTML escaping issue for popup message content
        if (isset($network_settings['popup_message']['popup_message'])) {
            // Remove WordPress automatic slashing and properly sanitize HTML content
            $network_settings['popup_message']['popup_message'] = wp_kses_post(
                wp_unslash($network_settings['popup_message']['popup_message'])
            );
        }

        // Handle Main Page fields (sanitize and unslash)
        if (isset($network_settings['main_page'])) {
            $main_page = $network_settings['main_page'];
            $network_settings['main_page']['main_title'] = isset($main_page['main_title']) ? sanitize_text_field(wp_unslash($main_page['main_title'])) : '';
            $network_settings['main_page']['description'] = isset($main_page['description']) ? sanitize_textarea_field(wp_unslash($main_page['description'])) : '';
            $network_settings['main_page']['banner'] = isset($main_page['banner']) ? absint($main_page['banner']) : '';
        }

        // Fix JSON escaping issue for Google Cloud Storage API key
        if (isset($network_settings['network_google_cloud_storage_api_key'])) {
            $val = $network_settings['network_google_cloud_storage_api_key'];
            // Check for BASE64 encoded content (WAF Bypass)
            if (strpos($val, 'BASE64:') === 0) {
                $val = base64_decode(substr($val, 7));
            }
            // Remove WordPress automatic slashing for JSON content
            $network_settings['network_google_cloud_storage_api_key'] = wp_unslash($val);
        }

        if (isset($network_settings['network_growth_dashboard_db_credentials'])) {
            $val = $network_settings['network_growth_dashboard_db_credentials'];
            // Check for BASE64 encoded content (WAF Bypass)
            if (strpos($val, 'BASE64:') === 0) {
                $val = base64_decode(substr($val, 7));
            }
            // Remove WordPress automatic slashing for JSON content
            $network_settings['network_growth_dashboard_db_credentials'] = wp_unslash($val);
        }

        // Handle Currency Settings
        if (isset($network_settings['network_store_currency'])) {
            $network_settings['network_store_currency'] = sanitize_text_field($network_settings['network_store_currency']);
        }

        // Handle Scripts fields
        if (isset($network_settings['network_scripts'])) {
            $scripts = $network_settings['network_scripts'];
            $sanitized_scripts = [];

            foreach ($scripts as $index => $script) {
                if (isset($script['network_script_name'])) {
                    $sanitized_scripts[$index]['network_script_name'] = sanitize_text_field(wp_unslash($script['network_script_name']));
                }

                if (isset($script['network_script_location'])) {
                    $location = sanitize_text_field(wp_unslash($script['network_script_location']));
                    // Validate location value
                    if (in_array($location, ['head', 'body_top', 'body_bottom'])) {
                        $sanitized_scripts[$index]['network_script_location'] = $location;
                    }
                }

                if (isset($script['network_script_code'])) {
                    $script_code = $script['network_script_code'];

                    // Check for BASE64 encoded content (WAF Bypass)
                    if (strpos($script_code, 'BASE64:') === 0) {
                        $script_code = base64_decode(substr($script_code, 7));
                    }

                    // Allow script tags and common script content
                    $sanitized_scripts[$index]['network_script_code'] = wp_kses(
                        wp_unslash($script_code),
                        array(
                            'script' => array(
                                'src' => array(),
                                'async' => array(),
                                'defer' => array(),
                                'type' => array(),
                                'id' => array(),
                                'class' => array(),
                            ),
                            'noscript' => array(),
                        )
                    );
                }

                if (isset($script['network_script_enabled'])) {
                    $sanitized_scripts[$index]['network_script_enabled'] = (bool) $script['network_script_enabled'];
                }
            }

            $network_settings['network_scripts'] = $sanitized_scripts;
        }

        // Update network settings
        update_site_option('network_store_settings', $network_settings);

        // Store current blog ID
        $current_blog_id = get_current_blog_id();

        // handle sub domains (update only with new settings)
        $sites = get_sites();


        // --- Collect all updates ---
        $updates = [];
        // Logo
        $logo_file_to_upload = '';
        if ($logo_changed && !empty($network_settings['network_store_logo'])) {
            switch_to_blog(1);
            $logo_file_to_upload = get_attached_file($network_settings['network_store_logo']);
            restore_current_blog();
            if (empty($logo_file_to_upload) || !file_exists($logo_file_to_upload)) {
                error_log("Network logo update failed: Could not get file path for attachment ID {$network_settings['network_store_logo']}");
                $logo_file_to_upload = '';
            }
        }
        $updates['logo'] = [
            'changed' => $logo_changed,
            'file' => $logo_file_to_upload,
            'id' => $network_settings['network_store_logo'] ?? ''
        ];

        // Banner
        $banner_file_to_upload = '';
        if ($banner_changed && !empty($network_settings['network_store_banner'])) {
            switch_to_blog(1);
            $banner_file_to_upload = get_attached_file($network_settings['network_store_banner']);
            restore_current_blog();
            if (empty($banner_file_to_upload) || !file_exists($banner_file_to_upload)) {
                error_log("Network store banner update failed: Could not get file path for attachment ID {$network_settings['network_store_banner']}");
                $banner_file_to_upload = '';
            }
        }
        $updates['banner'] = [
            'changed' => $banner_changed,
            'file' => $banner_file_to_upload,
            'id' => $network_settings['network_store_banner'] ?? ''
        ];

        // Site Icon
        $icon_changed = ($old_network_settings['network_site_icon'] ?? '') !== ($network_settings['network_site_icon'] ?? '');
        $icon_file_to_upload = '';
        if ($icon_changed && !empty($network_settings['network_site_icon'])) {
            switch_to_blog(1);
            $icon_file_to_upload = get_attached_file($network_settings['network_site_icon']);
            restore_current_blog();
            if (empty($icon_file_to_upload) || !file_exists($icon_file_to_upload)) {
                error_log("Network site icon update failed: Could not get file path for attachment ID {$network_settings['network_site_icon']}");
                $icon_file_to_upload = '';
            }
        }
        $updates['icon'] = [
            'changed' => $icon_changed,
            'file' => $icon_file_to_upload,
            'id' => $network_settings['network_site_icon'] ?? ''
        ];

        // buy_externally
        $old_buy_externally = isset($old_network_settings['network_buy_externally']) ? (bool) $old_network_settings['network_buy_externally'] : false;
        $new_buy_externally = isset($network_settings['network_buy_externally']) ? (bool) $network_settings['network_buy_externally'] : false;
        $updates['buy_externally'] = [
            'changed' => $old_buy_externally !== $new_buy_externally,
            'value' => $new_buy_externally
        ];

        // primary color
        $old_primary_color = $old_network_settings['network_primary_color'] ?? '';
        $new_primary_color = $network_settings['network_primary_color'] ?? '';
        $updates['primary_color'] = [
            'changed' => $old_primary_color !== $new_primary_color,
            'value' => $new_primary_color
        ];

        // secondary color
        $old_secondary_color = $old_network_settings['network_secondary_color'] ?? '';
        $new_secondary_color = $network_settings['network_secondary_color'] ?? '';
        $updates['secondary_color'] = [
            'changed' => $old_secondary_color !== $new_secondary_color,
            'value' => $new_secondary_color
        ];

        // product image style
        $old_image_style = $old_network_settings['product_image_style'] ?? 'contain-white';
        $new_image_style = $network_settings['product_image_style'] ?? 'contain-white';
        $updates['product_image_style'] = [
            'changed' => $old_image_style !== $new_image_style,
            'value' => $new_image_style
        ];

        // GA4 Property ID
        $old_ga4_property = $old_network_settings['network_ga4_property'] ?? '';
        $new_ga4_property = $network_settings['network_ga4_property'] ?? '';
        $updates['ga4_property'] = [
            'changed' => $old_ga4_property !== $new_ga4_property,
            'value' => $new_ga4_property
        ];

        // GTM Container ID
        $old_gtm_container = $old_network_settings['network_gtm_container'] ?? '';
        $new_gtm_container = $network_settings['network_gtm_container'] ?? '';
        $updates['gtm_container'] = [
            'changed' => $old_gtm_container !== $new_gtm_container,
            'value' => $new_gtm_container
        ];

        // popup enable (special case: only check for off->on)
        $old_popup_enabled = $old_network_settings['popup_message']['enable_popup'] ?? false;
        $new_popup_enabled = $network_settings['popup_message']['enable_popup'] ?? false;
        $popup_enable_changed = !$old_popup_enabled && $new_popup_enabled;
        $updates['popup_enable'] = [
            'changed' => $popup_enable_changed
        ];

        // Scripts
        $old_scripts = $old_network_settings['network_scripts'] ?? [];
        $new_scripts = $network_settings['network_scripts'] ?? [];
        $scripts_changed = $old_scripts !== $new_scripts;
        $updates['scripts'] = [
            'changed' => $scripts_changed,
            'value' => $new_scripts
        ];

        // --- Main site icon update (network level) ---
        if ($updates['icon']['changed']) {
            switch_to_blog(1);
            if (!empty($updates['icon']['id'])) {
                update_option('site_icon', $updates['icon']['id']);
            } else {
                delete_option('site_icon');
            }
            restore_current_blog();
        }

        // --- Main update loop ---
        foreach ($sites as $site) {
            // Skip main site for icon (already handled above)
            $is_main_site = ($site->blog_id == 1);
            switch_to_blog($site->blog_id);
            $site_settings = get_option('store_settings', []);

            // Logo
            if ($updates['logo']['changed']) {
                if (!empty($updates['logo']['file'])) {
                    $this->upload_logo($site->blog_id, $updates['logo']['file']);
                    // Refresh site_settings after logo upload
                    $site_settings = get_option('store_settings', []);
                } else {
                    $site_settings['store_logo'] = '';
                }
            }

            // Banner
            if ($updates['banner']['changed']) {
                if (!empty($updates['banner']['file'])) {
                    $this->upload_banner($site->blog_id, $updates['banner']['file']);
                    // Refresh site_settings after banner upload
                    $site_settings = get_option('store_settings', []);
                } else {
                    $site_settings['store_banner'] = '';
                }
            }

            // Site Icon
            if ($updates['icon']['changed'] && !$is_main_site) {
                if (!empty($updates['icon']['file'])) {
                    $this->upload_site_icon($site->blog_id, $updates['icon']['file']);
                } else {
                    delete_option('site_icon');
                }
            }

            // buy_externally
            if ($updates['buy_externally']['changed']) {
                $site_settings['buy_externally'] = $updates['buy_externally']['value'];
            }

            // primary color
            if ($updates['primary_color']['changed']) {
                $site_settings['primary_color'] = $updates['primary_color']['value'];
            }

            // secondary color
            if ($updates['secondary_color']['changed']) {
                $site_settings['secondary_color'] = $updates['secondary_color']['value'];
            }

            // product image style
            if ($updates['product_image_style']['changed']) {
                $site_settings['product_image_style'] = $updates['product_image_style']['value'];
            }

            // GA4 Property ID
            if ($updates['ga4_property']['changed']) {
                $site_settings['network_ga4_property'] = $updates['ga4_property']['value'];
            }

            // GTM Container ID
            if ($updates['gtm_container']['changed']) {
                $site_settings['network_gtm_container'] = $updates['gtm_container']['value'];
            }

            // popup enable (off->on)
            if ($updates['popup_enable']['changed']) {
                // disable local popup for all subdomains
                $site_settings['popup_message']['use_local'] = false;
            }

            // Scripts
            if ($updates['scripts']['changed']) {
                $site_settings['network_scripts'] = $updates['scripts']['value'];
            }

            // Save settings if changed
            update_option('store_settings', $site_settings);
            restore_current_blog();
        }

        // Switch back to original blog (in case)
        switch_to_blog($current_blog_id);
        restore_current_blog();

        // Redirect back to settings page with the current tab
        wp_redirect(
            add_query_arg([
                'page' => 'network-store-settings',
                'tab' => $current_tab,
                'updated' => 'true',
            ], network_admin_url('settings.php'))
        );
        exit;
    }


    public function renderNetworkSettingsPage()
    {
        if (isset($_GET['updated']) && $_GET['updated'] === 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>Network settings updated.</p></div>';
        }

        $network_options = get_site_option('network_store_settings', []);

        // Get tab from URL, fallback to 'network-general'
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'network-general';
        ?>
        <div class="wrap">
            <h1>Network Store Settings</h1>

            <form method="post" action="edit.php?action=network_store_settings" id="network-settings-form">
                <?php wp_nonce_field('network_store_settings_update', 'network_store_settings_nonce'); ?>

                <!-- Add hidden input for current tab -->
                <input type="hidden" name="network_store_settings_current_tab" id="current_tab_input"
                    value="<?php echo esc_attr($current_tab); ?>">

                <nav class="nav-tab-wrapper">
                    <a href="#network-general" data-tab="network-general"
                        class="nav-tab <?php echo $current_tab === 'network-general' ? 'nav-tab-active' : ''; ?>">
                        General
                    </a>
                    <a href="#network-main-page" data-tab="network-main-page"
                        class="nav-tab <?php echo $current_tab === 'network-main-page' ? 'nav-tab-active' : ''; ?>">
                        Main Page
                    </a>
                    <a href="#network-api-keys" data-tab="network-api-keys"
                        class="nav-tab <?php echo $current_tab === 'network-api-keys' ? 'nav-tab-active' : ''; ?>">
                        API Keys
                    </a>
                    <a href="#network-popup-message" data-tab="network-popup-message"
                        class="nav-tab <?php echo $current_tab === 'network-popup-message' ? 'nav-tab-active' : ''; ?>">
                        Popup Message
                    </a>
                    <a href="#network-growth-dashboard" data-tab="network-growth-dashboard"
                        class="nav-tab <?php echo $current_tab === 'network-growth-dashboard' ? 'nav-tab-active' : ''; ?>">
                        Growth Dashboard
                    </a>
                    <a href="#network-scripts" data-tab="network-scripts"
                        class="nav-tab <?php echo $current_tab === 'network-scripts' ? 'nav-tab-active' : ''; ?>">
                        Scripts
                    </a>
                </nav>

                <div id="network-general" class="tab-pane <?php echo $current_tab === 'network-general' ? 'active' : ''; ?>">
                    <?php
                    echo view('admin.network-settings.network-general', [
                        'network_options' => $network_options,
                        'current_tab' => $current_tab
                    ]);
                    ?>
                </div>

                <div id="network-main-page"
                    class="tab-pane <?php echo $current_tab === 'network-main-page' ? 'active' : ''; ?>">
                    <?php
                    echo view('admin.network-settings.network-main-page', [
                        'network_options' => $network_options,
                        'current_tab' => $current_tab
                    ]);
                    ?>
                </div>

                <div id="network-api-keys" class="tab-pane <?php echo $current_tab === 'network-api-keys' ? 'active' : ''; ?>">
                    <?php
                    echo view('admin.network-settings.network-api-keys', [
                        'network_options' => $network_options,
                        'current_tab' => $current_tab
                    ]);
                    ?>
                </div>

                <div id="network-popup-message"
                    class="tab-pane <?php echo $current_tab === 'network-popup-message' ? 'active' : ''; ?>">
                    <?php
                    echo view('admin.network-settings.network-popup-message', [
                        'network_options' => $network_options,
                        'current_tab' => $current_tab
                    ]);
                    ?>
                </div>

                <div id="network-growth-dashboard"
                    class="tab-pane <?php echo $current_tab === 'network-growth-dashboard' ? 'active' : ''; ?>">
                    <?php
                    echo view('admin.network-settings.network-growth-dashboard', [
                        'network_options' => $network_options,
                        'current_tab' => $current_tab
                    ]);
                    ?>
                </div>

                <div id="network-scripts" class="tab-pane <?php echo $current_tab === 'network-scripts' ? 'active' : ''; ?>">
                    <?php
                    echo view('admin.network-settings.network-scripts', [
                        'network_options' => $network_options,
                        'current_tab' => $current_tab
                    ]);
                    ?>
                </div>

                <?php submit_button('Save Network Settings'); ?>

                <script>
                    jQuery(document).ready(function ($) {
                        $('#network-settings-form').on('submit', function () {
                            const fieldsToEncode = [
                                'network_google_cloud_storage_api_key',
                                'network_growth_dashboard_db_credentials'
                            ];

                            // Encode simple fields
                            fieldsToEncode.forEach(function (id) {
                                const $field = $('#' + id);
                                if ($field.length && $field.val().trim() !== '') {
                                    const originalVal = $field.val();
                                    if (!originalVal.startsWith('BASE64:')) {
                                        $field.val('BASE64:' + btoa(unescape(encodeURIComponent(originalVal))));
                                    }
                                }
                            });

                            // Encode Script Code fields (dynamic)
                            $('textarea[id^="network_script_code_"]').each(function () {
                                const $field = $(this);
                                if ($field.val().trim() !== '') {
                                    const originalVal = $field.val();
                                    if (!originalVal.startsWith('BASE64:')) {
                                        $field.val('BASE64:' + btoa(unescape(encodeURIComponent(originalVal))));
                                    }
                                }
                            });
                        });
                    });
                </script>
            </form>
        </div>

        <style>
            .tab-content {
                padding: 20px 0;
            }

            .tab-pane {
                display: none;
            }

            .tab-pane.active {
                display: block;
            }
        </style>

        <script>
            jQuery(document).ready(function ($) {
                // Tab switching with URL update
                $('.nav-tab-wrapper .nav-tab').click(function (e) {
                    e.preventDefault();

                    // Remove active class from all tabs and content
                    $('.nav-tab').removeClass('nav-tab-active');
                    $('.tab-pane').removeClass('active');

                    // Add active class to current tab and content
                    $(this).addClass('nav-tab-active');
                    var target = $(this).attr('href');
                    $('.tab-pane').hide(); // Hide all
                    $(target).show().addClass('active'); // Show only the selected

                    // Update hidden input with current tab
                    var currentTab = $(this).data('tab');
                    $('#current_tab_input').val(currentTab);

                    // Update URL without page reload
                    var newUrl = updateQueryStringParameter(window.location.href, 'tab', currentTab);
                    history.pushState({}, '', newUrl);
                });

                // On page load, show only the active tab
                $('.tab-pane').hide();
                $('.tab-pane.active').show();

                // Function to update URL parameters
                function updateQueryStringParameter(uri, key, value) {
                    var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i');
                    var separator = uri.indexOf('?') !== -1 ? '&' : '?';
                    if (uri.match(re)) {
                        return uri.replace(re, '$1' + key + '=' + value + '$2');
                    }
                    return uri + separator + key + '=' + value;
                }
            });
        </script>
        <?php
    }

    private function upload_logo($blog_id, $file_to_upload)
    {
        // Validate inputs
        if (empty($file_to_upload)) {
            error_log("Logo upload failed: Empty file path provided for blog {$blog_id}");
            return false;
        }

        if (!is_numeric($blog_id)) {
            error_log("Logo upload failed: Invalid blog ID provided");
            return false;
        }

        // Store current blog ID
        $current_blog_id = get_current_blog_id();

        // Switch to the target blog
        switch_to_blog($blog_id);

        // Get file information
        $file_name = basename($file_to_upload);
        $file_type = wp_check_filetype($file_name)['type'];

        if (empty($file_type)) {
            error_log("Logo upload failed: Invalid file type for {$file_name}");
            switch_to_blog($current_blog_id);
            return false;
        }

        // Prepare upload directory for the current blog
        $upload_dir = wp_upload_dir();
        $unique_file_name = wp_unique_filename($upload_dir['path'], $file_name);
        $new_file = $upload_dir['path'] . '/' . $unique_file_name;

        // Create upload directory if it doesn't exist
        wp_mkdir_p($upload_dir['path']);

        // Copy the file to the current blog's uploads directory
        if (!@copy($file_to_upload, $new_file)) {
            error_log("Logo upload failed: Could not copy file from {$file_to_upload} to {$new_file} for blog {$blog_id}");
            switch_to_blog($current_blog_id);
            return false;
        }

        // Prepare attachment data
        $attachment = array(
            'guid' => $upload_dir['url'] . '/' . $unique_file_name,
            'post_mime_type' => $file_type,
            'post_title' => preg_replace('/\.[^.]+$/', '', $unique_file_name),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Insert attachment into the current blog's media library
        $attach_id = wp_insert_attachment($attachment, $new_file);

        if (!$attach_id) {
            error_log("Logo upload failed: Could not create attachment for blog {$blog_id}");
            switch_to_blog($current_blog_id);
            return false;
        }

        // Generate metadata for the attachment
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $new_file);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Update site settings with new logo ID
        $site_settings = get_option('store_settings', []);
        $site_settings['store_logo'] = $attach_id;
        update_option('store_settings', $site_settings);

        // Update WordPress custom logo theme mod
        set_theme_mod('custom_logo', $attach_id);

        // Switch back to original blog
        switch_to_blog($current_blog_id);

        return $attach_id;
    }

    private function upload_banner($blog_id, $file_to_upload)
    {
        // Validate inputs
        if (empty($file_to_upload)) {
            error_log("Banner upload failed: Empty file path provided for blog {$blog_id}");
            return false;
        }

        if (!is_numeric($blog_id)) {
            error_log("Banner upload failed: Invalid blog ID provided");
            return false;
        }

        // Store current blog ID
        $current_blog_id = get_current_blog_id();

        // Switch to the target blog
        switch_to_blog($blog_id);

        // Get file information
        $file_name = basename($file_to_upload);
        $file_type = wp_check_filetype($file_name)['type'];

        if (empty($file_type)) {
            error_log("Banner upload failed: Invalid file type for {$file_name}");
            switch_to_blog($current_blog_id);
            return false;
        }

        // Prepare upload directory for the current blog
        $upload_dir = wp_upload_dir();
        $unique_file_name = wp_unique_filename($upload_dir['path'], $file_name);
        $new_file = $upload_dir['path'] . '/' . $unique_file_name;

        // Create upload directory if it doesn't exist
        wp_mkdir_p($upload_dir['path']);

        // Copy the file to the current blog's uploads directory
        if (!@copy($file_to_upload, $new_file)) {
            error_log("Banner upload failed: Could not copy file from {$file_to_upload} to {$new_file} for blog {$blog_id}");
            switch_to_blog($current_blog_id);
            return false;
        }

        // Prepare attachment data
        $attachment = array(
            'guid' => $upload_dir['url'] . '/' . $unique_file_name,
            'post_mime_type' => $file_type,
            'post_title' => preg_replace('/\.[^.]+$/', '', $unique_file_name),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Insert attachment into the current blog's media library
        $attach_id = wp_insert_attachment($attachment, $new_file);

        if (!$attach_id) {
            error_log("Banner upload failed: Could not create attachment for blog {$blog_id}");
            switch_to_blog($current_blog_id);
            return false;
        }

        // Generate metadata for the attachment
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $new_file);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Update site settings with new banner ID
        $site_settings = get_option('store_settings', []);
        $site_settings['store_banner'] = $attach_id;
        update_option('store_settings', $site_settings);

        // Switch back to original blog
        switch_to_blog($current_blog_id);

        return $attach_id;
    }

    /**
     * Upload and set site icon for a specific blog
     */
    private function upload_site_icon($blog_id, $file_to_upload)
    {
        // Validate inputs
        if (empty($file_to_upload)) {
            error_log("Site icon upload failed: Empty file path provided for blog {$blog_id}");
            return false;
        }

        if (!is_numeric($blog_id)) {
            error_log("Site icon upload failed: Invalid blog ID provided");
            return false;
        }

        // Store current blog ID
        $current_blog_id = get_current_blog_id();

        // Switch to the target blog
        switch_to_blog($blog_id);

        // Get file information
        $file_name = basename($file_to_upload);
        $file_type = wp_check_filetype($file_name)['type'];

        if (empty($file_type)) {
            error_log("Site icon upload failed: Invalid file type for {$file_name}");
            switch_to_blog($current_blog_id);
            return false;
        }

        // Prepare upload directory
        $upload_dir = wp_upload_dir();
        $unique_file_name = wp_unique_filename($upload_dir['path'], $file_name);
        $new_file = $upload_dir['path'] . '/' . $unique_file_name;

        // Create upload directory if it doesn't exist
        wp_mkdir_p($upload_dir['path']);

        // Copy the file
        if (!@copy($file_to_upload, $new_file)) {
            error_log("Site icon upload failed: Could not copy file from {$file_to_upload} to {$new_file} for blog {$blog_id}");
            switch_to_blog($current_blog_id);
            return false;
        }

        // Prepare attachment data
        $attachment = array(
            'guid' => $upload_dir['url'] . '/' . $unique_file_name,
            'post_mime_type' => $file_type,
            'post_title' => preg_replace('/\.[^.]+$/', '', $unique_file_name),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Insert attachment
        $attach_id = wp_insert_attachment($attachment, $new_file);

        if (!$attach_id) {
            error_log("Site icon upload failed: Could not create attachment for blog {$blog_id}");
            switch_to_blog($current_blog_id);
            return false;
        }

        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $new_file);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Update site icon option
        update_option('site_icon', $attach_id);

        // Switch back to original blog
        switch_to_blog($current_blog_id);

        return $attach_id;
    }
}
