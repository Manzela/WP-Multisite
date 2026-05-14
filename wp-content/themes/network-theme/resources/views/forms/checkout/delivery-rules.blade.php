@php
// Delivery rules utility — requires $storeOptions to be injected by the parent template.

    function get_enable_delivery() {
        return $storeOptions['enable_delivery'] ?? 0;
    }

    function get_delivery_rule($index) {
        return $storeOptions['delivery_rules'][$index];
    }

    // show general information about shipping options 
    // triggered on first load (no chosen city) or if there are no available shipping options
    function get_default_message() {
        $message = 'Per store policy, shipping is available under the following terms:';
        $length = count($storeOptions['delivery_rules']);
    
        for($i=1; $i<=$length; $i++) {
            $delivery_rule = get_delivery_rule($i);
            if($delivery_rule && $delivery_rule['active']) {
                $message .= '<br/><br/><div id="delivery_rules_' . $i . '_wrapper" style="display: flex">'
                . parse_rule($delivery_rule) . '</div>';
            }
        }
        return $message;
    }

    // parse delivery rule to simple text
    function parse_rule($delivery_rule) {
        if(!$delivery_rule || !$delivery_rule['active'])
            return '';
        return
            'Minimum order: ' 
            . ($delivery_rule['min_order'] ? '&euro;' . $delivery_rule['min_order'] : 'None') .
            '. Area: '
            . (!empty($delivery_rule['city']) ? print_cities($delivery_rule['city']) : 'Nationwide') .
            '. Shipping cost: '
            . (!empty($delivery_rule['shipping_cost'] && !$delivery_rule['shipping_cost'] == 0) ?
                    '&euro;' . $delivery_rule['shipping_cost'] : 'Free') . '. ' . $delivery_rule['additional_text'];
    }

    // parse an array of cities to a simple string
    function print_cities($cities) {
        return implode(', ', $cities);
    }


    function get_policy($index) {
        return $storeOptions['policies'][$index]; // [ title: string, body: string ]    
    }
@endphp