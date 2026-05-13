<?php

namespace EventTracker;

class CookieRegistry {
    private $cookie_registry = [
        'essential' => [
            'wordpress_[hash]' => [
                'name' => 'WordPress Session',
                'description' => 'Used to maintain your session on the website',
                'duration' => 'Session',
                'provider' => 'WordPress'
            ],
            'woocommerce_cart_hash' => [
                'name' => 'WooCommerce Cart Hash',
                'description' => 'Stores your shopping cart information',
                'duration' => 'Session',
                'provider' => 'WooCommerce'
            ],
            'woocommerce_items_in_cart' => [
                'name' => 'WooCommerce Items in Cart',
                'description' => 'Tracks items in your shopping cart',
                'duration' => 'Session',
                'provider' => 'WooCommerce'
            ],
            'wp_woocommerce_session_[hash]' => [
                'name' => 'WooCommerce Session',
                'description' => 'Maintains your WooCommerce session',
                'duration' => 'Session',
                'provider' => 'WooCommerce'
            ],
            'visitor_id' => [
                'name' => 'Visitor ID',
                'description' => 'Unique identifier for tracking your visit',
                'duration' => '13 months',
                'provider' => 'Event Tracker'
            ]
        ],
        'analytics' => [
            '_ga' => [
                'name' => 'Google Analytics',
                'description' => 'Used to distinguish unique users',
                'duration' => '2 years',
                'provider' => 'Google'
            ],
            '_gid' => [
                'name' => 'Google Analytics',
                'description' => 'Used to distinguish users',
                'duration' => '24 hours',
                'provider' => 'Google'
            ],
            '_clck' => [
                'name' => 'Microsoft Clarity',
                'description' => 'Tracks user interactions for website analytics',
                'duration' => '13 months',
                'provider' => 'Microsoft'
            ],
            '_clsk' => [
                'name' => 'Microsoft Clarity Session',
                'description' => 'Session-specific tracking for user behavior analysis',
                'duration' => 'Session',
                'provider' => 'Microsoft'
            ]
        ],
        'marketing' => [
            'firstPopupTime' => [
                'name' => 'First Popup Time',
                'description' => 'Stores the timestamp of when the promotional popup was first shown to the user',
                'duration' => '13 months',
                'provider' => 'Example-Network'
            ],
            'popupShownCount' => [
                'name' => 'Popup Shown Count',
                'description' => 'Tracks how many times the promotional popup has been shown to the user',
                'duration' => '13 months',
                'provider' => 'Example-Network'
            ]
        ],
        'preferences' => [
            'et_consent' => [
                'name' => 'Consent Preferences',
                'description' => 'Stores your consent preferences',
                'duration' => '13 months',
                'provider' => 'Event Tracker'
            ]
        ]
    ];

    public function __construct() {
        add_action('init', [$this, 'register_cookie_filters']);
    }

    public function register_cookie_filters() {
        // Filter for setting cookies
        add_filter('wp_cookie_constants', [$this, 'filter_cookie_constants'], 10, 1);
        
        // Filter for WooCommerce cookies
        add_filter('woocommerce_cookie_consent', [$this, 'filter_woocommerce_cookies'], 10, 1);
    }

    public function filter_cookie_constants($cookies) {
        if (!$this->has_cookie_consent()) {
            // Only allow essential cookies
            return array_intersect_key($cookies, array_flip($this->cookie_registry['essential']));
        }
        return $cookies;
    }

    public function filter_woocommerce_cookies($cookies) {
        if (!$this->has_cookie_consent()) {
            // Only allow essential WooCommerce cookies
            return array_intersect_key($cookies, array_flip($this->cookie_registry['essential']));
        }
        return $cookies;
    }

    public function has_cookie_consent() {
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION['et_consent']) && isset($_SESSION['et_consent']['cookies'])) {
            return $_SESSION['et_consent']['cookies'] === 1;
        }
        
        return false;
    }

    public function get_cookie_categories() {
        return array_keys($this->cookie_registry);
    }

    public function get_cookies_by_category($category) {
        return isset($this->cookie_registry[$category]) ? $this->cookie_registry[$category] : [];
    }

    public function get_all_cookies() {
        return $this->cookie_registry;
    }

    public function is_cookie_essential($cookie_name) {
        return isset($this->cookie_registry['essential'][$cookie_name]);
    }
} 