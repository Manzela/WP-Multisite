<div id="social" class="tab-content" style="display: {{ request()->get('tab') === 'social' ? 'block' : 'none' }}">
    <div class="social-content-wrapper">
        <div class="social-links-section">
            <h3 class="social-media-title">Social Media Links</h3>
            
            {{-- Existing social links container --}}
            <div id="social-links-container" class="social-links-container">
                @foreach ($social_links ?? [] as $index => $social)
                    <div class="social-link-entry">
                        <select name="store_settings[social][{{ $index }}][icon]" class="social-icon-select">
                            <option value="">Select Platform</option>
                            @foreach ($social_icons as $icon_value => $icon_label)
                                <option value="{{ $icon_value }}" {{ ($social['icon'] ?? '') === $icon_value ? 'selected' : '' }}>
                                    {{ $icon_label }}
                                </option>
                            @endforeach
                        </select>
                        <input type="url" 
                            name="store_settings[social][{{ $index }}][url]" 
                            value="{{ esc_url($social['url'] ?? '') }}" 
                            class="social-link-input"
                            placeholder="Social Media URL"
                        >
                        <button type="button" class="remove-social-link">Remove</button>
                    </div>
                @endforeach
            </div>

            {{-- Hidden template for new social links --}}
            <template id="social-link-template">
                <div class="social-link-entry">
                    <select name="store_settings[social][INDEX][icon]" class="social-icon-select">
                        <option value="">Select Platform</option>
                        @foreach ($social_icons as $icon_value => $icon_label)
                            <option value="{{ $icon_value }}">{{ $icon_label }}</option>
                        @endforeach
                    </select>
                    <input type="url" 
                        name="store_settings[social][INDEX][url]" 
                        class="social-link-input"
                        placeholder="Social Media URL"
                    >
                    <button type="button" class="remove-social-link">Remove</button>
                </div>
            </template>

            <button type="button" id="add-social-link" class="add-social-link">
                Add Social Link
            </button>
        </div>
    </div>
</div>