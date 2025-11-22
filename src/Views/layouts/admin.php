<?php
$config = require __DIR__ . '/../../config/config.php';
$baseUrl = $config['base_url'];
$bootstrapCss = $config['base_url'] . '/assets/bootstrap/css/bootstrap.min.css';
$bootstrapIcons = $config['base_url'] . '/assets/bootstrap-icons/bootstrap-icons.min.css';
$bootstrapBundle = $config['base_url'] . '/assets/bootstrap/js/bootstrap.bundle.min.js';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Page Builder</title>
    <link href="<?= $bootstrapCss;?>" rel="stylesheet">
    <link href="<?= $bootstrapIcons;?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="?r=admin/dashboard"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>App Page Builder</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="?r=admin/dashboard">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="?r=admin/pages">Buat laman</a></li>
                <li class="nav-item"><a class="nav-link" href="?r=logout">Keluar</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="flex-fill">
    <div class="container my-4">
        <?php echo $content; ?>
    </div>
</main>
<footer class="text-center py-3 mt-auto">
    <div class="container">
        <span class="text-muted small">&copy; <?php echo date('Y'); ?>. App Page Builder by aiti-solutions</span>
    </div>
</footer>
<script src="<?= $bootstrapBundle;?>"></script>
</body>
</html>
