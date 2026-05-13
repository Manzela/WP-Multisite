{{-- Policies Tab --}}
<div id="policies" class="tab-content {{ request()->get('tab') === 'policies' ? 'tab-content-active' : '' }}">
    <div id="policies-container">
        @foreach ($policies as $index => $policy)
            <div class="policy-entry">
                <label>Title</label>
                <input type="text" name="store_settings[policies][{{ $index }}][title]" value="{{ esc_attr($policy['title'] ?? '') }}">

                <label>Body</label>
                <div class="wp-editor-container">
                    @php
                        wp_editor(
                            $policy['body'] ?? '',
                            'policy_editor_' . $index,
                            [
                                'textarea_name' => "store_settings[policies][{$index}][body]",
                                'media_buttons' => true,
                                'tinymce' => true,
                                'quicktags' => true,
                                'editor_height' => 200,
                            ]
                        );
                    @endphp
                </div>

                <button type="button" class="remove-policy">Remove Policy</button>
            </div>
        @endforeach
    </div>
    <button type="button" id="add-policy">Add Policy</button>
</div>