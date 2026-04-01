<?php
header('X-Robots-Tag: noindex, nofollow', true);

$toolFiles = glob(__DIR__ . DIRECTORY_SEPARATOR . '*.html') ?: [];
sort($toolFiles, SORT_NATURAL | SORT_FLAG_CASE);

$descriptions = [
  '4x6_label_generator_improved_layout.html' => 'Generate LPN pallet or carton labels with barcode output and print-ready 4x6 formatting.',
  'pallet_builder.html' => 'Calculate pallets required, layer placement, and build instructions for Canadian pallet footprints.'
];

function tool_title($filename) {
  $specialTitles = [
    '4x6_label_generator_improved_layout.html' => 'LPN Generator'
  ];

  if (isset($specialTitles[$filename])) {
    return $specialTitles[$filename];
  }

  $base = pathinfo($filename, PATHINFO_FILENAME);
  $base = str_replace(['_', '-'], ' ', $base);
  $base = preg_replace('/\s+/', ' ', trim($base));
  $base = preg_replace('/\bImproved Layout\b/i', '', $base);
  $base = trim($base);

  if (stripos($base, '4x6') === 0) {
    $base = '4x6 ' . trim(substr($base, 3));
  }

  return ucwords($base);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Grey Wolf Private Tools</title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="description" content="Private Grey Wolf utility page for internal HTML tools.">
  <link rel="stylesheet" href="../style.css?v=20260324-2">
  <link rel="icon" type="image/png" href="../favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <style>
    body.tools-private-page {
      background:
        radial-gradient(circle at top left, rgba(240, 161, 74, 0.12), transparent 24%),
        linear-gradient(180deg, #08131d 0%, #0f1d2b 28%, #eef3f7 28%, #f7f9fb 100%);
      color: #102131;
      min-height: 100vh;
    }

    .tools-private-page .container {
      width: min(1180px, calc(100% - 2rem));
    }

    .tools-hero {
      color: #eef4fb;
      padding: 6rem 0 2.5rem;
    }

    .tools-eyebrow {
      color: #f0b36e;
      font-size: 0.8rem;
      font-weight: 800;
      letter-spacing: 0.16em;
      margin-bottom: 0.8rem;
      text-transform: uppercase;
    }

    .tools-hero h1 {
      color: #f7fafc;
      font-family: 'Poppins', sans-serif;
      font-size: clamp(2.4rem, 5vw, 4.1rem);
      letter-spacing: -0.05em;
      line-height: 0.98;
      margin-bottom: 1rem;
      max-width: 10ch;
    }

    .tools-hero p {
      color: #bfd0df;
      font-size: 1.02rem;
      line-height: 1.72;
      margin: 0;
      max-width: 720px;
    }

    .tools-hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.85rem;
      margin-top: 1.4rem;
    }

    .tools-hero-actions .btn {
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      padding: 0.95rem 1.35rem;
      text-decoration: none;
    }

    .tools-hero-actions .btn-primary {
      background: linear-gradient(135deg, #d86a2d 0%, #f0a14a 100%);
      color: #08131d;
    }

    .tools-hero-actions .btn-secondary {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.14);
      color: #eef4fb;
    }

    .tools-panel {
      padding: 0 0 4rem;
    }

    .tools-card {
      background: rgba(255, 255, 255, 0.82);
      border: 1px solid rgba(16, 33, 49, 0.08);
      border-radius: 28px;
      box-shadow: 0 22px 60px rgba(10, 20, 30, 0.12);
      padding: 1.6rem;
    }

    .tools-card h2 {
      color: #102131;
      font-family: 'Poppins', sans-serif;
      font-size: 1.7rem;
      margin-bottom: 0.6rem;
    }

    .tools-card p {
      color: #5b6b7a;
      line-height: 1.68;
      margin: 0;
    }

    .tools-grid {
      display: grid;
      gap: 1rem;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      margin-top: 1.5rem;
    }

    .tool-link-card {
      background: rgba(16, 33, 49, 0.04);
      border: 1px solid rgba(16, 33, 49, 0.08);
      border-radius: 24px;
      display: flex;
      flex-direction: column;
      gap: 0.9rem;
      min-height: 220px;
      padding: 1.3rem;
    }

    .tool-link-icon {
      align-items: center;
      background: rgba(13, 59, 102, 0.1);
      border-radius: 16px;
      color: #0d3b66;
      display: inline-flex;
      font-size: 1.1rem;
      height: 52px;
      justify-content: center;
      width: 52px;
    }

    .tool-link-card h3 {
      color: #102131;
      font-family: 'Poppins', sans-serif;
      font-size: 1.2rem;
      line-height: 1.2;
      margin: 0;
    }

    .tool-link-card p {
      color: #5b6b7a;
      flex: 1 1 auto;
      line-height: 1.65;
      margin: 0;
    }

    .tool-link-meta {
      color: #6c7c8c;
      font-size: 0.84rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }

    .tool-link-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.7rem;
    }

    .tool-link-actions a {
      border-radius: 999px;
      font-weight: 800;
      padding: 0.85rem 1.1rem;
      text-decoration: none;
    }

    .tool-link-actions .open-tool {
      background: #0d3b66;
      color: #eef4fb;
    }

    .tool-link-actions .open-file {
      background: rgba(16, 33, 49, 0.06);
      border: 1px solid rgba(16, 33, 49, 0.08);
      color: #102131;
    }

    .tools-empty {
      background: rgba(16, 33, 49, 0.04);
      border: 1px dashed rgba(16, 33, 49, 0.18);
      border-radius: 22px;
      color: #5b6b7a;
      margin-top: 1.4rem;
      padding: 1.2rem;
    }

    @media (max-width: 760px) {
      .tools-hero {
        padding-top: 5.2rem;
      }

      .tools-hero h1 {
        max-width: none;
      }
    }
  </style>
