<?php

use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;

$authMiddleware = new AuthMiddleware();
$adminMiddleware = new AdminMiddleware();

// ---------------------------
// CONFIG ROUTES
// ---------------------------
$router->get('/config', 'ConfigController@index');

// ---------------------------
// AUTH ROUTES
// ---------------------------
$router->post('/register', 'AuthController@register');
$router->post('/login', 'AuthController@login');
$router->get('/me', 'AuthController@me', [$authMiddleware]);
$router->post('/logout', 'AuthController@logout', [$authMiddleware]);
$router->post('/auth/validate', 'AuthController@validate');

// ---------------------------
// LEADERBOARD ROUTES
// ---------------------------
$router->get('/leaderboard', 'LeaderboardController@index', [$authMiddleware]);

// ---------------------------
// QUIZ ROUTES (Admin + Public)
// ---------------------------
$router->get('/quizzes', 'QuizController@index', [AuthMiddleware::class]);
$router->get('/quizzes/{quiz_id}', 'QuizController@show', [AuthMiddleware::class]);
$router->post('/quizzes', 'QuizController@store', [AuthMiddleware::class, AdminMiddleware::class]);
$router->patch('/quizzes/{quiz_id}', 'QuizController@update', [AuthMiddleware::class, AdminMiddleware::class]);
$router->delete('/quizzes/{quiz_id}', 'QuizController@destroy', [AuthMiddleware::class, AdminMiddleware::class]);

// ---------------------------
// QUESTION ROUTES (Admin)
// ---------------------------

// Public list of questions per quiz
$router->get('/quizzes/{quiz_id}/questions', 'QuestionController@index', [AuthMiddleware::class]);

// View a single question
$router->get('/questions/{question_id}', 'QuestionController@show', [AuthMiddleware::class]);

// Admin-only question management
$router->post('/quizzes/{quiz_id}/questions', 'QuestionController@store', [AuthMiddleware::class, AdminMiddleware::class]);
$router->patch('/questions/{question_id}', 'QuestionController@update', [AuthMiddleware::class, AdminMiddleware::class]);
$router->delete('/questions/{question_id}', 'QuestionController@destroy', [AuthMiddleware::class, AdminMiddleware::class]);

// ---------------------------
// ANSWER ROUTES (Admin)
// ---------------------------

$router->get('/answers/{answer_id}', 'AnswerController@show', [AuthMiddleware::class]);
$router->patch('/answers/{answer_id}', 'AnswerController@update', [AuthMiddleware::class, AdminMiddleware::class]);
$router->delete('/answers/{answer_id}', 'AnswerController@destroy', [AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/questions/{question_id}/answers', 'AnswerController@store', [AuthMiddleware::class, AdminMiddleware::class]);

// ---------------------------
// (OPTIONAL) QUIZ ATTEMPTS / SUBMISSIONS
// ---------------------------
$router->post('/quizzes/{quiz_id}/attempts', 'QuizAttemptController@submitAttempt', [AuthMiddleware::class]);
$router->get('/users/{user_id}/attempts', 'QuizAttemptController@getAttempts', [AuthMiddleware::class]);
$router->get('/quizzes/{quiz_id}/attempts', 'QuizAttemptController@getAttempts', [AuthMiddleware::class]);
$router->get('/quizzes/{quiz_id}/attempts/{attempt_id}', 'QuizAttemptController@getAttempt', [AuthMiddleware::class]);

// Code execution routes
$router->post('/code/execute', 'CodeExecutionController@execute', [AuthMiddleware::class]);
$router->get('/code/languages', 'CodeExecutionController@getSupportedLanguages', [AuthMiddleware::class]);

// Performance routes
$router->get('/performance', 'PerformanceController@getPerformance', [AuthMiddleware::class]);

// ---------------------------
// STATS ROUTES
// ---------------------------
$router->get('/stats', 'StatsController@getStats', [AuthMiddleware::class]);

// ---------------------------
// ACTIVITY ROUTES
// ---------------------------
$router->get('/activity', 'ActivityController@getActivity', [AuthMiddleware::class]);
$router->post('/activity', 'ActivityController@recordActivity', [AuthMiddleware::class]);

// Admin Panel (Dashboard)
$router->get('/admin/panel', 'AdminController@panel', [AuthMiddleware::class, AdminMiddleware::class]);
$router->get('/admin/stats', 'AdminController@stats', [AuthMiddleware::class, AdminMiddleware::class]);

// User Management Routes
$router->get('/admin/users', 'AdminController@users', [AuthMiddleware::class, AdminMiddleware::class]);
$router->get('/admin/users/{user_id}', 'AdminController@show', [AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/admin/users', 'AdminController@store', [AuthMiddleware::class, AdminMiddleware::class]);
$router->patch('/admin/users/{user_id}', 'AdminController@toggleUserStatus', [AuthMiddleware::class, AdminMiddleware::class]);
$router->delete('/admin/users/{user_id}', 'AdminController@destroy', [AuthMiddleware::class, AdminMiddleware::class]);

// Quiz Management Routes
$router->get('/admin/quizzes', 'AdminController@quizzes', [AuthMiddleware::class, AdminMiddleware::class]);
$router->get('/admin/quizzes/{quiz_id}/questions', 'AdminController@quizQuestions', [AuthMiddleware::class, AdminMiddleware::class]);

// Quiz Progress Routes
$router->get('/quizzes/progress', 'QuizProgressController@getActiveQuiz', [AuthMiddleware::class]);

// ---------------------------
// QUIZ PROGRESS ROUTES
// ---------------------------
$router->get('/quizzes/{quiz_id}/progress', 'QuizProgressController@loadProgress', [AuthMiddleware::class]);
$router->post('/quizzes/{quiz_id}/progress', 'QuizProgressController@saveProgress', [AuthMiddleware::class]);
$router->delete('/quizzes/{quiz_id}/progress', 'QuizProgressController@clearProgress', [AuthMiddleware::class]);

// Achievement routes
$router->get('/achievements', 'AchievementController@getUserAchievements');
$router->get('/achievements/stats', 'AchievementController@getAchievementStats');
$router->get('/achievements/recent', 'AchievementController@getRecentAchievements');

