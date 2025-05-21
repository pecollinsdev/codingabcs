<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\LeaderboardModel;

/**
 * Controller for handling leaderboard functionality
 * 
 * Provides access to user rankings and achievements
 */
class LeaderboardController extends Controller
{
    /**
     * @var LeaderboardModel Leaderboard model instance
     */
    private LeaderboardModel $model;

    /**
     * Constructor for the LeaderboardController
     *
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->model = new LeaderboardModel();
    }

    /**
     * Get leaderboard data including rankings and achievements
     * 
     * Endpoint: GET /api/leaderboard
     * Returns:
     *   - User's current rank information
     *   - Top performers list
     *   - User's achievements
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with leaderboard data
     */
    public function index(Request $request): void
    {
        // Get current user ID from request
        $currentUserId = $request->getUser()['id'] ?? null;

        // Get leaderboard data
        $data = $this->model->getLeaderboardData($currentUserId);

        // Structure the response
        $response = [
            'status' => 'success',
            'data' => [
                'user_rank' => $data['user_rank'] ? [
                    'rank' => $data['user_rank']['rank'],
                    'name' => $data['user_rank']['name'],
                    'avatar' => $data['user_rank']['avatar'],
                    'average_score' => $data['user_rank']['average_score'],
                    'total_quizzes' => $data['user_rank']['total_quizzes'],
                    'current_streak' => $data['user_rank']['current_streak'],
                    'best_score' => $data['user_rank']['best_score'],
                    'last_active' => $data['user_rank']['last_active']
                ] : [
                    'rank' => 'N/A',
                    'name' => $request->getUser()['username'] ?? 'Guest',
                    'avatar' => $request->getUser()['avatar'] ?? '/codingabcs/client/assets/images/default-avatar.png',
                    'average_score' => 0,
                    'total_quizzes' => 0,
                    'current_streak' => 0,
                    'best_score' => 0,
                    'last_active' => null
                ],
                'top_performers' => array_map(function($user) {
                    return [
                        'id' => $user['user_id'],
                        'name' => $user['username'],
                        'avatar' => $user['avatar'],
                        'average_score' => $user['average_score'],
                        'total_quizzes' => $user['total_quizzes'],
                        'current_streak' => $user['current_streak'],
                        'best_score' => $user['best_score'],
                        'last_active' => $user['last_active'],
                        'rank' => $user['rank']
                    ];
                }, $data['leaderboard'] ?? []),
                'achievements' => array_map(function($achievement) {
                    return [
                        'id' => $achievement['id'],
                        'title' => $achievement['title'],
                        'description' => $achievement['description'],
                        'icon' => $achievement['icon'],
                        'unlocked' => (bool)$achievement['unlocked'],
                        'unlocked_at' => $achievement['unlocked_at']
                    ];
                }, $this->model->getAchievements($currentUserId))
            ]
        ];

        $this->respond($response);
    }
}