<?php
session_start();

$root = dirname(__DIR__);
$eventsFile = $root . '/data/events.json';

function readJsonFile($path) {
    if (!is_file($path)) return [];
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : [];
}
function writeJsonFile($path, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents($path, $json . PHP_EOL, LOCK_EX) !== false;
}
function cleanText($value, $maxLength) {
    $value = trim((string) $value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}
function makeId() { return bin2hex(random_bytes(8)); }
function h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $action = (string) ($_POST['action'] ?? '');
    $events = readJsonFile($eventsFile);

    if ($action === 'save_event') {
        $id = cleanText($_POST['id'] ?? '', 40);
        $date = cleanText($_POST['date'] ?? '', 10);
        $name = cleanText($_POST['name'] ?? '', 80);
        $time = cleanText($_POST['time'] ?? '', 40);
        $details = cleanText($_POST['details'] ?? '', 300);
        $validDate = DateTime::createFromFormat('Y-m-d', $date);

        if (!$validDate || $validDate->format('Y-m-d') !== $date || $name === '') {
            $error = 'Please enter a valid date and event name.';
        } else {
            $item = ['id' => $id !== '' ? $id : makeId(), 'date' => $date, 'name' => $name, 'time' => $time, 'details' => $details];
            $found = false;
            foreach ($events as $index => $event) {
                if ($id !== '' && isset($event['id']) && $event['id'] === $id) {
                    $events[$index] = $item;
                    $found = true;
                    break;
                }
            }
            if (!$found) $events[] = $item;
            usort($events, function ($a, $b) {
                return strcmp(($a['date'] ?? '') . ($a['time'] ?? ''), ($b['date'] ?? '') . ($b['time'] ?? ''));
            });
            $message = writeJsonFile($eventsFile, $events) ? ($id !== '' ? 'Event updated.' : 'Event added.') : '';
            if ($message === '') $error = 'The event could not be saved. Check folder permissions.';
        }
    }

    if ($action === 'delete_event') {
        $id = cleanText($_POST['id'] ?? '', 40);
        $events = array_values(array_filter($events, function ($event) use ($id) {
            return !isset($event['id']) || $event['id'] !== $id;
        }));
        if (writeJsonFile($eventsFile, $events)) $message = 'Event deleted.';
        else $error = 'The event could not be deleted.';
    }
}

$events = readJsonFile($eventsFile);
$editId = cleanText($_GET['edit'] ?? '', 40);
$editEvent = null;
foreach ($events as $event) {
    if ($editId !== '' && isset($event['id']) && $event['id'] === $editId) {
        $editEvent = $event;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Calendar | Sugar Code It</title>
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
  </style>
</head>
<body class="admin-body">
  <header class="nav"><div class="container navin"><a class="brand" href="/index.html"><img src="/assets/logo.png" alt="Sugar Code It logo"><span>Sugar Code It</span></a><div class="admin-nav-note">Calendar management</div></div></header>
<div class="admin-breadcrumb-wrap">
  <div class="container admin-breadcrumb" aria-label="Admin breadcrumb">
    <a href="/admin/">Admin Dashboard</a>
    <span class="separator">/</span>
    <span class="current">Calendar</span>
  </div>
</div>

  <main>
    <section class="page-hero admin-hero"><div class="container"><span class="eyebrow">Calendar</span><h1>Manage calendar.</h1><p>Add, edit, or delete events.</p><div class="form-actions"><a class="button secondary" href="/admin/">Back to Admin</a><a class="button secondary" href="/calendar.html" target="_blank" rel="noopener">View Public Calendar</a></div></div></section>
    <section><div class="container">
      <?php if ($message !== ''): ?><div class="admin-message success"><?php echo h($message); ?></div><?php endif; ?>
      <?php if ($error !== ''): ?><div class="admin-message error"><?php echo h($error); ?></div><?php endif; ?>
      <div class="admin-panel">
        <div class="section-head compact"><div><span class="eyebrow">Event editor</span><h2><?php echo $editEvent ? 'Edit event' : 'Add event'; ?></h2></div></div>
        <form class="editor-form admin-form" method="post">
          <input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']); ?>">
          <input type="hidden" name="action" value="save_event">
          <input type="hidden" name="id" value="<?php echo h($editEvent['id'] ?? ''); ?>">
          <label>Date<input type="date" name="date" required value="<?php echo h($editEvent['date'] ?? ''); ?>"></label>
          <label>Event name<input type="text" name="name" required maxlength="80" value="<?php echo h($editEvent['name'] ?? ''); ?>" placeholder="Club meeting"></label>
          <label>Time<input type="text" name="time" maxlength="40" value="<?php echo h($editEvent['time'] ?? ''); ?>" placeholder="3:00 PM - 4:00 PM"></label>
          <label>Details<textarea name="details" maxlength="300" rows="4" placeholder="Room, topic, supplies, deadline..."><?php echo h($editEvent['details'] ?? ''); ?></textarea></label>
          <div class="form-actions"><button class="button" type="submit"><?php echo $editEvent ? 'Update Event' : 'Add Event'; ?></button><?php if ($editEvent): ?><a class="button secondary" href="/admin/calendar.php">Cancel</a><?php endif; ?></div>
        </form>
      </div>
    </div></section>
    <section class="light-section"><div class="container"><div class="section-head"><div><span class="eyebrow">Calendar</span><h2>Current events</h2></div></div>
      <?php if (count($events) === 0): ?><div class="empty-state"><h3>No events yet</h3><p>Add the first event above.</p></div><?php else: ?><div class="admin-list">
      <?php foreach ($events as $event): ?><article class="admin-list-item"><div><div class="card-meta"><?php echo h($event['date'] ?? ''); ?><?php echo !empty($event['time']) ? ' | ' . h($event['time']) : ''; ?></div><h3><?php echo h($event['name'] ?? 'Event'); ?></h3><?php if (!empty($event['details'])): ?><p><?php echo h($event['details']); ?></p><?php endif; ?></div><div class="admin-actions"><a class="small-button" href="/admin/calendar.php?edit=<?php echo urlencode((string) ($event['id'] ?? '')); ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this event?');"><input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']); ?>"><input type="hidden" name="action" value="delete_event"><input type="hidden" name="id" value="<?php echo h($event['id'] ?? ''); ?>"><button class="small-button danger-text" type="submit">Delete</button></form></div></article><?php endforeach; ?>
      </div><?php endif; ?>
    </div></section>
  </main>
  <footer class="footer"><div class="container footer-grid"><div><strong style="color:white">Sugar Code It</strong><br>Private calendar management</div><div><a href="/admin/">Back to Admin</a></div><div><a href="/calendar.html">Public Calendar</a></div></div></footer>
</body>
</html>
