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

$shareText = $material_name_clean . "\n";
$shareText .= "Uploader: " . $uploader_clean . "\n\n";
$shareText .= "Category: " . $category_clean . "\n\n";
$shareText .= "Tags: " . $tags_clean . "\n\n";
$shareText .= "Uploaded On: " . (!empty($material['created_at']) ? date('d/m/Y', strtotime($material['created_at'])) : '') . "\n";

$mat_slug_param = !empty($material['slug']) ? $material['slug'] : $slug;
$canonicalUrl = "https://degreelibrary.gt.tc/material/" . htmlspecialchars($mat_slug_param ?? '', ENT_QUOTES, 'UTF-8');
$downloadUrl = "/api/download.php?id=" . $material['id'];
$datePublished = !empty($material['created_at']) ? date('c', strtotime($material['created_at'])) : '';
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
  <meta name="keywords" content="<?php echo htmlspecialchars($tags_clean . ", " . $category_clean . ", study material, academic notes", ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="author" content="<?php echo htmlspecialchars($uploader_clean, ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="canonical" href="<?php echo $canonicalUrl; ?>">
  
  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="article">
  <meta property="og:url" content="<?php echo $canonicalUrl; ?>">
  <meta property="og:site_name" content="Degree Library" />
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
  <link rel="stylesheet" href="/style.min.css?v=6.9.1">  <script>    window.INITIAL_ROUTE = ''; // To prevent overriding by JS if not intended  </script></head><body>
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
        <span id="clearSearchBtnHeader" style="position: absolute; right: 70px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 16px; color: #888; display: none;" onclick="document.getElementById('searchQuery').value=''; document.getElementById('searchQuery').dispatchEvent(new Event('input')); this.style.display='none';">âœ–</span>
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
  </header>  <div id="marqueeContainer"></div>  <main style="max-width: 800px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">    <h1 style="font-size: 24px; color: #1E293B; margin-bottom: 10px;"><?php echo htmlspecialchars($material['name']); ?></h1>    <div style="margin-bottom: 20px; color: #475569; font-size: 14px;">      <p><strong>Uploader:</strong> <?php echo htmlspecialchars($material['uploader']); ?></p>      <p><strong>Category:</strong> <?php echo htmlspecialchars($material['category']); ?></p>      <p><strong>Tags:</strong> <?php echo htmlspecialchars($material['tags']); ?></p>      <p><strong>Uploaded On:</strong> <?php echo date('d M Y', strtotime($material['created_at'])); ?></p>    </div>    <div style="margin-top: 30px;">      <a href="<?php echo htmlspecialchars($material['file_path']); ?>" download="<?php echo htmlspecialchars($material['file_name']); ?>" onclick="event.preventDefault(); downloadFile('<?php echo $material['id']; ?>', '<?php echo htmlspecialchars($material['file_name']); ?>')" style="display: inline-block; padding: 10px 20px; background-color: #4F46E5; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">Download Material</a>      <button onclick="shareContent('<?php echo addslashes(htmlspecialchars($material_name_clean)); ?>', <?php echo htmlspecialchars(json_encode($shareText), ENT_QUOTES, 'UTF-8'); ?>, '/material/<?php echo htmlspecialchars($slug); ?>')" style="display: inline-block; padding: 10px 20px; background-color: #E2E8F0; color: #1E293B; text-decoration: none; border-radius: 4px; font-weight: bold; border: none; cursor: pointer; margin-left: 10px;">Share</button>    </div>    <div style="margin-top: 40px;">        <a href="/" style="color: #4F46E5; text-decoration: underline;">&larr; Back to Search</a>    </div>  </main>  <!-- We reuse the app.min.js?v=5.3.0 for download ad logic and sidebar -->  <script src="/app.min.js?v=5.3.0"></script>  <!-- Sidebar overlay backdrop -->
  <div id="sidebarOverlay" class="sidebar-overlay" onclick="closeSidebar()"></div>
  <!-- Sidebar Menu Drawer -->
  <div id="sidebarMenu" class="sidebar">
    <div class="sidebar-header">
      <span class="sidebar-title">MENU</span>
      <button class="sidebar-close" aria-label="Close menu" onclick="closeSidebar()">X</button>
    </div>
    <div class="sidebar-content">
      <!-- Section 2: Browse Content -->
      <div class="menu-section">
        <div class="menu-section-title">Browse Content</div>
        <a href="index.html" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="home"></i></span> Home
        </a>
        <a href="community.html" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="message-circle"></i></span> Student Community
        </a>
        <a href="upload.html" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="upload-cloud"></i></span> Upload Materials
        </a>
        <a href="important.html" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="zap"></i></span> Important Questions
        </a>
        <a href="mca.html" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="graduation-cap"></i></span> MCA
        </a>
        <a href="icet.html" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="graduation-cap"></i></span> ICET PYQs
        </a>
        <a href="admin.html" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="key"></i></span> Admin Panel
        </a>
      </div>
      <!-- Section 3: Help -->
      <div class="menu-section">
        <div class="menu-section-title">Help</div>
        <div class="menu-item-dropdown">
          <div class="menu-item" style="cursor: pointer; justify-content: space-between;" onclick="toggleMenuDropdown(this)">
            <span style="display: flex; align-items: center; gap: 12px;"><span class="menu-item-icon"><i data-lucide="book-open"></i></span> Official Syllabus Content</span>
            <span class="arrow">â–¾</span>
          </div>
          <div class="menu-dropdown-content" style="display: none; padding-left: 20px;">
            <div style="padding: 10px 15px; color: #818CF8; font-size: 12px; font-weight: bold;">UG</div>
            <a href="https://svuniversity.edu.in/degree-course-syllabus/" target="_blank" rel="noopener noreferrer" class="menu-item" style="border-bottom: none; padding-top: 5px; padding-bottom: 5px;">
              <span class="menu-item-icon"><i data-lucide="graduation-cap"></i></span> SV University
            </a>
            <a href="https://cuap.ac.in/syllabus/" target="_blank" rel="noopener noreferrer" class="menu-item" style="border-bottom: none; padding-top: 5px; padding-bottom: 5px;">
              <span class="menu-item-icon"><i data-lucide="graduation-cap"></i></span> Andhra University
            </a>
            <div style="padding: 10px 15px; color: #818CF8; font-size: 12px; font-weight: bold; margin-top: 5px;">PG</div>
            <a href="https://svuniversity.edu.in/pg-course-syllabus/" target="_blank" rel="noopener noreferrer" class="menu-item" style="border-bottom: none; padding-top: 5px; padding-bottom: 5px;">
              <span class="menu-item-icon"><i data-lucide="graduation-cap"></i></span> SV University
            </a>
            <a href="https://andhrauni-ac.in/admissions/school-of-distance-education/pgcourses.html" target="_blank" rel="noopener noreferrer" class="menu-item" style="border-bottom: none; padding-top: 5px; padding-bottom: 5px;">
              <span class="menu-item-icon"><i data-lucide="graduation-cap"></i></span> Andhra University
            </a>
          </div>
        </div>
        <a href="https://cets.apsche.ap.gov.in/APSCHE/APSCHEHome.aspx" target="_blank" rel="noopener noreferrer" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="landmark"></i></span> APSHE
        </a>
        <div class="menu-item-dropdown">
          <div class="menu-item" style="cursor: pointer; justify-content: space-between;" onclick="toggleMenuDropdown(this)">
            <span style="display: flex; align-items: center; gap: 12px;"><span class="menu-item-icon"><i data-lucide="file-text"></i></span> Results</span>
            <span class="arrow">â–¾</span>
          </div>
          <div class="menu-dropdown-content" style="display: none; padding-left: 20px;">
            <a href="https://www.manabadi.co.in/" target="_blank" rel="noopener noreferrer" class="menu-item" style="border-bottom: none;">
              <span class="menu-item-icon"><i data-lucide="search"></i></span> Results(Manabadi)
            </a>
            <a href="https://g21.tcsion.com/per/g21/pub/1723/SelfServices/templates/sdham15012018101517/Sdham515012018101605.html" target="_blank" rel="noopener noreferrer" class="menu-item" style="border-bottom: none;">
              <span class="menu-item-icon"><i data-lucide="school"></i></span> Results(TCSion)
            </a>
          </div>
        </div>
        <a href="https://pixels111.github.io/bcahub/index.html" target="_blank" rel="noopener noreferrer" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="book-open"></i></span> BCA Resources
        </a>
      </div>
      <!-- Section 4: Other Products -->
      <div class="menu-section">
        <div class="menu-section-title">Our Other Free Products</div>
        <a href="https://buildmyresume.free.nf/" target="_blank" rel="noopener noreferrer" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="briefcase"></i></span> ATS Friendly Resume Builder
        </a>
        <a href="https://upiqr.ct.ws/" target="_blank" rel="noopener noreferrer" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="smartphone"></i></span> UPI QR Generator
        </a>
        <a href="https://cirravosolutions.co.in/safex/index.html?i=1" target="_blank" rel="noopener noreferrer" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="lock"></i></span> Offline Password Manager
        </a>
        <a href="https://1amgiri.itch.io/ilikecode" target="_blank" rel="noopener noreferrer" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="smartphone"></i></span> iLikeCode - Mobile Coding
        </a>
      </div>
      <!-- Section 5: Extras -->
      <div class="menu-section" style="border-top: 1px dashed #334155; padding-top: 10px;">
        <a href="#" onclick="alertBugReport(event)" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="alert-triangle"></i></span> Report Error/Bug
        </a>
        <a href="about.html" class="menu-item" style="background: #EEF2FF; font-weight: bold; color: #4F46E5;">
          <span class="menu-item-icon"><i data-lucide="info"></i></span> About Free Degree Library
        </a>
        <a href="admin.html" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="key"></i></span> Admin Login
        </a>
        <a href="https://www.instagram.com/cirravo/" target="_blank" rel="noopener noreferrer" class="menu-item">
          <span class="menu-item-icon"><i data-lucide="instagram"></i></span> Follow on Instagram
        </a>
      </div>
    </div>
  </div>

</body></html>