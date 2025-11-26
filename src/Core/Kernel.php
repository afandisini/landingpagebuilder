<?php

final class Kernel
{
    private Container $container;
    private Router $router;
    private ErrorHandler $errors;
    private MiddlewarePipeline $middleware;

    public function __construct(?Container $container = null, ?Router $router = null, ?ErrorHandler $errors = null)
    {
        $this->container = $container ?? new Container();
        $this->router = $router ?? new Router();
        $this->errors = $errors ?? new ErrorHandler();
        $this->middleware = new MiddlewarePipeline();

        $this->bootstrapContainer();
        $this->registerRoutes();
    }

    public function handle(Request $request): Response
    {
        if ($request->route() === '') {
            $request->setRoute(Auth::check() ? 'admin/dashboard' : 'login');
        }

        $this->container->set(Request::class, $request);

        try {
            return $this->router->dispatch($request, $this->container, $this->middleware->all());
        } catch (Throwable $e) {
            return $this->errors->render($e, $request);
        }
    }

    public function addMiddleware(callable $middleware): void
    {
        $this->middleware->add($middleware);
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function container(): Container
    {
        return $this->container;
    }

    private function bootstrapContainer(): void
    {
        $this->container->set(Container::class, $this->container);
        $this->container->set(Router::class, $this->router);
        $this->container->set(ErrorHandler::class, $this->errors);
        $this->container->set(PDO::class, fn() => Database::getConnection());
    }

    private function registerRoutes(): void
    {
        $this->router->add('GET', 'login', [AuthController::class, 'loginForm']);
        $this->router->add('POST', 'login', [AuthController::class, 'login']);
        $this->router->add('GET', 'logout', [AuthController::class, 'logout']);

        $this->router->add('GET', 'admin/dashboard', [DashboardController::class, 'index']);
        $this->router->add('GET', 'admin/pages', [PageController::class, 'index']);
        $this->router->add('GET', 'admin/pages/template', [PageController::class, 'chooseTemplate']);
        $this->router->add('POST', 'admin/pages/template', [PageController::class, 'selectTemplate']);
        $this->router->add('GET', 'admin/pages/create', [PageController::class, 'create']);
        $this->router->add('POST', 'admin/pages/store', [PageController::class, 'store']);
        $this->router->add('GET', 'admin/pages/edit', [PageController::class, 'edit']);
        $this->router->add('POST', 'admin/pages/update', [PageController::class, 'update']);
        $this->router->add('POST', 'admin/pages/delete', [PageController::class, 'delete']);
        $this->router->add('POST', 'admin/pages/publish', [PageController::class, 'publish']);

        $this->router->add('POST', 'api/payments/qris', [PaymentController::class, 'createQrisPayment']);
        $this->router->add('GET', 'api/payments/{orderId}/status', [PaymentController::class, 'getStatus']);
        $this->router->add('GET', 'api/payments/status', function (Request $request) {
            $orderId = (string)$request->input('order_id', '');
            return (new PaymentController())->getStatus($orderId);
        });
        $this->router->add('POST', 'webhook/midtrans', [PaymentController::class, 'handleWebhook']);
    }
}
