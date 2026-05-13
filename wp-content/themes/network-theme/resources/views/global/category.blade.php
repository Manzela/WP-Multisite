@extends('layouts.app')

@section('content')
<article class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 rtl">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-[var(--color-primary)] mb-4">
            {!! $store_settings['store_name'] ?? get_bloginfo('name') !!}
        </h1>
        <p class="text-gray-600 text-lg">Choose a category from the list below</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($categories as $category)
            <a href="{{ get_term_link($category) }}" 
               class="group relative bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                @if($category->thumbnail_id)
                    <div class="aspect-w-16 aspect-h-9">
                        {!! wp_get_attachment_image($category->thumbnail_id, 'medium', false, ['class' => 'w-full h-full object-cover']) !!}
                    </div>
                @endif
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 group-hover:text-[var(--color-primary)] transition-colors duration-300">
                        {{ $category->name }}
                    </h2>
                    @if($category->description)
                        <p class="mt-2 text-gray-600 line-clamp-2">
                            {{ $category->description }}
                        </p>
                    @endif
                    <div class="mt-4 flex items-center text-[var(--color-primary)]">
                        <span class="text-sm font-medium">View products</span>
                        <svg class="w-5 h-5 mr-2 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</article>
@endsection