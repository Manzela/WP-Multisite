<?php

namespace EventTracker;

class ConsentManager {
    private $cookie_registry;
    
    /**
     * Default consent structure when no consent is given
     */
    public const DEFAULT_CONSENT = [
        'analytics' => 0,
        'enhanced' => 0,
        'location' => 0,
        'cookies' => [
            'essential' => 1, // Always enabled
            'analytics' => 0,
            'marketing' => 0,
            'preferences' => 0
        ],
        'timestamp' => 0,
        'version' => '1.0'
    ];
    
    public function __construct() {
        $this->cookie_registry = new CookieRegistry();
        
        add_action('wp_footer', [$this, 'render_consent_banner']);
        add_action('wp_ajax_et_save_consent', [$this, 'save_consent']);
        add_action('wp_ajax_nopriv_et_save_consent', [$this, 'save_consent']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_consent_scripts']);
    }
    
    public function enqueue_consent_scripts() {
        // First enqueue cookie-utils.js
        wp_enqueue_script(
            'cookie-utils-script',
            EVENT_TRACKER_PLUGIN_URL . 'js/cookie-utils.js',
            array(),
            '1.0.0',
            true
        );

        // Then enqueue consent-manager.js with cookie-utils as dependency
        wp_enqueue_script(
            'consent-manager-script',
            EVENT_TRACKER_PLUGIN_URL . 'js/consent-manager.js',
            array('jquery', 'cookie-utils-script'),
            '1.0.0',
            true
        );
        
        wp_localize_script('consent-manager-script', 'consentData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('et_consent_nonce'),
            'has_consent' => $this->has_valid_consent(),
            'cookie_categories' => $this->cookie_registry->get_cookie_categories(),
            'cookies' => $this->cookie_registry->get_all_cookies(),
            'default_consent' => self::DEFAULT_CONSENT,
            'manage_settings_text' => __('Manage Settings', 'sage'),
            'hide_settings_text' => __('Hide Settings', 'sage')
        ));
    }
    
