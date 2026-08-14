<?php
// material.php
require_once 'api/db.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    echo "<div style='text-align: center; padding: 50px; font-family: sans-serif;'><img src='/error_cat.svg' style='height: 150px; margin-bottom: 20px; opacity: 0.8;'><h1>404 Not Found</h1></div>";
    exit;
}

// Check if slug column exists
$slug_exists = false;
$colRes = $conn->query("SHOW COLUMNS FROM materials LIKE 'slug'");
if ($colRes && $colRes->num_rows > 0) {
    $slug_exists = true;
}

$fallback_id = 0;
if (is_numeric($slug)) {
    $fallback_id = (int)$slug;
}

if ($slug_exists) {
    $stmt = $conn->prepare("SELECT * FROM materials WHERE slug = ? OR id = ?");
    $stmt->bind_param("si", $slug, $fallback_id);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    // Fallback if slug column doesn't exist yet
    $allRes = $conn->query("SELECT * FROM materials");
    $foundId = 0;
    while($row = $allRes->fetch_assoc()) {
        $gen = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $row['name'] ?? '')));
        $gen = preg_replace('/-+/', '-', $gen);
        $gen = trim($gen, '-') ?: $row['id'];
        if ($gen === $slug || (string)$row['id'] === $slug || $row['id'] == $fallback_id) {
            $foundId = $row['id'];
            break;
        }
    }
    if ($foundId > 0) {
        $stmt = $conn->prepare("SELECT * FROM materials WHERE id = ?");
        $stmt->bind_param("i", $foundId);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query("SELECT * FROM materials WHERE id = 0");
    }
}

if ($res->num_rows === 0) {
    header("HTTP/1.0 404 Not Found");
    echo "<div style='text-align: center; padding: 50px; font-family: sans-serif;'><img src='/error_cat.svg' style='height: 150px; margin-bottom: 20px; opacity: 0.8;'><h1>404 Not Found - Material does not exist.</h1></div>";
    exit;
}

