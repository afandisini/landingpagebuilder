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
        $pages = Page::all();
        $pageViewCounts = PageVisit::getTotalsByPageIds(array_column($pages, 'id'));
        $config = require __DIR__ . '/../config/config.php';
        $content = $this->renderView(__DIR__ . '/../Views/admin/dashboard.php', [
            'stats' => $stats,
            'pages' => $pages,
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
