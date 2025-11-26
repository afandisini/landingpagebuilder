<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Models/PageVisit.php';
require_once __DIR__ . '/../Models/Page.php';

class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();

        $stats = PageVisit::getSummary();

        // Ambil semua page
        $pages = Page::all();

        // Urutkan: published_at DESC → created_at DESC → id DESC
        usort($pages, function ($a, $b) {
            // Helper parse time
            $tp = function ($row, $key) {
                if (empty($row[$key])) return null;
                $t = strtotime($row[$key]);
                return $t ?: null;
            };

            $aPub = $tp($a, 'published_at');
            $bPub = $tp($b, 'published_at');

            if ($aPub && $bPub) return $bPub <=> $aPub;   // keduanya publish: terbaru duluan
            if ($aPub && !$bPub) return -1;               // A sudah publish, B belum → A di atas
            if (!$aPub && $bPub) return 1;                // B sudah publish, A belum → B di atas

            // Keduanya belum publish atau sama-sama kosong → pakai created_at
            $aCre = $tp($a, 'created_at');
            $bCre = $tp($b, 'created_at');
            if ($aCre && $bCre) return $bCre <=> $aCre;   // terbaru duluan
            if ($aCre && !$bCre) return -1;
            if (!$aCre && $bCre) return 1;

            // Fallback terakhir: id DESC
            return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
        });

        // Hitung view per page (nggak berubah)
        $pageViewCounts = PageVisit::getTotalsByPageIds(array_column($pages, 'id'));

        $config = require __DIR__ . '/../config/config.php';

        $content = $this->renderView(__DIR__ . '/../Views/admin/dashboard.php', [
            'stats' => $stats,
            'pages' => $pages,                  // sudah diurutkan terbaru → atas
            'pageViewCounts' => $pageViewCounts,
            'baseUrl' => $config['base_url'],
        ]);

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    private function renderView(string $view, array $params = []): string
    {
        extract($params, EXTR_SKIP);
        ob_start();
        include $view;
        return ob_get_clean();
    }
}
