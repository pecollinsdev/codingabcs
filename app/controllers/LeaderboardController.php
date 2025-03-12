<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * LeaderboardController handles the leaderboard functionality.
 *
 * This controller is responsible for displaying the leaderboard
 * page with the top users based on their quiz scores. It interacts
 * with the Leaderboard model to fetch the leaderboard data.
 */
class LeaderboardController extends Controller {
    // Leaderboard model instance
    private $leaderboardModel;

    // Constructor
    public function __construct() {
        $this->leaderboardModel = $this->model('LeaderboardModel');
    }

    // Display the leaderboard
    public function index() {
        $leaderboard = $this->leaderboardModel->getLeaderboard();
        $this->view('stats/leaderboard', ['leaderboard' => $leaderboard]);
    }
}

