<div id="store-info" class="tab-content {{ request()->get('tab') === 'store-info' ? 'tab-content-active' : '' }}">
    <div class="store-info-section">

        <div style="border-bottom: 2px solid black; padding-bottom: 10px; margin-bottom: 20px;">
            <h2>Store Information</h2>
        </div>

        {{-- Full address fields --}}
        <div style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
            <h3>Address</h3>
        </div>
        <div class="form-field">
            <label for="store_address">Street address</label>
            <input type="text" id="store_address" name="store_settings[store_info][address]"
                value="{{ esc_attr($store_info['address'] ?? get_option('woocommerce_store_address', '')) }}"
                class="regular-text" placeholder="Street address">
        </div>

        <div class="form-field">
            <label for="store_address_2">Apartment, suite, unit, etc.</label>
            <input type="text" id="store_address_2" name="store_settings[store_info][address_2]"
                value="{{ esc_attr($store_info['address_2'] ?? get_option('woocommerce_store_address_2', '')) }}"
                class="regular-text" placeholder="Apartment, suite, unit, etc. (optional)">
        </div>

        <div class="form-field">
            <label for="store_city">City</label>
            <input type="text" id="store_city" name="store_settings[store_info][city]"
                value="{{ esc_attr($store_info['city'] ?? get_option('woocommerce_store_city', '')) }}"
                class="regular-text" placeholder="City">
        </div>

        <div class="form-field">
            <label for="store_postcode">Postcode / ZIP</label>
            <input type="text" id="store_postcode" name="store_settings[store_info][postcode]"
                value="{{ esc_attr($store_info['postcode'] ?? get_option('woocommerce_store_postcode', '')) }}"
                class="regular-text" placeholder="Postcode / ZIP">
        </div>

        <div class="form-field">
            <label for="store_country">Country / Region</label>
            <select id="store_country" name="store_settings[store_info][country]" class="regular-text">
                @php
                    $countries = WC()->countries->get_countries();
                    $current_country = $store_info['country'] ?? get_option('woocommerce_default_country');
                @endphp
                @foreach($countries as $code => $name)
                    <option value="{{ $code }}" {{ selected($current_country, $code, false) }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
            <h3>Contact Information</h3>
        </div>

        {{-- Email Field --}}
        <div class="form-field">
            <label for="store_email">Store Email</label>
            <input type="email" id="store_email" name="store_settings[store_info][email]"
                value="{{ esc_attr($store_info['email'] ?? '') }}" class="regular-text">
        </div>

        {{-- Phone Number Field --}}
        <div class="form-field">
            <label for="store_phone">Store Phone Number</label>
            <input type="tel" id="store_phone" name="store_settings[store_info][phone]"
                value="{{ esc_attr($store_info['phone'] ?? '') }}" class="regular-text"
                placeholder="e.g., +1 234 567 8900">
        </div>
        {{-- store eCommerce link --}}
        <div class="form-field">
            <label for="store_ecommerce_link">Store eCommerce Link</label>
            <input type="url" id="store_ecommerce_link" name="store_settings[store_info][ecommerce_link]"
                value="{{ esc_attr($store_info['ecommerce_link'] ?? '') }}" class="regular-text">
        </div>

        <div style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
            <h3>More information</h3>
        </div>
        {{-- Store Description Field with Gutenberg --}}
        <div class="form-field">
            <label for="store_description">Store Description</label>
            <div class="store-description-editor">
                <?php
$content = $store_info['description'] ?? '';
$editor_settings = [
    'media_buttons' => true,
    'textarea_name' => 'store_settings[store_info][description]',
    'textarea_rows' => 10,
    'editor_class' => 'rtl-text',
    'editor_css' => '<style>.wp-editor-area{direction:rtl;}</style>',
    'tinymce' => [
        'directionality' => 'rtl',
        'content_css' => get_stylesheet_directory_uri() . '/resources/styles/editor.css',
    ],
];
wp_editor($content, 'store_description', $editor_settings);
                ?>
            </div>
            <p class="description">
                This description will be displayed on your store page.
            </p>
        </div>

        {{-- Opening Hours Field --}}
        <div class="form-field opening-hours-container">
            <h3>Opening Hours</h3>
            <div class="opening-hours-wrapper">
                @php
                    $days = [
                        'sunday' => 'Sunday',
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                    ];
                @endphp

                @foreach ($days as $day_key => $day_label)
                    @php
                        $day_data = $store_info['hours'][$day_key] ?? ['closed' => false, 'periods' => [['open' => '09:00', 'close' => '17:00']]];
                        $is_closed = $day_data['closed'] ?? false;
                        $periods = $day_data['periods'] ?? [['open' => '09:00', 'close' => '17:00']];
                    @endphp

                    <div class="day-row" data-day="{{ $day_key }}">
                        <div class="day-name">
                            {{ $day_label }}
                        </div>
                        <div class="day-times">
                            <label class="closed-checkbox">
                                <input type="checkbox" name="store_settings[store_info][hours][{{ $day_key }}][closed]"
                                    value="1" class="day-closed-toggle" {{ $is_closed ? 'checked' : '' }}>
                                Closed
                            </label>

                            <div class="periods-container {{ $is_closed ? 'hidden' : '' }}">
                                @foreach ($periods as $period_index => $period)
                                    <div class="period-row" data-period-index="{{ $period_index }}">
                                        <div class="time-inputs">
                                            <input type="time"
                                                name="store_settings[store_info][hours][{{ $day_key }}][periods][{{ $period_index }}][open]"
                                                value="{{ $period['open'] ?? '09:00' }}" class="time-input">
                                            <span class="time-separator">-</span>
                                            <input type="time"
                                                name="store_settings[store_info][hours][{{ $day_key }}][periods][{{ $period_index }}][close]"
                                                value="{{ $period['close'] ?? '17:00' }}" class="time-input">
                                            @if ($period_index > 0)
                                                <button type="button" class="button remove-period"
                                                    title="Remove this period">×</button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                <div class="period-actions">
                                    <button type="button" class="button add-period">Add Break Period</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Copy Hours Button --}}
            <div class="hours-actions">
                <button type="button" id="copy-to-all" class="button">
                    Copy Sunday to All Days
                </button>
            </div>
        </div>

        <style>
            .period-row {
                margin-bottom: 8px;
            }

            .period-row .time-inputs {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .period-actions {
                margin-top: 8px;
            }

            .remove-period {
                margin-left: 8px;
                color: #d63384;
                font-size: 16px;
                line-height: 1;
                padding: 2px 6px;
            }

            .add-period {
                font-size: 12px;
                padding: 4px 8px;
            }

            .periods-container {
                margin-left: 20px;
            }
        </style>

        {{-- Custom Tab Field --}}
        <div class="form-field">
            <label for="custom_tab">Custom Tab</label>
            <div class="custom-tab-editor">
                <?php
$content = $store_info['custom_tab'] ?? '';
$editor_settings = [
    'media_buttons' => true,
    'textarea_name' => 'store_settings[store_info][custom_tab]',
    'textarea_rows' => 10,
    'editor_class' => 'rtl-text',
    'editor_css' => '<style>.wp-editor-area{direction:rtl;}</style>',
    'tinymce' => [
        'directionality' => 'rtl',
        'content_css' => get_stylesheet_directory_uri() . '/resources/styles/editor.css',
    ],
];
wp_editor($content, 'custom_tab', $editor_settings);
            ?>
            </div>
            <p class="description">
                This text will be displayed in a custom tab on the product page.
            </p>
        </div>
    </div>
</div>