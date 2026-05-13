<div class="policies-accordion space-y-6" x-data="{ activeTab: 0 }">
    @forelse ($policies as $index => $policy)
        <div id="policy-{{ $index }}" 
             class="policy-accordion group rounded-xl overflow-hidden hover:ring-2 hover:ring-[var(--color-primary)] transition-all duration-200">
            
            <button @click="activeTab = activeTab === {{ $index }} ? null : {{ $index }}"
                    class="accordion-header w-full flex justify-between items-center p-5 text-left transition-all duration-200
                           bg-white hover:bg-gray-50 
                           group-hover:text-[var(--color-primary)]"
                    :class="{ 'shadow-sm': activeTab !== {{ $index }} }">
                <span class="text-lg font-medium flex items-center space-x-4">
                    <span class="ml-4 flex-shrink-0 w-8 h-8 rounded-full bg-[var(--color-primary)] bg-opacity-10 flex items-center justify-center">
                        <span class="text-[var(--color-secondary)]">{{ $index + 1 }}</span>
                    </span>
                    <span >{{ esc_html($policy['title'] ?? __('Untitled Policy', 'sage')) }}</span>
                </span>
                
                <span class="accordion-icon ml-4 flex-shrink-0 w-6 h-6 transition-transform duration-300"
                      :class="{ 'rotate-180': activeTab === {{ $index }} }">
                    @svg('arrowclose', 'w-6 h-6 text-gray-400 group-hover:text-[var(--color-primary)] transition-colors duration-200')
                </span>
            </button>

            <div x-show="activeTab === {{ $index }}"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 class="accordion-content border-t">
                <div class="p-5 bg-white text-gray-600 leading-relaxed">
                    {!! wp_kses_post($policy['body'] ?? '') !!}
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" 
                      stroke-linejoin="round" 
                      stroke-width="2" 
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No policies', 'sage') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('No policies created yet.', 'sage') }}</p>
        </div>
    @endforelse
</div>