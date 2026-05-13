<!-- Sidebar-Categories Slider -->
<div x-show="openSidebarCategories" class="fixed inset-0 bg-gray-800 bg-opacity-50 z-50" @click="openSidebarCategories = false" x-cloak>
    <div class="absolute {{ is_rtl() ? 'right-0' : 'left-0' }} top-0 w-64 bg-white h-full shadow-lg transform transition-transform duration-300"
        x-show="openSidebarCategories" @click.stop x-transition:enter="transform transition-transform duration-300"
        x-transition:enter-start="{{ is_rtl() ? 'translate-x-full' : '-translate-x-full' }}" 
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition-transform duration-300" 
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="{{ is_rtl() ? 'translate-x-full' : '-translate-x-full' }}">
        @include('sections.sidebar-categories')
    </div>
</div>
