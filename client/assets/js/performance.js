// Performance page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Function to update performance data
    function updatePerformanceData() {
        try {
            // Use data passed directly from PHP
            const data = window.performanceData;
            if (!data) {
                throw new Error('No performance data available');
            }
            
            // Update charts with data
            updateCharts(data);
        } catch (error) {
            console.error('Error updating performance data:', error);
            throw error;
        }
    }

    // Function to update all charts with new data
    function updateCharts(data) {
        // Update score trend chart
        if (window.scoreTrendChart) {
            window.scoreTrendChart.data.labels = data.score_trend.labels.map(label => label.display);
            window.scoreTrendChart.data.datasets[0].data = data.score_trend.scores;
            window.scoreTrendChart.update();
        }

        // Update category performance chart
        if (window.categoryChart) {
            window.categoryChart.data.labels = data.type_performance.labels;
            window.categoryChart.data.datasets[0].data = data.type_performance.scores;
            window.categoryChart.update();
        }

        // Update time analysis chart
        if (window.timeAnalysisChart) {
            window.timeAnalysisChart.data.labels = data.time_analysis.labels;
            window.timeAnalysisChart.data.datasets[0].data = data.time_analysis.scores;
            window.timeAnalysisChart.update();
        }

        // Update day of week performance chart
        if (window.dayOfWeekChart) {
            window.dayOfWeekChart.data.labels = data.day_of_week_performance.labels;
            window.dayOfWeekChart.data.datasets[0].data = data.day_of_week_performance.scores;
            window.dayOfWeekChart.update();
        }

        // Update stat cards
        updateStatCards(data);
    }

    // Function to update stat cards
    function updateStatCards(data) {
        // Find all stat cards and update their content
        const statCards = document.querySelectorAll('.stat-card .stat-value');
        if (statCards.length >= 4) {
            // Use statsData for stats that come from the stats endpoint
            statCards[0].textContent = `${window.statsData.average_score ?? 0}%`; // Average Score
            statCards[1].textContent = window.statsData.quizzes_taken ?? 0; // Quizzes Taken
            
            // Use performanceData for performance-specific stats
            statCards[2].textContent = `${data.best_score ?? 0}%`; // Best Score
            statCards[3].textContent = `${data.improvement ?? 0}%`; // Improvement
        }
    }

    // Function to show toast notifications
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        const toastContainer = document.querySelector('.toast-container') || createToastContainer();
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    // Function to create toast container if it doesn't exist
    function createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }

    // Initial load of performance data
    updatePerformanceData();
}); 