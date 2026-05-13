# Event Tracker Plugin

A comprehensive WordPress analytics and visitor tracking plugin designed for WooCommerce multisite networks. This plugin provides detailed user behavior analytics, visitor tracking, and e-commerce event monitoring with Google Cloud Storage integration, all while maintaining strict GDPR compliance.

## Key Features

### 🔒 **GDPR Compliance & User Privacy**
- **Comprehensive Consent Management**
  - Interactive consent banner with granular controls
  - Cookie management interface for users
  - Real-time consent status tracking
  - Automatic consent expiration handling

- **User Data Rights Implementation**
  - Data access requests (Article 15)
  - Right to erasure (Article 17)
  - Consent withdrawal (Article 7)
  - Privacy policy integration

- **Privacy-First Architecture**
  - Cookie-based storage with session fallback
  - No tracking before consent
  - Automatic data cleanup on withdrawal
  - Transparent data collection policies

### 🎯 **Advanced Event Tracking**
- **Page View Analytics** - Detailed tracking of user navigation patterns
- **E-commerce Events** - Product views, cart actions, purchase behavior
- **User Interactions** - Search queries, filter selections, social media clicks
- **Product Analytics** - Variation selections, image interactions, accordion usage

### 👥 **Visitor Management**
- **Unique Visitor Identification** - Cookie-based visitor ID with session fallback
- **Session Tracking** - Complete user session monitoring
- **Geolocation Support** - Optional location-based analytics (with user permission)
- **User Email Capture** - Automatic email tracking for logged-in users

### 🌐 **Multisite Network Support**
- **Cross-Site Tracking** - Unified analytics across WordPress multisite networks
- **Domain-Based Filtering** - Site-specific data analysis
- **Centralized Management** - Network admin interface for all sites

### 📊 **Cloud Storage System**
- **Local MySQL Database** - Immediate data access via WordPress admin
- **Google Cloud Storage Integration** - Cloud-based analytics and long-term storage
- **Real-time Sync** - Automatic data synchronization between systems

## Third-Party Script Management

The plugin now includes a **scalable third-party script manager** that automatically handles consent-based loading for any third-party service. No need to create individual managers for each plugin!

### How It Works

1. **Automatic Detection**: The system automatically detects third-party scripts by URL patterns and script handles
2. **Consent-Based Blocking**: Scripts are blocked by default and only loaded when proper consent is given
3. **Dynamic Loading**: When users change their consent preferences, scripts are loaded/unloaded accordingly

### Adding New Services

To add support for a new third-party service, simply add it to the `$service_registry` in `class-third-party-script-manager.php`:

```php
'your-service-name' => [
    'consent_type' => 'analytics', // or 'marketing', 'none'
    'scripts' => [
        'patterns' => ['service-domain.com', 'cdn.service.com'],
        'handles' => ['service-script-handle']
    ],
    'method' => 'block_and_defer'
]
```

### Supported Services

Currently configured services:

#### Analytics (requires analytics consent)
- **Google Analytics** (Site Kit)
- **Microsoft Clarity** 
- **Google Tag Manager**

#### Marketing (requires marketing consent)
- **Google AdSense** (Site Kit)
- **Theme AdSense** (meta tag)

#### No Consent Required
- **IndexNow** (SEO/search indexing)

### Examples

**Microsoft Clarity** - Added to registry as:
```php
'microsoft-clarity' => [
    'consent_type' => 'analytics', 
    'scripts' => [
        'patterns' => ['clarity.ms'],
        'handles' => ['microsoft-clarity', 'clarity-ms']
    ],
    'method' => 'block_and_defer'
],
```

**Future Plugin** - Just add:
```php
'future-analytics-plugin' => [
    'consent_type' => 'analytics',
    'scripts' => [
        'patterns' => ['analytics-plugin.com'],
        'handles' => ['analytics-plugin-handle']
    ],
    'method' => 'block_and_defer'
],
```

### Benefits

✅ **Scalable**: Add new plugins by just updating the registry
✅ **Automatic**: No manual script management needed
✅ **GDPR Compliant**: Blocks scripts until proper consent
✅ **Performance**: Scripts only load when needed
✅ **Maintainable**: All logic in one place

### Migration

If you previously had individual script managers (like `AdSenseManager`, `GoogleAnalyticsManager`), you can remove them. The new system handles everything automatically through the registry.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress 'Plugins' menu
3. Configure Google Cloud Storage credentials in Network Settings
4. The plugin will automatically create necessary database tables

