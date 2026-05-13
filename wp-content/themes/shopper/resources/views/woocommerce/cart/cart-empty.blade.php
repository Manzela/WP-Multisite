@extends('layouts.app')

@section('content')
    <div class="flex flex-col items-center justify-center">
        <h2 class="text-2xl font-bold text-center my-8">{{ __('Your cart is currently empty!', 'woocommerce') }}</h2>
        <a href="{{ home_url('/') }}" class="text-blue-500">{{ __('Return to shop', 'woocommerce') }}</a>
    </div>
@endsection