$material = $res->fetch_assoc();
$material_name_clean = strip_tags($material['name'] ?? 'Study Material');
$uploader_clean = strip_tags($material['uploader'] ?? 'User');
$category_clean = strip_tags($material['category'] ?? 'General');
$tags_clean = strip_tags($material['tags'] ?? 'academic');
$title = htmlspecialchars($material_name_clean . " - Free Degree Library", ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars("Download " . $material_name_clean . " (" . $category_clean . ") uploaded by " . $uploader_clean . " on Free Degree Library. Free academic resources and notes.", ENT_QUOTES, 'UTF-8');
$mat_slug_param = !empty($material['slug']) ? $material['slug'] : $slug;
$canonicalUrl = "https://degreelibrary.gt.tc/material/" . htmlspecialchars($mat_slug_param ?? '', ENT_QUOTES, 'UTF-8');
$downloadUrl = "/api/download.php?id=" . $material['id'];
$datePublished = !empty($material['created_at']) ? date('c', strtotime($material['created_at'])) : '';
?><!DOCTYPE html><html lang="en"><head>  <!-- Google Tag Manager & Google tag (gtag.js) Deferred for Core Web Vitals -->
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag() { dataLayer.push(arguments); }
      function initAnalytics() {
        if (window.analyticsLoaded) return;
        window.analyticsLoaded = true;
        /* GTM */
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-W46D2S8X');
        /* gtag.js */
        var gtScript = document.createElement("script");
        gtScript.async = true;
        gtScript.src = "https://www.googletagmanager.com/gtag/js?id=G-VHYRJJZX5H";
        document.head.appendChild(gtScript);
        gtag("js", new Date());
        gtag("config", "G-VHYRJJZX5H");
      }
      window.addEventListener("load", function() { setTimeout(initAnalytics, 1000); });
      ["scroll", "click", "touchstart", "mousemove"].forEach(function(ev) {
        window.addEventListener(ev, initAnalytics, { once: true, passive: true });
      });
    </script>  <meta charset="UTF-8">  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?php echo $title; ?></title>
  <meta name="description" content="<?php echo $description; ?>">
  <meta name="keywords" content="<?php echo htmlspecialchars($tags_clean . ", " . $category_clean . ", study material, academic notes", ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="author" content="<?php echo htmlspecialchars($uploader_clean, ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="canonical" href="<?php echo $canonicalUrl; ?>">
  
  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="article">
  <meta property="og:url" content="<?php echo $canonicalUrl; ?>">
  <meta property="og:title" content="<?php echo $title; ?>">
  <meta property="og:description" content="<?php echo $description; ?>">
  <meta property="og:image" content="https://degreelibrary.gt.tc/logo.png">
  
  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?php echo $canonicalUrl; ?>">
  <meta name="twitter:title" content="<?php echo $title; ?>">
  <meta name="twitter:description" content="<?php echo $description; ?>">
  <meta name="twitter:image" content="https://degreelibrary.gt.tc/logo.png">
  
  <!-- JSON-LD Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "CreativeWork",
    "name": <?php echo json_encode($material_name_clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    "description": <?php echo json_encode("Download " . $material_name_clean . " uploaded by " . $uploader_clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    "genre": <?php echo json_encode($category_clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    "keywords": <?php echo json_encode($tags_clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    "author": {
      "@type": "Person",
      "name": <?php echo json_encode($uploader_clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
    },
    "datePublished": "<?php echo $datePublished; ?>",
    "url": "<?php echo $canonicalUrl; ?>"
  }
  </script>  <link rel="icon" type="image/png" href="/favicon.png">  <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap"></noscript>
  <link rel="stylesheet" href="/style.min.css?v=3.0.0">  <script>    window.INITIAL_ROUTE = ''; // To prevent overriding by JS if not intended  </script></head><body>  <header>    <div class="header-container">      <div class="header-left">        <div style="display: flex; flex-direction: column;">          <a href="/" class="brand">Free Degree Library</a>          <div class="brand-subtitle">Powered by: <a href="https://cirravosolutions.co.in/" target="_blank"              rel="noopener noreferrer">Cirravo Solutions</a></div>        </div>      </div>      <nav>        <a href="/">Home</a>        <a href="/upload.html">Upload</a>        <a href="/community.html">Community<span class="blinking-dot"></span></a>        <a href="/mca.html">MCA</a>        <a href="#" onclick="openSubscribeModal(event)" class="subscribe-btn">Subscribe <span            class="subscribe-count">0</span></a>        </nav>    </div>  </header>  <div id="marqueeContainer"></div>  <main style="max-width: 800px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">    <h1 style="font-size: 24px; color: #1E293B; margin-bottom: 10px;"><?php echo htmlspecialchars($material['name']); ?></h1>    <div style="margin-bottom: 20px; color: #475569; font-size: 14px;">      <p><strong>Uploader:</strong> <?php echo htmlspecialchars($material['uploader']); ?></p>      <p><strong>Category:</strong> <?php echo htmlspecialchars($material['category']); ?></p>      <p><strong>Tags:</strong> <?php echo htmlspecialchars($material['tags']); ?></p>      <p><strong>Uploaded On:</strong> <?php echo date('d/m/Y', strtotime($material['created_at'])); ?></p>    </div>    <div style="margin-top: 30px;">      <a href="<?php echo htmlspecialchars($material['file_path']); ?>" download="<?php echo htmlspecialchars($material['file_name']); ?>" onclick="event.preventDefault(); downloadFile('<?php echo $material['id']; ?>', '<?php echo htmlspecialchars($material['file_name']); ?>')" style="display: inline-block; padding: 10px 20px; background-color: #4F46E5; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">Download Material</a>      <button onclick="shareContent('<?php echo addslashes(htmlspecialchars($material['name'])); ?>', 'Check out this study material on Free Degree Library', '/material/<?php echo htmlspecialchars($slug); ?>')" style="display: inline-block; padding: 10px 20px; background-color: #E2E8F0; color: #1E293B; text-decoration: none; border-radius: 4px; font-weight: bold; border: none; cursor: pointer; margin-left: 10px;">Share</button>    </div>    <div style="margin-top: 40px;">        <a href="/" style="color: #4F46E5; text-decoration: underline;">&larr; Back to Search</a>    </div>  </main>  <!-- We reuse the app.min.js?v=3.0.0 for download ad logic and sidebar -->  <script src="/app.min.js?v=3.0.0"></script></body></html>