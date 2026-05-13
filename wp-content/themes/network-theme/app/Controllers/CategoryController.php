<?php

namespace App\Controllers;

//https://brand.test.test/category
class CategoryController 
{

    public function index()
    {
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true, // Only return categories that contain products
        ]);

        return view('global.category', compact('categories'));
    }
} 