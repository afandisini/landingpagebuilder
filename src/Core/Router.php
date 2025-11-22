<?php
require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Controllers/DashboardController.php';
require_once __DIR__ . '/../Controllers/PageController.php';
require_once __DIR__ . '/../Controllers/PaymentController.php';

class Router
{
    public function dispatch(): void
    {
        Auth::startSession();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $route = $_GET['r'] ?? null;

        // Support friendly paths when ?r is not provided (e.g. /api/... endpoints).
        if ($route === null) {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
            $path = trim($path, '/');
            $base = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
            if ($base !== '' && strpos($path, $base) === 0) {
                $path = ltrim(substr($path, strlen($base)), '/');
            }
            if ($path === '') {
                $route = Auth::check() ? 'admin/dashboard' : 'login';
            } elseif ($path === 'api/payments/qris') {
                $route = 'api/payments/qris';
            } elseif (preg_match('#^api/payments/([^/]+)/status$#', $path, $matches)) {
                $_GET['order_id'] = $matches[1];
                $route = 'api/payments/status';
            } elseif ($path === 'webhook/midtrans') {
                $route = 'webhook/midtrans';
            } else {
                $route = $path;
            }
        }

        switch ($route) {
            case 'login':
                $controller = new AuthController();
                if ($method === 'POST') {
                    $controller->login();
                } else {
                    $controller->loginForm();
                }
                break;
            case 'logout':
                (new AuthController())->logout();
                break;
            case 'admin/dashboard':
                (new DashboardController())->index();
                break;
            case 'admin/pages':
                (new PageController())->index();
                break;
            case 'admin/pages/template':
                $pageController = new PageController();
                if ($method === 'POST') {
                    $pageController->selectTemplate();
                } else {
                    $pageController->chooseTemplate();
                }
                break;
            case 'admin/pages/create':
                (new PageController())->create();
                break;
            case 'admin/pages/store':
                if ($method === 'POST') {
                    (new PageController())->store();
                }
                break;
            case 'admin/pages/edit':
                (new PageController())->edit();
                break;
            case 'admin/pages/update':
                if ($method === 'POST') {
                    (new PageController())->update();
                }
                break;
            case 'admin/pages/delete':
                if ($method === 'POST') {
                    (new PageController())->delete();
                }
                break;
            case 'admin/pages/publish':
                if ($method === 'POST') {
                    (new PageController())->publish();
                }
                break;
            case 'api/payments/qris':
                if ($method === 'POST') {
                    (new PaymentController())->createQrisPayment();
                }
                break;
            case 'api/payments/status':
                if ($method === 'GET') {
                    $orderId = $_GET['order_id'] ?? '';
                    (new PaymentController())->getStatus($orderId);
                }
                break;
            case 'webhook/midtrans':
                if ($method === 'POST') {
                    (new PaymentController())->handleWebhook();
                }
                break;
            default:
                header('Location: ?r=login');
                exit;
        }
    }
}
