<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin | Sugar Code It</title>
  <link rel="stylesheet" href="/styles.css">
  <style>
    .admin-dashboard-button {
      display: inline-block;
      margin-top: 22px;
      padding: 12px 22px;
      border: 1px solid #6f86ff;
      border-radius: 999px;
      background: #6f86ff;
      color: #ffffff;
      font-weight: 800;
      text-decoration: none;
      transition: transform 0.15s ease, background 0.15s ease;
    }

    .admin-dashboard-button:hover {
      background: #8296ff;
      transform: translateY(-2px);
    }

    .admin-dashboard-button:focus {
      outline: 3px solid rgba(111, 134, 255, 0.35);
      outline-offset: 3px;
    }


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

    .admin-breadcrumb .current {
      display: inline-block;
      padding: 9px 16px;
      border: 1px solid #6f86ff;
      border-radius: 999px;
      color: #dce4ff;
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
      <div class="admin-nav-note">Admin dashboard</div>
    </div>
  </header>
  <div class="admin-breadcrumb-wrap">
    <div class="container admin-breadcrumb" aria-label="Admin breadcrumb">
      <span class="current">Admin Dashboard</span>
    </div>
  </div>


  <main>
    <section class="page-hero admin-hero">
      <div class="container">
        <span class="eyebrow">Private management</span>
        <h1>Website admin.</h1>
        <p>Choose the part of the website you want to update.</p>
      </div>
    </section>

    <section>
      <div class="container">
        <div class="cards three">
          <article class="card">
            <span class="eyebrow">Calendar</span>
            <h3>Manage calendar</h3>
            <p>Add, edit, and delete club events.</p>
            <a class="admin-dashboard-button" href="/admin/calendar.php">Open Calendar</a>
          </article>

          <article class="card">
            <span class="eyebrow">Photos</span>
            <h3>Manage photos</h3>
            <p>Upload and remove photos for public pages.</p>
            <a class="admin-dashboard-button" href="/admin/photos.php">Open Photos</a>
          </article>

          <article class="card">
            <span class="eyebrow">Meetings</span>
            <h3>Manage meetings</h3>
            <p>Update meeting-page content and meeting minutes.</p>
            <a class="admin-dashboard-button" href="/admin/meetings.php">Open Meetings</a>
          </article>

          <article class="card">
            <span class="eyebrow">Road Map</span>
            <h3>Manage Road Map</h3>
            <p>Update the text for each Road Map item.</p>
            <a class="admin-dashboard-button" href="/admin/roadmap.php">Open Road Map</a>
          </article>

          <article class="card">
            <span class="eyebrow">Impact</span>
            <h3>Manage impact</h3>
            <p>Update the content for the three Impact period pages.</p>
            <a class="admin-dashboard-button" href="/admin/impact.php">Open Impact</a>
          </article>

          <article class="card">
            <span class="eyebrow">Learn</span>
            <h3>Manage Learn</h3>
            <p>Update the Learn sections and their full lesson pages.</p>
            <a class="admin-dashboard-button" href="/admin/learn.php">Open Learn</a>
          </article>

          <article class="card">
            <span class="eyebrow">Projects</span>
            <h3>Manage projects</h3>
            <p>Add, edit, and delete Arduino, Programming, and Engineering projects.</p>
            <a class="admin-dashboard-button" href="/admin/projects.php">Open Projects</a>
          </article>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container footer-grid">
      <div><strong style="color:white">Sugar Code It</strong><br>Private website management</div>
      <div><a href="/index.html">Return to site</a></div>
      <div>Protect /admin/ in cPanel</div>
    </div>
  </footer>
</body>
</html>
