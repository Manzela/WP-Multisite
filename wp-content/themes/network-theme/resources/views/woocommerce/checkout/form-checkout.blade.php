@php
    if (!defined('ABSPATH')) {
        exit;
    }
    do_action('woocommerce_before_checkout_form', $checkout);

    if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
        echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
        return;
    }
@endphp

@php
    $formatted_full_address =
        get_option('woocommerce_store_address') .
        ', ' .
        get_option('woocommerce_store_address_2') .
        ', ' .
        get_option('woocommerce_store_city') .
        ', ' .
        WC()->countries->countries[get_option('woocommerce_default_country')] .
        ', ' .
        get_option('woocommerce_store_postcode');

    $string = preg_replace('/\s*,\s*/', ', ', $formatted_full_address); // Normalize spaces and commas
    $string = preg_replace('/,+/', ',', $string); // Replace multiple commas with a single comma
    $string = rtrim($string, ', '); // Remove trailing comma and space if exists

    $formatted_full_address = $string;
@endphp

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <form name="checkout" method="post" class="checkout woocommerce-checkout" 
          action="{{ esc_url(wc_get_checkout_url()) }}" enctype="multipart/form-data">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Left Column (Contact & Payment) --}}
            <div class="space-y-8">

                {{-- Contact Information --}}
                <div>
                    <div class="bg-white rounded-lg p-6">
                        {{-- Default billing hook disabled; using custom contact fields instead --}}
                        <!-- @php(do_action('woocommerce_checkout_billing')) -->
                        <h2 class="text-lg font-medium text-gray-900 mb-6">
                            {{__('Contact information', 'woocommerce')}}
                        </h2>
                        
                        <div class="grid grid-cols-1 gap-6">
                            {{-- First Name --}}
                            <div>
                                <label for="contact_first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{__('First name', 'woocommerce')}} <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="contact_first_name" 
                                       id="contact_first_name"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                                       required>
                            </div>

                            {{-- Last Name --}}
                            <div>
                                <label for="contact_last_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{__('Last name', 'woocommerce')}} <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="contact_last_name" 
                                       id="contact_last_name"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                                       required>
                            </div>

                            {{-- Phone Number --}}
                            <div>
                                <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{__('Phone Number', 'woocommerce')}} <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" 
                                       name="contact_phone" 
                                       id="contact_phone"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                                       required>
                            </div>

                            {{-- Email Address --}}
                            <div>
                                <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{__('Email', 'woocommerce')}} <span class="text-red-500">*</span>
                                </label>
                                <input type="email" 
                                       name="contact_email" 
                                       id="contact_email"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                                       required>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Shipping Section --}}
                <!-- Shipping section disabled; pickup-only flow -->
                <div class="hidden">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        {{__('Shipping details', 'woocommerce')}}
                    </h2>
                    <p class="text-sm text-gray-500 mb-4">
                        {{__('Select how you would like to receive your order.', 'woocommerce')}}
                    </p>
                    <div class="bg-gray-50 rounded-lg p-6">
                        @php(do_action('woocommerce_checkout_shipping'))
                    </div>
                </div>
            </div>

            {{-- Right Column (Order Summary) --}}
            <div>
                <div class="bg-gray-50 rounded-lg sticky top-4">

                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">
                            {{__('Order summary', 'woocommerce')}}
                        </h2>
                        <div class="space-y-4">
                            @php(do_action('woocommerce_checkout_order_review'))
                        </div>
                    </div>
                    <div class="border-t border-gray-200 p-6">
                        <div class="flex flex-col text-xl text-gray-500 font-semibold mb-4 items-center justify-center">
                            {{__('Reserve your products for pickup from', 'sage')}}-
                            <span class="flex items-center justify-center">{{ $formatted_full_address }}</span>
                        </div>

                        <div class="text-sm text-gray-500 mb-4">
                            {!! sprintf(
                                __('You must accept our %1$s and %2$s to continue with your purchase.', 'woocommerce'),
                                '<a href="/terms-and-conditions" class="text-[var(--color-primary)] hover:underline">'.__('Terms and Conditions', 'woocommerce').'</a>',
                                '<a href="/privacy-policy" class="text-[var(--color-primary)] hover:underline">'.__('Privacy Policy', 'woocommerce').'</a>'
                            ) !!}
                        </div>
                        <button type="submit" 
                                class="w-full custom-bg-color-primary border border-transparent rounded-md 
                                       shadow-sm py-3 px-4 text-base font-medium text-white 
                                       hover:opacity-50 focus:outline-none focus:ring-2 
                                       focus:ring-offset-2 ring-[var(--color-primary)]">
                            {{__('Place order', 'woocommerce')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @php(do_action('woocommerce_checkout_after_customer_details'))
    </form>
</main>

@php(do_action('woocommerce_after_checkout_form', $checkout))

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.querySelector('.lg\\:hidden');
        const orderSummary = toggleButton?.closest('.bg-gray-50')?.querySelector('.space-y-4');
        
        if (toggleButton && orderSummary) {
            toggleButton.addEventListener('click', function() {
                orderSummary.classList.toggle('hidden');
                toggleButton.querySelector('span').classList.toggle('rotate-180');
            });
        }
    });
</script>
