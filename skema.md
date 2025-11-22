landingpagebuilder/
├─ public/
│ ├─ index.php # Front controller
│ ├─ assets/ # CSS, JS, gambar umum
│ ├─ page/ # HASIL GENERATE: landing-demo-1.html, dst
│ └─ tracker.php # Endpoint tracking pengunjung
├─ src/
│ ├─ config/
│ │ └─ config.php # DB config, base_url, dll
│ ├─ Core/
│ │ ├─ Database.php
│ │ ├─ Auth.php
│ │ └─ Router.php
│ ├─ Controllers/
│ │ ├─ AuthController.php
│ │ ├─ DashboardController.php
│ │ └─ PageController.php
│ ├─ Models/
│ │ ├─ User.php
│ │ ├─ Page.php
│ │ └─ PageVisit.php
│ └─ Views/
│ ├─ auth/
│ │ └─ login.php
│ ├─ admin/
│ │ ├─ dashboard.php # tracking & statistik
│ │ └─ pages/
│ │ ├─ index.php # list landing page
│ │ ├─ create.php # CanvasEditor editor
│ │ └─ edit.php
│ └─ layouts/
│ ├─ admin.php
│ └─ auth.php
├─ storage/
│ └─ logs/ # Optional
└─ vendor/ # Kalau pakai Composer
