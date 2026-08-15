<?php
session_start();
$root = dirname(__DIR__);
$file = $root . '/data/meeting-content.json';
function readData($path) { if (!is_file($path)) return []; $data = json_decode((string) file_get_contents($path), true); return is_array($data) ? $data : []; }
function saveData($path, $data) { $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); return file_put_contents($path, $json . PHP_EOL, LOCK_EX) !== false; }
function cleanText($value, $maxLength) { $value = trim((string) $value); return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength); }
function h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Invalid request token.'); }
    $data = readData($file);
    $data['home_label'] = cleanText($_POST['home_label'] ?? '', 60);
    $data['home_date'] = cleanText($_POST['home_date'] ?? '', 100);
    $data['home_title'] = cleanText($_POST['home_title'] ?? '', 160);
    $data['home_details'] = cleanText($_POST['home_details'] ?? '', 220);
    $data['content'] = cleanText($_POST['meeting_content'] ?? '', 5000);
    $data['minutes'] = cleanText($_POST['meeting_minutes'] ?? '', 10000);
    $data['updated'] = date('c');
    if (saveData($file, $data)) $message = 'Meeting information updated.'; else $error = 'The meeting information could not be updated. Check folder permissions.';
}
$data = readData($file);
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Manage Meetings | Sugar Code It</title><link rel="stylesheet" href="/styles.css">
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
  </style>
</head>
<body class="admin-body">
<header class="nav"><div class="container navin"><a class="brand" href="/index.html"><img src="/assets/logo.png" alt="Sugar Code It logo"><span>Sugar Code It</span></a><div class="admin-nav-note">Meeting management</div></div></header>
<div class="admin-breadcrumb-wrap">
  <div class="container admin-breadcrumb" aria-label="Admin breadcrumb">
    <a href="/admin/">Admin Dashboard</a>
    <span class="separator">/</span>
    <span class="current">Meetings</span>
  </div>
</div>

<main>
<section class="page-hero admin-hero"><div class="container"><span class="eyebrow">Meetings</span><h1>Manage meeting information.</h1><p>Update the Home page meeting banner, meeting-page content and meeting minutes.</p><div class="form-actions"><a class="button secondary" href="/admin/">Back to Admin</a><a class="button secondary" href="/index.html" target="_blank" rel="noopener">View Home Page</a><a class="button secondary" href="/meetings.html" target="_blank" rel="noopener">View Meetings Page</a></div></div></section>
<section><div class="container"><?php if ($message !== ''): ?><div class="admin-message success"><?php echo h($message); ?></div><?php endif; ?><?php if ($error !== ''): ?><div class="admin-message error"><?php echo h($error); ?></div><?php endif; ?><div class="admin-panel"><form class="editor-form admin-form" method="post"><input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']); ?>"><h2>Home page meeting banner</h2><label>Small label<input type="text" name="home_label" maxlength="60" value="<?php echo h($data['home_label'] ?? 'Next meeting'); ?>" placeholder="Next meeting"></label><label>Meeting date<input type="text" name="home_date" maxlength="100" value="<?php echo h($data['home_date'] ?? ''); ?>" placeholder="August 28"></label><label>Meeting title<input type="text" name="home_title" maxlength="160" value="<?php echo h($data['home_title'] ?? ''); ?>" placeholder="2026 to 2027 Kickoff Meeting"></label><label>Time and location<input type="text" name="home_details" maxlength="220" value="<?php echo h($data['home_details'] ?? ''); ?>" placeholder="3:00 to 4:00 PM, Room 2102"></label><hr><h2>Meetings page</h2><label>Meeting page content<textarea name="meeting_content" maxlength="5000" rows="8" placeholder="Add meeting updates, what was covered, announcements, or other information..."><?php echo h($data['content'] ?? ''); ?></textarea></label><label>Meeting minutes<textarea name="meeting_minutes" maxlength="10000" rows="12" placeholder="Add the minutes from the latest meeting..."><?php echo h($data['minutes'] ?? ''); ?></textarea></label><button class="button" type="submit">Save Meeting Information</button></form></div></div></section>
</main><footer class="footer"><div class="container footer-grid"><div><strong style="color:white">Sugar Code It</strong><br>Private meeting management</div><div><a href="/admin/">Back to Admin</a></div><div><a href="/meetings.html">Public Meetings</a></div></div></footer>
</body></html>
