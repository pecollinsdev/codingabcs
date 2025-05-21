<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\QuizAttemptModel;
use App\Core\Response;

/**
 * Controller for handling user performance analytics
 * 
 * Provides performance statistics and analytics for user quiz attempts
 */
class PerformanceController extends Controller {
    /**
     * @var QuizAttemptModel Quiz attempt model instance
     */
    private QuizAttemptModel $attemptModel;

    /**
     * Constructor for the PerformanceController
     *
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request) {
        parent::__construct($request);
        $this->attemptModel = new QuizAttemptModel();
    }

    /**
     * Get user's performance statistics
     * 
     * Endpoint: GET /api/performance
     * Access: Authentication required
     * Returns:
     *   - Average score, total attempts, best score
     *   - Score trends over time
     *   - Performance by question type
     *   - Performance by difficulty
     *   - Performance by time of day
     *   - Performance by day of week
     *   - Recent attempts
     *
     * @return void Sends JSON response with performance data
     */
    public function getPerformance(): void {
        try {
            $user = $this->request->getUser();
            if (!$user) {
                $this->respondUnauthorized('Authentication required');
                return;
            }

            $userId = $user['id'];
            
            // Get all attempts for the user
            $attempts = $this->attemptModel->getUserAttempts($userId, true);
            if (empty($attempts)) {
                $this->respond([
                    'status' => 'success',
                    'data' => [
                        'average_score' => 0,
                        'total_attempts' => 0,
                        'best_score' => 0,
                        'improvement' => 0,
                        'score_trend' => [
                            'labels' => [],
                            'scores' => []
                        ],
                        'type_performance' => [
                            'labels' => ['Coding', 'Multiple Choice'],
                            'scores' => [0, 0]
                        ],
                        'day_of_week_performance' => [
                            'labels' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                            'scores' => [0, 0, 0, 0, 0, 0, 0]
                        ],
                        'time_analysis' => [
                            'labels' => ['Morning (6-12)', 'Afternoon (12-18)', 'Evening (18-24)', 'Night (0-6)'],
                            'scores' => [0, 0, 0, 0]
                        ],
                        'recent_attempts' => []
                    ]
                ]);
                return;
            }

            // Sort attempts by completed_at in descending order
            usort($attempts, function($a, $b) {
                return strtotime($b['completed_at']) - strtotime($a['completed_at']);
            });

            // Calculate statistics
            $scores = array_column($attempts, 'score');
            $averageScore = array_sum($scores) / count($scores);
            $bestScore = max($scores);
            
            // Calculate improvement (compare last 5 attempts with previous 5)
            $recentAttempts = array_slice($attempts, 0, 5);
            $previousAttempts = array_slice($attempts, 5, 5);
            $recentAverage = empty($recentAttempts) ? 0 : array_sum(array_column($recentAttempts, 'score')) / count($recentAttempts);
            $previousAverage = empty($previousAttempts) ? 0 : array_sum(array_column($previousAttempts, 'score')) / count($previousAttempts);
            $improvement = $previousAverage > 0 ? (($recentAverage - $previousAverage) / $previousAverage) * 100 : 0;

            // Prepare score trend data
            $scoreTrend = [
                'labels' => array_map(function($attempt) {
                    return [
                        'display' => date('M d', strtotime($attempt['completed_at'])),
                        'full' => date('M d, g:i A', strtotime($attempt['completed_at']))
                    ];
                }, $attempts),
                'scores' => $scores
            ];

            // Calculate performance by question type
            $typePerformance = [
                'labels' => ['Coding', 'Multiple Choice'],
                'scores' => [
                    $this->calculateTypeScore($attempts, 'coding'),
                    $this->calculateTypeScore($attempts, 'multiple_choice')
                ]
            ];

            // Calculate difficulty performance
            $difficultyPerformance = $this->calculateDifficultyPerformance($attempts);

            // Calculate time of day analysis
            $timeAnalysis = $this->calculateTimeAnalysis($attempts);

            // Calculate day of week performance
            $dayOfWeekPerformance = $this->calculateDayOfWeekPerformance($attempts);

            // Prepare recent attempts data - ensure scores are properly formatted
            $recentAttempts = array_map(function($attempt) {
                return [
                    'id' => $attempt['id'],
                    'quiz_id' => $attempt['quiz_id'],
                    'quiz_title' => $attempt['quiz_title'],
                    'completed_at' => $attempt['completed_at'],
                    'score' => isset($attempt['score']) ? (float)$attempt['score'] : 0,
                    'time_taken' => (int)$attempt['time_taken']
                ];
            }, array_slice($attempts, 0, 5));

            $this->respond([
                'status' => 'success',
                'data' => [
                    'average_score' => round($averageScore, 1),
                    'total_attempts' => count($attempts),
                    'best_score' => round($bestScore, 1),
                    'improvement' => round($improvement, 1),
                    'score_trend' => $scoreTrend,
                    'type_performance' => $typePerformance,
                    'day_of_week_performance' => $dayOfWeekPerformance,
                    'difficulty_performance' => $difficultyPerformance,
                    'time_analysis' => $timeAnalysis,
                    'recent_attempts' => $recentAttempts
                ]
            ]);
        } catch (\Exception $e) {
            $this->respondError('Failed to get performance data', 500);
        }
    }

