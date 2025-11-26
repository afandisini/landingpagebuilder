<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Env.php';
require_once __DIR__ . '/../src/Core/Logger.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Core/Request.php';
require_once __DIR__ . '/../src/Core/Response.php';
require_once __DIR__ . '/../src/Core/MiddlewarePipeline.php';
require_once __DIR__ . '/../src/Core/Container.php';
require_once __DIR__ . '/../src/Core/ErrorHandler.php';
require_once __DIR__ . '/../src/Core/Router.php';
require_once __DIR__ . '/../src/Core/Kernel.php';
require_once __DIR__ . '/../src/Core/helper.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/DashboardController.php';
require_once __DIR__ . '/../src/Controllers/PageController.php';
require_once __DIR__ . '/../src/Controllers/PaymentController.php';

Env::load(__DIR__ . '/../.env');
Logger::register();

$errorHandler = new ErrorHandler();
$errorHandler->register();

Auth::startSession();
$request = Request::capture();
$kernel = new Kernel();
$response = $kernel->handle($request);
$response->send();
