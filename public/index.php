<?php
require_once '../app/config/config.php';
require_once '../vendor/autoload.php';

use App\Core\Router;
use App\Core\Session;

// Start Session
Session::start();
Session::autoRegenerate(300);

// Load Routes
$router = new Router();
require_once '../app/routes.php';

// Dispatch Request
$router->dispatch($_GET['url'] ?? '', $_SERVER['REQUEST_METHOD']);



