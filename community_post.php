<?php
// community_post.php
require_once 'api/db.php';
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 Not Found</h1>";
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
    echo "<h1>404 Not Found - Post does not exist.</h1>";
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
$datePublished = !empty($post['created_at']) ? date('c', strtotime($post['created_at'])) : '';
?><!DOCTYPE html><html lang="en"><head>  <script async src="https://www.googletagmanager.com/gtag/js?id=G-VHYRJJZX5H"></script>  <script>    window.dataLayer = window.dataLayer || [];    function gtag() { dataLayer.push(arguments); }    gtag('js', new Date());    gtag('config', 'G-VHYRJJZX5H');  </script>  <meta charset="UTF-8">  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?php echo $title; ?></title>
  <meta name="description" content="<?php echo $description; ?>">
  <meta name="keywords" content="community discussion, student posts, Q&A, degree library, <?php echo htmlspecialchars($author_clean, ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="author" content="<?php echo htmlspecialchars($author_clean, ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="canonical" href="<?php echo $canonicalUrl; ?>">
  
  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="article">
  <meta property="og:url" content="<?php echo $canonicalUrl; ?>">
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
  <link rel="icon" type="image/png" href="/favicon.png">  <link rel="stylesheet" href="/style.css?v=2.0.35"></head><body>  <header>    <div class="header-container">      <div class="header-left">        <div style="display: flex; flex-direction: column;">          <a href="/" class="brand">Free Degree Library</a>          <div class="brand-subtitle">Powered by: <a href="https://cirravosolutions.co.in/" target="_blank"              rel="noopener noreferrer">Cirravo Solutions</a></div>        </div>      </div>      <nav>        <a href="/">Home</a>        <a href="/upload.html">Upload</a>        <a href="/community.html" style="font-weight: bold;">Community<span class="blinking-dot"></span></a>        <a href="/mca.html">MCA</a>        <a href="#" onclick="openSubscribeModal(event)" class="subscribe-btn">Subscribe <span            class="subscribe-count">0</span></a>        </nav>    </div>  </header>  <div id="marqueeContainer"></div>  <main style="max-width: 800px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">    <div class="post-item" style="border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; background: #f8fafc;">        <div class="post-header" style="display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid #cbd5e1; padding-bottom: 10px;">          <span style="font-weight: bold; color: #0f172a;"><?php echo $post['is_admin'] ? '<span style="color: navy; border-bottom: 1px dotted navy; display: inline-flex; align-items: center; gap: 4px;">' . $author . '</span>' : $author; ?></span>          <span style="color: #64748b; font-size: 14px;"><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></span>        </div>        <div class="post-body" style="color: #334155; line-height: 1.6; font-size: 16px; word-wrap: break-word; overflow-wrap: break-word;">            <?php                 if ($post['allow_html']) {                    echo $post['content'];                } else {                    $escaped = htmlspecialchars($post['content']);                    $escaped = preg_replace_callback(                        '/(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/i',                        function($matches) {                            $url = $matches[1];                            $displayUrl = strlen($url) > 30 ? substr($url, 0, 27) . '...' : $url;                            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" style="color: #4F46E5; text-decoration: underline;">' . $displayUrl . '</a>';                        },                        $escaped                    );                    echo nl2br($escaped);                }            ?>        </div>        <?php if (!empty($post['image_path'])): ?>        <div class="post-image-container" style="margin-top: 20px;">            <img src="/<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post Image" style="max-width: 100%; border-radius: 4px;">        </div>        <?php endif; ?>        <!-- Interactive part defaults back to community board since we need JS to vote -->        <div style="margin-top: 30px;">            <a href="/community.html?post=<?php echo $post['id']; ?>" style="display: inline-block; padding: 10px 20px; background-color: #4F46E5; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">View Comments & Vote</a>            <button onclick="shareContent('Post by <?php echo addslashes(strip_tags($author)); ?>', 'Check out this post on Free Degree Library', '/community/<?php echo htmlspecialchars($slug); ?>')" style="display: inline-block; padding: 10px 20px; background-color: #E2E8F0; color: #1E293B; text-decoration: none; border-radius: 4px; font-weight: bold; border: none; cursor: pointer; margin-left: 10px;">Share</button>        </div>    </div>    <div style="margin-top: 40px;">        <a href="/community.html" style="color: #4F46E5; text-decoration: underline;">&larr; Back to Community Board</a>    </div>  </main>  <script src="/app.js?v=2.0.35"></script></body></html>