    public function render_consent_banner() {
        $primary_color = get_option('store_settings')['primary_color'] ?? 'black';
		$text_color = wc_light_or_dark($primary_color, 'black', 'white');

        // Always render the banner but let JavaScript handle show/hide based on consent cookie
        ?>
        <div id="et-consent-banner" class="et-consent-banner" style="display: none;" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
            <div class="et-consent-content">
                <div class="et-consent-text">
                    <p> 
                        <?php echo __('We collect page visits, product interactions, and location data to improve your experience.', 'sage'); ?>
                        <a href="/privacy-policy" target="_blank"><?php echo __('Read our privacy policy', 'sage'); ?></a>
                    </p>
                </div>
                
                <div class="et-consent-options" id="et-consent-options" style="display: none;">
                    <label class="et-consent-option">
                        <div class="et-consent-option-header">
                            <input type="checkbox" name="consent_analytics" value="1">
                            <span><?php echo __('Analytics & Performance', 'sage'); ?></span>
                        </div>
                        <small><?php echo __('Basic page visits and conversion tracking', 'sage'); ?></small>
                    </label>
                    
                    <label class="et-consent-option">
                        <div class="et-consent-option-header">
                            <input type="checkbox" name="consent_enhanced" value="1">
                            <span><?php echo __('Enhanced Features', 'sage'); ?></span>
                        </div>
                        <small><?php echo __('Detailed interactions (search, products, filters)', 'sage'); ?></small>
                    </label>
                    
                    <label class="et-consent-option">
                        <div class="et-consent-option-header">
                            <input type="checkbox" name="consent_location" value="1">
                            <span><?php echo __('Location Services', 'sage'); ?></span>
                        </div>
                        <small><?php echo __('Geographic data for store optimization', 'sage'); ?></small>
                    </label>

                    <label class="et-consent-option">
                        <div class="et-consent-option-header">
                            <input type="checkbox" name="consent_cookies" value="1">
                            <span><?php echo __('Cookies', 'sage'); ?></span>
                            <button id="et-manage-cookies" class="et-btn et-btn-link">
                                <?php echo __('Manage Cookies', 'sage'); ?>
                            </button>
                        </div>
                        <small><?php echo __('Allow us to use cookies to improve your experience', 'sage'); ?></small>
                    </label>
                </div>
                
                <div class="et-consent-buttons">
                    <button id="et-accept-all" class="et-btn et-btn-primary" style="background-color: <?php echo $primary_color; ?>; color: <?php echo $text_color; ?>;">
                        <?php echo __('Accept All', 'sage'); ?>
                    </button>
                    <button id="et-reject-all" class="et-btn et-btn-secondary" style="background-color: <?php echo $primary_color; ?>; color: <?php echo $text_color; ?>;">
                        <?php echo __('Reject All', 'sage'); ?>
                    </button>
                    <button id="et-accept-selected" class="et-btn et-btn-primary" style="background-color: <?php echo $primary_color; ?>; color: <?php echo $text_color; ?>; display: none;">
                        <?php echo __('Accept Selected', 'sage'); ?>
                    </button>
                    <button id="et-manage-settings" class="et-btn et-btn-secondary" style="border: 2px solid <?php echo $primary_color; ?>; color: <?php echo wc_light_or_dark($primary_color, $text_color, $primary_color); ?>; background-color: white;">
                        <?php echo __('Manage Settings', 'sage'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Cookie Management Modal -->
        <div id="et-cookie-modal" class="et-cookie-modal" style="display: none;" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
            <div class="et-cookie-modal-content">
                <div class="et-cookie-modal-header">
                    <span class="et-cookie-modal-title"><?php echo __('Cookie Settings', 'sage'); ?></span>
                    <button class="et-cookie-modal-close">&times;</button>
                </div>
                <div class="et-cookie-modal-body">
                    <?php 
                    $categories = $this->cookie_registry->get_cookie_categories();
                    foreach ($categories as $category): 
                        $cookies = $this->cookie_registry->get_cookies_by_category($category);
                        if (empty($cookies)) continue;
                    ?>
                        <div class="et-cookie-category">
                            <div class="et-cookie-category-header">
                                <label>
                                    <input type="checkbox" name="cookie_category_<?php echo $category; ?>" 
                                           <?php echo $category === 'essential' ? 'checked disabled' : ''; ?>>
                                    <span><?php echo ucfirst($category); ?></span>
                                </label>
                                <p class="et-cookie-category-description">
                                    <?php
                                    switch ($category) {
                                        case 'essential':
                                            echo __('These cookies are necessary for the website to function properly.', 'sage');
                                            break;
                                        case 'analytics':
                                            echo __('These cookies help us understand how visitors interact with our website.', 'sage');
                                            break;
                                        case 'marketing':
                                            echo __('These cookies are used to deliver promotional content and track marketing campaign effectiveness.', 'sage');
                                            break;
                                        case 'preferences':
                                            echo __('These cookies remember your settings and preferences.', 'sage');
                                            break;
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="et-cookie-list">
                                <?php foreach ($cookies as $cookie_name => $cookie_data): ?>
                                    <div class="et-cookie-item">
                                        <div class="et-cookie-header">
                                            <div class="et-cookie-name"><?php echo $cookie_data['name']; ?></div>
                                            <div class="et-cookie-identifier"><?php echo $cookie_name; ?></div>
                                        </div>
                                        <div class="et-cookie-description"><?php echo $cookie_data['description']; ?></div>
                                        <div class="et-cookie-meta">
                                            <span class="et-cookie-duration"><?php echo __('Duration: ', 'sage') . $cookie_data['duration']; ?></span>
                                            <span class="et-cookie-provider"><?php echo __('Provider: ', 'sage') . $cookie_data['provider']; ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="et-cookie-modal-footer">
                    <button id="et-save-cookie-preferences" class="et-btn et-btn-primary" style="background-color: <?php echo $primary_color; ?>; color: <?php echo $text_color; ?>;">
                        <?php echo __('Save Preferences', 'sage'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <style>
        .et-consent-banner {
            position: fixed;
            text-align: center;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 3px solid <?php echo $primary_color ?: '#0073aa'; ?>;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 9999;
            padding: 20px;
        }
        
        .et-consent-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        
        .et-consent-text {
            flex: 1;
            min-width: 300px;
            text-align: center;
        }
        
        .et-consent-text h4 {
            margin: 0 0 8px 0;
            font-size: 18px;
            color: #333;
        }
        
        .et-consent-text p {
            margin: 0;
            color: #666;
            line-height: 1.4;
        }
        
        .et-consent-text a {
            color: #0073aa;
            text-decoration: underline;
        }
        
        .et-consent-options {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            width: 100%;
        }
        
        .et-consent-option {
            display: flex;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            font-size: 14px;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: #f9f9f9;
            min-width: 200px;
            text-align: center;
        }
        
        .et-consent-option-header {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        
        .et-consent-option small {
            color: #666;
            font-size: 12px;
            line-height: 1.3;
        }
        
        .et-consent-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .et-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        
        .et-btn-primary:hover, .et-btn-secondary:hover {
            opacity: 50%;
        }
        
        .et-btn-link {
            background: none;
            color: #0073aa;
            text-decoration: underline;
            padding: 0 !important;
        }

        .et-btn-link:hover {
            color: #005a87;
        }

        /* Checkbox styling with dynamic colors */
        .et-consent-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            border: 2px solid #ccc;
            border-radius: 3px;
            background-color: #fff;
            position: relative;
            transition: all 0.2s ease;
        }

        .et-consent-option input[type="checkbox"]:checked {
            background-color: <?php echo $primary_color; ?> !important;
            border-color: <?php echo $primary_color; ?> !important;
        }



        .et-consent-option input[type="checkbox"]:focus {
            outline: 2px solid <?php echo $primary_color; ?>;
            outline-offset: 2px;
            --tw-ring-color: <?php echo $primary_color; ?>;
        }

        .et-consent-option input[type="checkbox"]:hover {
            border-color: <?php echo $primary_color; ?>;
        }

        /* Cookie Modal Styles */
        .et-cookie-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
        }

        .et-cookie-modal-content {
            background: #fff;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .et-cookie-modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .et-cookie-modal-header .et-cookie-modal-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .et-cookie-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            color: #6b7280;
        }

        .et-cookie-modal-body {
            padding: 1.5rem;
            overflow-y: auto;
        }

        .et-cookie-category {
            margin-bottom: 2rem;
        }

        .et-cookie-category-header {
            margin-bottom: 1rem;
        }

        .et-cookie-category-header label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        /* Cookie modal checkbox styling - same as consent banner */
        .et-cookie-category-header input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            border: 2px solid #ccc;
            border-radius: 3px;
            background-color: #fff;
            position: relative;
            transition: all 0.2s ease;
        }

        .et-cookie-category-header input[type="checkbox"]:checked {
            background-color: <?php echo $primary_color; ?> !important;
            border-color: <?php echo $primary_color; ?> !important;
        }

        .et-cookie-category-header input[type="checkbox"]:focus {
            outline: 2px solid <?php echo $primary_color; ?>;
            outline-offset: 2px;
            --tw-ring-color: <?php echo $primary_color; ?>;
        }

        .et-cookie-category-header input[type="checkbox"]:hover {
            border-color: <?php echo $primary_color; ?>;
        }

        .et-cookie-category-header input[type="checkbox"]:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .et-cookie-category-description {
            color: #6b7280;
            font-size: 0.875rem;
            margin: 0.5rem 0;
        }

        .et-cookie-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .et-cookie-item {
            background: #f9fafb;
            border-radius: 6px;
            padding: 1rem;
        }

        .et-cookie-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 0.25rem;
        }

        .et-cookie-name {
            font-weight: 600;
        }

        .et-cookie-identifier {
            font-family: monospace;
            font-size: 0.75rem;
            color: #6b7280;
            background: #e5e7eb;
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
        }

        .et-cookie-description {
            color: #4b5563;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .et-cookie-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .et-cookie-modal-footer {
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
        }

        [dir="rtl"] .et-cookie-modal-close {
            margin-left: 0;
            margin-right: auto;
        }

        [dir="rtl"] .et-cookie-modal-footer {
            justify-content: flex-start;
        }

        @media (max-width: 640px) {
            .et-cookie-modal-content {
                width: 95%;
                margin: 1rem;
            }
            
            .et-cookie-meta {
                flex-direction: column;
                gap: 0.5rem;
            }

            .et-cookie-header {
                flex-direction: column;
                gap: 0.25rem;
            }
        }
        </style>
        <?php
    }
    
