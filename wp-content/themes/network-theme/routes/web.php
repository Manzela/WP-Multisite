<?php

use Illuminate\Support\Facades\Route;
use App\Providers\DeliveryRulesServiceProvider;
use App\Controllers\CategoryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
|
*/

Route::view('/privacy-policy/', 'global.privacy')->name('privacy-policy');
Route::view('/terms-and-conditions/', 'global.terms')->name('terms-and-conditions');
Route::view('/accessibility-policy/', 'global.accessibility')->name('accessibility-policy');
Route::view('/return-policy/', 'global.return')->name('return-policy');

Route::get('/about/', function () {
    $store_settings = get_option('store_settings');
    $enable_delivery = DeliveryRulesServiceProvider::getEnableDelivery();
    
    // Process social links the same way as SchemaServiceProvider
    $socialLinks = array_map(function($social) {
        return $social['url'] ?? '';
    }, $store_settings['social'] ?? []);
    
    // Filter out empty social links
    $socialLinks = array_filter($socialLinks);
    
    return view('global.about', [
        'store_settings' => $store_settings,
        'enable_delivery' => $enable_delivery,
        'delivery_rules' => $enable_delivery ? DeliveryRulesServiceProvider::getDefaultMessage() : null,
        'socialLinks' => array_values($socialLinks)
    ]);
})->name('about');

// Category routes
Route::get('/category', [CategoryController::class, 'index']);
