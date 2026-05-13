<div id="seo" class="tab-content {{ !request()->has('tab') || request()->get('tab') === 'seo' ? 'active' : '' }}">
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="store_name">Store Name</label>
            </th>
            <td>
                <input type="text" id="store_name" name="store_settings[seo][store_name]"
                    value="{{ get_option('store_settings')['seo']['store_name'] ?? '' }}" class="regular-text">
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="language">Language</label>
            </th>
            <td>
                <input type="text" id="language" name="store_settings[seo][language]"
                    value="{{ get_option('store_settings')['seo']['language'] ?? '' }}" class="regular-text">
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="image_alt">Image Alt Tag</label>
            </th>
            <td>
                <input type="text" id="image_alt" name="store_settings[seo][image_alt]"
                    value="{{ get_option('store_settings')['seo']['image_alt'] ?? '' }}" class="regular-text">
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="city">Location - City Name</label>
            </th>
            <td>
                <input type="text" id="city" name="store_settings[seo][city]"
                    value="{{ get_option('store_settings')['seo']['city'] ?? '' }}" class="regular-text">
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="mall">Location - Mall Name</label>
            </th>
            <td>
                <input type="text" id="mall" name="store_settings[seo][mall]"
                    value="{{ get_option('store_settings')['seo']['mall'] ?? '' }}" class="regular-text">
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="neighborhood">Location - Neighborhood Name</label>
            </th>
            <td>
                <input type="text" id="neighborhood" name="store_settings[seo][neighborhood]"
                    value="{{ get_option('store_settings')['seo']['neighborhood'] ?? '' }}" class="regular-text">
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="latitude">Latitude</label>
            </th>
            <td>
                <input type="text" id="latitude" name="store_settings[store_info][latitude]"
                    value="{{ get_option('store_settings')['store_info']['latitude'] ?? '' }}" class="regular-text">
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="longitude">Longitude</label>
            </th>
            <td>
                <input type="text" id="longitude" name="store_settings[store_info][longitude]"
                    value="{{ get_option('store_settings')['store_info']['longitude'] ?? '' }}" class="regular-text">
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="price_range">Price Range</label>
            </th>
            <td>
                <select id="price_range" name="store_settings[store_info][price_range]" class="regular-text">
                    <option value="$" {{ (get_option('store_settings')['store_info']['price_range'] ?? '') === '$' ? 'selected' : '' }}>
                        $ (Budget)
                    </option>
                    <option value="$$" {{ (get_option('store_settings')['store_info']['price_range'] ?? '') === '$$' ? 'selected' : '' }}>
                        $$ (Moderate)
                    </option>
                    <option value="$$$" {{ (get_option('store_settings')['store_info']['price_range'] ?? '') === '$$$' ? 'selected' : '' }}>
                        $$$ (Expensive)
                    </option>
                </select>
                <p class="description">Select the price range for your store</p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="payment_accepted">Payment Methods</label>
            </th>
            <td>
                <input type="text" id="payment_accepted" name="store_settings[store_info][payment_accepted]"
                    value="{{ get_option('store_settings')['store_info']['payment_accepted'] ?? '' }}"
                    class="regular-text">
                <p class="description">Enter accepted payment methods (e.g., "Cash, Credit Card, PayPal")</p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="business_type">Business Type</label>
            </th>
            <td>
                @php
                    $business_types = include get_template_directory() . '/app/Data/BusinessTypes.php';
                    $selected_types = get_option('store_settings')['store_info']['business_type'] ?? [];
                @endphp
                <select id="business_type" name="store_settings[store_info][business_type][]"
                    class="regular-text select2-multiple business-type-select" multiple="multiple">
                    @foreach($business_types as $slug => $type_data)
                        <option value="{{ $slug }}" {{ in_array($slug, $selected_types) ? 'selected' : '' }}>
                            {{ $type_data[1] }}
                        </option>
                    @endforeach
                </select>
                <p class="description">Select business types that describe your store</p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="gmb_link">GMB Link</label>
            </th>
            <td>
                <input type="url" id="gmb_link" name="store_settings[seo][gmb_link]"
                    value="{{ get_option('store_settings')['seo']['gmb_link'] ?? '' }}" class="regular-text">
                <p class="description">Enter your Google My Business profile URL</p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="gmb_name">GMB Profile Name</label>
            </th>
            <td>
                <input type="text" id="gmb_name" name="store_settings[seo][gmb_name]"
                    value="{{ get_option('store_settings')['seo']['gmb_name'] ?? '' }}" class="regular-text">
                <p class="description">Enter your Google My Business profile name (this will override the search term
                    for GMB sync)</p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="gmb_name">Buy in-store Custom link</label>
            </th>
            <td>
                <input type="text" id="buy_in_store_custom_link" name="store_settings[seo][buy_in_store_custom_link]"
                    value="{{ get_option('store_settings')['seo']['buy_in_store_custom_link'] ?? '' }}"
                    class="regular-text">
                <p class="description">Enter your custom link including https:// (this will override the default
                    Google-maps behavior)</p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="storeCode">Store Code</label>
            </th>
            <td>
                <input type="text" id="storeCode" name="store_settings[seo][storeCode]"
                    value="{{ get_option('store_settings')['seo']['storeCode'] ?? '' }}" class="regular-text">
                <p class="description">Enter your store code (for google my-business)</p>
            </td>
        </tr>

    </table>
</div>