<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Landing Page Builder</title>

  <!-- Bootstrap & Icons (ubah ke lokal kalau perlu) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body { 
        min-height: 100vh;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: center;

            background: linear-gradient(145deg, #166cec, #a3068e);
            background-size: 300% 300%;
            animation: gradientMove 12s ease infinite;
        }

        @keyframes gradientMove {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
     }

     .auth-card { background: #ffffff; border-radius: 16px; padding: 32px; box-shadow: 0 10px 40px rgba(0,0,0,.15); width: 100%; max-width: 420px; animation: fadeIn .4s ease; } .brand-title { font-weight: 700; letter-spacing: .5px; } .input-icon { position: absolute; top: 85%; left: 12px; transform: translateY(-85%); font-size: 18px; color: #6c757d;opacity: .8;} .form-control.has-icon { padding-left: 40px; } .password-toggle { position: absolute; top: 85%; right: 12px; transform: translateY(-85%); cursor: pointer; color: #6c757d; font-size: 18px; } @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .alert.autoclose { overflow: hidden; transition: opacity .3s ease, max-height .3s ease, padding .3s ease; }
  </style>
</head>

<body>
<div class="auth-card">

  <?php $hideLogin = !empty($error); ?>

  <?php if (!empty($error)): ?>
    <div id="alert-error" class="alert alert-danger alert-dismissible fade show autoclose" role="alert">
      <?= htmlspecialchars($error); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- SECTION LOGIN (judul + deskripsi + form dari $content) -->
  <div id="login-section" class="<?= $hideLogin ? 'd-none' : '' ?>">
    <h1 class="text-center brand-title">Login</h1>
    <p class="text-center text-muted mb-2 text-muted small">Masuk untuk mengelola Halaman Anda</p>
  </div>
  <?= $content; ?>
</div>

<script>
  // Show/Hide password
  document.addEventListener("click", function(e) {
    if (e.target.classList.contains("password-toggle")) {
      const input = document.getElementById("password");
      const icon = e.target;
      if (input.type === "password") {
        input.type = "text"; icon.classList.replace("bi-eye-slash","bi-eye");
      } else {
        input.type = "password"; icon.classList.replace("bi-eye","bi-eye-slash");
      }
    }
  });

  // Alert auto-hide + restore login-section
  (function () {
    const alertEl = document.getElementById('alert-error');
    const loginSection = document.getElementById('login-section');
    const emailInput = document.getElementById('email');

    if (!alertEl) return;

    // Saat alert ada: sembunyikan login
    if (loginSection) loginSection.classList.add('d-none');

    const closeNow = () => {
      if (!alertEl) return;
      alertEl.style.opacity = '0';
      alertEl.style.maxHeight = '0';
      alertEl.style.padding = '0 1rem';
      setTimeout(() => {
        alertEl.remove();
        if (loginSection) loginSection.classList.remove('d-none');
        // fokuskan ke email biar cepat isi ulang
        if (emailInput) emailInput.focus();
      }, 550);
    };

    // Auto-close 5 detik
    setTimeout(closeNow, 5000);

    // Klik X → tutup segera
    alertEl.addEventListener('click', (e) => {
      if (e.target.classList.contains('btn-close')) {
        e.preventDefault();
        closeNow();
      }
    });
  })();
</script>
</body>
</html>
