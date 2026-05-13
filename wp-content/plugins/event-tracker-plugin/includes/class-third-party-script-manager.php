<?php

namespace EventTracker;

class ThirdPartyScriptManager {
    
    /**
     * Registry of third-party services and their consent requirements
     * This is where you add new services - much more scalable than individual managers
     */
    private $service_registry = [
        // Analytics Services
        'google-analytics' => [
            'consent_type' => 'analytics',
            'scripts' => [
                'patterns' => [
                    'googletagmanager.com/gtag',
                    'google-analytics.com/analytics',
                    'googlesitekit.*analytics'
                ],
                'handles' => ['google-site-kit-analytics', 'google_gtagjs']
            ],
            'method' => 'block_and_defer'
        ],
        
        'microsoft-clarity' => [
            'consent_type' => 'analytics', 
            'scripts' => [
                'patterns' => ['clarity.ms'],
                'handles' => ['microsoft-clarity', 'clarity-ms']
            ],
            'method' => 'block_and_defer'
        ],
        
        // Marketing Services
        'google-adsense' => [
            'consent_type' => 'marketing',
            'scripts' => [
                'patterns' => [
                    'pagead2.googlesyndication.com',
                    'googletagservices.com/tag/js/gpt.js'
                ],
                'handles' => ['google-site-kit-adsense']
            ],
            'method' => 'block_and_defer'
        ],
        
        // Tag Managers (usually analytics)
        'google-tag-manager' => [
            'consent_type' => 'analytics',
            'scripts' => [
                'patterns' => ['googletagmanager.com/gtm.js'],
                'handles' => ['google-site-kit-tagmanager']
            ],
            'method' => 'block_and_defer'
        ],
        
        // Services that don't require consent (SEO/technical)
        'indexnow' => [
            'consent_type' => 'none', // No consent required for search indexing
            'scripts' => [
                'patterns' => ['indexnow'],
                'handles' => ['indexnow']
            ],
            'method' => 'allow'
        ]
    ];
    
    private $consent_manager;
    
    public function __construct() {
        $this->consent_manager = new ConsentManager();
        
        // Hook early to catch script enqueues
        add_action('wp_enqueue_scripts', [$this, 'intercept_scripts'], 1);
        add_action('admin_enqueue_scripts', [$this, 'intercept_scripts'], 1);
        
        // Filter script output to add consent attributes
        add_filter('script_loader_tag', [$this, 'filter_script_tags'], 10, 3);
        
        // Block scripts in the head
        add_action('wp_head', [$this, 'block_head_scripts'], 1);
        
        // Handle consent changes
        add_action('wp_footer', [$this, 'inject_consent_handler']);
    }
    
    /**
     * Intercept and modify script enqueues based on consent requirements
     */
    public function intercept_scripts() {
        global $wp_scripts;
        
        if (!$wp_scripts) return;
        
        foreach ($wp_scripts->registered as $handle => $script) {
            $service = $this->identify_service($script->src, $handle);
            
            if ($service && $this->service_registry[$service]['method'] === 'block_and_defer') {
                $consent_type = $this->service_registry[$service]['consent_type'];
                
                if (!$this->has_consent($consent_type)) {
                    // Dequeue the script and mark it for deferred loading
                    wp_dequeue_script($handle);
                    $this->store_deferred_script($handle, $script, $consent_type);
                }
            }
        }
    }
    
    /**
     * Filter script tags to add consent attributes for deferred loading
     */
    public function filter_script_tags($tag, $handle, $src) {
        $service = $this->identify_service($src, $handle);
        
        if ($service) {
            $consent_type = $this->service_registry[$service]['consent_type'];
            
            if ($consent_type !== 'none' && !$this->has_consent($consent_type)) {
                // Add data-consent attribute and change script type to prevent execution
                $tag = str_replace('<script ', '<script data-consent="' . $consent_type . '" data-original-src="' . $src . '" type="text/plain" ', $tag);
                $tag = str_replace('src="' . $src . '"', 'src=""', $tag);
            }
        }
        
        return $tag;
    }
    
    /**
     * Block inline scripts and meta tags in the head section
     */
    public function block_head_scripts() {
        if ($this->has_consent('marketing')) {
            // Allow AdSense meta tag when marketing consent is given
            $this->inject_adsense_meta();
        }
        
        // Add script to block other inline scripts
        ?>
        <script>
        // Block common third-party inline scripts based on consent
        (function() {
            const originalCreateElement = document.createElement;
            document.createElement = function(tagName) {
                const element = originalCreateElement.call(this, tagName);
                
                if (tagName.toLowerCase() === 'script') {
                    const originalSetAttribute = element.setAttribute;
                    element.setAttribute = function(name, value) {
                        if (name === 'src') {
                            const service = identifyServiceFromSrc(value);
                            if (service && !hasRequiredConsent(service)) {
                                // Block by setting data attributes instead
                                this.setAttribute('data-consent', getConsentType(service));
                                this.setAttribute('data-original-src', value);
                                this.setAttribute('type', 'text/plain');
                                return;
                            }
                        }
                        originalSetAttribute.call(this, name, value);
                    };
                }
                
                return element;
            };
        })();
        </script>
        <?php
    }
    
