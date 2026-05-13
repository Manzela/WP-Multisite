<?php

namespace App;

/**
 * Customize WooCommerce emails
 */
class WC_Email_Customization {
    public function __construct() {
        // Add favicon to email header
        add_filter('woocommerce_email_header_image_attachment', [$this, 'add_favicon_to_email'], 10, 2);
        
        // Add custom styles without overriding defaults
        add_filter('woocommerce_email_styles', [$this, 'add_custom_email_styles'], 99);

        // Hide shipping/delivery details from emails
        add_filter('woocommerce_email_order_meta_fields', [$this, 'hide_shipping_details'], 10, 3);
        add_filter('woocommerce_email_customer_details_fields', [$this, 'hide_customer_shipping_details'], 10, 3);
        add_filter('woocommerce_email_order_shipping_address', '__return_false');

        // Add footer text customization
        add_filter('woocommerce_email_footer_text', [$this, 'customize_email_footer_text']);
    }

    // Add custom email styles without overriding defaults
    public function add_custom_email_styles($css) {
        // Get primary color from theme settings or default
        $primary_color = get_option('store_settings', [])['primary_color'] ?? '#f7f7f7';
        
        // determine text color based on background color
        $text_color = wc_light_or_dark($primary_color, '#000000', '#ffffff');
        
        // Only add specific custom styles
        $custom_css = "
            /* RTL Overrides */
            html, body, #wrapper, #template_container, 
            #template_header, #template_body, #template_footer,
            .td, table, tr, th, .order-info , .a3s {
                direction: rtl !important;
                text-align: right !important;
            }

            /* Fix table alignments for RTL */
            .td, .text, table.td, th {
                text-align: right !important;
            }

            /* Reverse padding for better RTL display */
            .td {
                padding: 12px 0 12px 12px !important;
            }

            /* other styles */
            #template_header_image {
                max-width: 100px !important;
                margin: 0 auto !important;
            }
            
            #template_header {
                background-color: {$primary_color} !important;
            }

            #template_header h1 {
                color: {$text_color} !important;
            }
            
            .order-info a {
                color: {$primary_color} !important;
            }

            /* Hide shipping-related elements */
            .shipping-address,
            .shipping-method,
            .shipping-total {
                display: none !important;
            }
        ";
        
        return $css . $custom_css;
    }

    // Hide shipping details from order meta
    public function hide_shipping_details($fields, $sent_to_admin, $order) {
        unset($fields['shipping_method']);
        unset($fields['shipping_total']);
        return $fields;
    }
    
    // Hide shipping details from customer details
    public function hide_customer_shipping_details($fields, $sent_to_admin, $order) {
        unset($fields['shipping_address_1']);
        unset($fields['shipping_address_2']);
        unset($fields['shipping_city']);
        unset($fields['shipping_postcode']);
        unset($fields['shipping_country']);
        unset($fields['shipping_state']);
        return $fields;
    }

    // Add footer text customization
    public function customize_email_footer_text($text) {
        return '<a href="' . esc_url(get_site_url()) . '">' . esc_html(get_site_url()) . '</a>';
    }
}

// Initialize the class
new WC_Email_Customization(); 