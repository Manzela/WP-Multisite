@extends('layouts.app')

@section('title')
  {{ __('Terms and Conditions', 'sage') }} - {!! get_bloginfo('name') !!}
@endsection

@section('content')
    <!-- do not use @include('partials.page-header') here because the translation is not woocommerce -->
    <!-- set up a custom page header -->
    <div class="page-header border-b border-gray-200">
        <h1 class="my-4 text-3xl font-bold leading-none tracking-tight text-gray-900">
            {{ __('Terms and conditions - Customers', 'sage') }}
        </h1>
    </div>

    @php
    $current_locale = get_locale();
    $is_hebrew = $current_locale === 'he_IL';
    @endphp

    @if($is_hebrew)
    {{-- Hebrew content --}}
    <div class="space-y-4 mt-4">
        <p class=""><span>[content in jurisdictional language]10/6/2024</span></p>
        <p class="">[content in jurisdictional language]<strong>Network</strong>...</p>
        <ol class="list-decimal mr-8 space-y-2 ">
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]"[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]18 [content in jurisdictional language]18, [content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
        </ol>
        <p class="font-bold ">[content in jurisdictional language]</p>
        <ol class="list-decimal mr-8 space-y-2 ">
            <li>[content in jurisdictional language]"[content in jurisdictional language]") [content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]/ [content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]                <ul class="list-disc mr-8 mt-2 space-y-1">
                    <li>[content in jurisdictional language]/[content in jurisdictional language]</li>
                    <li>[content in jurisdictional language]/[content in jurisdictional language]</li>
                    <li>[content in jurisdictional language]</li>
                    <li>[content in jurisdictional language]</li>
                    <li>[content in jurisdictional language]</li>
                    <li>[content in jurisdictional language]</li>
                </ul>
            </li>
        </ol>
        <p class="font-bold ">[content in jurisdictional language]</p>
        <ol class="list-decimal mr-8 space-y-2 ">
            <li>[content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]"[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/ [content in jurisdictional language]</li>
        </ol>
        <p class="font-bold ">[content in jurisdictional language]</p>
        <ol class="list-decimal mr-8 space-y-2 ">
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]"[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]7 [content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]</li>
        </ol>
        <p class="font-bold ">[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]"[content in jurisdictional language]<br>[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]</p>
        <p class="font-bold ">[content in jurisdictional language]</p>
        <ol class="list-decimal mr-8 space-y-2 ">
            <li>[content in jurisdictional language]/ [content in jurisdictional language]/ [content in jurisdictional language]/[content in jurisdictional language]"[content in jurisdictional language]" [content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]"[content in jurisdictional language]1981 [content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]                <ul class="list-disc mr-8 mt-2 space-y-1">
                    <li>[content in jurisdictional language]</li>
                    <li>[content in jurisdictional language]</li>
                    <li>[content in jurisdictional language]</li>
                    <li>[content in jurisdictional language]</li>
                    <li>[content in jurisdictional language]</li>
                </ul>
            </li>
        </ol>
        <p class="font-bold ">[content in jurisdictional language]</p>
        <ol class="list-decimal mr-8 space-y-2 ">
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]').</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]"[content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]' [content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]â€[content in jurisdictional language]"[content in jurisdictional language]"[content in jurisdictional language]</li>
        </ol>
        <p class="font-bold ">[content in jurisdictional language]</p>
        <ol class="list-decimal mr-8 space-y-2 ">
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]"[content in jurisdictional language]" [content in jurisdictional language]"[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]</li>
        </ol>
        <p class="font-bold ">[content in jurisdictional language]</p>
        <ol class="list-decimal mr-8 space-y-2 ">
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]"[content in jurisdictional language]") [content in jurisdictional language]"AS IS") [content in jurisdictional language]"Status Bar") [content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
        </ol>
        <p class="font-bold ">[content in jurisdictional language]</p>
        <ol class="list-decimal mr-8 space-y-2 ">
            <li>[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]"[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
        </ol>
        <p class="font-bold ">[content in jurisdictional language]</p>
        <ol class="list-decimal mr-8 space-y-2 ">
            <li>[content in jurisdictional language]"[content in jurisdictional language]"). [content in jurisdictional language]</li>
            <li>[content in jurisdictional language]"[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]Crawlers Robots [content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]</li>
        </ol>
        <p class="font-bold ">[content in jurisdictional language]</p>
        <ol class="list-decimal mr-8 space-y-2 ">
            <li>[content in jurisdictional language]</li>
            <li>[content in jurisdictional language]/[content in jurisdictional language]</li>
        </ol>
        <p class="font-bold ">[content in jurisdictional language]</p>
        <p class="">[content in jurisdictional language]<a href="mailto:info@example-network.com">info@example-network.com</a></p>
        <p class=""></p>
    </div>

    @else
    {{-- English content --}}
    {{-- English locale content not yet implemented --}}
    @endif

@endsection