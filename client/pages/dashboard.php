<?php
require_once __DIR__ . '/../assets/php/card_builder.php';
require_once __DIR__ . '/../assets/php/api_client.php';

// Initialize card builder
$cardBuilder = new CardBuilder();

// Fetch dashboard data from API
$apiClient = new ApiClient();
$stats = $apiClient->get('/stats');
$activity = $apiClient->get('/activity');
$performance = $apiClient->get('/performance');


// Extract data from API responses
$statsData = is_array($stats) && isset($stats['data']) ? $stats['data'] : [];
$activityData = is_array($activity) && isset($activity['data']) ? $activity['data'] : [];
$performanceData = is_array($performance) && isset($performance['data']) ? $performance['data'] : [];

// Add data to window object for JavaScript
echo '<script>
    window.statsData = ' . json_encode($statsData) . ';
    window.activityData = ' . json_encode($activityData) . ';
    window.performanceData = ' . json_encode($performanceData) . ';
</script>';

// Check if we have valid data
?>

<div class="dashboard-container">
    <?php echo $cardBuilder->addButtonStyles(); ?>
    
    <!-- Dashboard Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Dashboard</h1>
    </div>

    <div class="row g-4">
        <!-- Stats Cards -->
        <div class="col-md-3">
            <?php
            echo $cardBuilder->build([
                'title' => 'Total Quizzes',
                'content' => '<h2 class="stat-value">' . ($statsData['quizzes_taken'] ?? 0) . '</h2>',
                'icon' => '<i class="fas fa-question-circle"></i>',
                'classes' => 'stat-card',
                'tooltip' => 'Total number of quizzes taken',
                'hover' => false
            ]);
            ?>
        </div>
        <div class="col-md-3">
            <?php
            echo $cardBuilder->build([
                'title' => 'Current Streak',
                'content' => '<h2 class="stat-value">' . ($statsData['current_streak'] ?? 0) . ' days</h2>',
                'icon' => '<i class="fas fa-fire"></i>',
                'classes' => 'stat-card',
                'tooltip' => 'Your current quiz streak',
                'hover' => false
            ]);
            ?>
        </div>
        <div class="col-md-3">
            <?php
            echo $cardBuilder->build([
                'title' => 'Average Score',
                'content' => '<h2 class="stat-value">' . ($statsData['average_score'] ?? 0) . '%</h2>',
                'icon' => '<i class="fas fa-chart-line"></i>',
                'classes' => 'stat-card',
                'tooltip' => 'Your average score across all completed quizzes',
                'hover' => false
            ]);
            ?>
        </div>
        <div class="col-md-3">
            <?php
            echo $cardBuilder->build([
                'title' => 'Rank',
                'content' => '<h2 class="stat-value">#' . ($statsData['rank'] ?? 'N/A') . '</h2>',
                'icon' => '<i class="fas fa-trophy"></i>',
                'classes' => 'stat-card',
                'tooltip' => 'Your current position in the leaderboard',
                'hover' => false
            ]);
            ?>
        </div>
    </div>

    <div class="row mt-4 g-4">
        <!-- Quick Actions -->
        <div class="col-md-4">
            <?php
            echo $cardBuilder->build([
                'title' => 'Quick Actions',
                'content' => '
                    <div class="d-grid gap-2">
                        ' . $cardBuilder->primaryButton('Start New Quiz', 'quiz.php', 'fas fa-play') . '
                        ' . $cardBuilder->outlineButton('Resume Quiz', 'quiz.php?resume=1', 'fas fa-redo') . '
                        ' . $cardBuilder->outlineButton('View Performance', 'index.php?page=performance', 'fas fa-chart-bar') . '
                        ' . $cardBuilder->outlineButton('Leaderboard', 'index.php?page=leaderboard', 'fas fa-trophy') . '
                    </div>
                ',
                'classes' => 'quick-actions',
                'hover' => false
            ]);
            ?>
        </div>

        <!-- Recent Activity -->
        <div class="col-md-8">
            <?php
            $activityContent = '<div class="list-group list-group-flush">';
            
            // Check if we have activities data
            if (empty($activityData)) {
                $activityContent .= '
                    <div class="list-group-item text-center py-4" style="background-color: var(--card-bg);">
                        <i class="fas fa-history fa-2x mb-3" style="color: var(--text-muted);"></i>
                        <p class="mb-0" style="color: var(--text-muted);">No recent activity to display</p>
                        <small style="color: var(--text-muted);">Your activities will appear here</small>
                    </div>
                ';
            } else {
                // Limit to 5 items
                $activityData = array_slice($activityData, 0, 5);
                foreach ($activityData as $item) {
                    // Map activity types to icons
                    $iconMap = [
                        'quiz' => 'fa-question-circle',
                        'achievement' => 'fa-trophy',
                        'login' => 'fa-sign-in-alt',
                        'logout' => 'fa-sign-out-alt',
                        'profile_update' => 'fa-user-edit',
                        'default' => 'fa-circle'
                    ];
                    
                    $icon = $iconMap[$item['type']] ?? $iconMap['default'];
                    
                    // Format the timestamp
                    $timestamp = new DateTime($item['timestamp']);
                    $timeAgo = $timestamp->format('M j, Y g:i A');
                    
                    $activityContent .= '
                        <div class="list-group-item" style="background-color: var(--card-bg); border-color: var(--border-color);">
                            <div class="d-flex align-items-center">
                                <div class="activity-icon me-3">
                                    <i class="fas ' . $icon . '" style="color: var(--primary-color);"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1" style="color: var(--text-muted);">' . htmlspecialchars($item['title']) . '</h6>
                                    <small style="color: var(--text-muted);">' . $timeAgo . '</small>
                                </div>
                            </div>
                        </div>
                    ';
                }
            }
            $activityContent .= '</div>';

            echo $cardBuilder->build([
                'title' => 'Recent Activity',
                'content' => $activityContent,
                'classes' => 'activity-feed',
                'hover' => false
            ]);
            ?>
        </div>
    </div>

    <!-- Performance Overview -->
    <div class="row mt-4">
        <div class="col-12">
            <?php
            $performanceContent = '
                <div class="row">
                    <div class="col-md-6">
                        <h5 style="color: var(--text-color);">Progress Over Time</h5>
                        <div class="chart-container" style="min-height: 300px; background-color: var(--card-bg); border-radius: 8px; border: 1px solid var(--border-color);">
                            ' . (empty($performanceData['score_trend']['labels']) ? '
                                <div class="d-flex flex-column align-items-center justify-content-center h-100 p-4">
                                    <i class="fas fa-chart-line fa-3x mb-3" style="color: var(--text-muted);"></i>
                                    <p class="mb-0" style="color: var(--text-muted);">No performance data available</p>
                                    <small style="color: var(--text-muted);">Complete some quizzes to see your progress</small>
                                </div>
                            ' : '<canvas id="performanceChart"></canvas>') . '
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 style="color: var(--text-color);">Quiz Categories</h5>
                        <div class="chart-container" style="min-height: 300px; background-color: var(--card-bg); border-radius: 8px; border: 1px solid var(--border-color);">
                            ' . (empty($performanceData['type_performance']['labels']) ? '
                                <div class="d-flex flex-column align-items-center justify-content-center h-100 p-4">
                                    <i class="fas fa-pie-chart fa-3x mb-3" style="color: var(--text-muted);"></i>
                                    <p class="mb-0" style="color: var(--text-muted);">No category data available</p>
                                    <small style="color: var(--text-muted);">Try different types of quizzes to see category breakdown</small>
                                </div>
                            ' : '<canvas id="categoriesChart"></canvas>') . '
                        </div>
                    </div>
                </div>
            ';

            echo $cardBuilder->build([
                'title' => 'Performance Overview',
                'content' => $performanceContent,
                'classes' => 'performance-overview',
                'hover' => false
            ]);
            ?>
        </div>
    </div>