## Configuration

### GDPR Consent Management

The plugin includes a comprehensive GDPR compliance system that automatically handles user consent:

1. **Consent Banner**
   - Appears automatically for new visitors
   - Three granular consent categories: Analytics, Enhanced Features, Location Services
   - Accept All, Accept Selected, or Reject All options
   - Automatically disappears after consent is given

2. **Privacy-First Tracking**
   - Before consent: temporary visitor ID in sessionStorage only
   - After consent: promotes to persistent cookie storage
   - No data loss during consent flow
   - Automatic cleanup of non-consented data

3. **User Data Rights Integration**
   - Built-in endpoints for GDPR Article 15 (Right of Access)
   - Built-in endpoints for GDPR Article 17 (Right to Erasure)
   - Built-in endpoints for GDPR Article 7 (Consent Withdrawal)
   - Automatic integration with theme privacy policy pages

### Google Cloud Storage Setup

1. **Create Google Cloud Project**
   - Set up a new project in Google Cloud Console
   - Enable Cloud Storage API

2. **Create Service Account**
   - Generate service account with Cloud Storage permissions
   - Download JSON credentials file

3. **Configure Storage Bucket**
   - Create a storage bucket for event data
   - Set appropriate permissions and lifecycle policies

4. **WordPress Configuration**
   - Add Cloud Storage credentials to network settings
   - Ensure proper API permissions

## Usage

### Tracked Events

The plugin automatically tracks these events:

**Global Events:**
- Page views (all page types)
- Search interactions
- Social media clicks

**E-commerce Events:**
- Product page views
- Add to cart actions
- Product variation selections
- Filter and sort interactions

**Navigation Events:**
- Category browsing
- Search suggestions
- Breadcrumb navigation

### Admin Interface

**Network Admin → Foottracking**
- View real-time visitor data
- Filter by domain and date
- Search specific visitor information
- Export data for analysis

### GDPR User Rights

**Privacy Policy Integration**
The plugin automatically adds GDPR controls to your theme's privacy policy page:

- **Download My Data** - Users can request all their personal data
- **Withdraw Consent** - Users can revoke tracking consent at any time  
- **Delete My Data** - Users can request complete data deletion

**Admin Interface for GDPR**
- View pending data deletion requests
- Manual data export tools for admin requests
- GDPR compliance audit logging
- User consent status monitoring

### Custom Event Tracking

Add custom events using the JavaScript API:

```javascript
// Check consent before tracking (automatic in trackEvent function)
trackEvent('custom_event_name', {
    _customProperty: 'value',
    _anotherProperty: 'data'
});

// Track with product context (only with user consent)
trackEvent('special_interaction', {
    _actionType: 'button_click',
    _buttonLocation: 'header'
});

// Check consent status manually
if (hasAnalyticsConsent()) {
    // Perform analytics operations
}
```

## Technical Details

### Architecture

- **Frontend:** Vanilla JavaScript with jQuery
- **Backend:** PHP with WordPress hooks
- **Storage:** MySQL + Google Cloud Storage
- **Authentication:** WordPress nonce system
- **Client Storage:** Cookies for persistent data, sessionStorage for temporary data

### Data Flow

1. **Consent Check** - All tracking requires explicit user consent first
2. **Client-Side Collection** - JavaScript captures user interactions (consent-gated)
3. **Server Processing** - PHP validates and cleans data with GDPR considerations
4. **Cloud Storage** - Data saved to both MySQL and Google Cloud Storage with retention policies
5. **User Rights Processing** - GDPR endpoints handle access, withdrawal, and deletion
6. **Admin Display** - WordPress admin interface with privacy controls

### File Structure

```
event-tracker-plugin/
├── event-tracker-plugin.php       # Main plugin file + GDPR endpoints
├── js/
│   ├── event-tracker.js           # Consent-aware tracking script
│   ├── visitor-tracking.js        # Visitor ID management (consent-based)
│   ├── consent-manager.js         # GDPR consent banner system
│   ├── gdpr-controls.js           # GDPR user rights interface
│   └── cookie-utils.js            # Global cookie management utilities
├── includes/
│   ├── class-event-processor.php  # Data processing with consent validation
│   ├── class-gcs-client.php       # Google Cloud Storage integration
│   ├── class-visitor-manager.php  # Visitor tracking
│   ├── class-consent-manager.php  # GDPR consent management
│   ├── class-gdpr-data-manager.php # User data rights implementation
│   ├── class-foottracking-admin.php # Admin interface
│   └── class-foottracking-table-list.php # Data display
└── README.md
```

## Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **WooCommerce:** 4.0 or higher (for e-commerce tracking)
- **Google Cloud:** Cloud Storage API access
- **Browser:** Modern browsers with geolocation support

## GDPR Compliance & Privacy

### ✅ **Full GDPR Implementation**

**Consent Management (Article 6 & 7)**
- ✅ Granular consent system with three categories
- ✅ Clear, plain language consent requests
- ✅ Easy withdrawal mechanism on privacy policy page
- ✅ Consent proof with timestamps and version tracking

**User Rights Implementation**
- ✅ **Article 15** - Right of Access (data download)
- ✅ **Article 17** - Right to Erasure (data deletion)
- ✅ **Article 7** - Consent Withdrawal (revoke permissions)
- ✅ **Article 13** - Information provision (privacy policy integration)

**Technical Safeguards (Article 25)**
- ✅ Privacy by design - no tracking without consent
- ✅ Data minimization - only necessary data collected
- ✅ Storage limitation - automatic retention policies
- ✅ Cookie-based storage with session fallback

### 🔐 **Data Collection (Consent-Based)**

**Analytics Consent:**
- Page visit patterns and user journey mapping
- Product interaction data and conversion tracking
- Search behavior and filter usage patterns
- Persistent cookie-based visitor identification

**Enhanced Features Consent:**
- Detailed product engagement metrics
- Advanced user behavior analysis
- Cross-session activity correlation
- Cookie-based preference storage

**Location Services Consent:**
- Geolocation data (with explicit permission)
- Regional analytics and store optimization data
- Cookie-based location preferences

**Always Collected (No Consent Required):**
- Essential technical data (IP addresses for security)
- Error logs and system performance data
- Temporary sessionStorage for visitor ID before consent

### 📋 **Data Retention Policies**

- **Analytics data:** 24 months maximum
- **Location data:** 12 months maximum  
- **Visitor identifiers:** Until deletion request or consent withdrawal
- **Consent records:** 6 years (compliance requirement)
- **GDPR audit logs:** 6 years (compliance requirement)

### 🛠 **Implementation Steps**

1. **Automatic Setup**
   - Plugin activation creates consent banner
   - Privacy policy page gets GDPR controls automatically
   - All tracking becomes consent-gated immediately

2. **Privacy Policy Updates**
   - Update your privacy policy to reference the data collection
   - GDPR controls are automatically integrated
   - User-friendly interface for exercising data rights

3. **Ongoing Compliance**
   - Monitor consent rates via admin dashboard
   - Handle user requests via automated endpoints
   - Regular audits via built-in logging system

## Support

### Troubleshooting

**Common Issues:**
- **No data appearing:** Check Google Cloud Storage credentials and verify users have given consent
- **Consent banner not showing:** Check if user already has stored consent in cookies
- **Geolocation not working:** Ensure HTTPS and user permissions + location consent
- **Admin interface empty:** Verify database table creation and consent status
- **GDPR controls not visible:** Ensure theme privacy policy page exists

**GDPR Debug Mode:**
Enable WordPress debug logging to troubleshoot GDPR issues:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check consent status in browser console
console.log('Analytics consent:', hasAnalyticsConsent());
console.log('Visitor ID:', getCookie('visitor_id') || sessionStorage.getItem('temp_visitor_id'));
console.log('Consent data:', getCookie('et_consent'));
```

### Performance

- Minimal impact on page load times
- Asynchronous data processing
- Efficient database queries
- Optimized for high-traffic sites

## Changelog

### Version 1.0.0
- ✅ **Full GDPR Compliance Implementation**
  - Complete consent management system with granular controls
  - User data rights endpoints (access, deletion, withdrawal)
  - Privacy-first visitor tracking with cookie-based storage
  - Automatic privacy policy integration
- ✅ **Advanced Analytics System**
  - Complete event tracking system with consent validation
  - Google Cloud Storage integration with GDPR considerations
  - Multisite network support with privacy controls
  - Real-time consent-aware data processing
- ✅ **User Experience**
  - Clean, professional consent banner
  - Seamless tracking activation after consent
  - User-friendly data rights interface
  - Cookie-based persistent storage with sessionStorage fallback

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Sharon Chen for comprehensive WordPress analytics and visitor tracking. 