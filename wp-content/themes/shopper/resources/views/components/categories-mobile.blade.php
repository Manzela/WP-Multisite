<nav class="w-full fixed right-0 h-full bg-white z-40" aria-label="Global" id="mobile-menu" x-show="mobileMenuOpen" x-cloak
    x-transition:enter="transition ease-out duration-300" 
    x-transition:enter-start="transform translate-x-[100%] opacity-0" 
    x-transition:enter-end="transform translate-x-0 opacity-100" 
    x-transition:leave="transition ease-in duration-300" 
    x-transition:leave-start="transform translate-x-0 opacity-100" 
    x-transition:leave-end="transform translate-x-[100%] opacity-0"
>
    <div class="space-y-1 px-2 pb-3 pt-2 {{ is_rtl()? 'text-right' : 'text-left' }}">
        <?php
        $uncategorized_id = get_option('default_product_cat');
        $categories = get_terms([
            'taxonomy' => 'product_cat', 
            'hide_empty' => true, 
            'parent' => 0,
            'exclude' => [$uncategorized_id], // Exclude Uncategorized category
        ]);

        foreach($categories as $category) {
            $has_children = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => $category->term_id, 'number' => 1 ]);

            if(!empty($has_children) ) { ?>

                <div x-data="{ open<?php echo $category->term_id ?>: false }">
                    <div class="flex items-center justify-between">
                        <button @click="open<?php echo $category->term_id ?> = !open<?php echo $category->term_id ?>" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-base font-medium text-gray-900 hover:bg-gray-50 hover:text-gray-900">
                            {{ $category->name }}
                            <span class="transform transition-transform duration-200" :class="open<?php echo $category->term_id ?> ? 'rotate-90' : 'rtl:rotate-180'">
                                @svg('catarrow', 'w-4 h-4 text-gray-500')
                            </span>
                        </button>
                    </div>

                    <div x-show="open<?php echo $category->term_id ?>" class="{{ is_rtl()? 'mr-4' : 'ml-4' }}">
                        <?php render_sub_categories($category); ?>
                    </div>
            <?php
            } else { ?>
                <a href="{{ get_term_link($category) }}" class="block rounded-md px-3 py-2 text-base font-medium text-gray-900 hover:bg-gray-50 hover:text-gray-900">
                    {{ $category->name }}
                </a>
            <?php
            }
        } ?>
    </div>
</nav>


<?php 

function render_sub_categories($parent_category) { ?>
    <!-- Parent Category Link -->
    <a href="{{ get_term_link($parent_category) }}" class="block rounded-md px-3 py-2 text-base font-medium text-[--color-primary] hover:bg-gray-50">
        {{ __('All') }} {{ $parent_category->name }}
    </a>
    <?php
    $uncategorized_id = get_option('default_product_cat');
    $sub_categories = get_terms([
        'taxonomy' => 'product_cat', 
        'hide_empty' => true, 
        'parent' => $parent_category->term_id,
        'exclude' => [$uncategorized_id], // Exclude Uncategorized category
    ]);
    foreach($sub_categories as $category) {
        $has_children = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => $category->term_id, 'number' => 1 ]);

        if(!empty($has_children)) { ?>
            <div x-data="{ open<?php echo $category->term_id ?>: false }">
                <div class="flex items-center justify-between">
                    <button @click="open<?php echo $category->term_id ?> = !open<?php echo $category->term_id ?>" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-base font-medium text-gray-900 hover:bg-gray-50 hover:text-gray-900">
                        {{ $category->name }}
                        <span class="transform transition-transform duration-200" :class="open<?php echo $category->term_id ?> ? 'rotate-90' : 'rtl:rotate-180'">
                            @svg('catarrow', 'w-4 h-4 text-gray-500')
                        </span>
                    </button>
                </div>

                <div x-show="open<?php echo $category->term_id ?>" class="{{ is_rtl()? 'mr-4' : 'ml-4' }}">
                    <?php render_sub_categories($category); ?>
                </div>
        <?php
        } else { ?>
            <a href="{{ get_term_link($category) }}" class="block rounded-md px-3 py-2 text-base font-medium text-gray-900 hover:bg-gray-50 hover:text-gray-900">
                {{ $category->name }}
            </a>
        <?php
        }
    }
}