<?php
session_start();
$root = dirname(__DIR__);
$file = $root . '/data/roadmap-content.json';
function readData($path) { if (!is_file($path)) return []; $data = json_decode((string) file_get_contents($path), true); return is_array($data) ? $data : []; }
function saveData($path, $data) { $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); return file_put_contents($path, $json . PHP_EOL, LOCK_EX) !== false; }
function cleanText($value, $maxLength) { $value = trim((string) $value); return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength); }
function h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
$message = '';
$error = '';
$defaults = [
 ['year'=>'2025 | Digital foundation','title'=>"Establish the club's online presence",'text'=>'Website work begins under the Webmaster role, giving students a clearer way to discover meetings, activities and opportunities.'],
 ['year'=>'Spring 2026 | Documentation','title'=>'Make club activity visible','text'=>'Capture meeting photos, student participation and hands-on activities so the club builds a record of what members actually do.'],
 ['year'=>'2026-27 | Platform expansion','title'=>'Move beyond a one-page website','text'=>'Organize the club website into clear pages for meetings, learning resources, projects, impact and membership.'],
 ['year'=>'2026-27 | Member pathway','title'=>'Create progression for members','text'=>'Help beginners move from basic lessons to build challenges, project teams, teaching and leadership instead of attending disconnected meetings.'],
 ['year'=>'2026-27 | Measurable impact','title'=>'Track outcomes without guessing','text'=>'Record verified projects completed, workshops delivered, volunteer activity and students reached so the Impact page reflects real work.'],
 ['year'=>'2027 | Handoff','title'=>'Leave future officers a working system','text'=>'Organize resources, project documentation and website structure so the next leadership team can continue improving the club rather than rebuilding from scratch.']
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Invalid request token.'); }
    $items = [];
    for ($i = 0; $i < 6; $i++) $items[] = ['year'=>cleanText($_POST['roadmap_year'][$i] ?? '',100),'title'=>cleanText($_POST['roadmap_title'][$i] ?? '',160),'text'=>cleanText($_POST['roadmap_text'][$i] ?? '',1000)];
    if (saveData($file, ['items'=>$items,'updated'=>date('c')])) $message = 'Road Map page updated.'; else $error = 'The Road Map page could not be updated. Check folder permissions.';
}
$data = readData($file);
$items = isset($data['items']) && is_array($data['items']) ? $data['items'] : $defaults;
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Manage Road Map | Sugar Code It</title><link rel="stylesheet" href="/styles.css">
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
</head><body class="admin-body">
<header class="nav"><div class="container navin"><a class="brand" href="/index.html"><img src="/assets/logo.png" alt="Sugar Code It logo"><span>Sugar Code It</span></a><div class="admin-nav-note">Road Map management</div></div></header>
<div class="admin-breadcrumb-wrap">
  <div class="container admin-breadcrumb" aria-label="Admin breadcrumb">
    <a href="/admin/">Admin Dashboard</a>
    <span class="separator">/</span>
    <span class="current">Road Map</span>
  </div>
</div>

<main><section class="page-hero admin-hero"><div class="container"><span class="eyebrow">Road Map</span><h1>Manage Road Map.</h1><p>Update the text for each existing Road Map item.</p><div class="form-actions"><a class="button secondary" href="/admin/">Back to Admin</a><a class="button secondary" href="/roadmap.html" target="_blank" rel="noopener">View Road Map Page</a></div></div></section>
<section><div class="container"><?php if ($message !== ''): ?><div class="admin-message success"><?php echo h($message); ?></div><?php endif; ?><?php if ($error !== ''): ?><div class="admin-message error"><?php echo h($error); ?></div><?php endif; ?><div class="admin-panel"><form class="editor-form admin-form" method="post"><input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']); ?>">
<?php for ($i=0;$i<6;$i++): $item=$items[$i]??$defaults[$i]; ?><div class="roadmap-admin-item"><h3>Road Map item <?php echo $i+1; ?></h3><label>Year / phase<input type="text" name="roadmap_year[]" maxlength="100" value="<?php echo h($item['year'] ?? ''); ?>"></label><label>Heading<input type="text" name="roadmap_title[]" maxlength="160" value="<?php echo h($item['title'] ?? ''); ?>"></label><label>Text<textarea name="roadmap_text[]" maxlength="1000" rows="4"><?php echo h($item['text'] ?? ''); ?></textarea></label></div><?php endfor; ?>
<button class="button" type="submit">Save Road Map</button></form></div></div></section></main><footer class="footer"><div class="container footer-grid"><div><strong style="color:white">Sugar Code It</strong><br>Private Road Map management</div><div><a href="/admin/">Back to Admin</a></div><div><a href="/roadmap.html">Public Road Map</a></div></div></footer></body></html>
