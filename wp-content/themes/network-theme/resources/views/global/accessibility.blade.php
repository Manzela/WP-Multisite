@extends('layouts.app')

@section('title')
    {{ __('Accessibility Policy', 'sage') }} - {!! get_bloginfo('name') !!}
@endsection

@section('content')
    <div class="page-header border-b border-gray-200">
        <h1 class="my-4 text-3xl font-bold leading-none tracking-tight text-gray-900">
            {{ __('Accessibility policy', 'sage') }}
        </h1>
    </div>

    @php
        $current_locale = get_locale();
        $is_hebrew = $current_locale === 'he_IL';
    @endphp

    @if($is_hebrew)
        {{-- Hebrew content --}}
        <div class="space-y-4 mt-4">
            <h2 class="text-xl font-bold">[content in jurisdictional language]</h2>
            <ol class="list-decimal mr-8 space-y-2">
                <li>[content in jurisdictional language]2022.</li>
                <li>[content in jurisdictional language][COMPANY NAME] LTD. [content in jurisdictional language]                    [content in jurisdictional language]</li>
            </ol>

            <h2 class="text-xl font-bold">[content in jurisdictional language]</h2>
            <ol class="list-decimal mr-8 space-y-2">
                <li>[content in jurisdictional language]One Click Accessibility ([content in jurisdictional language]"One Click
                    Accessibility")</li>
                <li>One Click Accessibility [content in jurisdictional language]                    [content in jurisdictional language]ADA).</li>
            </ol>

            <p class="">[content in jurisdictional language]10/6/2024</p>
            <ol class="list-decimal mr-8 space-y-2 ">
                <li>[content in jurisdictional language]/[content in jurisdictional language]                    [content in jurisdictional language]                    [content in jurisdictional language]</li>
                <li>[content in jurisdictional language]                    [content in jurisdictional language]</li>
                <li>[content in jurisdictional language]                    [content in jurisdictional language]                    [content in jurisdictional language]"[content in jurisdictional language]"[content in jurisdictional language]").</li>
                <li>[content in jurisdictional language]</li>
            </ol>
            <p class="">[content in jurisdictional language]</p>
            <ul class="list-disc mr-8 space-y-2 ">
                <li>[content in jurisdictional language]/[content in jurisdictional language]</li>
                <li>[content in jurisdictional language]</li>
                <li>[content in jurisdictional language]</li>
                <li>[content in jurisdictional language]</li>
                <li>[content in jurisdictional language]</li>
            </ul>
            <div><br></div>
            <ol start="5" class="list-decimal mr-8 space-y-2 ">
                <li>[content in jurisdictional language]<br style="letter-spacing: -0.1px;">
                    <ul class="list-disc mr-8 mt-2 space-y-1 ">
                        <li>[content in jurisdictional language]IP [content in jurisdictional language]</li>
                        <li>[content in jurisdictional language]</li>
                        <li>[content in jurisdictional language]</li>
                    </ul>
                </li>
            </ol>
            <ol start="6" class="list-decimal mr-8 space-y-2 ">
                <li>[content in jurisdictional language]Cookies), [content in jurisdictional language]                    [content in jurisdictional language]</li>
                <li>[content in jurisdictional language]Google).</li>
                <li>[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]</li>
            </ol>
            <p class="">[content in jurisdictional language]AI [content in jurisdictional language]                [content in jurisdictional language]                [content in jurisdictional language]IP), [content in jurisdictional language]                [content in jurisdictional language]</p>
            <p class="">[content in jurisdictional language]                [content in jurisdictional language]</p>
            <ol start="9" class="list-decimal mr-8 space-y-2 ">
                <li>[content in jurisdictional language]                    [content in jurisdictional language]/
                    [content in jurisdictional language]/ [content in jurisdictional language]</li>
                <li>[content in jurisdictional language]                    [content in jurisdictional language]</li>
            </ol>
            <p class="">[content in jurisdictional language]</p>
            <ul class="list-disc mr-8 space-y-2 ">
                <li>[content in jurisdictional language]</li>
                <li>[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]                    [content in jurisdictional language]</li>
                <li>[content in jurisdictional language]â€[content in jurisdictional language]                    [content in jurisdictional language]                    [content in jurisdictional language]</li>
            </ul>
            <div><br></div>
            <ol start="11" class="list-decimal mr-8 space-y-2 ">
                <li>[content in jurisdictional language]                    [content in jurisdictional language]</li>
                <li>[content in jurisdictional language]                    [content in jurisdictional language]</li>
                <li>[content in jurisdictional language]                    [content in jurisdictional language]                    [content in jurisdictional language]</li>
                <li>[content in jurisdictional language]                    [content in jurisdictional language]                    [content in jurisdictional language]                    [content in jurisdictional language]</li>
            </ol>
            <p></p>

            <h2 class="text-xl font-bold">[content in jurisdictional language]</h2>
            <ol class="list-decimal mr-8 space-y-2">
                <li>[content in jurisdictional language]</li>
                <li>[content in jurisdictional language]                    [content in jurisdictional language]"[content in jurisdictional language]</li>
                <li>[content in jurisdictional language]                    <ul class="list-disc mr-8 mt-2 space-y-1">
                        <li>[content in jurisdictional language]0528870270</li>
                        <li>[content in jurisdictional language]info@example-network.com</li>
                    </ul>
                </li>
            </ol>
        </div>

    @else
        {{-- English content --}}
        {{-- TODO --}}
    @endif
@endsection