</head>
<body class="tools-private-page brand-page no-breadcrumbs">
  <header>
    <div class="container">
      <a class="logo site-brand" href="../index.html" aria-label="Grey Wolf 3PL home">
        <img class="brand-mark" src="../logo_wolf_invert.png" alt="Grey Wolf 3PL logo">
      </a>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-label="Toggle navigation">
        <i class="fas fa-bars"></i>
      </button>
      <nav>
        <ul>
          <li><a href="../index.html">Home</a></li>
          <li><a href="../drayage.html">Drayage</a></li>
          <li><a href="../delivery-appointment.html">Delivery Appointments</a></li>
          <li><a href="../tracking.html">Tracking</a></li>
          <li class="nav-action"><a href="index.php" class="cta-btn">Private Tools</a></li>
        </ul>
      </nav>
      <div class="header-actions">
        <a href="../index.html" class="call-btn">Back to site</a>
        <a href="index.php" class="cta-btn">Private Tools</a>
      </div>
    </div>
  </header>

  <main>
    <section class="tools-hero">
      <div class="container">
        <p class="tools-eyebrow">Internal utilities</p>
        <h1>Grey Wolf private tools.</h1>
        <p>This page is for internal Grey Wolf use only. It lists the HTML-based tools currently stored in the `tools` folder and gives you one clean place to open them quickly.</p>
        <div class="tools-hero-actions">
          <a class="btn btn-primary" href="../index.html">Back to main site</a>
          <a class="btn btn-secondary" href="../privacy.html">Privacy page</a>
        </div>
      </div>
    </section>

    <section class="tools-panel">
      <div class="container">
        <div class="tools-card">
          <h2>Available tool pages</h2>
          <p>Only `.html` tools from this folder are shown here. This page is not linked from the public site navigation and is set to `noindex`.</p>

          <?php if (!empty($toolFiles)): ?>
            <div class="tools-grid">
              <?php foreach ($toolFiles as $toolPath): ?>
                <?php
                  $filename = basename($toolPath);
                  $title = tool_title($filename);
                  $description = $descriptions[$filename] ?? 'Internal HTML utility page for Grey Wolf operations.';
                ?>
                <article class="tool-link-card">
                  <span class="tool-link-icon"><i class="fas fa-screwdriver-wrench" aria-hidden="true"></i></span>
                  <span class="tool-link-meta">HTML utility</span>
                  <h3><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>
                  <p><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></p>
                  <div class="tool-link-actions">
                    <a class="open-tool" href="<?php echo rawurlencode($filename); ?>">Open tool</a>
                    <a class="open-file" href="<?php echo rawurlencode($filename); ?>">Open file</a>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="tools-empty">No HTML tools were found in this folder yet.</div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <div class="container footer-layout">
      <div>
        <p class="footer-brand">Grey Wolf 3PL &amp; Logistics Inc</p>
        <p class="footer-copy">Internal utilities and private operational tools.</p>
      </div>
      <div class="footer-links">
        <a href="../index.html">Home</a>
        <a href="../tracking.html">Tracking</a>
        <a href="../delivery-appointment.html">Appointments</a>
        <a href="../privacy.html">Privacy</a>
      </div>
    </div>
    <p class="site-copyright">&copy; <span class="js-year"></span> Grey Wolf 3PL &amp; Logistics Inc. All rights reserved.</p>
  </footer>

  <script src="../js/site.js?v=20260330-2"></script>
</body>
</html>
