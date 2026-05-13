<?php

use App\Controllers\EzImportController;
use App\Controllers\EzExportController;
use App\Controllers\EzJsonValidatorController;
use App\Controllers\StoreInfoController;

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/products', [
        'methods' => 'GET',
        'callback' => [app(EzExportController::class), 'getProducts'],
        'permission_callback' => function() {
            return current_user_can('edit_products');
        },
    ]);

    register_rest_route('custom/v1', '/products/update', [
        'methods' => ['PUT', 'POST'],
        'callback' => [app(EzImportController::class), 'updateProducts'],
        'permission_callback' => function() {
            return current_user_can('edit_products');
        },
    ]);

    register_rest_route('custom/v1', '/validate', [
        'methods' => ['POST'],
        'callback' => [app(EzJsonValidatorController::class), 'validateJson'],
        'permission_callback' => function() {
            return current_user_can('edit_products');
        },
    ]);

    register_rest_route('custom/v1', '/stores', [
        'methods' => 'GET',
        'callback' => [app(StoreInfoController::class), 'index'],
        'permission_callback' => function() {
            return current_user_can('edit_products');
        },
    ]);
});
