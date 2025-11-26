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
    <style>
        .custum-file-upload {
            height: 100%;
            min-height: 140px;
            width: 100%;
            max-width: 100%;
            background: #ffffff;
            border: 2px dashed #d6d6d6;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            cursor: pointer;
            transition: 0.25s ease;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .custum-file-upload:hover {
            border-color: #0d6efd;
            background: rgba(13, 110, 253, 0.04);
            box-shadow: 0 8px 24px rgba(13, 110, 253, 0.15);
        }

        .custum-file-upload .icon svg {
            height: 40px;
            width: 40px;
            fill: #444;
            opacity: 0.85;
            transition: 0.2s ease;
            pointer-events: none;
        }

        .custum-file-upload:hover .icon svg {
            fill: #0d6efd;
            opacity: 1;
        }

        .custum-file-upload .text span {
            font-size: 15px;
            font-weight: 600;
            color: #555;
        }

        /* input hidden */
        .custum-file-upload input {
            display: none;
        }

        /* ===== Preview jika mau tampil gambar ===== */
        .custum-file-upload.preview-active {
            padding: 0;
        }

        .custum-file-upload.preview-active .icon,
        .custum-file-upload.preview-active .text {
            display: none;
        }

        /* preview image */
        .custum-file-upload img.preview,
        .custum-file-upload img[data-upload-preview] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 1rem;
            z-index: 2;
            display: block;
        }

        .custum-file-upload.has-image .icon,
        .custum-file-upload.has-image .text {
            display: none;
        }
    </style>

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
<script>
    (function(){
  document.querySelectorAll(".custum-file-upload").forEach((wrap)=>{
    const btn = wrap.querySelector(".remove-preview");
    if(!btn || btn.dataset.bound==="1") return;
    btn.dataset.bound="1";
    btn.addEventListener("click", ()=>{
      wrap.querySelectorAll(".preview").forEach(n=>n.remove());
      wrap.classList.remove("preview-active");
      const input = wrap.querySelector('input[type="file"]');
      if (input) input.value = "";
    });
  });
})();
</script>
</body>
</html>
