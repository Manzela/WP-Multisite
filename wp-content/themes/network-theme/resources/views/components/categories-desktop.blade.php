<!-- Desktop Categories  -->
<div x-data="{ 
    openCategory: null,
    switchCategory(categoryId) {
        if (this.openCategory === categoryId) {
            this.openCategory = null;
        } else {
            this.openCategory = null;
            setTimeout(() => {
                this.openCategory = categoryId;
            }, 200);
        }
    }
}" class="relative">
    <nav class="hidden lg:flex lg:space-x-8 lg:py-2" aria-label="Global">
        @php
            $categories = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'parent' => 0,
            ]);
        @endphp

        <!-- Category Buttons -->
        @foreach ($categories as $category)
            @php
                $has_children = get_terms([
                    'taxonomy' => 'product_cat',
                    'hide_empty' => false,
                    'parent' => $category->term_id,
                    'number' => 1
                ]);
            @endphp
            <div class="relative">
                @if (!empty($has_children))
                    <button 
                        type="button" 
                        @click="switchCategory({{ $category->term_id }})"
                        class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50 hover:text-gray-900"
                        :class="{ 'bg-gray-100': openCategory === {{ $category->term_id }} }"
                    >
                        {{ $category->name }}
                    </button>
                @else
                    <a 
                        href="{{ get_term_link($category) }}"
                        class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50 hover:text-gray-900"
                    >
                        {{ $category->name }}
                    </a>
                @endif
            </div>
        @endforeach
    </nav>

    <!-- Mega Menus -->
    @foreach ($categories as $category)
        @php
            $subcategories = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'parent' => $category->term_id,
            ]);
        @endphp

        @if (!empty($subcategories))
            <div 
                x-show="openCategory === {{ $category->term_id }}"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-1"
                class="absolute left-0 right-0 z-10 mt-2 bg-white shadow-lg"
                @click.away="openCategory = null"
                style="display: none;"
            >
                <div class="mx-auto max-w-7xl px-8">
                    <!-- Parent Category Link -->
                    <div class="border-b border-gray-200 pb-4 pt-6">
                        <a href="{{ get_term_link($category) }}" class="text-xl font-bold text-gray-900 hover:text-gray-600">
                            {{ __('All') }} {{ $category->name }}
                        </a>
                    </div>
                    <div class="grid grid-cols-4 gap-x-8 gap-y-10 py-16">
                        @foreach ($subcategories as $subcategory)
                            <div class="group relative">
                                <a href="{{ get_term_link($subcategory) }}" class="text-base font-semibold text-gray-900 hover:text-gray-600">
                                    {{ $subcategory->name }}
                                </a>
                                @php
                                    $subsubcategories = get_terms([
                                        'taxonomy' => 'product_cat',
                                        'hide_empty' => false,
                                        'parent' => $subcategory->term_id,
                                    ]);
                                @endphp
                                @if (!empty($subsubcategories))
                                    <ul class="mt-4 space-y-4">
                                        @foreach ($subsubcategories as $subsubcategory)
                                            <li>
                                                <a href="{{ get_term_link($subsubcategory) }}" class="text-sm text-gray-500 hover:text-gray-900">
                                                    {{ $subsubcategory->name }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
