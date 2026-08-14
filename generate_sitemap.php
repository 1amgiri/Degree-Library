<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = null;
try {
    if (file_exists(__DIR__ . '/api/secrets.php')) {
        require_once __DIR__ . '/api/secrets.php';
    }
    $servername = defined('CFG_DB_HOST') ? CFG_DB_HOST : "localhost";
    $username   = defined('CFG_DB_USER') ? CFG_DB_USER : "db_user";
    $password   = defined('CFG_DB_PASS') ? CFG_DB_PASS : "db_password";
    $dbname     = defined('CFG_DB_NAME') ? CFG_DB_NAME : "db_name";
    
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        $conn = null;
    }
} catch (Throwable $e) {
    $conn = null;
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$baseUrl = "https://degreelibrary.gt.tc";

function addUrl($url, $lastmod = null, $changefreq = 'weekly', $priority = '0.8') {
    global $xml;
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
    if ($lastmod) {
        $xml .= "    <lastmod>" . date('Y-m-d', strtotime($lastmod)) . "</lastmod>\n";
    }
    $xml .= "    <changefreq>" . $changefreq . "</changefreq>\n";
    $xml .= "    <priority>" . $priority . "</priority>\n";
    $xml .= "  </url>\n";
}


// 1. Home and core pages
addUrl($baseUrl . "/", date('Y-m-d'), 'daily', '1.0');
addUrl($baseUrl . "/upload.html", null, 'monthly', '0.6');
addUrl($baseUrl . "/community.html", date('Y-m-d'), 'daily', '0.8');
addUrl($baseUrl . "/important.html", null, 'weekly', '0.8');
addUrl($baseUrl . "/icet.html", null, 'weekly', '0.8');
addUrl($baseUrl . "/mca.html", null, 'weekly', '0.8');


// Use try-catch blocks to prevent fatal errors (500) if tables don't exist

// 2. SEO Pages (Categories, Subcategories)
try { if (isset($conn) && $conn instanceof mysqli) { 
    $seoResult = $conn->query("SELECT url_slug, updated_at FROM seo_pages");
    if ($seoResult && $seoResult->num_rows > 0) {
        while($row = $seoResult->fetch_assoc()) {
            addUrl($baseUrl . "/" . $row['url_slug'], $row['updated_at'], 'weekly', '0.9');
        }
    }
} } catch (Throwable $e) { /* ignore */ }

// 3. Blog Posts
try { if (isset($conn) && $conn instanceof mysqli) { 
    $blogResult = $conn->query("SELECT slug, updated_at FROM blog_posts WHERE status = 'published'");
    if ($blogResult && $blogResult->num_rows > 0) {
        while($row = $blogResult->fetch_assoc()) {
            addUrl($baseUrl . "/blog/" . $row['slug'], $row['updated_at'], 'weekly', '0.8');
        }
    }
} } catch (Throwable $e) { /* ignore */ }

// 4. Materials (Dynamic individual material pages)
try { if (isset($conn) && $conn instanceof mysqli) { 
    $matResult = $conn->query("SELECT slug, created_at FROM materials WHERE slug IS NOT NULL AND slug != ''");
    if ($matResult && $matResult->num_rows > 0) {
        while($row = $matResult->fetch_assoc()) {
            addUrl($baseUrl . "/material/" . $row['slug'], $row['created_at'], 'monthly', '0.7');
        }
    }
} } catch (Throwable $e) { /* ignore */ }

// 5. Community Posts
try { if (isset($conn) && $conn instanceof mysqli) { 
    $postResult = $conn->query("SELECT slug, created_at FROM community_posts WHERE slug IS NOT NULL AND slug != ''");
    if ($postResult && $postResult->num_rows > 0) {
        while($row = $postResult->fetch_assoc()) {
            addUrl($baseUrl . "/community/" . $row['slug'], $row['created_at'], 'weekly', '0.6');
        }
    }
} } catch (Throwable $e) { /* ignore */ }

$xml .= '</urlset>';

// Attempt to write the file
$bytes = file_put_contents(__DIR__ . '/sitemap.xml', $xml);

if ($bytes !== false) {
    echo "<h1>Success!</h1>";
    echo "<p>Sitemap generated successfully. " . $bytes . " bytes written to sitemap.xml.</p>";
    echo "<p><a href='/sitemap.xml'>View Sitemap</a></p>";
} else {
    echo "<h1>Error</h1>";
    echo "<p>Failed to write to sitemap.xml. Check file permissions on the server.</p>";
    
    // Print the raw XML so the user can copy-paste it if writing fails
    echo "<h3>If it failed to write, you can manually copy this XML and save it as sitemap.xml on your server:</h3>";
    echo "<textarea style='width:100%; height:500px;'>";
    echo htmlspecialchars($xml);
    echo "</textarea>";
}
?>
