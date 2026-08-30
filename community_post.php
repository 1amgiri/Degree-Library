<?php
// community_post.php
require_once 'api/db.php';
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    echo "<div style='text-align: center; padding: 50px; font-family: sans-serif;'><img src='/error_cat.svg' style='height: 150px; margin-bottom: 20px; opacity: 0.8;'><h1>404 Not Found</h1></div>";
    exit;
}
// Check if slug column exists
$slug_exists = false;
$colRes = $conn->query("SHOW COLUMNS FROM community_posts LIKE 'slug'");
if ($colRes && $colRes->num_rows > 0) {
    $slug_exists = true;
}
$fallback_id = 0;
if (preg_match('/-(\d+)$/', $slug, $matches)) {
    $fallback_id = (int)$matches[1];
} elseif (preg_match('/^post-(\d+)$/', $slug, $matches)) {
    $fallback_id = (int)$matches[1];
} elseif (is_numeric($slug)) {
    $fallback_id = (int)$slug;
}
if ($slug_exists) {
    $stmt = $conn->prepare("SELECT * FROM community_posts WHERE slug = ? OR id = ?");
    $stmt->bind_param("si", $slug, $fallback_id);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    // Fallback if slug column doesn't exist yet
    $allRes = $conn->query("SELECT * FROM community_posts");
    $foundId = 0;
    while($row = $allRes->fetch_assoc()) {
        $text = preg_replace('/<(style|script|svg)[^>]*>.*?<\/\1>/is', '', $row['content'] ?? '');
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
        if (strlen($text) > 60) {
            $text = substr($text, 0, 60);
            $lastSpace = strrpos($text, ' ');
            if ($lastSpace !== false && $lastSpace > 0) {
                $text = substr($text, 0, $lastSpace);
            }
        }
        $gen = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
        $gen = preg_replace('/-+/', '-', $gen);
        $gen = trim($gen, '-') ?: 'post';
        if ($gen === $slug || $row['id'] == $slug || 'post-' . $row['id'] === $slug || ($fallback_id > 0 && $row['id'] == $fallback_id)) {
            $foundId = $row['id'];
            break;
        }
    }
    if ($foundId > 0) {
        $stmt = $conn->prepare("SELECT * FROM community_posts WHERE id = ?");
        $stmt->bind_param("i", $foundId);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query("SELECT * FROM community_posts WHERE id = 0");
    }
}
if ($res->num_rows === 0) {
    header("HTTP/1.0 404 Not Found");
    echo "<div style='text-align: center; padding: 50px; font-family: sans-serif;'><img src='/error_cat.svg' style='height: 150px; margin-bottom: 20px; opacity: 0.8;'><h1>404 Not Found - Post does not exist.</h1></div>";
    exit;
}
$post = $res->fetch_assoc();
$author = $post['name'] ?? 'Guest';
$author_clean = strip_tags($author);
$content_clean = preg_replace('/<(style|script|svg)[^>]*>.*?<\/\1>/is', '', $post['content'] ?? '');
$content_clean = trim(preg_replace('/\s+/', ' ', strip_tags($content_clean)));
$excerpt = substr($content_clean, 0, 150) . (strlen($content_clean) > 150 ? '...' : '');
if (empty(trim($excerpt))) {
    $excerpt = "Check out this community post and discussion on Free Degree Library.";
}
$title = htmlspecialchars("Discussion by " . $author_clean . " - Free Degree Library Community", ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars("Join the discussion by " . $author_clean . " on Free Degree Library: " . $excerpt, ENT_QUOTES, 'UTF-8');
$post_slug_param = !empty($post['slug']) ? $post['slug'] : $slug;
$canonicalUrl = "https://degreelibrary.gt.tc/community/" . htmlspecialchars($post_slug_param, ENT_QUOTES, 'UTF-8');
$ogImage = !empty($post['image_path']) ? "https://degreelibrary.gt.tc/" . htmlspecialchars($post['image_path'], ENT_QUOTES, 'UTF-8') : "https://degreelibrary.gt.tc/logo.png";
$datePublished = !empty($post['created_at']) ? date('c', strtotime($post['created_at'] . ' +12 hours')) : '';
?><!DOCTYPE html><html lang="en"><head>
<script>
(function() {
    var ua = navigator.userAgent.toLowerCase();
    var isApp = window.matchMedia('(display-mode: standalone)').matches || 
                window.navigator.standalone || 
                ua.includes('wv') || 
                (ua.includes('android') && ua.includes('version/'));
    if (isApp || window.location.search.includes('app=true')) {
        document.documentElement.classList.add('is-android-app');
    }
})();
</script>
  <!-- Google Tag Manager & Google tag (gtag.js) Deferred for Core Web Vitals -->
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
  <meta name="keywords" content="community discussion, student posts, Q&A, degree library, <?php echo htmlspecialchars($author_clean, ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="author" content="<?php echo htmlspecialchars($author_clean, ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="canonical" href="<?php echo $canonicalUrl; ?>">
  
  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="article">
  <meta property="og:url" content="<?php echo $canonicalUrl; ?>">
  <meta property="og:site_name" content="Degree Library" />
  <meta property="og:title" content="<?php echo $title; ?>">
  <meta property="og:description" content="<?php echo $description; ?>">
  <meta property="og:image" content="<?php echo $ogImage; ?>">
  
  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?php echo $canonicalUrl; ?>">
  <meta name="twitter:title" content="<?php echo $title; ?>">
  <meta name="twitter:description" content="<?php echo $description; ?>">
  <meta name="twitter:image" content="<?php echo $ogImage; ?>">
  
  <!-- JSON-LD Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "DiscussionForumPosting",
    "headline": <?php echo json_encode("Discussion by " . $author_clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    "text": <?php echo json_encode($excerpt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    "datePublished": "<?php echo $datePublished; ?>",
    "author": {
      "@type": "Person",
      "name": <?php echo json_encode($author_clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
    },
    "url": "<?php echo $canonicalUrl; ?>"
  }
  </script>
  <style>
    .post-body {
        overflow-x: auto;
        max-width: 100%;
    }
    .post-body * {
        max-width: 100% !important;
        position: static !important;
        float: none !important;
    }
    .post-body img, .post-body video {
        height: auto !important;
    }
    .post-body img, .post-body iframe, .post-body video {
        display: block;
        margin: 10px 0;
    }
    .post-item {
        position: relative;
        z-index: 1;
        overflow: hidden; /* Prevent horizontal scrollbars spilling out */
    }
    .clear-both {
        clear: both;
    }
  </style>
  <link rel="icon" type="image/png" href="/favicon.png">  <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap"></noscript>
  <link rel="stylesheet" href="/style.min.css?v=6.6.0"></head><body>
    <div id="splashScreen" style="display:none;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 250" style="max-width: 400px; width: 90%; height: auto;">
  <style>
    /* Importing a Google font that closely matches the rounded, bold look of logo.jpg */
    @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@700&amp;display=swap');
    
    .bg { fill: transparent; }
    
    .main-text {
      font-family: 'Quicksand', sans-serif;
      font-size: 72px;
      font-weight: 700;
      fill: transparent;
      stroke: #ffffff;
      stroke-width: 2.5;
      stroke-dasharray: 800;
      stroke-dashoffset: 800;
      /* Animates the stroke drawing, then fills the text solid */
      animation: drawText 2s ease-in-out forwards, fillText 1s 1.8s ease-in-out forwards;
    }

    .sub-text {
      font-family: 'Quicksand', sans-serif;
      font-size: 28px;
      font-weight: 700;
      fill: #ffffff;
      opacity: 0;
      /* Fades in after the main text finishes drawing */
      animation: fadeIn 1s 2.5s ease-in-out forwards;
    }

    .accent {
      fill: #3924ff; /* Matching the vibrant blue from logo.jpg */
    }

    .line {
      stroke: #ffffff;
      stroke-width: 2;
      opacity: 0;
      animation: fadeIn 1s 2.5s ease-in-out forwards;
    }

    /* Keyframes for the animation */
    @keyframes drawText {
      100% { stroke-dashoffset: 0; }
    }

    @keyframes fillText {
      100% { fill: #ffffff; stroke-width: 0; }
    }

    @keyframes fadeIn {
      100% { opacity: 1; }
    }
  </style>

  <!-- Main Animated Text -->
  <text x="50%" y="45%" text-anchor="middle" dominant-baseline="central" class="main-text">
    Free Degree Library
  </text>

  <!-- Sub Text / Footer matching logo.jpg layout -->
  <g class="sub-text">
    <text x="50%" y="80%" text-anchor="middle" dominant-baseline="central">
      Powered By: <tspan class="accent">Cirravo Solutions</tspan>
    </text>
  </g>
  
  <!-- Decorative Side Lines -->
  <line x1="12%" y1="80%" x2="23%" y2="80%" class="line" />
  <line x1="77%" y1="80%" x2="88%" y2="80%" class="line" />
</svg></div><script>if(!sessionStorage.getItem("splashShown")){document.getElementById("splashScreen").style.display="flex";sessionStorage.setItem("splashShown","true");}</script>      <header class="modern-header">
    <div class="nav-left">
      <div style="display: flex; align-items: center; min-width: 0;">
        <!-- Desktop Hamburger Menu (YouTube Style) -->
        <button class="menu-btn-desktop" onclick="toggleSidebar()" aria-label="Toggle menu" style="background:transparent; border:none; outline:none; box-shadow:none; cursor:pointer; color: #ffffff; margin-right: 15px; display: flex; align-items: center; justify-content: center; padding: 0; flex-shrink: 0;">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
        </button>
        <a href="/" class="brand-logo" style="display:flex; align-items:center; text-decoration:none; min-width: 0;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 250" style="height: 70px; width: auto; max-width: 100%;">
  <style>
    /* Importing a Google font that closely matches the rounded, bold look of logo.jpg */
    @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@700&amp;display=swap');
    
    .bg { fill: transparent; }
    
    .main-text {
      font-family: 'Quicksand', sans-serif;
      font-size: 72px;
      font-weight: 700;
      fill: transparent;
      stroke: #ffffff;
      stroke-width: 2.5;
      stroke-dasharray: 800;
      stroke-dashoffset: 800;
      /* Animates the stroke drawing, then fills the text solid */
      animation: drawText 2s ease-in-out forwards, fillText 1s 1.8s ease-in-out forwards;
    }

    .sub-text {
      font-family: 'Quicksand', sans-serif;
      font-size: 28px;
      font-weight: 700;
      fill: #ffffff;
      opacity: 0;
      /* Fades in after the main text finishes drawing */
      animation: fadeIn 1s 2.5s ease-in-out forwards;
    }

    .accent {
      fill: #3924ff; /* Matching the vibrant blue from logo.jpg */
    }

    .line {
      stroke: #ffffff;
      stroke-width: 2;
      opacity: 0;
      animation: fadeIn 1s 2.5s ease-in-out forwards;
    }

    /* Keyframes for the animation */
    @keyframes drawText {
      100% { stroke-dashoffset: 0; }
    }

    @keyframes fillText {
      100% { fill: #ffffff; stroke-width: 0; }
    }

    @keyframes fadeIn {
      100% { opacity: 1; }
    }
  </style>

  <!-- Main Animated Text -->
  <text x="50%" y="45%" text-anchor="middle" dominant-baseline="central" class="main-text">
    Free Degree Library
  </text>

  <!-- Sub Text / Footer matching logo.jpg layout -->
  <g class="sub-text">
    <text x="50%" y="80%" text-anchor="middle" dominant-baseline="central">
      Powered By: <tspan class="accent">Cirravo Solutions</tspan>
    </text>
  </g>
  
  <!-- Decorative Side Lines -->
  <line x1="12%" y1="80%" x2="23%" y2="80%" class="line" />
  <line x1="77%" y1="80%" x2="88%" y2="80%" class="line" />
</svg>
        </a>
      </div>
      <div style="display: flex; align-items: center; gap: 15px; flex-shrink: 0; color: white;">
        <!-- Mobile Subscribe Bell -->
        <a href="#" onclick="openSubscribeModal(event)" class="nav-icon-link mobile-subscribe-btn" title="Subscribe" style="color: white; padding: 0; margin: 0; display: none;">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </a>
        <!-- Mobile Help Button -->
        <a href="#" onclick="openHelpModal(event)" class="nav-icon-link mobile-help-btn" title="Help" style="color: white; padding: 0; margin: 0; display: none;">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
        </a>
      </div>
    </div>

    <div class="nav-center">
      <div class="search-container-header" style="position: relative; width: 100%;">
        <input type="text" id="searchQuery" placeholder="Search materials..." aria-label="Search materials" class="search-input-header" autocomplete="off" oninput="document.getElementById('clearSearchBtnHeader').style.display = this.value ? 'block' : 'none';" />
        <span id="clearSearchBtnHeader" style="position: absolute; right: 70px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 16px; color: #888; display: none;" onclick="document.getElementById('searchQuery').value=''; document.getElementById('searchQuery').dispatchEvent(new Event('input')); this.style.display='none';">✖</span>
        <button class="search-btn-header" onclick="document.getElementById('searchQuery').focus()" aria-label="Search">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </button>
      </div>
    </div>

    <div class="nav-right">
      <a href="/" class="nav-icon-link" title="Home">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span class="nav-icon-text">Home</span>
      </a>
      <a href="/community.html" class="nav-icon-link" title="Community" style="position: relative;">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span class="blinking-dot" style="position:absolute; top:24px; right:calc(50% - 18px);"></span>
        <span class="nav-icon-text">Community</span>
      </a>
      <a href="/upload.html" class="nav-icon-link" title="Add Notes">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        <span class="nav-icon-text">Add</span>
      </a>
      <a href="/mca.html" class="nav-icon-link" title="MCA">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        <span class="nav-icon-text">MCA</span>
      </a>
      <a href="#" onclick="openHelpModal(event)" class="nav-icon-link desktop-help-btn" title="Help">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
        <span class="nav-icon-text">Help</span>
      </a>
      <a href="#" onclick="openSubscribeModal(event)" class="modern-subscribe-btn">Subscribe </a>
      
      <!-- Menu button now styled exactly like the rest -->
      <button class="nav-icon-link menu-btn-mobile" onclick="toggleSidebar()" aria-label="Toggle menu" style="background:transparent; border:none; outline:none; box-shadow:none; cursor:pointer;">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
        <span class="nav-icon-text">Menu</span>
      </button>
    </div>
  </header>  <div id="marqueeContainer"></div>  <main style="max-width: 800px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">    <div class="post-item" style="border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; background: #f8fafc; position: relative; z-index: 1;">        <div class="post-header" style="display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid #cbd5e1; padding-bottom: 10px;">          <span style="font-weight: bold; color: #0f172a; display: inline-flex; align-items: center;"><?php echo ($post['is_admin'] && stripos($author, 'giri') !== false) ? '<img src="https://res.cloudinary.com/y6hvobnk/image/upload/v1788084727/Admin_logo.png" style="width: 24px; height: 24px; border-radius: 50%; margin-right: 8px; vertical-align: middle; object-fit: cover;" alt="Admin" />' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#94a3b8" style="margin-right: 8px; border-radius: 50%; vertical-align: middle;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>'; ?><?php echo $post['is_admin'] ? '<span style="color: navy; border-bottom: 1px dotted navy; display: inline-flex; align-items: center; gap: 4px;">' . $author . '</span>' : $author; ?></span>          <span style="color: #64748b;"><?php echo date('d M Y H:i', strtotime($post['created_at'] . ' +12 hours')); ?></span>        </div>        <div class="post-body clear-both" style="color: #334155; line-height: 1.6; font-size: 16px; word-wrap: break-word; overflow-wrap: break-word; max-width: 100%;">
<?php 
$content = trim($post['content']);
// Strip leading spaces and non-breaking spaces (including right after the first HTML tag)
$content = preg_replace('/^(\s|&nbsp;)+/i', '', $content);
$content = preg_replace('/^(<[a-z0-9]+[^>]*>)(\s|&nbsp;)+/i', '$1', $content);

if ($post['allow_html']) {
    echo $content;
} else {
    $escaped = htmlspecialchars($content);
    $escaped = preg_replace_callback(
        '/(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/i',
        function($matches) {
            $url = $matches[1];
            $displayUrl = strlen($url) > 30 ? substr($url, 0, 27) . '...' : $url;
            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" style="color: #4F46E5; text-decoration: underline;">' . $displayUrl . '</a>';
        },
        $escaped
    );
    echo nl2br($escaped);
}
?>
        </div>        <?php if (!empty($post['image_path'])): ?>        <div class="post-image-container clear-both" style="margin-top: 20px; max-width: 100%;">            <a href="/<?php echo htmlspecialchars($post['image_path']); ?>" target="_blank" style="display: block; cursor: pointer;">                <img src="/<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post Image" style="max-width: 100%; height: auto; border-radius: 4px; display: block; position: static !important; margin: 0 auto;">            </a>        </div>        <?php endif; ?>    </div>    <div class="clear-both" style="margin-top: 40px; position: relative; z-index: 2; padding-bottom: 20px;">        <a href="/community.html" style="color: #4F46E5; text-decoration: underline; font-weight: bold; display: inline-block;">&larr; Back to Community Board</a>    </div>  </main>
  <script>
    // Make sure any pasted images are statically positioned, properly scaled, and open in full view when clicked.
    document.addEventListener("DOMContentLoaded", function() {
        var postBodyImages = document.querySelectorAll('.post-body img');
        postBodyImages.forEach(function(img) {
            img.style.position = "static";
            img.style.display = "block";
            img.style.maxWidth = "100%";
            img.style.height = "auto";
            img.style.marginBottom = "10px";
            img.style.cursor = "pointer";
            
            img.onclick = function(e) {
                e.preventDefault();
                window.open(img.src, '_blank');
            };
        });
        
        // Also force static position on iframes and embeds
        var embeds = document.querySelectorAll('.post-body iframe, .post-body embed, .post-body object');
        embeds.forEach(function(el) {
            el.style.position = "static";
            el.style.maxWidth = "100%";
        });
    });
  </script>
  <script src="/app.min.js?v=5.3.0"></script></body></html>