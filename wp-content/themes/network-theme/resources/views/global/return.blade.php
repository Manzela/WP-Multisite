@extends('layouts.app')

@section('title')
  {{ __('Return & Cancellation Policy', 'sage') }} - {!! get_bloginfo('name') !!}
@endsection

@section('head')
  <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
    <!-- Custom page header (partials.page-header is not available in this context) -->
    <div class="page-header border-b border-gray-200">
        <h1 class="my-4 text-3xl font-bold leading-none tracking-tight text-gray-900">
            {{ __('Returns & Cancellations Policy', 'woocommerce') }}
        </h1>
    </div>

    @php
    $current_locale = get_locale();
    // Conditional-locale render pattern: replace `he_IL` with whichever locale
    // your jurisdiction-specific legal text is written in.
    $is_jurisdictional_locale = $current_locale === 'he_IL';
    @endphp

    @if($is_jurisdictional_locale)
    {{-- Jurisdiction-specific legal content (placeholder).
         In production, this block carries the local-language legal text
         required by your jurisdiction (return windows, perishable-goods
         exclusions, customer-rights notices, etc.). The original content
         has been redacted in this public blueprint.

         Replace with your own jurisdiction's required clauses. --}}
    <div class="space-y-4 mt-4">
        <p class="text-sm text-gray-500">Last updated: [DATE]</p>
        <h2 class="text-xl font-bold mt-6">{{ __('Delivery & Shipping', 'network-theme') }}</h2>
        <ul class="list-decimal ml-8 space-y-2">
            <li>[Place jurisdiction-specific delivery & shipping clauses here]</li>
        </ul>

        <h2 class="text-xl font-bold mt-6">{{ __('Order Cancellation & Returns', 'network-theme') }}</h2>
        <p>[Place jurisdiction-specific cancellation policy here, citing the relevant local consumer-protection law.]</p>

        <h3 class="text-lg font-bold mt-4">{{ __('Non-cancellable orders', 'network-theme') }}</h3>
        <ul class="list-disc ml-8 space-y-2 mt-2">
            <li>[Perishable goods (e.g., flowers, dairy)]</li>
            <li>[Custom-made / made-to-order goods]</li>
            <li>[Recordable / copyable goods opened by the customer]</li>
            <li>[Goods that local law prohibits returning]</li>
            <li>[Opened goods not in original packaging or damaged at the customer]</li>
        </ul>

        <h2 class="text-xl font-bold mt-6">{{ __('Returns Policy', 'network-theme') }}</h2>
        <ul class="list-disc ml-8 space-y-2 mt-2">
            <li>[Returns are handled per the merchant's policy.]</li>
            <li>[Items must be returned in original packaging and condition.]</li>
            <li>[Return shipping cost: customer responsibility unless otherwise specified.]</li>
            <li>[Refunds processed per the merchant's refund policy.]</li>
        </ul>

        <h2 class="text-xl font-bold mt-6">{{ __('Validity', 'network-theme') }}</h2>
        <p>[Validity & change-of-policy notice — replace with your standard text.]</p>

        <p>{{ __('Return window:', 'network-theme') }} 14 days</p>
    </div>

    @else
    {{-- Default English content --}}
    <p>{{ __('Return window:', 'network-theme') }} 14 days</p>
    @endif
@endsection
