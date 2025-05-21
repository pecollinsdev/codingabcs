<?php

// Load environment variables
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Logger\Logger;
use Dotenv\Dotenv;
use App\Core\ErrorHandler;
use App\Core\Session;
use App\Core\Router;
use App\Core\Request;
use App\Services\JwtService;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configure JWT service
JwtService::configureFromEnv();

// Initialize logger with configuration
Logger::configure(require __DIR__ . '/../src/config/logger.php');

// Register error handler
ErrorHandler::register();

// Start session
Session::start();

// Initialize router
$request = new Request();
$router = new Router('codingabcs/api/public');

// Include routes
require_once __DIR__ . '/../src/routes/api.php';

// Dispatch request
$router->dispatch($request->getUri(), $request->getMethod());

