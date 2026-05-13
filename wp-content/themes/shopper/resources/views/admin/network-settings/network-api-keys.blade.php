<p class="notice notice-info" style="height: 2rem; font-size: 1rem; align-content: center;">
    NOTE: All API keys configured here are used across all sites in the network.
</p>

<table class="form-table" role="presentation">
    <tbody>
        <tr>
            <th scope="row">
                <label for="network_google_places_api_key">Google Places</label>
            </th>
            <td>
                <input 
                    type="text" 
                    id="network_google_places_api_key" 
                    name="network_store_settings[network_google_places_api_key]" 
                    value="{{ $network_options['network_google_places_api_key'] ?? '' }}"
                    class="regular-text"
                >
                <p class="description">
                Required for syncing with Google Business data.
                </p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="network_google_cloud_storage_api_key">Google Cloud Storage Service Account</label>
            </th>
            <td>
                <textarea 
                    id="network_google_cloud_storage_api_key" 
                    name="network_store_settings[network_google_cloud_storage_api_key]" 
                    rows="15" 
                    cols="80"
                    class="large-text code"
                    placeholder='JSON content goes here'
                >{!! $network_options['network_google_cloud_storage_api_key'] ?? '' !!}
                </textarea>
                <p class="description">
                    Paste the complete JSON content here. Required for storing data on Google Cloud Storage (Bucket).
                </p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="network_growth_dashboard_db_credentials">Growth Dashboard Database Credentials</label>
            </th>
            <td>
                <textarea 
                    id="network_growth_dashboard_db_credentials" 
                    name="network_store_settings[network_growth_dashboard_db_credentials]" 
                    rows="9" 
                    cols="80"
                    class="large-text code"
                    placeholder='JSON content goes here'
                >{!! $network_options['network_growth_dashboard_db_credentials'] ?? '' !!}
                </textarea>
                <p class="description">
                    Paste the complete JSON content here. Required for storing data on Growth Dashboard database.
                </p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="network_cloudflare_dns_api_key">Cloudflare DNS</label>
            </th>
            <td>
                <input 
                    type="text" 
                    id="network_cloudflare_dns_api_key" 
                    name="network_store_settings[network_cloudflare_dns_api_key]" 
                    value="{{ $network_options['network_cloudflare_dns_api_key'] ?? '' }}"
                    class="regular-text"
                >
                <p class="description">
                    Required for domain ownership verification (via Merchantor).
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="network_ga4_property">Network GA4 Property ID</label>
            </th>
            <td>
                @php
                    $ga4_property = $network_options['network_ga4_property'] ?? '';
                @endphp
                <input 
                    type="text" 
                    id="network_ga4_property" 
                    name="network_store_settings[network_ga4_property]" 
                    value="{{ esc_attr($ga4_property) }}"
                    class="regular-text"
                    placeholder="G-XXXXXXXXXX"
                >
                <p class="description">
                    Enter your Google Analytics 4 Property ID (e.g., G-XXXXXXXXXX). This will be applied to all sites in the network.
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="network_gtm_container">Network GTM Container ID</label>
            </th>
            <td>
                @php
                    $gtm_container = $network_options['network_gtm_container'] ?? '';
                @endphp
                <input 
                    type="text" 
                    id="network_gtm_container" 
                    name="network_store_settings[network_gtm_container]" 
                    value="{{ esc_attr($gtm_container) }}"
                    class="regular-text"
                    placeholder="GT-XXXXXXX"
                >
                <p class="description">
                    Enter your Google Tag Manager Container ID (e.g., GT-XXXXXXX). This will be applied to all sites in the network.
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="network_bing_api_key">Network Bing API Key</label>
            </th>
            <td>
                @php
                    $bing_api_key = $network_options['network_bing_api_key'] ?? '';
                @endphp
                <input 
                    type="text" 
                    id="network_bing_api_key" 
                    name="network_store_settings[network_bing_api_key]" 
                    value="{{ esc_attr($bing_api_key) }}"
                    class="regular-text"
                    placeholder="API Key from Bing Webmaster Tools"
                >
                <p class="description">
                    Required for fetching impressions (via Growth dashboard)
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="network_bing_webmaster">Network Bing Webmaster Tools</label>
            </th>
            <td>
                @php
                    $bing_webmaster = $network_options['network_bing_webmaster'] ?? '';
                @endphp
                <input 
                    type="text" 
                    id="network_bing_webmaster" 
                    name="network_store_settings[network_bing_webmaster]" 
                    value="{{ esc_attr($bing_webmaster) }}"
                    class="regular-text"
                    placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX"
                >
                <p class="description">
                    Enter your Bing Webmaster Tools verification code. This will be applied to all sites in the network.
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="network_microsoft_clarity">Network Microsoft Clarity</label>
            </th>
            <td>
                @php
                    $microsoft_clarity = $network_options['network_microsoft_clarity'] ?? '';
                @endphp
                <input 
                    type="text" 
                    id="network_microsoft_clarity" 
                    name="network_store_settings[network_microsoft_clarity]" 
                    value="{{ esc_attr($microsoft_clarity) }}"
                    class="regular-text"
                    placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX"
                >
                <p class="description">
                    Enter your Microsoft Clarity Project ID. This will be applied to all sites in the network.
                </p>
            </td>
        </tr>
    </tbody>
</table>