    /**
     * Calculate average score for a specific question type
     *
     * @param array $attempts Array of quiz attempt data
     * @param string $type Question type ('coding' or 'multiple_choice')
     * @return float Average score for the specified question type
     */
    private function calculateTypeScore(array $attempts, string $type): float {
        $typeScores = [];
        foreach ($attempts as $attempt) {
            // For coding type, prioritize the actual quiz score if quiz_type is coding
            if ($type === 'coding') {
                if ($attempt['quiz_type'] === 'coding') {
                    $typeScores[] = (float)$attempt['score'];
                    continue;
                }
                
                // If not a coding quiz, check type_scores
                if (isset($attempt['type_scores']) && is_array($attempt['type_scores'])) {
                    $score = $attempt['type_scores']['coding'] ?? $attempt['type_scores']['programming'] ?? null;
                    if ($score !== null) {
                        $typeScores[] = (float)$score;
                    }
                }
            } else {
                // For multiple choice, use type_scores
                if (isset($attempt['type_scores']) && is_array($attempt['type_scores'])) {
                    $score = $attempt['type_scores'][$type] ?? null;
                    if ($score !== null) {
                        $typeScores[] = (float)$score;
                    }
                }
            }
        }
        
        $result = empty($typeScores) ? 0 : round(array_sum($typeScores) / count($typeScores), 1);
        return $result;
    }

    /**
     * Calculate performance by difficulty level
     *
     * @param array $attempts Array of quiz attempt data
     * @return array Performance data by difficulty level (easy, medium, hard)
     */
    private function calculateDifficultyPerformance(array $attempts): array {
        $difficultyScores = [
            'labels' => ['Easy', 'Medium', 'Hard'],
            'scores' => [0, 0, 0]
        ];

        $difficultyCounts = [0, 0, 0];

        foreach ($attempts as $attempt) {
            if (isset($attempt['difficulty'])) {
                $index = array_search($attempt['difficulty'], ['easy', 'medium', 'hard']);
                if ($index !== false) {
                    $difficultyScores['scores'][$index] += $attempt['score'];
                    $difficultyCounts[$index]++;
                }
            }
        }

        // Calculate averages
        for ($i = 0; $i < 3; $i++) {
            if ($difficultyCounts[$i] > 0) {
                $difficultyScores['scores'][$i] = round($difficultyScores['scores'][$i] / $difficultyCounts[$i], 1);
            }
        }

        return $difficultyScores;
    }

    /**
     * Calculate performance by time of day
     *
     * @param array $attempts Array of quiz attempt data
     * @return array Performance data by time of day (morning, afternoon, evening, night)
     */
    private function calculateTimeAnalysis(array $attempts): array {
        $timeSlots = [
            'labels' => ['Morning (6-12)', 'Afternoon (12-18)', 'Evening (18-24)', 'Night (0-6)'],
            'scores' => [0, 0, 0, 0]
        ];

        $timeCounts = [0, 0, 0, 0];

        foreach ($attempts as $attempt) {
            if (isset($attempt['completed_at'])) {
                $hour = (int)date('H', strtotime($attempt['completed_at']));
                $index = 0;
                
                if ($hour >= 6 && $hour < 12) {
                    $index = 0; // Morning
                } elseif ($hour >= 12 && $hour < 18) {
                    $index = 1; // Afternoon
                } elseif ($hour >= 18 && $hour < 24) {
                    $index = 2; // Evening
                } else {
                    $index = 3; // Night
                }

                $timeSlots['scores'][$index] += $attempt['score'];
                $timeCounts[$index]++;
            }
        }

        // Calculate averages
        for ($i = 0; $i < 4; $i++) {
            if ($timeCounts[$i] > 0) {
                $timeSlots['scores'][$i] = round($timeSlots['scores'][$i] / $timeCounts[$i], 1);
            }
        }

        return $timeSlots;
    }

    /**
     * Calculate performance by day of week
     *
     * @param array $attempts Array of quiz attempt data
     * @return array Performance data by day of week (Sunday through Saturday)
     */
    private function calculateDayOfWeekPerformance(array $attempts): array {
        $dayPerformance = [
            'labels' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'scores' => [0, 0, 0, 0, 0, 0, 0]
        ];

        $dayCounts = [0, 0, 0, 0, 0, 0, 0];

        foreach ($attempts as $attempt) {
            if (isset($attempt['completed_at'])) {
                $dayOfWeek = (int)date('w', strtotime($attempt['completed_at'])); // 0 = Sunday, 6 = Saturday
                $dayPerformance['scores'][$dayOfWeek] += $attempt['score'];
                $dayCounts[$dayOfWeek]++;
            }
        }

        // Calculate averages
        for ($i = 0; $i < 7; $i++) {
            if ($dayCounts[$i] > 0) {
                $dayPerformance['scores'][$i] = round($dayPerformance['scores'][$i] / $dayCounts[$i], 1);
            }
        }

        return $dayPerformance;
    }
}