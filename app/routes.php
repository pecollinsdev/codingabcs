<?php
// Define the routes and corresponding controllers for the application

// Define the routes for the landing page
$router->add('GET', '', 'HomeController@index');
$router->add('GET', 'home', 'HomeController@index');

// Define the routes for the authentication
$router->add('GET', 'auth/login', 'AuthController@login');
$router->add('POST', 'auth/login', 'AuthController@loginPost');
$router->add('GET', 'auth/register', 'AuthController@register');
$router->add('POST', 'auth/register', 'AuthController@registerPost');
$router->add('GET', 'auth/logout', 'AuthController@logout');

// Define the routes for the dashboard
$router->add('GET', 'dashboard', 'DashboardController@index');

// Define the routes for the quizzes
$router->add('GET', 'quiz/quizzes', 'QuizController@index');
$router->add('GET', 'quiz/view/{id}', 'QuizController@viewQuiz');
$router->add('GET', 'quiz/start/{id}', 'QuizController@start');
$router->add('POST', 'quiz/submit', 'QuizController@submit');
$router->add('GET', 'quiz/result/{quizId}/{attemptId}', 'QuizController@result');

// Define the routes for user performance
$router->add('GET', 'stats/performance', 'PerformanceController@index');
$router->add('GET', 'stats/leaderboard', 'LeaderboardController@index');