    /**
     * Inject JavaScript to handle consent changes and load deferred scripts
     */
    public function inject_consent_handler() {
        $deferred_scripts = $this->get_deferred_scripts();
        ?>
        <script>
        // Service registry for client-side identification
        window.etServiceRegistry = <?php echo json_encode($this->service_registry); ?>;
        
        // Function to identify service from script source
        function identifyServiceFromSrc(src) {
            for (const [service, config] of Object.entries(window.etServiceRegistry)) {
                for (const pattern of config.scripts.patterns) {
                    if (src.includes(pattern)) {
                        return service;
                    }
                }
            }
            return null;
        }
        
        // Function to get consent type for service
        function getConsentType(service) {
            return window.etServiceRegistry[service]?.consent_type || 'analytics';
        }
        
        // Function to check if service has required consent
        function hasRequiredConsent(service) {
            const consentType = getConsentType(service);
            switch(consentType) {
                case 'analytics': return hasAnalyticsConsent();
                case 'marketing': return hasMarketingConsent();
                case 'none': return true;
                default: return false;
            }
        }
        
        // Load deferred scripts when consent is given
        document.addEventListener('consentUpdated', function(event) {
            const consent = event.detail || window.userConsent;
            loadDeferredScriptsByConsent(consent);
        });
        
        // Load scripts based on consent type
        function loadDeferredScriptsByConsent(consent) {
            // Load analytics scripts
            if (consent.analytics === 1 || consent.cookies.analytics === 1) {
                loadScriptsByConsentType('analytics');
            }
            
            // Load marketing scripts
            if (consent.cookies.marketing === 1) {
                loadScriptsByConsentType('marketing');
            }
        }
        
        function loadScriptsByConsentType(consentType) {
            const scripts = document.querySelectorAll(`script[data-consent="${consentType}"]`);
            scripts.forEach(script => {
                const originalSrc = script.getAttribute('data-original-src');
                if (originalSrc && !script.hasAttribute('data-loaded')) {
                    const newScript = document.createElement('script');
                    newScript.src = originalSrc;
                    newScript.async = script.async;
                    newScript.defer = script.defer;
                    script.setAttribute('data-loaded', 'true');
                    script.parentNode.insertBefore(newScript, script);
                }
            });
        }
        
        // Deferred scripts from server-side blocking
        const deferredScripts = <?php echo json_encode($deferred_scripts); ?>;
        
        // Function to load server-deferred scripts
        function loadDeferredScripts(consentType) {
            if (deferredScripts[consentType]) {
                deferredScripts[consentType].forEach(scriptData => {
                    const script = document.createElement('script');
                    script.src = scriptData.src;
                    script.async = scriptData.async || false;
                    script.defer = scriptData.defer || false;
                    if (scriptData.id) script.id = scriptData.id;
                    document.head.appendChild(script);
                });
                delete deferredScripts[consentType]; // Prevent double loading
            }
        }
        
        // Load deferred scripts when consent changes
        document.addEventListener('consentUpdated', function(event) {
            const consent = event.detail || window.userConsent;
            
            if (consent.analytics === 1 || consent.cookies.analytics === 1) {
                loadDeferredScripts('analytics');
            }
            
            if (consent.cookies.marketing === 1) {
                loadDeferredScripts('marketing');
            }
        });
        </script>
        <?php
    }
    
    /**
     * Identify which service a script belongs to
     */
    private function identify_service($src, $handle) {
        foreach ($this->service_registry as $service => $config) {
            // Check by URL patterns
            foreach ($config['scripts']['patterns'] as $pattern) {
                if (strpos($src, $pattern) !== false) {
                    return $service;
                }
            }
            
            // Check by script handle
            if (in_array($handle, $config['scripts']['handles'])) {
                return $service;
            }
        }
        
        return null;
    }
    
    /**
     * Check if user has given consent for a specific type
     */
    private function has_consent($consent_type) {
        switch ($consent_type) {
            case 'analytics':
                return $this->consent_manager->get_consent_preferences()['analytics'] === 1 ||
                       $this->consent_manager->is_cookie_allowed('analytics');
            
            case 'marketing':
                return $this->consent_manager->is_cookie_allowed('marketing');
            
            case 'none':
                return true;
            
            default:
                return false;
        }
    }
    
    /**
     * Store deferred script for later loading
     */
    private function store_deferred_script($handle, $script, $consent_type) {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['et_deferred_scripts'])) {
            $_SESSION['et_deferred_scripts'] = [];
        }
        
        $_SESSION['et_deferred_scripts'][$consent_type][] = [
            'handle' => $handle,
            'src' => $script->src,
            'async' => isset($script->extra['async']) ? $script->extra['async'] : false,
            'defer' => isset($script->extra['defer']) ? $script->extra['defer'] : false,
            'id' => $handle
        ];
    }
    
    /**
     * Get deferred scripts for JavaScript injection
     */
    private function get_deferred_scripts() {
        if (!session_id()) {
            session_start();
        }
        
        return isset($_SESSION['et_deferred_scripts']) ? $_SESSION['et_deferred_scripts'] : [];
    }
    
    /**
     * Inject AdSense meta tag when marketing consent is given
     */
    private function inject_adsense_meta() {
        echo '<meta name="google-adsense-account" content="ca-pub-8691797672038960">' . "\n";
    }
    
    /**
     * Add a new service to the registry (for dynamic additions)
     */
    public function register_service($service_id, $config) {
        $this->service_registry[$service_id] = $config;
    }
    
    /**
     * Get all registered services (for debugging/admin interface)
     */
    public function get_registered_services() {
        return $this->service_registry;
    }
} 