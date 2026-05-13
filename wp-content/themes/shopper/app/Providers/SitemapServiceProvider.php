<?php
/**
 * Generate Static Sitemaps & llms.txt for WordPress Multisite
 * Audit Fix: 2026 AI Compliance, Dynamic Domains, Data Integrity, LLM Context
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use WP_Query;

class SitemapServiceProvider extends ServiceProvider
{
    // Increased limit to 1000 to reduce file fragmentation (Google allows 50k)
    protected $productsPerFile = 1000;

    public function register()
    {
        // Nothing to register
    }

    public function boot()
    {
        // Disable default WordPress sitemaps to prevent conflicts
        add_filter('wp_sitemaps_enabled', '__return_false');

        // Add rewrite rules for sitemap files & llms.txt
        add_action('init', [$this, 'addSitemapRewriteRules']);

        // ROBOTS.TXT: Filter to append rules (Audit Fix: Do not overwrite)
        add_filter('robots_txt', [$this, 'modifyRobotsTxt'], 20, 2);

        // Add redirect for default sitemap
        add_action('template_redirect', [$this, 'redirectDefaultSitemap']);

        // Handle sitemap requests
        add_action('template_redirect', [$this, 'handleSitemapRequest']);

        // Handle llms.txt requests (NEW)
        add_action('template_redirect', [$this, 'handleLlmsTxtRequest']);

        // Flush rewrite rules if needed
        add_action('init', [$this, 'maybeFlushRules']);

        // Prevent other plugins from interfering with sitemap/llms.txt output
        add_action('plugins_loaded', function () {
            if (isset($_GET['sitemap']) || isset($_GET['robots']) || isset($_GET['llms_txt'])) {
                remove_all_actions('wp_head');
                remove_all_actions('wp_footer');
                remove_all_filters('the_content');
            }
        }, 1);
    }

    public function addSitemapRewriteRules()
    {
        add_rewrite_rule('^robots\.txt$', 'index.php?robots=1', 'top');
        add_rewrite_rule('([^/]+)-sitemap\.xml$', 'index.php?sitemap=$matches[1]', 'top');
        add_rewrite_rule('^sitemap\.xml$', 'index.php?sitemap=main', 'top');

        // NEW: llms.txt rewrite rule
        add_rewrite_rule('^llms\.txt$', 'index.php?llms_txt=1', 'top');

        add_filter('query_vars', function ($vars) {
            $vars[] = 'sitemap';
            $vars[] = 'robots';
            $vars[] = 'llms_txt';
            return $vars;
        });
    }

    public function maybeFlushRules()
    {
        $rules_version = '1.5'; // Incremented for llms.txt support
        $current_rules_version = get_option('sitemap_rules_version');

        if ($current_rules_version !== $rules_version) {
            flush_rewrite_rules();
            update_option('sitemap_rules_version', $rules_version);
        }
    }

    // --- llms.txt Handler (NEW) ---
    public function handleLlmsTxtRequest()
    {
        if (get_query_var('llms_txt')) {
            while (ob_get_level())
                ob_end_clean();
            header('HTTP/1.1 200 OK');
            header('Content-Type: text/plain; charset=UTF-8');
            header('X-Robots-Tag: noindex'); // Don't index this in Google Search, it's for Agents
            echo $this->generateLlmsTxt();
            exit;
        }
    }

    protected function generateLlmsTxt()
    {
        $site_name = get_bloginfo('name');
        $tagline = get_bloginfo('description');

        // Markdown Format compliant with llms.txt spec
        $content = "# {$site_name}\n";

        // CRITICAL FOR AI AGENTS: Explicitly state the directory's scope
        // Note: [Insert Region/City Dynamic Tag] is replaced by the tagline which typically contains it in local sites.
        $content .= "> {$tagline}. This site provides verified local listings, reviews, and contact details for businesses in this area.\n\n";

        $content .= "## Browse Local Listings\n";

        // List Categories with "View [Category] in [City]" context
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 50]);
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $cat) {
                $link = get_term_link($cat);
                if (!is_wp_error($link)) {
                    // Help the LLM understand the link destination
                    $content .= "- [{$cat->name}]({$link}): Directory listings for {$cat->name}\n";
                }
            }
        }

        $content .= "\n## Site Structure\n";
        $content .= "- [Main Sitemap](" . home_url('/sitemap.xml') . ")\n";

        return $content;
    }

    // --- Sitemap Handler ---

    public function handleSitemapRequest()
    {
        $sitemap = get_query_var('sitemap');
        $robots = get_query_var('robots');

        if ($robots) {
            while (ob_get_level())
                ob_end_clean();
            header('HTTP/1.1 200 OK');
            header('Content-Type: text/plain; charset=UTF-8');
            // Pass empty string as base since we are serving the file directly
            echo $this->modifyRobotsTxt('', true);
            exit;
        }

        if (!$sitemap)
            return;

        while (ob_get_level())
            ob_end_clean();
        @ini_set('display_errors', 0);
        header('HTTP/1.1 200 OK');
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex'); // Prevent indexing the sitemap file itself

        try {
            $content = '';
            switch ($sitemap) {
                case 'main':
                    $content = $this->generateMainSitemap();
                    break;
                case 'homepage':
                    $content = $this->generateHomepageSitemap();
                    break;
                case 'categories':
                    if (is_main_site())
                        throw new \Exception('N/A');
                    $content = $this->generateCategoriesSitemap();
                    break;
                default:
                    if (preg_match('/^products-(\d+)$/', $sitemap, $matches)) {
                        if (is_main_site())
                            throw new \Exception('N/A');
                        $content = $this->generateProductsSitemap((int) $matches[1]);
                    } else {
                        throw new \Exception('Invalid sitemap type');
                    }
            }
            echo trim($content);
        } catch (\Exception $e) {
            status_header(404);
            die('Sitemap Error: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * Generate main sitemap index
     * FIX: Removed hardcoded domain. Now uses dynamic get_site_url().
     */
    protected function generateMainSitemap()
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $content .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        if (is_main_site()) {
            // Root Site
            $main_site_url = get_site_url(1, '/');
            $content .= $this->addSitemapIndexEntry($main_site_url . 'homepage-sitemap.xml');

            // Subsites (Dynamic)
            $sites = get_sites([
                'public' => 1,
                'archived' => 0,
                'mature' => 0,
                'spam' => 0,
                'deleted' => 0,
                'site__not_in' => [1]
            ]);

            foreach ($sites as $site) {
                // FIX: Dynamic URL generation per site
                $site_url = get_site_url($site->blog_id, '/');
                if ($site_url) {
                    $site_url = trailingslashit($site_url);
                    $content .= $this->addSitemapIndexEntry($site_url . 'main-sitemap.xml');
                }
            }
        } else {
            // Subsite Index
            $home_url = trailingslashit(home_url());
            $content .= $this->addSitemapIndexEntry($home_url . 'homepage-sitemap.xml');

            if ($this->hasCategories()) {
                $content .= $this->addSitemapIndexEntry($home_url . 'categories-sitemap.xml');
            }

            $product_count = $this->getProductCount();
            $total_pages = ceil($product_count / $this->productsPerFile);

            for ($i = 1; $i <= $total_pages; $i++) {
                $content .= $this->addSitemapIndexEntry($home_url . "products-{$i}-sitemap.xml");
            }
        }

        $content .= '</sitemapindex>';
        return $content;
    }

    protected function generateHomepageSitemap()
    {
        $content = $this->getXmlHeader();
        $home_url = home_url('/');
        $content .= $this->addUrlEntry($home_url, date('c'), 'daily', '1.0');

        if (!is_main_site()) {
            $shop_page_id = get_option('woocommerce_shop_page_id');
            if ($shop_page_id && get_option('page_on_front') != $shop_page_id) {
                $shop_url = get_permalink($shop_page_id);
                if ($shop_url !== $home_url) {
                    $content .= $this->addUrlEntry($shop_url, get_the_modified_date('c', $shop_page_id), 'daily', '1.0');
                }
            }
        }
        $content .= '</urlset>';
        return $content;
    }

    protected function generateCategoriesSitemap()
    {
        $content = $this->getXmlHeader();
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);

        foreach ($categories as $category) {
            $url = get_term_link($category);
            if (!is_wp_error($url)) {
                $content .= $this->addUrlEntry($url, date('c'), 'weekly', '0.8');
            }
        }
        $content .= '</urlset>';
        return $content;
    }

    protected function generateProductsSitemap($page)
    {
        if (function_exists('ini_set'))
            @ini_set('memory_limit', '1024M');

        // FIX: Added xmlns:image for Google Shopping compliance
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        $offset = ($page - 1) * $this->productsPerFile;

        $products = get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $this->productsPerFile,
            'offset' => $offset,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'DESC',
            'no_found_rows' => true
        ]);

        foreach ($products as $product_id) {
            $url = get_permalink($product_id);
            if (!$url)
                continue;

            // FIX: Removed destructive regex. Trust WordPress permalinks.

            // Image processing
            $img_tag = '';
            $thumb_id = get_post_thumbnail_id($product_id);
            if ($thumb_id) {
                $img_url = wp_get_attachment_url($thumb_id);
                if ($img_url) {
                    $img_tag = "\n<image:image><image:loc>" . $this->encodeUrl($img_url) . "</image:loc><image:title>" . $this->esc_xml(get_the_title($product_id)) . "</image:title></image:image>";
                }
            }

            $content .= $this->addUrlEntry(
                $url,
                get_the_modified_date('c', $product_id),
                'daily',
                '0.9',
                $img_tag
            );
        }

        $content .= '</urlset>';
        return $content;
    }

    /**
     * 2026 AI SEARCH OPTIMIZED ROBOTS.TXT
     * Reference: Google Search Central & Momentic AI Guide
     */
    public function modifyRobotsTxt($output, $public)
    {
        // We append to existing WP rules to be safe, but define strict AI protocols first.
        $rules = "\n\n# --- Shopper Network AI Protocols (2026) ---\n";

        // 1. ALLOW: AI Search Agents & Citations
        // These bots drive traffic and provide "Answer Engine" citations.
        $rules .= "# Group 1: AI Search & Citation (Allow)\n";
        $rules .= "User-agent: OAI-SearchBot\n";   // ChatGPT Search
        $rules .= "User-agent: ChatGPT-User\n";    // User-driven browsing (Critical for custom GPTs)
        $rules .= "User-agent: PerplexityBot\n";   // Perplexity AI
        $rules .= "User-agent: ClaudeBot\n";       // Anthropic Citations
        $rules .= "User-agent: claude-web\n";      // Anthropic Web Fetcher
        $rules .= "User-agent: BingBot\n";         // Microsoft Copilot/Bing
        $rules .= "User-agent: Googlebot\n";       // Google Search

        // NEW: Applebot specifically allowed for Siri/Spotlight search visibility
        $rules .= "User-agent: Applebot\n";

        // NEW: Google Inspection Tools (Explicit Allow)
        $rules .= "User-agent: Google-InspectionTool\n";
        $rules .= "User-agent: Schema-Markup-Validator\n";
        $rules .= "Allow: /\n\n";

        // 2. BLOCK: AI Model Training (Data Protection)
        // These bots scrape data solely for training LLMs, often without traffic attribution.
        // Ref: Google-Extended Documentation
        $rules .= "# Group 2: AI Model Training (Block)\n";
        $rules .= "User-agent: GPTBot\n";            // OpenAI Training
        $rules .= "User-agent: Google-Extended\n";   // Gemini/Vertex AI Training
        $rules .= "User-agent: anthropic-ai\n";      // Claude Training
        $rules .= "User-agent: Applebot-Extended\n"; // Apple Intelligence Training
        $rules .= "User-agent: CCBot\n";             // Common Crawl (Used by many LLMs)
        $rules .= "User-agent: Bytespider\n";        // TikTok/ByteDance
        $rules .= "User-agent: FacebookBot\n";       // Meta AI Training
        $rules .= "Disallow: /\n\n";

        // 3. GENERAL & SECURITY
        $rules .= "# Group 3: General\n";
        $rules .= "User-agent: *\n";
        $rules .= "Allow: /\n";
        $rules .= "Allow: /wp-content/uploads/\n";
        $rules .= "Allow: /wp-admin/admin-ajax.php\n";

        // Block internal/sensitive paths (Spam Prevention)

        // Block Dynamic/Private Paths
        $rules .= "Disallow: /wp-admin/\n";
        $rules .= "Disallow: /search\n";
        $rules .= "Disallow: /cart/\n";
        $rules .= "Disallow: /checkout/\n";
        $rules .= "Disallow: /my-account/\n";
        $rules .= "Disallow: /*?*filter=\n"; // Faceted Nav
        $rules .= "Disallow: /*?*sort=\n";
        $rules .= "\n";

        // 4. Sitemaps
        // Using trailingslashit(home_url()) ensures correct path in subdirectories
        $rules .= "Sitemap: " . trailingslashit(home_url()) . "main-sitemap.xml\n";
        if (!is_main_site()) {
            $rules .= "Sitemap: " . trailingslashit(home_url()) . "homepage-sitemap.xml\n";
        }

        return $output . $rules;
    }

    // --- Helper Methods ---

    protected function getXmlHeader()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ' .
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 ' .
            'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
    }

    protected function addSitemapIndexEntry($url)
    {
        return "<sitemap><loc>" . $this->encodeUrl($url) . "</loc><lastmod>" . date('c') . "</lastmod></sitemap>\n";
    }

    protected function addUrlEntry($loc, $lastmod, $changefreq, $priority, $extra = '')
    {
        return "<url>\n" .
            "  <loc>" . $this->encodeUrl($loc) . "</loc>\n" .
            "  <lastmod>" . $lastmod . "</lastmod>\n" .
            "  <changefreq>" . $changefreq . "</changefreq>\n" .
            "  <priority>" . $priority . "</priority>" .
            $extra . "\n" .
            "</url>\n";
    }

    protected function encodeUrl($url)
    {
        // Ensure we don't double encode, but properly handle UTF-8
        return htmlspecialchars(urldecode($url), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    protected function esc_xml($string)
    {
        return htmlspecialchars($string, ENT_XML1, 'UTF-8');
    }

    public function redirectDefaultSitemap()
    {
        if (preg_match('/wp-sitemap.*\.xml$/', $_SERVER['REQUEST_URI'])) {
            wp_redirect(home_url('/sitemap.xml'), 301);
            exit;
        }
    }

    protected function hasCategories()
    {
        $cats = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 1]);
        return !empty($cats) && !is_wp_error($cats);
    }

    protected function getProductCount()
    {
        $counts = wp_count_posts('product');
        return $counts->publish ?? 0;
    }

    protected function isCorrectDomain()
    {
        return true;
    }
}
