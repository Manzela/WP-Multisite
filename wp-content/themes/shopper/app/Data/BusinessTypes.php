<?php

/**
 * BusinessTypes.php — Single Source of Truth
 *
 * Maps internal slugs (which match Google Places API type identifiers)
 * to their Schema.org type equivalent and a human-readable label.
 *
 * Used by:
 * - GoogleBusinessServiceProvider::updateStoreSettings() — to populate store_info[business_type][]
 * - SchemaServiceProvider::buildLocalBusiness()           — to map stored types → Schema.org @type
 * - seo.blade.php admin dropdown                         — to list available business types
 *
 * Structure: 'google_api_type_slug' => ['schema_type', 'Human Label']
 *
 * To add a new type: add a line below following the same pattern.
 * The slug must match what Google Places API returns in primaryType / types[].
 *
 * @see https://developers.google.com/maps/documentation/places/web-service/place-types
 * @see https://schema.org/LocalBusiness (and subtypes)
 */

return [
    // Retail
    'pet_store' => ['PetStore', 'Pet Store'],
    'shoe_store' => ['ShoeStore', 'Shoe Store'],
    'clothing_store' => ['ClothingStore', 'Clothing Store'],
    'electronics_store' => ['ElectronicsStore', 'Electronics Store'],
    'furniture_store' => ['FurnitureStore', 'Furniture Store'],
    'jewelry_store' => ['JewelryStore', 'Jewelry Store'],
    'hardware_store' => ['HardwareStore', 'Hardware Store'],
    'sporting_goods_store' => ['SportingGoodsStore', 'Sporting Goods Store'],
    'convenience_store' => ['ConvenienceStore', 'Convenience Store'],
    'department_store' => ['DepartmentStore', 'Department Store'],
    'grocery_or_supermarket' => ['GroceryStore', 'Grocery / Supermarket'],
    'home_goods_store' => ['HomeGoodsStore', 'Home Goods Store'],
    'book_store' => ['BookStore', 'Book Store'],
    'bicycle_store' => ['BikeStore', 'Bicycle Store'],
    'liquor_store' => ['LiquorStore', 'Liquor Store'],

    // Services
    'florist' => ['Florist', 'Florist'],
    'pharmacy' => ['Pharmacy', 'Pharmacy'],
    'car_dealer' => ['AutoDealer', 'Auto Dealer'],
    'car_repair' => ['AutoRepair', 'Auto Repair'],
    'beauty_salon' => ['BeautySalon', 'Beauty Salon'],
    'hair_care' => ['HairSalon', 'Hair Salon'],
    'laundry' => ['DryCleaningOrLaundry', 'Dry Cleaning / Laundry'],
    'locksmith' => ['Locksmith', 'Locksmith'],
    'gas_station' => ['GasStation', 'Gas Station'],
    'travel_agency' => ['TravelAgency', 'Travel Agency'],
    'veterinary_care' => ['VeterinaryCare', 'Veterinary Care'],

    // Food & Drink
    'bakery' => ['Bakery', 'Bakery'],
    'cafe' => ['CafeOrCoffeeShop', 'Café / Coffee Shop'],
    'restaurant' => ['Restaurant', 'Restaurant'],
    'bar' => ['BarOrPub', 'Bar / Pub'],

    // Health & Fitness
    'gym' => ['HealthClub', 'Gym / Health Club'],
    'movie_theater' => ['MovieTheater', 'Movie Theater'],

    // Broad Categories (fallback matches)
    'food' => ['FoodEstablishment', 'Food Establishment'],
    'health' => ['MedicalBusiness', 'Medical / Health Business'],
    'store' => ['Store', 'General Store'],
    'shopping_mall' => ['ShoppingCenter', 'Shopping Mall / Center'],
    'supermarket' => ['GroceryStore', 'Supermarket'],
];
