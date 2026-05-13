<?php

namespace App\Providers;

class DeliveryRulesServiceProvider
{
    /**
     * Store settings options.
     *
     * @var array
     */
    private static $storeOptions;

    /**
     * Get store options.
     *
     * @return array
     */
    private static function getStoreOptions()
    {
        if (!isset(self::$storeOptions)) {
            self::$storeOptions = get_option('store_settings', []);
        }
        return self::$storeOptions;
    }

    /**
     * Get enable delivery option.
     *
     * @return int
     */
    public static function getEnableDelivery()
    {
        return self::getStoreOptions()['enable_delivery'] ?? 0;
    }

    /**
     * Get delivery rule by index.
     *
     * @param int $index
     * @return array|null
     */
    public static function getDeliveryRule($index)
    {
        return self::getStoreOptions()['delivery_rules'][$index] ?? null;
    }

    /**
     * Get default message for delivery rules.
     *
     * @return string
     */
    public static function getDefaultMessage()
    {
        $message = 'ע"פ מדיניות החנות ניתן לבצע משלוח ע"פ התנאים הבאים:';
        $length = count(self::getStoreOptions()['delivery_rules'] ?? []);
    
        for ($i = 0; $i < $length; $i++) {
            $delivery_rule = self::getDeliveryRule($i);
            if ($delivery_rule && $delivery_rule['active']) {
                $message .= '<br/><br/><div id="delivery_rules_' . $i . '_wrapper" style="display: flex">'
                . self::parseRule($delivery_rule) . '</div>';
            }
        }
        return $message;
    }

    /**
     * Parse delivery rule to simple text.
     *
     * @param array $delivery_rule
     * @return string
     */
    public static function parseRule($delivery_rule)
    {
        if (!$delivery_rule || !$delivery_rule['active']) {
            return '';
        }
        return
            'מינימום הזמנה: ' 
            . ($delivery_rule['min_order'] ? '&#8362;' . $delivery_rule['min_order'] : 'ללא') .
            '. איזור: '
            . (!empty($delivery_rule['city']) ? self::printCities($delivery_rule['city']) : 'כל הארץ') .
            '. מחיר משלוח: '
            . (!empty($delivery_rule['shipping_cost']) && $delivery_rule['shipping_cost'] != 0 ?
                    '&#8362;' . $delivery_rule['shipping_cost'] : 'חינם') . '. ' . $delivery_rule['additional_text'];
    }

    /**
     * Parse an array of cities to a simple string.
     *
     * @param array $cities
     * @return string
     */
    public static function printCities($cities)
    {
        return implode(', ', $cities);
    }

    /**
     * Get policy by index.
     *
     * @param int $index
     * @return array|null
     */
    public static function getPolicy($index)
    {
        return self::getStoreOptions()['policies'][$index] ?? null;
    }

    /**
     * Remove unused checkout fields.
     *
     * @param array $fields
     * @return array
     */
    public static function removeUnusedCheckoutFields($fields)
    {
        unset($fields['billing']['billing_state']);
        unset($fields['billing']['billing_country']);
        unset($fields['billing']['billing_company']);

        unset($fields['shipping']['shipping_company']);
        unset($fields['shipping']['shipping_state']);
        unset($fields['shipping']['shipping_country']);
        unset($fields['shipping']['shipping_city']);
        unset($fields['shipping']['shipping_address_1']);
        unset($fields['shipping']['shipping_address_2']);
        unset($fields['shipping']['shipping_postcode']);
        
        return $fields;
    }

    /**
     * Remove delivery fields if delivery is disabled.
     *
     * @param array $fields
     * @return array
     */
    public static function removeDeliveryFields($fields)
    {
        if (!self::getEnableDelivery()) {
            unset($fields['billing']['billing_city']);
            unset($fields['billing']['billing_address_1']);
            unset($fields['billing']['billing_postcode']); // Represents street-number
            unset($fields['billing']['billing_address_2']);
            unset($fields['billing']['delivery_rules']);
        }
        
        return $fields;
    }
} 