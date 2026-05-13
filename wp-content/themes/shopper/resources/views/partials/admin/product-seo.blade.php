<div class="shopper-seo-wrapper">
    <div class="shopper-seo-field">
        <label for="shopper_meta_title">Meta Title</label>
        <input 
            type="text" 
            id="_shopper_meta_title" 
            name="_shopper_meta_title" 
            value="{{ esc_attr($meta_title) }}" 
            class="widefat"
        >
    </div>

    <div class="shopper-seo-field">
        <label for="_shopper_meta_description">Meta Description</label>
        <textarea 
            id="_shopper_meta_description" 
            name="_shopper_meta_description" 
            rows="3" 
            class="widefat"
        >{{ esc_textarea($meta_description) }}</textarea>
    </div>

    <div class="shopper-seo-field">
        <label for="_shopper_focus_keywords">Focus Keywords (separated by comma)</label>
        <input 
            type="text" 
            id="_shopper_focus_keywords" 
            name="_shopper_focus_keywords" 
            value="{{ esc_attr($focus_keywords) }}" 
            class="widefat"
        >
    </div>

    <div class="shopper-seo-field">
        <label for="_shopper_canonical_url">Canonical URL</label>
        <input 
            type="url" 
            id="_shopper_canonical_url" 
            name="_shopper_canonical_url" 
            value="{{ esc_url($canonical_url) }}" 
            class="widefat"
        >
    </div>

    <div class="shopper-seo-field">
        <label for="_shopper_redirect_to">Redirect To</label>
        <input 
            type="url" 
            id="_shopper_redirect_to" 
            name="_shopper_redirect_to" 
            value="{{ esc_url($redirect_to) }}" 
            class="widefat"
        >
    </div>

    <div class="shopper-seo-field">
        <label for="_shopper_redirect_type">Redirect Type</label>
        <select id="_shopper_redirect_type" name="_shopper_redirect_type" class="widefat">
            <option value="301" {{ selected($redirect_type, '301', false) }}>301 - Permanent</option>
            <option value="302" {{ selected($redirect_type, '302', false) }}>302 - Temporary</option>
        </select>
    </div>

    <div class="shopper-seo-field">
        <label for="_shopper_image_alt">Image Alt Tag</label>
        <input 
            type="text" 
            id="_shopper_image_alt" 
            name="_shopper_image_alt" 
            value="{{ esc_attr($image_alt) }}" 
            class="widefat"
        >
    </div>

    <div class="shopper-seo-field">
        <label for="_shopper_source_url">Source URL</label>
        <input 
            type="text" 
            id="_shopper_source_url" 
            name="_shopper_source_url" 
            value="{{ $source_url ?? '' }}" 
            class="widefat"
        >
        <span class="description">The original source URL of the product</span>
    </div>

    <div class="shopper-seo-field">
        <label for="_shopper_display_rank">Display Rank</label>
        <input 
            type="number" 
            id="_shopper_display_rank" 
            name="_shopper_display_rank" 
            value="{{ esc_attr($display_rank) }}" 
            min="1" 
            max="10" 
            class="widefat"
        >
        <span class="description">Set the display rank (1-10). Default is 1.</span>
    </div>
</div>

<style>
.shopper-seo-wrapper {
    padding: 12px;
}

.shopper-seo-field {
    margin-bottom: 16px;
}

.shopper-seo-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.shopper-seo-field input,
.shopper-seo-field textarea,
.shopper-seo-field select {
    width: 100%;
    margin-top: 4px;
}
</style> 