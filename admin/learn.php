<?php
session_start();

$dataFile = __DIR__ . '/../data/learn-content.json';
$message = '';
$error = '';

function loadLearnData($file) {
    if (!file_exists($file)) {
        return ['sections' => []];
    }

    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data) || !isset($data['sections']) || !is_array($data['sections'])) {
        return ['sections' => []];
    }

    return $data;
}

$data = loadLearnData($dataFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = $_POST['sections'] ?? [];
    $updated = [];

    foreach ($data['sections'] as $index => $section) {
        $row = $submitted[$index] ?? [];
        $updated[] = [
            'id' => $section['id'],
            'number' => $section['number'],
            'title' => trim($row['title'] ?? $section['title']),
            'summary' => trim($row['summary'] ?? $section['summary']),
            'level' => trim($row['level'] ?? $section['level']),
            'content' => trim($row['content'] ?? $section['content'])
        ];
    }

    $newData = ['sections' => $updated];
    $json = json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($json !== false && file_put_contents($dataFile, $json . PHP_EOL, LOCK_EX) !== false) {
        $data = $newData;
        $message = 'Learn content saved.';
    } else {
        $error = 'The Learn content could not be saved. Check that the data folder is writable.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Learn | Sugar Code It</title>
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
    }

    .admin-breadcrumb a,
    .admin-breadcrumb .current {
      display: inline-block;
      padding: 9px 16px;
      border: 1px solid #6f86ff;
      border-radius: 999px;
      text-decoration: none;
    }

    .admin-breadcrumb a {
      background: #6f86ff;
      color: #ffffff;
    }

    .admin-breadcrumb .current {
      color: #dce4ff;
    }

    .learn-admin-card {
      margin-bottom: 24px;
    }

    .admin-form label {
      display: block;
      margin: 16px 0 7px;
      font-weight: 800;
      color: #ffffff;
    }

    .admin-form input,
    .admin-form textarea {
      box-sizing: border-box;
      width: 100%;
      padding: 12px 14px;
      border: 1px solid rgba(255,255,255,.18);
      border-radius: 12px;
      background: #0d1b2d;
      color: #ffffff;
      font: inherit;
    }

    .admin-form textarea {
      min-height: 120px;
      resize: vertical;
    }

    .admin-form .full-content {
      min-height: 220px;
    }

    .save-button {
      display: inline-block;
      margin-top: 8px;
      padding: 12px 22px;
      border: 1px solid #6f86ff;
      border-radius: 999px;
      background: #6f86ff;
      color: #ffffff;
      font-weight: 800;
      cursor: pointer;
    }

    .admin-message {
      margin-bottom: 22px;
      padding: 14px 18px;
      border-radius: 12px;
      background: rgba(111, 134, 255, .15);
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
      <div class="admin-nav-note">Learn editor</div>
    </div>
  </header>

  <div class="admin-breadcrumb-wrap">
    <div class="container admin-breadcrumb" aria-label="Admin breadcrumb">
      <a href="/admin/">Admin Dashboard</a>
      <span>/</span>
      <span class="current">Learn</span>
    </div>
  </div>

  <main>
    <section class="page-hero admin-hero">
      <div class="container">
        <span class="eyebrow">Learn</span>
        <h1>Manage Learn content.</h1>
        <p>Edit the text shown on the main Learn page and the full content shown on each lesson subpage.</p>
      </div>
    </section>

    <section>
      <div class="container">
        <?php if ($message): ?>
          <div class="admin-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="admin-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" class="admin-form">
          <?php foreach ($data['sections'] as $index => $section): ?>
            <article class="card learn-admin-card">
              <span class="eyebrow">Section <?php echo htmlspecialchars($section['number']); ?></span>

              <label for="title-<?php echo $index; ?>">Section title</label>
              <input id="title-<?php echo $index; ?>" name="sections[<?php echo $index; ?>][title]" value="<?php echo htmlspecialchars($section['title']); ?>" required>

              <label for="level-<?php echo $index; ?>">Level label</label>
              <input id="level-<?php echo $index; ?>" name="sections[<?php echo $index; ?>][level]" value="<?php echo htmlspecialchars($section['level']); ?>" required>

              <label for="summary-<?php echo $index; ?>">Short description on Learn page</label>
              <textarea id="summary-<?php echo $index; ?>" name="sections[<?php echo $index; ?>][summary]" required><?php echo htmlspecialchars($section['summary']); ?></textarea>

              <label for="content-<?php echo $index; ?>">Full content on lesson subpage</label>
              <textarea class="full-content" id="content-<?php echo $index; ?>" name="sections[<?php echo $index; ?>][content]" required><?php echo htmlspecialchars($section['content']); ?></textarea>

              <p><a href="/learn-<?php echo htmlspecialchars($section['id']); ?>.html" target="_blank">View public lesson page</a></p>
            </article>
          <?php endforeach; ?>

          <button class="save-button" type="submit">Save Learn Content</button>
        </form>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container footer-grid">
      <div><strong style="color:white">Sugar Code It</strong><br>Private website management</div>
      <div><a href="/admin/">Admin Dashboard</a></div>
      <div><a href="/learn.html">View Learn page</a></div>
    </div>
  </footer>
</body>
</html>
