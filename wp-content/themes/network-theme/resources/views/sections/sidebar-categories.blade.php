<?php
if (!function_exists('render_sub_categories2')) {
    function render_sub_categories2($parent_category)
    {
        // Parent Category Link
        echo '<a href="' . get_term_link($parent_category) . '" class="block rounded-md px-3 py-2 text-base font-medium text-[--color-primary] hover:bg-gray-50">';
        echo __('All') . ' ' . $parent_category->name;
        echo '</a>';

        $uncategorized_id = get_option('default_product_cat');
        $sub_categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => $parent_category->term_id,
            'exclude' => [$uncategorized_id],
        ]);

        foreach ($sub_categories as $category) {
            $has_children = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => $category->term_id, 'number' => 1]);

            if (!empty($has_children)) {
                $openState = 'open' . $category->term_id;
                echo '<div x-data="{ ' . $openState . ': false }">';
                echo '<div class="flex items-center justify-between">';
                echo '<button @click="' . $openState . ' = !' . $openState . '" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-base font-medium text-gray-900 hover:bg-gray-50 hover:text-gray-900">';
                echo $category->name;
                echo '<span class="transform transition-transform duration-200" :class="' . $openState . ' ? \'rotate-90\' : \'rtl:rotate-180\'">';
                echo '<svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>'; // Inline SVG to avoid blade directive issues inside php
                echo '</span></button></div>';

                $wClass = is_rtl() ? 'mr-4' : 'ml-4';
                echo '<div x-show="' . $openState . '" class="' . $wClass . '">';
                render_sub_categories2($category);
                echo '</div></div>';
            } else {
                echo '<a href="' . get_term_link($category) . '" class="block rounded-md px-3 py-2 text-base font-medium text-gray-900 hover:bg-gray-50 hover:text-gray-900">' . $category->name . '</a>';
            }
        }
    }
}
?>
{{-- same code as < x-categories-mobile> but without the wrapping nav (animation) --}}
    {{-- also using render_sub_categories2() which is a duplication (cannot access the other function and cannot
    redefine it with the same name) --}}
    <div
        class="sidebar-categories bg-white p-6 rounded-lg shadow-md overflow-y-auto h-full sticky top-0 {{ is_rtl() ? 'text-right' : 'text-left' }}">
        <h2 class="text-2xl font-bold mt-10 mb-4 text-gray-800">{{ __('Categories', 'woocommerce') }}</h2>
        <div class="space-y-1 px-2 pb-3 pt-2">
            <?php
$uncategorized_id = get_option('default_product_cat');
$categories = get_terms([
    'taxonomy' => 'product_cat',
    'hide_empty' => true,
    'parent' => 0,
    'exclude' => [$uncategorized_id], // Exclude Uncategorized category
]);

foreach ($categories as $category) {
    $has_children = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => $category->term_id, 'number' => 1]);

    if (!empty($has_children)) { ?>

            <div x-data="{ open<?php        echo $category->term_id ?>: false }">
                <div class="flex items-center justify-between">
                    <button
                        @click="open<?php        echo $category->term_id ?> = !open<?php        echo $category->term_id ?>"
                        class="flex w-full items-center justify-between rounded-md px-3 py-2 text-base font-medium text-gray-900 hover:bg-gray-50 hover:text-gray-900">
                        {{ $category->name }}
                        <span class="transform transition-transform duration-200"
                            :class="open<?php        echo $category->term_id ?> ? 'rotate-90' : 'rtl:rotate-180'">
                            @svg('catarrow', 'w-4 h-4 text-gray-500')
                        </span>
                    </button>
                </div>

                <div x-show="open<?php        echo $category->term_id ?>" class="{{ is_rtl() ? 'mr-4' : 'ml-4' }}">
                    <?php        render_sub_categories2($category); ?>
                </div>
                <?php
    } else { ?>
                <a href="{{ get_term_link($category) }}"
                    class="block rounded-md px-3 py-2 text-base font-medium text-gray-900 hover:bg-gray-50 hover:text-gray-900">
                    {{ $category->name }}
                </a>
                <?php
    }
} ?>
            </div>
        </div>

    </div>
    </div>