<?php
require_once __DIR__ . '/../assets/php/card_builder.php';
require_once __DIR__ . '/../assets/php/api_client.php';

// Initialize card builder
$cardBuilder = new CardBuilder();

// Get user ID from JWT token
$userId = null;
if (isset($_COOKIE['jwt_token'])) {
    try {
        $token = $_COOKIE['jwt_token'];
        $payload = json_decode(base64_decode(explode('.', $token)[1]), true);
        $userId = $payload['id'] ?? null;
    } catch (Exception $e) {
        // Token is invalid or malformed
        $userId = null;
    }
}

// Fetch leaderboard data from API
$apiClient = new ApiClient();
$response = $apiClient->get("/leaderboard" . ($userId ? "?user_id=$userId" : ""));
$data = is_array($response) && isset($response['data']) ? $response['data'] : [];
?>

<div class="leaderboard-container">
    <?php echo $cardBuilder->addButtonStyles(); ?>
    
    <!-- Leaderboard Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Leaderboard</h1>
    </div>

    <div class="row g-4">
        <!-- User Rank Card -->
        <div class="col-md-4">
            <?php
            $userRank = $data['user_rank'] ?? [];
            echo $cardBuilder->build([
                'title' => 'Your Rank',
                'content' => '
                    <div class="card-body">
                        <div class="rank-badge">
                            <i class="fas fa-trophy"></i>
                            <span class="rank-number">#' . ($userRank['rank'] ?? 'N/A') . '</span>
                        </div>
                        <h3>' . htmlspecialchars($userRank['name'] ?? 'Guest') . '</h3>
                        <p>
                            <i class="fas fa-chart-line"></i>
                            Average Score: ' . number_format($userRank['average_score'] ?? 0, 1) . '%
                        </p>
                        <p>
                            <i class="fas fa-tasks"></i>
                            Unique Quizzes: ' . ($userRank['total_quizzes'] ?? 0) . '
                        </p>
                        <p>
                            <i class="fas fa-fire"></i>
                            Current Streak: ' . ($userRank['current_streak'] ?? 0) . ' days
                        </p>
                        <p>
                            <i class="fas fa-star"></i>
                            Best Score: ' . number_format($userRank['best_score'] ?? 0, 1) . '%
                        </p>
                    </div>
                ',
                'classes' => 'rank-card',
                'hover' => false
            ]);
            ?>
        </div>

        <!-- Top Performers -->
        <div class="col-md-8">
            <?php
            $topPerformers = $data['top_performers'] ?? [];
            $topPerformersContent = '
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-center">Rank</th>
                                <th scope="col">User</th>
                                <th scope="col" class="text-center">Avg Score</th>
                                <th scope="col" class="text-center">Unique Quizzes</th>
                                <th scope="col" class="text-center">Streak</th>
                                <th scope="col" class="text-center">Best</th>
                            </tr>
                        </thead>
                        <tbody>
            ';

            if (empty($topPerformers)) {
                $topPerformersContent .= '
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="fas fa-users fa-3x mb-3" style="color: var(--text-muted);"></i>
                            <p class="mb-0" style="color: var(--text-muted);">No leaderboard data available</p>
                        </td>
                    </tr>
                ';
            } else {
                foreach ($topPerformers as $user) {
                    $topPerformersContent .= '
                        <tr class="' . ($user['rank'] <= 3 ? 'top-' . $user['rank'] : '') . '">
                            <td class="text-center">
                                <span class="rank-badge">#' . $user['rank'] . '</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="' . htmlspecialchars($user['avatar']) . '" 
                                         alt="' . htmlspecialchars($user['name']) . '" 
                                         class="avatar me-2">
                                    <span>' . htmlspecialchars($user['name']) . '</span>
                                </div>
                            </td>
                            <td class="text-center">' . number_format($user['average_score'], 1) . '%</td>
                            <td class="text-center">' . $user['total_quizzes'] . '</td>
                            <td class="text-center">' . $user['current_streak'] . ' days</td>
                            <td class="text-center">' . number_format($user['best_score'], 1) . '%</td>
                        </tr>
                    ';
                }
            }

            $topPerformersContent .= '
                        </tbody>
                    </table>
                </div>
            ';

            echo $cardBuilder->build([
                'title' => 'Top Performers',
                'content' => $topPerformersContent,
                'classes' => 'leaderboard-card',
                'hover' => false
            ]);
            ?>
        </div>
    </div>

    <!-- Achievement Section -->
    <div class="row mt-4">
        <div class="col-12">
            <?php
            $achievements = $data['achievements'] ?? [];
            $achievementsContent = '
                <div class="achievements-grid">
            ';

            if (empty($achievements)) {
                $achievementsContent .= '
                    <div class="text-center py-4">
                        <i class="fas fa-trophy fa-3x mb-3" style="color: var(--text-muted);"></i>
                        <p class="mb-0" style="color: var(--text-muted);">No achievements available</p>
                    </div>
                ';
            } else {
                foreach ($achievements as $achievement) {
                    $achievementsContent .= '
                        <div class="achievement-card ' . ($achievement['unlocked'] ? 'unlocked' : 'locked') . '">
                            <div class="achievement-icon">
                                <i class="fas ' . $achievement['icon'] . '"></i>
                            </div>
                            <div class="achievement-info">
                                <h4>' . htmlspecialchars($achievement['title']) . '</h4>
                                <p class="mb-0">' . htmlspecialchars($achievement['description']) . '</p>
                                <small class="text-muted">' . 
                                    ($achievement['unlocked'] 
                                        ? 'Unlocked ' . date('M j, Y', strtotime($achievement['unlocked_at'])) 
                                        : 'Locked'
                                    ) . 
                                '</small>
                            </div>
                        </div>
                    ';
                }
            }

            $achievementsContent .= '
                </div>
            ';

            echo $cardBuilder->build([
                'title' => 'Achievements',
                'content' => $achievementsContent,
                'classes' => 'achievements-card',
                'hover' => false
            ]);
            ?>
        </div>
    </div>
</div>

<!-- Add necessary JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>