</div>

<!-- Resume Quiz Modal -->
<div class="modal fade" id="resumeQuizModal" tabindex="-1" aria-labelledby="resumeQuizModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resumeQuizModalLabel">Resume Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="resumeQuizInfo">
                    <p>You have an active quiz in progress:</p>
                    <h6 id="resumeQuizTitle"></h6>
                    <p class="text-muted" id="resumeQuizProgress"></p>
                </div>
                <div id="noActiveQuiz" class="d-none">
                    <p>No active quiz found. Would you like to start a new quiz?</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="resumeQuizBtn" class="btn btn-primary">Resume Quiz</a>
            </div>
        </div>
    </div>
</div>

<!-- Add Chart.js for performance visualization -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Define theme colors with the theme's primary green
    const chartColors = {
        green: '#10a37f',
        lightGreen: 'rgba(16, 163, 127, 0.2)',
        borderGreen: 'rgba(16, 163, 127, 0.8)',
        greenGradient: [
            'rgba(16, 163, 127, 1)',
            'rgba(16, 163, 127, 0.8)',
            'rgba(16, 163, 127, 0.6)',
            'rgba(16, 163, 127, 0.4)'
        ]
    };

    // Common chart options
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
                labels: {
                    color: 'var(--text-color)',
                    font: {
                        size: 12
                    },
                    padding: 20
                }
            },
            tooltip: {
                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                titleColor: '#333',
                bodyColor: '#333',
                borderColor: 'var(--border-color)',
                borderWidth: 1,
                padding: 12,
                displayColors: true,
                titleFont: {
                    size: 13,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 12
                },
                callbacks: {
                    label: function(context) {
                        return ` ${context.dataset.label}: ${context.parsed.y}%`;
                    }
                }
            }
        }
    };

    // Common scale options
    const commonScales = {
        y: {
            beginAtZero: true,
            max: 100,
            ticks: {
                color: 'var(--text-color)',
                font: {
                    size: 11
                },
                padding: 8,
                callback: function(value) {
                    return value + '%';
                }
            },
            grid: {
                color: 'var(--border-color)',
                drawBorder: false,
                lineWidth: 0.5
            }
        },
        x: {
            ticks: {
                color: 'var(--text-color)',
                font: {
                    size: 11
                },
                padding: 8
            },
            grid: {
                display: false
            }
        }
    };

    // Performance Chart
    const performanceChartEl = document.getElementById('performanceChart');
    if (performanceChartEl) {
        const performanceCtx = performanceChartEl.getContext('2d');
        const scoreTrendData = <?php echo json_encode($performanceData['score_trend'] ?? ['labels' => [], 'scores' => []]); ?>;
        
        new Chart(performanceCtx, {
            type: 'line',
            data: {
                labels: scoreTrendData.labels.map(label => label.display),
                datasets: [{
                    label: 'Score',
                    data: scoreTrendData.scores,
                    borderColor: chartColors.green,
                    backgroundColor: chartColors.lightGreen,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: chartColors.green,
                    pointBorderColor: 'var(--card-bg)',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                ...commonOptions,
                scales: commonScales,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            title: function(context) {
                                const dataIndex = context[0].dataIndex;
                                return scoreTrendData.labels[dataIndex].full;
                            },
                            label: function(context) {
                                return `Score: ${context.parsed.y}%`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Categories Chart
    const categoriesChartEl = document.getElementById('categoriesChart');
    if (categoriesChartEl) {
        const categoriesCtx = categoriesChartEl.getContext('2d');
        const typePerformanceData = <?php echo json_encode($performanceData['type_performance'] ?? ['labels' => [], 'scores' => []]); ?>;
        
        new Chart(categoriesCtx, {
            type: 'doughnut',
            data: {
                labels: typePerformanceData.labels,
                datasets: [{
                    data: typePerformanceData.scores,
                    backgroundColor: chartColors.greenGradient,
                    borderColor: 'var(--card-bg)',
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                ...commonOptions,
                cutout: '70%',
                radius: '90%',
                plugins: {
                    ...commonOptions.plugins,
                    legend: {
                        display: true,
                        position: 'right',
                        labels: {
                            color: 'var(--text-color)',
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw}%`;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
