<?php
// Dynamic Blog Resolution (sunrise.php)
// Automatically resolves any subsite by matching the longest DB path prefix.
// ROUTING STRATEGY:
//   /st/ (store home) -> Resolve to subsite directly (sunrise handles)
//   /pl/ (category)   -> Resolve to subsite directly (sunrise handles, subsite sets up category query)
//   /pd/ (product)    -> Stay on Main Site for PermalinkServiceProvider resolver (switch_to_blog)
// 
// Product pages MUST stay on Main Site because single-product.php needs
// explicit template inclusion after switch_to_blog(). Categories and store
// pages work via the subsite's native Sage Blade views.
global $wpdb;

// 0. Safety: CLI Check
$request_path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

// 1. Sanitize: Remove query string
if (strpos($request_path, '?') !== false) {
    $request_path = substr($request_path, 0, strpos($request_path, '?'));
}
// 2. Normalize: Ensure trailing slash for consistent matching against DB
$request_path = rtrim($request_path, '/') . '/';

// 3. ROUTING GATE: /pd/ (product detail) MUST stay on Main Site.
// PermalinkServiceProvider's rewrite rules + switch_to_blog() handle these.
$is_product_detail = (strpos($request_path, '/pd/') !== false);

if ($is_product_detail) {
    // Do NOT resolve to a subsite. Let WordPress boot as Main Site (ID 1).
    return;
}

// 4. For /pl/ and /br/ URLs, convert to /st/ for DB path matching only.
// The actual REQUEST_URI stays unchanged so the subsite can detect the mode.
$request_path_for_db = str_replace(
    ['/pd/', '/pl/', '/br/'],
    '/st/',
    $request_path
);

// 5. Query: Find the most specific (longest) path that matches the request.
$blogs_table = isset($wpdb->blogs) && !empty($wpdb->blogs) ? $wpdb->blogs : $wpdb->base_prefix . 'blogs';

$query = "
    SELECT * FROM $blogs_table
    WHERE path != '/' 
    AND %s LIKE CONCAT(path, '%%') 
    ORDER BY LENGTH(path) DESC 
    LIMIT 1
";

$site = $wpdb->get_row($wpdb->prepare($query, $request_path_for_db));

if ($site) {
    global $current_blog, $current_site;

    $GLOBALS['blog_id'] = $site->blog_id;

    $current_blog = $site;
    $current_blog->site_id = 1;
    $current_blog->public = (string) $current_blog->public;
    $current_blog->archived = (string) $current_blog->archived;

    $GLOBALS['blog_id'] = $site->blog_id;
    $GLOBALS['current_blog'] = $current_blog;

    $current_site = new stdClass();
    $current_site->id = 1;
    $current_site->domain = $site->domain;
    $current_site->path = '/';
    $current_site->site_name = 'Network Directory';
}
