# Shopper - WordPress Multisite E-commerce Theme

[![WordPress](https://img.shields.io/badge/WordPress-6.0+-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0+-green.svg)](https://woocommerce.com/)
[![Sage](https://img.shields.io/badge/Sage-10.0+-orange.svg)](https://roots.io/sage/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.0+-38B2AC.svg)](https://tailwindcss.com/)
[![RTL Support](https://img.shields.io/badge/RTL-Hebrew%20%7C%20Arabic-red.svg)](https://en.wikipedia.org/wiki/Right-to-left)

A modern, RTL-focused WordPress Multisite e-commerce theme built with Sage 10, Blade templating, Tailwind CSS, and WooCommerce. Designed specifically for Hebrew and Arabic markets with comprehensive product import/export functionality.

## 📋 Table of Contents

- [Changelog](#changelog)
- [Features](#features)
- [Quick Start](#quick-start)
- [Architecture](#architecture)
- [Development](#development)
- [API Documentation](#api-documentation)
- [Deployment](#deployment)
- [Support](#support)

## 📝 Changelog

- **18-09-2025** Enhanced EzImport attribute handling with custom slug support
  - Added support for custom attribute taxonomy slugs via `slug` field in attribute data
  - Implemented custom term slug support for attribute options using object format or `option_slugs` array
  - Enhanced attribute import to handle both string and object formats for options
  - Added support for hex color codes and other custom slug formats (e.g., "ff0000" for red)
  - Maintained backward compatibility with existing string-based option imports
  - Files: `app/Controllers/EzImportController.php`
- **10-08-2025** Fixed product display rank field validation and limits
  - Removed artificial 1-10 limit on display rank field to allow unlimited ranking
  - Updated rank field validation to only enforce minimum value of 1
  - Fixed issue where rank values were being reset due to validation constraints
  - Files: `app/Providers/ProductSeoServiceProvider.php`, `resources/views/partials/admin/product-seo.blade.php`
- **06-08-2025** Added Google Analytics 4 integration with network-wide support
  - Implemented dynamic GA4 property ID management through Network Store Settings
  - Created headscripts partial system for global script inclusion
  - Added automatic GA4 code injection on all pages across the network
  - Supports fallback from network settings to site-specific settings
  - Files: `resources/views/partials/headscripts/google-analytics.blade.php`, `resources/views/layouts/app.blade.php`, `resources/views/admin/network-settings/network-general.blade.php`, `app/Providers/NetworkFieldsServiceProvider.php`
- **22-07-2025** Enhanced EzImport source_url handling for Hebrew/Arabic URLs
  - Fixed product identification by source_url to handle both encoded and decoded Hebrew/Arabic characters
  - Updated meta query logic to search for URL in multiple formats (original, encoded, decoded)
  - Improved product update/creation logic to prevent duplicates when URLs contain Hebrew characters
  - Applied fix to both product creation and deletion functions
  - File: `app/Controllers/EzImportController.php`
  - Fixed Title in "Global pages"
- **21-07-2025** New compnent called product-full can be called from single product page or anyother page with product variable
  - <x-product-full :product="$product" /> this will show
  - descrption usning  $product->get_description()
  - product information using do_action('woocommerce_product_additional_information', $product)
    - File: app/product-custom-tabs.php

### [Unreleased]
- Enhanced Hebrew/Arabic URL support in source_url fields
- Improved product import/export with proper character encoding
- Updated admin interface to display Hebrew URLs correctly
- Fixed URL encoding issues across all template files
- **Added collapsible store about section** with dynamic color theming
  - File: `resources/views/sections/store-about-section.blade.php`
- **Implemented responsive store information display** with minimal default view
  - File: `resources/views/sections/store-about-section.blade.php`
- **Enhanced user experience** with progressive content disclosure
  - File: `resources/views/sections/store-about-section.blade.php`
- **Added dynamic store color integration** for buttons and UI elements
  - File: `resources/views/sections/store-about-section.blade.php`
- **Improved mobile responsiveness** with full-width content layout
  - File: `resources/views/sections/store-about-section.blade.php`
- **Added text-only toggle button** with store brand colors
  - File: `resources/views/sections/store-about-section.blade.php`
- **Implemented content truncation** for better initial page load performance
  - File: `resources/views/sections/store-about-section.blade.php`
- **Replaced Google Maps with OpenStreetMap** for location display (no API key required)
  - File: `resources/views/sections/store-about-section.blade.php`
- **Enhanced product tab management** with conditional description tab removal
  - File: `resources/views/components/custom-tabs.blade.php`

### [1.0.0] - 2024-01-15
- Initial release
- WordPress Multisite support
- WooCommerce integration
- RTL (Hebrew/Arabic) support
- Product import/export system
- Custom SEO management
- Dynamic store settings

---

## ✨ Features

### 🌐 Multilingual & RTL Support
- **Hebrew & Arabic Support**: Full RTL language support with proper text direction
- **Multisite Ready**: Network-wide product management and synchronization
- **Localized Content**: Site-specific content and settings management

### 🛒 E-commerce Features
- **WooCommerce Integration**: Full WooCommerce compatibility with custom templates
- **Product Import/Export**: Bulk product management with JSON API
- **Variable Products**: Support for product variations and attributes
- **Brand Management**: Automatic brand taxonomy creation and management
- **Category Hierarchy**: Nested category support with parent-child relationships

### 🎨 Modern Development Stack
- **Sage 10**: Advanced WordPress starter theme with Laravel Blade
- **Tailwind CSS**: Utility-first CSS framework for rapid development
- **Blade Templating**: Laravel's powerful templating engine
- **Modern JavaScript**: ES6+ with module bundling

### 🔧 Advanced Features
- **Custom SEO Management**: Comprehensive SEO fields and meta management
- **Image Handling**: Support for both local and external image storage
- **Delivery Rules**: City-based delivery calculations and rules
- **Store Settings**: Dynamic store configuration and theming
- **API Endpoints**: RESTful API for product management

## 🚀 Quick Start

### Prerequisites
- WordPress 6.0+
- WooCommerce 8.0+
- PHP 8.0+
- Node.js 16+
- Composer

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/shopper-theme.git
   cd shopper-theme
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Build assets**
   ```bash
   npm run build
   ```

4. **Activate the theme**
   - Upload to `/wp-content/themes/`
   - Activate in WordPress admin
   - Network activate for multisite

### Required Plugins
```php
// Network activate these plugins
- WooCommerce
- Ajax Search for WooCommerce
- Fibosearch
- Fast Indexing API
- Visitor Tracking Plugin (Custom)
```

## 🏗️ Architecture

### Directory Structure
```
shopper/
├── app/
│   ├── Controllers/          # API Controllers
│   │   ├── EzExportController.php
│   │   └── EzImportController.php
│   ├── Data/                # Static data arrays
│   │   ├── cities.php
│   │   └── social.php
│   ├── Providers/           # Service Providers
│   │   ├── ProductSeoServiceProvider.php
│   │   ├── StoreFieldsServiceProvider.php
│   │   └── ThemeServiceProvider.php
│   ├── View/Composers/      # Blade view composers
│   └── widgets/             # Custom widgets
├── resources/
│   ├── scripts/             # JavaScript files
│   ├── styles/              # SCSS files
│   └── views/               # Blade templates
│       ├── components/      # Reusable components
│       ├── woocommerce/     # WooCommerce templates
│       └── partials/        # Template partials
└── routes/                  # Custom routes
```

### Core Components

#### Service Providers
- **ProductSeoServiceProvider**: Manages product SEO fields and Hebrew URL handling
- **StoreFieldsServiceProvider**: Handles store settings and configuration
- **ThemeServiceProvider**: Core theme functionality and asset management

#### Controllers
- **EzImportController**: Handles product import with Hebrew/Arabic support
- **EzExportController**: Manages product export with proper character encoding

#### Templates
- **Blade Templates**: Modern templating with Laravel Blade syntax
- **WooCommerce Integration**: Custom product templates and hooks
- **RTL Support**: Right-to-left layout and typography

## 💻 Development

### Development Workflow

1. **Start development server**
   ```bash
   npm run dev
   ```

2. **Watch for changes**
   ```bash
   npm run watch
   ```

3. **Build for production**
   ```bash
   npm run build
   ```

### Code Standards
- **PHP**: Follow WordPress Coding Standards
- **JavaScript**: ES6+ with modern syntax
- **SCSS**: BEM methodology with Tailwind utilities
- **Blade**: Laravel Blade templating conventions

### Key Development Features

#### Hebrew/Arabic URL Support
```php
// Proper URL handling for Hebrew characters
private function processHebrewUrl($url) {
    if (empty($url)) return '';
    
    $decoded_url = urldecode($url);
    if ($decoded_url !== $url) {
        $decoded_url = urldecode($decoded_url);
    }
    
    return $decoded_url;
}
```

#### Dynamic Store Settings
```php
// Store settings with fallbacks
$store_name = get_option('store_settings')['seo']['store_name'] ?? '';
$primary_color = get_option('store_settings')['primary_color'] ?? '#000000';
```

#### RTL Support
```php
// Conditional RTL classes
<span class="{{ is_rtl() ? 'mr-2' : 'ml-2' }}">
    {{ __('Content', 'sage') }}
</span>
```

## 📡 API Documentation

### Product Import/Export API

#### Import Products
**Endpoint**: `POST /wp-json/shopper/v1/import`

**Parameters**:
- `skip_image_download` (boolean): Store external image URLs
- `onallsites` (boolean): Import across all network sites

**Request Body**:
```json
{
  "products": [
    {
      "name": "Product Name",
      "sku": "PROD-123",
      "regular_price": "99.99",
      "description": "Product description",
      "attributes": [
        {
          "name": "Color",
          "slug": "color",
          "options": [
            {
              "name": "Red",
              "slug": "ff0000"
            },
            {
              "name": "Blue",
              "slug": "0000ff"
            }
          ],
          "variation": true,
          "visible": true
        }
      ],
      "seo": {
        "source_url": "https://example.com/אבג",
        "meta_title": "Meta Title"
      }
    }
  ]
}
```

#### Export Products
**Endpoint**: `GET /wp-json/shopper/v1/export`

**Parameters**:
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `category` (string): Filter by category
- `search` (string): Search term
- `all` (boolean): Export all products

#### Delete Products
**Request Body**:
```json
{
  "products": [
    {
      "id": "123",
      "delete": true
    }
  ]
}
```

### Response Format
```json
{
  "success": true,
  "data": {
    "products": [...],
    "total": 100,
    "total_pages": 10,
    "current_page": 1
  }
}
```

## 📋 Complete JSON Schema

### Full Product Import Structure
```json
{
  "products": [
    {
      "created_at": "2025-05-08 11:05:00",
      "name": "Product Name",
      "sku": "PROD-123",
      "regular_price": "99.99",
      "sale_price": "89.99",
      "description": "<p>Full product description with HTML support.</p>",
      "short_description": "Brief product overview",
      "stock_status": "instock",
      "manage_stock": true,
      "stock_quantity": 100,
      "image": "https://example.com/product-image.jpg",
      
      "brand": {
        "name": "Brand Name",
        "slug": "brand-slug",
        "description": "Brand description",
        "image": "https://example.com/brand-logo.jpg"
      },
      
      "categories": [
        {
          "id": 10,
          "name": "Category Name"
        },
        {
          "name": "New Category",
          "parent": "Parent Category"
        }
      ],
      
      "tags": [
        {
          "id": 5,
          "name": "Existing Tag"
        },
        {
          "name": "New Tag"
        }
      ],
      
      "attributes": [
        {
          "name": "Color",
          "slug": "color",
          "options": [
            {
              "name": "Red",
              "slug": "ff0000"
            },
            {
              "name": "Blue",
              "slug": "0000ff"
            },
            {
              "name": "Green",
              "slug": "00ff00"
            }
          ],
          "position": 1,
          "visible": true,
          "variation": true
        },
        {
          "name": "Size",
          "slug": "size",
          "options": [
            {
              "name": "Small",
              "slug": "s"
            },
            {
              "name": "Medium",
              "slug": "m"
            },
            {
              "name": "Large",
              "slug": "l"
            }
          ],
          "position": 2,
          "visible": true,
          "variation": true
        }
      ],
      
      "variations": [
        {
          "attributes": {
            "Color": {
              "name": "Red",
              "slug": "ff0000"
            },
            "Size": {
              "name": "Small",
              "slug": "s"
            }
          },
          "regular_price": "99.99",
          "sale_price": "89.99",
          "sku": "PROD-123-RED-S",
          "stock_status": "instock",
          "stock_quantity": 10,
          "manage_stock": true,
          "image": "https://example.com/variation-image.jpg"
        }
      ],
      
      "seo": {
        "meta_title": "Product Meta Title",
        "meta_description": "Product meta description",
        "focus_keywords": "keyword1, keyword2, keyword3",
        "canonical_url": "https://example.com/product-url",
        "redirect_to": "",
        "redirect_type": "",
        "image_alt": "Product image alt text",
        "source_url": "https://source-site.com/product"
      },
      
      "gallery_images": [
        "https://example.com/gallery1.jpg",
        "https://example.com/gallery2.jpg",
        "https://example.com/gallery3.jpg"
      ],
      
      "store_name": "Store Name",
      "language": "he",
      "image_alt": "Main product image alt text",
      "city": "Tel Aviv",
      "mall": "Mall Name",
      "neighborhood": "Neighborhood",
      "status": "publish",
      "featured": false,
      "catalog_visibility": "visible",
      "tax_status": "taxable",
      "tax_class": "standard",
      "purchase_note": "Thank you for your purchase!",
      "menu_order": 0,
      "virtual": false,
      "downloadable": false,
      "reviews_allowed": true
    }
  ],
  "coupons": []
}
```

### Key Features Supported

#### 1. **Custom Attribute Slugs**
```json
"attributes": [
  {
    "name": "Color",
    "slug": "color",  // Custom attribute taxonomy slug
    "options": [
      {
        "name": "Blue",
        "slug": "ff0000"  // Custom term slug (hex color)
      }
    ]
  }
]
```

#### 2. **Duplicate Names with Unique Slugs**
```json
"options": [
  {
    "name": "Blue",
    "slug": "ff0000"  // First Blue variant
  },
  {
    "name": "Blue", 
    "slug": "0000ff"  // Second Blue variant (different slug)
  }
]
```

#### 3. **Variation Attributes with Slug Support**
```json
"variations": [
  {
    "attributes": {
      "Color": {
        "name": "Blue",
        "slug": "ff0000"  // Specifically targets first Blue
      }
    }
  }
]
```

#### 4. **Brand Object Format**
```json
"brand": {
  "name": "Nike",
  "slug": "nike",
  "description": "Just Do It",
  "image": "https://example.com/nike-logo.jpg"
}
```

#### 5. **Backward Compatibility**
```json
// Legacy format still supported
"attributes": [
  {
    "name": "Color",
    "options": ["Red", "Blue", "Green"]  // String array
  }
]

"variations": [
  {
    "attributes": {
      "Color": "Red",  // String format
      "Size": "Small"
    }
  }
]
```

## 🚀 Deployment

### Production Build
```bash
# Build optimized assets
npm run build

# Compile Blade templates
composer install --optimize-autoloader --no-dev
```

### Environment Configuration
```php
// wp-config.php
define('WP_DEBUG', false);
define('WP_CACHE', true);
define('FORCE_SSL_ADMIN', true);
```

### Performance Optimization
- **Caching**: Enable object caching (Redis/Memcached)
- **CDN**: Configure CDN for static assets
- **Database**: Optimize database tables
- **Images**: Use WebP format with fallbacks

## 🛠️ Customization

### Adding Custom Fields
```php
// In your Service Provider
public function register() {
    add_action('add_meta_boxes', [$this, 'addCustomMetaBox']);
    add_action('save_post_product', [$this, 'saveCustomFields']);
}
```

### Custom Templates
```blade
{{-- resources/views/woocommerce/custom-template.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="custom-product-layout">
        {{-- Your custom content --}}
    </div>
@endsection
```

### Styling with Tailwind
```scss
// resources/styles/app.scss
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer components {
    .custom-button {
        @apply bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded;
    }
}
```

## 📚 Resources

### Documentation
- [Sage Documentation](https://roots.io/sage/docs/)
- [WooCommerce Documentation](https://docs.woocommerce.com/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Blade Documentation](https://laravel.com/docs/blade)

### Community
- [Roots Discourse](https://discourse.roots.io/)
- [WordPress Support](https://wordpress.org/support/)
- [WooCommerce Community](https://woocommerce.com/community/)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow WordPress coding standards
- Add tests for new features
- Update documentation for API changes
- Test RTL functionality for Hebrew/Arabic content

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Getting Help
- **Documentation**: Check the documentation above
- **Issues**: [GitHub Issues](https://github.com/your-username/shopper-theme/issues)
- **Community**: [Roots Discourse](https://discourse.roots.io/)

### Common Issues

#### Hebrew URLs Not Displaying
```php
// Ensure proper URL decoding
$source_url = urldecode(get_post_meta($product_id, '_shopper_source_url', true));
```

#### RTL Layout Issues
```css
/* Check RTL support in CSS */
[dir="rtl"] .custom-element {
    margin-left: 0;
    margin-right: 1rem;
}
```

#### Import/Export Errors
- Check file permissions
- Verify JSON format
- Ensure WooCommerce is active
- Check PHP memory limits

---

**Built with ❤️ for the Hebrew and Arabic e-commerce community**
