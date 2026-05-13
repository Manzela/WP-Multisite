@php
// TODO: include this file
// TODO: $storeOptions is undefined here

    function get_enable_delivery() {
        return $storeOptions['enable_delivery'] ?? 0;
    }

    function get_delivery_rule($index) {
        return $storeOptions['delivery_rules'][$index];
    }

    // show general information about shipping options 
    // triggered on first load (no chosen city) or if there are no available shipping options
    function get_default_message() {
        $message = 'ע"פ מדיניות החנות ניתן לבצע משלוח ע"פ התנאים הבאים:';
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
            'מינימום הזמנה: ' 
            . ($delivery_rule['min_order'] ? '&#8362;' . $delivery_rule['min_order'] : 'ללא') .
            '. איזור: '
            . (!empty($delivery_rule['city']) ? print_cities($delivery_rule['city']) : 'כל הארץ') .
            '. מחיר משלוח: '
            . (!empty($delivery_rule['shipping_cost'] && !$delivery_rule['shipping_cost'] == 0) ?
                    '&#8362;' . $delivery_rule['shipping_cost'] : 'חינם') . '. ' . $delivery_rule['additional_text'];
    }

    // parse an array of cities to a simple string
    function print_cities($cities) {
        return implode(', ', $cities);
    }


    function get_policy($index) {
        return $storeOptions['policies'][$index]; // [ title: string, body: string ]    
    }
@endphp