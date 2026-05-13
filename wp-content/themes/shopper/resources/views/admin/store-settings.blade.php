<div class="wrap">
    <h1>Store Settings</h1>
    
    <nav class="nav-tab-wrapper">
        <a href="#general" class="nav-tab {{ !request()->has('tab') || request()->get('tab') === 'general' ? 'nav-tab-active' : '' }}">
            General
        </a>
        <a href="#store-info" class="nav-tab {{ request()->get('tab') === 'store-info' ? 'nav-tab-active' : '' }}">
            Store Info
        </a>
        <a href="#google-business" class="nav-tab {{ request()->get('tab') === 'google-business' ? 'nav-tab-active' : '' }}">
            Google Business
        </a>
        <a href="#delivery" class="nav-tab {{ request()->get('tab') === 'delivery' ? 'nav-tab-active' : '' }}">
            Delivery
        </a>
        <a href="#policies" class="nav-tab {{ request()->get('tab') === 'policies' ? 'nav-tab-active' : '' }}">
            Policies
        </a>
        <a href="#social" class="nav-tab {{ request()->get('tab') === 'social' ? 'nav-tab-active' : '' }}">
            Social
        </a>
        <a href="#popup-message" class="nav-tab {{ request()->get('tab') === 'popup-message' ? 'nav-tab-active' : '' }}">
            Popup Message
        </a>
        <a href="#seo" class="nav-tab {{ request()->get('tab') === 'seo' ? 'nav-tab-active' : '' }}">
            SEO
        </a>
        <a href="#images-preview" class="nav-tab {{ request()->get('tab') === 'images-preview' ? 'nav-tab-active' : '' }}">
            Images Preview
        </a>
    </nav>

    <form method="post" action="options.php" id="store-settings-form">
        @php(settings_fields('store_settings'))
        
        <!-- Tab contents -->
        @include('admin.tabs.general')
        @include('admin.tabs.store-info')
        @include('admin.tabs.google-business')
        @include('admin.tabs.delivery')
        @include('admin.tabs.policies')
        @include('admin.tabs.social')
        @include('admin.tabs.popup-message')
        @include('admin.tabs.seo')
        @include('admin.tabs.images-preview')

        @php(submit_button())
    </form>
</div>
