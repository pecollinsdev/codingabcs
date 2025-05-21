<?php
// File: /codingabcs/client/public/index.php

// 1) Base path constant
define('BASE_PATH', dirname(__DIR__));

// 2) Derive the base URI dynamically (handles sub-folder installs)
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$baseUri    = rtrim(dirname($scriptName), '/');  // e.g. '/codingabcs/client/public' or ''

// 3) Extract the request path
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($baseUri !== '' && strpos($requestUri, $baseUri) === 0) {
    $request = substr($requestUri, strlen($baseUri));
} else {
    $request = $requestUri;
}
$request = trim($request, '/');

// 4) Split into segments
$parts = explode('/', $request);
$page  = $parts[0] ?? 'home';

// 5) Special route handling for quiz and results
if ($page === 'quiz') {
    if (isset($parts[1]) && is_numeric($parts[1])) {
        $_GET['id'] = (int)$parts[1];
    } else {
        header("Location: {$baseUri}/quizzes");
        exit;
    }
}
if ($page === 'results') {
    if (isset($parts[1]) && is_numeric($parts[1])) {
        $_GET['id'] = (int)$parts[1];
    } else {
        header("Location: {$baseUri}/quizzes");
        exit;
    }
}
if ($page === 'admin') {
    if (isset($parts[1])) {
        if ($parts[1] === 'quiz_create') {
            $page = 'admin_quiz_create';
        } else {
            $page = 'admin_' . $parts[1];
            if (isset($parts[2]) && is_numeric($parts[2])) {
                $_GET['id'] = (int)$parts[2];
            }
        }
    } else {
        $page = 'admin_dashboard';
    }
} elseif ($page === 'admin_quiz_create') {
    $page = 'admin_quiz_create';
}

// 6) Merge any query-string parameters into $_GET
if (!empty($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $queryParams);
    $_GET = array_merge($_GET, $queryParams);
}

// 7) Default to "home" if blank
$page = $page === '' ? 'home' : $page;

// 8) Whitelist pages and layout flags
$pageSettings = [
    'home'            => ['showSidebar' => false, 'showFooter' => true],
    'dashboard'       => ['showSidebar' => true,  'showFooter' => false],
    'quizzes'         => ['showSidebar' => true,  'showFooter' => false],
    'performance'     => ['showSidebar' => true,  'showFooter' => false],
    'leaderboard'     => ['showSidebar' => true,  'showFooter' => false],
    'quiz'            => ['showSidebar' => false, 'showFooter' => false],
    'results'         => ['showSidebar' => false, 'showFooter' => false],
    'login'           => ['showSidebar' => false, 'showFooter' => true],
    'register'        => ['showSidebar' => false, 'showFooter' => true],
    'admin_dashboard' => ['showSidebar' => true,  'showFooter' => false],
    'admin_users'     => ['showSidebar' => true,  'showFooter' => false],
    'admin_quizzes'   => ['showSidebar' => true,  'showFooter' => false],
    'admin_quiz_edit' => ['showSidebar' => true,  'showFooter' => false],
    'admin_quiz_create' => ['showSidebar' => true,  'showFooter' => false],
    '404'             => ['showSidebar' => false, 'showFooter' => true],
];

// 9) Validate page; fallback to 404
if (!array_key_exists($page, $pageSettings)) {
    $page = '404';
}
$settings    = $pageSettings[$page];
$showSidebar = $settings['showSidebar'];
$showFooter  = $settings['showFooter'];

// 10) Hand off to our layout (which includes pages/{$page}.php)
require_once BASE_PATH . '/components/layout.php';
