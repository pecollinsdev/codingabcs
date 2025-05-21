<?php
require_once __DIR__ . '/../assets/php/card_builder.php';
require_once __DIR__ . '/../assets/php/api_client.php';

// Initialize card builder
$cardBuilder = new CardBuilder();

// Fetch performance data from API
$apiClient = new ApiClient();
$performance = $apiClient->get('/performance');
$stats = $apiClient->get('/stats');

// Extract data from API responses
$performanceData = is_array($performance) && isset($performance['data']) ? $performance['data'] : [];
$statsData = is_array($stats) && isset($stats['data']) ? $stats['data'] : [];

// Add data to window object for JavaScript
echo '<script>
    window.performanceData = ' . json_encode($performanceData) . ';
    window.statsData = ' . json_encode($statsData) . ';
</script>';
?>

<div class="performance-container">
    <?php echo $cardBuilder->addButtonStyles(); ?>
    
    <!-- Performance Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Performance Analytics</h1>
    </div>

    <div class="row g-4">
        <!-- Performance Overview Cards -->
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
                'title' => 'Quizzes Taken',
                'content' => '<h2 class="stat-value">' . ($statsData['quizzes_taken'] ?? 0) . '</h2>',
                'icon' => '<i class="fas fa-question-circle"></i>',
                'classes' => 'stat-card',
                'tooltip' => 'Total number of quizzes completed',
                'hover' => false
            ]);
            ?>
        </div>
        <div class="col-md-3">
            <?php
            echo $cardBuilder->build([
                'title' => 'Best Score',
                'content' => '<h2 class="stat-value">' . ($performanceData['best_score'] ?? 0) . '%</h2>',
                'icon' => '<i class="fas fa-trophy"></i>',
                'classes' => 'stat-card',
                'tooltip' => 'Your highest score achieved',
                'hover' => false
            ]);
            ?>
        </div>
        <div class="col-md-3">
            <?php
            echo $cardBuilder->build([
                'title' => 'Improvement',
                'content' => '<h2 class="stat-value">' . ($performanceData['improvement'] ?? 0) . '%</h2>',
                'icon' => '<i class="fas fa-arrow-up"></i>',
                'classes' => 'stat-card',
                'tooltip' => 'Score improvement over time',
                'hover' => false
            ]);
            ?>
        </div>
    </div>

    <!-- Performance Charts -->
    <div class="row mt-4 g-4">
        <!-- Score Trend -->
        <div class="col-md-8">
            <?php
            $scoreTrendContent = '
                <div class="chart-container" style="min-height: 400px;">
                    ' . (empty($performanceData['score_trend']['labels']) ? '
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 p-4">
                            <i class="fas fa-chart-line fa-3x mb-3" style="color: var(--text-muted);"></i>
                            <p class="mb-0" style="color: var(--text-muted);">No performance data available</p>
                            <small style="color: var(--text-muted);">Complete some quizzes to see your progress</small>
                        </div>
                    ' : '<canvas id="scoreTrendChart"></canvas>') . '
                </div>
            ';

            echo $cardBuilder->build([
                'title' => 'Score Trend',
                'content' => $scoreTrendContent,
                'classes' => 'chart-card',
                'hover' => false
            ]);
            ?>
        </div>

        <!-- Category Performance -->
        <div class="col-md-4">
            <?php
            $categoryContent = '
                <div class="chart-container" style="min-height: 400px;">
                    ' . (empty($performanceData['type_performance']['labels']) ? '
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 p-4">
                            <i class="fas fa-pie-chart fa-3x mb-3" style="color: var(--text-muted);"></i>
                            <p class="mb-0" style="color: var(--text-muted);">No category data available</p>
                            <small style="color: var(--text-muted);">Try different types of quizzes to see category breakdown</small>
                        </div>
                    ' : '<canvas id="categoryChart"></canvas>') . '
                </div>
            ';

            echo $cardBuilder->build([
                'title' => 'Category Performance',
                'content' => $categoryContent,
                'classes' => 'chart-card',
                'hover' => false
            ]);
            ?>
        </div>
    </div>

    <!-- Detailed Performance Metrics -->
    <div class="row mt-4 g-4">
        <!-- Time of Day Analysis -->
        <div class="col-md-6">
            <?php
            $timeAnalysisContent = '
                <div class="chart-container" style="min-height: 300px;">
                    ' . (empty($performanceData['time_analysis']['labels']) ? '
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 p-4">
                            <i class="fas fa-clock fa-3x mb-3" style="color: var(--text-muted);"></i>
                            <p class="mb-0" style="color: var(--text-muted);">No time analysis data available</p>
                        </div>
                    ' : '<canvas id="timeAnalysisChart"></canvas>') . '
                </div>
            ';

            echo $cardBuilder->build([
                'title' => 'Time of Day Analysis',
                'content' => $timeAnalysisContent,
                'classes' => 'chart-card',
                'hover' => false
            ]);
            ?>
        </div>

        <!-- Day of Week Performance -->
        <div class="col-md-6">
            <?php
            $dayOfWeekContent = '
                <div class="chart-container" style="min-height: 300px;">
                    ' . (empty($performanceData['day_of_week_performance']['labels']) ? '
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 p-4">
                            <i class="fas fa-calendar-alt fa-3x mb-3" style="color: var(--text-muted);"></i>
                            <p class="mb-0" style="color: var(--text-muted);">No day of week data available</p>
                        </div>
                    ' : '<canvas id="dayOfWeekChart"></canvas>') . '
                </div>
            ';

            echo $cardBuilder->build([
                'title' => 'Performance by Day of Week',
                'content' => $dayOfWeekContent,
                'classes' => 'chart-card',
                'hover' => false
            ]);
            ?>
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
                display: false, // Hide legend for bar charts
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
                display: false // Hide x-axis grid lines
            }
        }
    };

    // Score Trend Chart
    const scoreTrendChartEl = document.getElementById('scoreTrendChart');
    if (scoreTrendChartEl) {
        const scoreTrendCtx = scoreTrendChartEl.getContext('2d');
        const scoreTrendData = <?php echo json_encode($performanceData['score_trend'] ?? ['labels' => [], 'scores' => []]); ?>;
        
        window.scoreTrendChart = new Chart(scoreTrendCtx, {
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

    // Category Performance Chart
    const categoryChartEl = document.getElementById('categoryChart');
    if (categoryChartEl) {
        const categoryCtx = categoryChartEl.getContext('2d');
        window.categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($performanceData['type_performance']['labels'] ?? []); ?>,
                datasets: [{
                    data: <?php echo json_encode($performanceData['type_performance']['scores'] ?? []); ?>,
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

    // Time of Day Analysis Chart
    const timeAnalysisChartEl = document.getElementById('timeAnalysisChart');
    if (timeAnalysisChartEl) {
        const timeAnalysisCtx = timeAnalysisChartEl.getContext('2d');
        window.timeAnalysisChart = new Chart(timeAnalysisCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($performanceData['time_analysis']['labels'] ?? []); ?>,
                datasets: [{
                    label: 'Average Score',
                    data: <?php echo json_encode($performanceData['time_analysis']['scores'] ?? []); ?>,
                    backgroundColor: chartColors.lightGreen,
                    borderColor: chartColors.borderGreen,
                    borderWidth: 2,
                    borderRadius: 4,
                    hoverBackgroundColor: chartColors.green
                }]
            },
            options: {
                ...commonOptions,
                scales: commonScales,
                barThickness: 'flex',
                maxBarThickness: 40
            }
        });
    }

    // Day of Week Performance Chart
    const dayOfWeekChartEl = document.getElementById('dayOfWeekChart');
    if (dayOfWeekChartEl) {
        const dayOfWeekCtx = dayOfWeekChartEl.getContext('2d');
        const dayOfWeekData = <?php echo json_encode($performanceData['day_of_week_performance'] ?? ['labels' => [], 'scores' => []]); ?>;
        
        window.dayOfWeekChart = new Chart(dayOfWeekCtx, {
            type: 'bar',
            data: {
                labels: dayOfWeekData.labels,
                datasets: [{
                    label: 'Average Score',
                    data: dayOfWeekData.scores,
                    backgroundColor: chartColors.lightGreen,
                    borderColor: chartColors.borderGreen,
                    borderWidth: 2,
                    borderRadius: 4,
                    hoverBackgroundColor: chartColors.green
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    ...commonScales,
                    y: {
                        ...commonScales.y,
                        grid: {
                            color: 'var(--border-color)',
                            drawBorder: false,
                            lineWidth: 0.5
                        }
                    }
                },
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: function(context) {
                                return `Average Score: ${context.parsed.y}%`;
                            }
                        }
                    }
                },
                barThickness: 'flex',
                maxBarThickness: 40
            }
        });
    }
});
</script> 