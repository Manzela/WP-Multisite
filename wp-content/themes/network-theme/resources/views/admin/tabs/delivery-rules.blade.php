<div id="delivery-rules" class="tab-content {{ request()->get('tab') === 'delivery-rules' ? 'tab-content-active' : '' }}">
   
    <div class="delivery-settings mb-4">
        {{-- Enable Delivery Checkbox --}}
        <div class="delivery-enable">
            <label for="enable_delivery">Enable Delivery</label>
            <input type="checkbox" id="enable_delivery" name="store_settings[enable_delivery]" value="1" {{ isset($options['enable_delivery']) && $options['enable_delivery'] ? 'checked' : '' }}>
        </div>
    </div>
    <div id="delivery-rules-container">
        @foreach ($delivery_rules as $index => $rule)
            <div class="delivery-rule bg-white rounded-lg shadow-sm border border-gray-200 mb-4">
                <div class="delivery-rule-header toggle-rule">
                    <span class="rule-title">Delivery Rule #{{ $index + 1 }}</span>
                    <button type="button" class="toggle-rule-icon">
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                </div>
                <div class="delivery-rule-content p-4 space-y-4" style="display: none;">
                    <label>Active</label>
                    <input type="checkbox" name="store_settings[delivery_rules][{{ $index }}][active]" value="1" {{ isset($rule['active']) && $rule['active'] ? 'checked' : '' }}>

                    <label>Minimum Order</label>
                    <input type="number" name="store_settings[delivery_rules][{{ $index }}][min_order]" value="{{ esc_attr($rule['min_order'] ?? '') }}">
                    <label>Cities</label>
                    <select class="city-select" name="store_settings[delivery_rules][{{ $index }}][city][]" multiple>
                        @foreach ($cities ?? [] as $city)
                            <option value="{{ esc_attr($city) }}" {{ isset($rule['city']) && in_array($city, $rule['city']) ? 'selected' : '' }}>{{ esc_html($city) }}</option>
                        @endforeach
                    </select>

                    <label>Shipping Cost</label>
                    <input type="number" name="store_settings[delivery_rules][{{ $index }}][shipping_cost]" value="{{ esc_attr($rule['shipping_cost'] ?? '') }}">

                    <label>Additional Text</label>
                    <div class="wp-editor-container">
                        @php
                            wp_editor(
                                $rule['additional_text'] ?? '',
                                'delivery_additional_text_' . $index,
                                [
                                    'textarea_name' => "store_settings[delivery_rules][{$index}][additional_text]",
                                    'media_buttons' => true,
                                    'tinymce' => true,
                                    'quicktags' => true,
                                    'editor_height' => 200,
                                ]
                            );
                        @endphp
                    </div>

                    <button type="button" class="remove-delivery-rule">Remove Delivery Rule</button>
                </div>
            </div>
        @endforeach
    </div>
    <button type="button" id="add-delivery-rule">Add Delivery Rule</button>
</div>