    public function save_consent() {
        check_ajax_referer('et_consent_nonce', 'nonce');
        
        $consent_data = array(
            'analytics' => isset($_POST['analytics']) ? 1 : 0,
            'enhanced' => isset($_POST['enhanced']) ? 1 : 0,
            'location' => isset($_POST['location']) ? 1 : 0,
            'cookies' => array(
                'essential' => 1, // Always enabled
                'analytics' => isset($_POST['cookies']['analytics']) ? 1 : 0,
                'marketing' => isset($_POST['cookies']['marketing']) ? 1 : 0,
                'preferences' => isset($_POST['cookies']['preferences']) ? 1 : 0
            ),
            'timestamp' => time(),
            'version' => '1.0'
        );
        
        // Store in session for server-side access if needed
        if (!session_id()) {
            session_start();
        }
        $_SESSION['et_consent'] = $consent_data;
        
        // Send consent data to frontend for cookies storage
        wp_send_json_success(array(
            'message' => __('Consent preferences saved', 'event-tracker-plugin'),
            'consent' => $consent_data
        ));
    }
    
    /**
     * Check if a specific cookie type is allowed based on user consent
     *
     * @param string $cookie_type The type of cookie to check (essential, analytics, preferences, location)
     * @return bool Whether the cookie type is allowed
     */
    public function is_cookie_allowed($cookie_type) {
        // Essential cookies are always allowed
        if ($cookie_type === 'essential') {
            return true;
        }

        $consent_data = $this->get_consent_preferences();

        // Check if the cookie type is allowed in the cookies consent
        return isset($consent_data['cookies'][$cookie_type]) && $consent_data['cookies'][$cookie_type] === 1;
    }
    
    public function has_valid_consent() {
        $consent_data = $this->get_consent_preferences();
        
        if (!$consent_data || !isset($consent_data['timestamp'])) {
            return false;
        }
        
        // Check if consent is older than 6 months (GDPR requirement)
        $max_age = 6 * 30 * 24 * 60 * 60; // 6 months in seconds
        if (time() - $consent_data['timestamp'] > $max_age) {
            return false;
        }
        
        return true;
    }
    
    public function get_consent_preferences() {
        // First check for consent in cookies (preferred method)
        if (isset($_COOKIE['et_consent'])) {
            try {
                $consent_data = json_decode(stripslashes($_COOKIE['et_consent']), true);
                if ($consent_data && isset($consent_data['timestamp'])) {
                    // Also update session for server-side access
                    if (!session_id()) {
                        session_start();
                    }
                    $_SESSION['et_consent'] = $consent_data;
                    return $consent_data;
                }
            } catch (Exception $e) {
                // Invalid JSON in cookie, continue to session check
            }
        }
        
        // Fallback to session storage
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION['et_consent'])) {
            return $_SESSION['et_consent'];
        }
        
        // Return default consent if no consent found
        return self::DEFAULT_CONSENT;
    }
} 