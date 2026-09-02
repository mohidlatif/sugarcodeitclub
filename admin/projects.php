<?php
session_start();

$root = dirname(__DIR__);
$projectsFile = $root . '/data/projects.json';

function readProjects($path) {
    if (!is_file($path)) {
        return [];
    }

    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function writeProjects($path, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents($path, $json . PHP_EOL, LOCK_EX) !== false;
}

function cleanProjectText($value, $maxLength) {
    $value = trim((string) $value);

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength);
    }

    return substr($value, 0, $maxLength);
}

function projectId() {
    return bin2hex(random_bytes(8));
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
}

$message = '';
$error = '';

$allowedCategories = [
    'arduino',
    'programming',
    'engineering',
    'bioengineering'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';

    if (!hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $action = (string) ($_POST['action'] ?? '');
    $projects = readProjects($projectsFile);

    if ($action === 'save_project') {
        $id = cleanProjectText($_POST['id'] ?? '', 40);
        $category = strtolower(cleanProjectText($_POST['category'] ?? '', 30));
        $title = cleanProjectText($_POST['title'] ?? '', 120);
        $description = cleanProjectText($_POST['description'] ?? '', 1200);
        $status = cleanProjectText($_POST['status'] ?? '', 80);
        $link = cleanProjectText($_POST['link'] ?? '', 500);

        if (
            !in_array($category, $allowedCategories, true) ||
            $title === ''
        ) {
            $error = 'Choose a category and enter a project title.';
        } elseif (
            $link !== '' &&
            !filter_var($link, FILTER_VALIDATE_URL)
        ) {
            $error = 'Enter a complete project link, including https://';
        } else {
            $item = [
                'id' => $id !== '' ? $id : projectId(),
                'category' => $category,
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'link' => $link,
                'updated' => date('c')
            ];

            $found = false;

            foreach ($projects as $index => $project) {
                if (
                    $id !== '' &&
                    isset($project['id']) &&
                    $project['id'] === $id
                ) {
                    $projects[$index] = $item;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $projects[] = $item;
            }

            if (writeProjects($projectsFile, $projects)) {
                $message = $id !== ''
                    ? 'Project updated.'
                    : 'Project added.';
            } else {
                $error = 'The project could not be saved. Check folder permissions.';
            }
        }
    }

    if ($action === 'delete_project') {
        $id = cleanProjectText($_POST['id'] ?? '', 40);

        $projects = array_values(
            array_filter(
                $projects,
                function ($project) use ($id) {
                    return !isset($project['id']) ||
                        $project['id'] !== $id;
                }
            )
        );

        if (writeProjects($projectsFile, $projects)) {
            $message = 'Project deleted.';
        } else {
            $error = 'The project could not be deleted.';
        }
    }
}

$projects = readProjects($projectsFile);

$editId = cleanProjectText($_GET['edit'] ?? '', 40);
$editProject = null;

foreach ($projects as $project) {
    if (
        $editId !== '' &&
        isset($project['id']) &&
        $project['id'] === $editId
    ) {
        $editProject = $project;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Manage Projects | Sugar Code It
    </title>

    <link
        rel="stylesheet"
        href="/styles.css"
    >

    <style>
        .admin-breadcrumb-wrap {
            background: #0d1b2d;
            border-bottom: 1px solid rgba(255,255,255,0.10);
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

<header class="nav">
    <div class="container navin">
        <a
            class="brand"
            href="/index.html"
        >
            <img
                src="/assets/logo.png"
                alt="Sugar Code It logo"
            >

            <span>
                Sugar Code It
            </span>
        </a>

        <div class="admin-nav-note">
            Project management
        </div>
    </div>
</header>

<div class="admin-breadcrumb-wrap">
    <div
        class="container admin-breadcrumb"
        aria-label="Admin breadcrumb"
    >
        <a href="/admin/">
            Admin Dashboard
        </a>

        <span class="separator">
            /
        </span>

        <span class="current">
            Projects
        </span>
    </div>
</div>

<main>

<section class="page-hero admin-hero">
    <div class="container">

        <span class="eyebrow">
            Projects
        </span>

        <h1>
            Manage club projects.
        </h1>

        <p>
            Add, edit, or remove projects under Arduino,
            Programming, Engineering, and Bioengineering.
        </p>

        <div class="form-actions">

            <a
                class="button secondary"
                href="/admin/"
            >
                Back to Admin
            </a>

            <a
                class="button secondary"
                href="/projects.html"
                target="_blank"
                rel="noopener"
            >
                View Projects Page
            </a>

        </div>
    </div>
</section>

<section>
    <div class="container">

        <?php if ($message !== ''): ?>
            <div class="admin-message success">
                <?php echo h($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="admin-message error">
                <?php echo h($error); ?>
            </div>
        <?php endif; ?>

        <div class="admin-panel">

            <div class="section-head compact">
                <div>
                    <span class="eyebrow">
                        Project editor
                    </span>

                    <h2>
                        <?php
                        echo $editProject
                            ? 'Edit project'
                            : 'Add project';
                        ?>
                    </h2>
                </div>
            </div>

            <form
                class="editor-form admin-form"
                method="post"
            >

                <input
                    type="hidden"
                    name="csrf"
                    value="<?php echo h($_SESSION['csrf']); ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="save_project"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo h($editProject['id'] ?? ''); ?>"
                >

                <label>
                    Category

                    <select
                        name="category"
                        required
                    >
                        <option value="">
                            Choose category
                        </option>

                        <?php foreach ($allowedCategories as $category): ?>
                            <option
                                value="<?php echo h($category); ?>"
                                <?php
                                echo (
                                    ($editProject['category'] ?? '') === $category
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                <?php echo h(ucfirst($category)); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </label>

                <label>
                    Project title

                    <input
                        type="text"
                        name="title"
                        maxlength="120"
                        required
                        value="<?php echo h($editProject['title'] ?? ''); ?>"
                        placeholder="Project name"
                    >
                </label>

                <label>
                    Description

                    <textarea
                        name="description"
                        maxlength="1200"
                        rows="6"
                        placeholder="What the project does, what members are building, and what skills it uses."
                    ><?php echo h($editProject['description'] ?? ''); ?></textarea>
                </label>

                <label>
                    Status

                    <input
                        type="text"
                        name="status"
                        maxlength="80"
                        value="<?php echo h($editProject['status'] ?? ''); ?>"
                        placeholder="Planning, In progress, Completed"
                    >
                </label>

                <label>
                    Project link (optional)

                    <input
                        type="url"
                        name="link"
                        maxlength="500"
                        value="<?php echo h($editProject['link'] ?? ''); ?>"
                        placeholder="https://github.com/..."
                    >
                </label>

                <div class="form-actions">

                    <button
                        class="button"
                        type="submit"
                    >
                        <?php
                        echo $editProject
                            ? 'Update Project'
                            : 'Add Project';
                        ?>
                    </button>

                    <?php if ($editProject): ?>
                        <a
                            class="button secondary"
                            href="/admin/projects.php"
                        >
                            Cancel
                        </a>
                    <?php endif; ?>

                </div>
            </form>

        </div>
    </div>
</section>

<section class="light-section">
    <div class="container">

        <div class="section-head">
            <div>
                <span class="eyebrow">
                    Current projects
                </span>

                <h2>
                    All project entries
                </h2>
            </div>
        </div>

        <?php if (count($projects) === 0): ?>

            <div class="empty-state">
                <h3>
                    No projects yet
                </h3>

                <p>
                    Add the first project above.
                </p>
            </div>

        <?php else: ?>

            <div class="admin-list">

                <?php foreach ($projects as $project): ?>

                    <article class="admin-list-item">

                        <div>

                            <div class="card-meta">
                                <?php
                                echo h(
                                    ucfirst(
                                        $project['category'] ?? ''
                                    )
                                );
                                ?>

                                <?php
                                echo !empty($project['status'])
                                    ? ' | ' . h($project['status'])
                                    : '';
                                ?>
                            </div>

                            <h3>
                                <?php
                                echo h(
                                    $project['title'] ?? 'Project'
                                );
                                ?>
                            </h3>

                            <?php if (!empty($project['description'])): ?>

                                <p>
                                    <?php
                                    echo h(
                                        $project['description']
                                    );
                                    ?>
                                </p>

                            <?php endif; ?>

                        </div>

                        <div class="admin-actions">

                            <a
                                class="small-button"
                                href="/admin/projects.php?edit=<?php
                                echo urlencode(
                                    (string) ($project['id'] ?? '')
                                );
                                ?>"
                            >
                                Edit
                            </a>

                            <form
                                method="post"
                                onsubmit="return confirm('Delete this project?');"
                            >

                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?php echo h($_SESSION['csrf']); ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete_project"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?php echo h($project['id'] ?? ''); ?>"
                                >

                                <button
                                    class="small-button danger-text"
                                    type="submit"
                                >
                                    Delete
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>
</section>

</main>

<footer class="footer">
    <div class="container footer-grid">

        <div>
            <strong style="color:white">
                Sugar Code It
            </strong>
            <br>
            Private project management
        </div>

        <div>
            <a href="/admin/">
                Back to Admin
            </a>
        </div>

        <div>
            <a href="/projects.html">
                Public Projects
            </a>
        </div>

    </div>
</footer>

</body>
</html>