<?php
session_start();
$root = dirname(__DIR__);
$file = $root . '/data/impact-content.json';

function readImpactData($path) {
    if (!is_file($path)) return [];
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function saveImpactData($path, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents($path, $json . PHP_EOL, LOCK_EX) !== false;
}

function cleanImpactText($value, $maxLength) {
    $value = trim((string) $value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
}

$defaults = [
    '2022-2024' => [
        'title' => 'Impact from 2022 to 2024',
        'summary' => 'Use the admin page to add a summary for this period.',
        'details' => 'Add the activities, projects, outreach, achievements, and other impact you want to document for 2022 to 2024.'
    ],
    '2024-2026' => [
        'title' => 'Impact from 2024 to 2026',
        'summary' => 'Use the admin page to add a summary for this period.',
        'details' => 'Add the activities, projects, outreach, achievements, and other impact you want to document for 2024 to 2026.'
    ],
    '2026-2028' => [
        'title' => 'Impact from 2026 to 2028',
        'summary' => 'Use the admin page to add a summary for this period.',
        'details' => 'Add the activities, projects, outreach, achievements, and other impact you want to document for 2026 to 2028.'
    ]
];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $periods = [];
    foreach (array_keys($defaults) as $period) {
        $periods[$period] = [
            'title' => cleanImpactText($_POST['title'][$period] ?? '', 160),
            'summary' => cleanImpactText($_POST['summary'][$period] ?? '', 500),
            'details' => cleanImpactText($_POST['details'][$period] ?? '', 5000)
        ];
    }

    if (saveImpactData($file, ['periods' => $periods, 'updated' => date('c')])) {
        $message = 'Impact pages updated.';
    } else {
        $error = 'The Impact pages could not be updated. Check folder permissions.';
    }
}

$data = readImpactData($file);
$periods = isset($data['periods']) && is_array($data['periods']) ? $data['periods'] : $defaults;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Impact | Sugar Code It</title>
  <link rel="stylesheet" href="/styles.css">
  <style>
    .admin-breadcrumb-wrap {
      background: #0d1b2d;
      border-bottom: 1px solid rgba(255, 255, 255, 0.10);
    }
    .admin-breadcrumb {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 18px 0;
      font-weight: 800;
      flex-wrap: wrap;
    }
    .admin-breadcrumb a,
    .admin-breadcrumb span {
      display: inline-block;
      padding: 9px 16px;
      border-radius: 999px;
      text-decoration: none;
    }
    .admin-breadcrumb a {
      background: #6f86ff;
      border: 1px solid #6f86ff;
      color: #ffffff;
    }
    .admin-breadcrumb a:hover {
      background: #8296ff;
    }
    .admin-breadcrumb .separator {
      padding: 0;
      color: #9fb0c7;
    }
    .admin-breadcrumb .current {
      background: transparent;
      border: 1px solid #6f86ff;
      color: #dce4ff;
    }
    .impact-admin-item {
      padding: 24px 0;
      border-bottom: 1px solid rgba(255,255,255,0.10);
    }
    .impact-admin-item:last-child {
      border-bottom: 0;
    }
  </style>
</head>
<body class="admin-body">
<header class="nav">
  <div class="container navin">
    <a class="brand" href="/index.html">
      <img src="/assets/logo.png" alt="Sugar Code It logo">
      <span>Sugar Code It</span>
    </a>
    <div class="admin-nav-note">Impact management</div>
  </div>
</header>

<div class="admin-breadcrumb-wrap">
  <div class="container admin-breadcrumb" aria-label="Admin breadcrumb">
    <a href="/admin/">Admin Dashboard</a>
    <span class="separator">/</span>
    <span class="current">Impact</span>
  </div>
</div>

<main>
  <section class="page-hero admin-hero">
    <div class="container">
      <span class="eyebrow">Impact</span>
      <h1>Manage Impact.</h1>
      <p>Update the public content for each Impact period.</p>
      <div class="form-actions">
        <a class="button secondary" href="/admin/">Back to Admin</a>
        <a class="button secondary" href="/impact.html" target="_blank" rel="noopener">View Impact Page</a>
      </div>
    </div>
  </section>

  <section>
    <div class="container">
      <?php if ($message !== ''): ?>
        <div class="admin-message success"><?php echo h($message); ?></div>
      <?php endif; ?>
      <?php if ($error !== ''): ?>
        <div class="admin-message error"><?php echo h($error); ?></div>
      <?php endif; ?>

      <div class="admin-panel">
        <form class="editor-form admin-form" method="post">
          <input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']); ?>">

          <?php foreach ($defaults as $period => $default): $item = $periods[$period] ?? $default; ?>
            <div class="impact-admin-item">
              <h3><?php echo h(str_replace('-', ' to ', $period)); ?></h3>
              <label>
                Page heading
                <input type="text" name="title[<?php echo h($period); ?>]" maxlength="160" value="<?php echo h($item['title'] ?? ''); ?>">
              </label>
              <label>
                Short summary
                <textarea name="summary[<?php echo h($period); ?>]" maxlength="500" rows="3"><?php echo h($item['summary'] ?? ''); ?></textarea>
              </label>
              <label>
                Main content
                <textarea name="details[<?php echo h($period); ?>]" maxlength="5000" rows="8"><?php echo h($item['details'] ?? ''); ?></textarea>
              </label>
              <a class="button secondary" href="/impact-<?php echo h($period); ?>.html" target="_blank" rel="noopener">View this page</a>
            </div>
          <?php endforeach; ?>

          <button class="button" type="submit">Save Impact Pages</button>
        </form>
      </div>
    </div>
  </section>
</main>

<footer class="footer">
  <div class="container footer-grid">
    <div><strong style="color:white">Sugar Code It</strong><br>Private Impact management</div>
    <div><a href="/admin/">Back to Admin</a></div>
    <div><a href="/impact.html">Public Impact</a></div>
  </div>
</footer>
</body>
</html>
