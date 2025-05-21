// Leaderboard page functionality
document.addEventListener('DOMContentLoaded', () => {
    
    // Get user ID from JWT token
    let userId = null;
    try {
        const token = document.cookie.split('; ').find(row => row.startsWith('jwt_token='))?.split('=')[1];
        if (token) {
            const payload = JSON.parse(atob(token.split('.')[1]));
            userId = payload.id;
        }
    } catch (e) {
        throw new Error('Error parsing JWT token:', e);
    }

    // Initialize with initial data if available
    if (window.initialLeaderboardData) {
        updateLeaderboardUI(window.initialLeaderboardData);
    }

    // Function to update the UI with leaderboard data
    function updateLeaderboardUI(data) {
        
        // Update user rank card
        if (data.user_rank) {
            updateUserRankCard(data.user_rank);
        }
        
        // Update top performers table
        if (data.top_performers) {
            updateTopPerformersTable(data.top_performers);
        }
        
        // Update achievements
        if (data.achievements) {
            updateAchievements(data.achievements);
        }
    }

    // Function to update user rank card
    function updateUserRankCard(userRank) {
        
        const rankCard = document.querySelector('.rank-card');
        if (!rankCard) {
            return;
        }

        const rankNumber = rankCard.querySelector('.rank-number');
        const userName = rankCard.querySelector('h3');
        const stats = rankCard.querySelectorAll('p');

        // Update rank number
        if (rankNumber) {
            rankNumber.textContent = userRank.rank === 'N/A' ? 'N/A' : `#${userRank.rank}`;
        }

        // Update user name
        if (userName) {
            userName.textContent = userRank.name || 'Guest';
        }
        
        // Update stats
        if (stats.length >= 4) {
            stats[0].innerHTML = `<i class="fas fa-chart-line"></i> Average Score: ${userRank.average_score.toFixed(1)}%`;
            stats[1].innerHTML = `<i class="fas fa-tasks"></i> Unique Quizzes: ${userRank.total_quizzes}`;
            stats[2].innerHTML = `<i class="fas fa-fire"></i> Current Streak: ${userRank.current_streak} days`;
            stats[3].innerHTML = `<i class="fas fa-star"></i> Best Score: ${userRank.best_score.toFixed(1)}%`;
        }
    }

    // Function to update top performers table
    function updateTopPerformersTable(performers) {
        const tbody = document.querySelector('.leaderboard-card tbody');
        if (!tbody) return;

        tbody.innerHTML = performers.map((user, index) => `
            <tr class="${index < 3 ? 'top-' + (index + 1) : ''}">
                <td class="text-center">
                    <span class="rank-badge">#${user.rank}</span>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${user.avatar}" alt="${user.name}" class="avatar me-2">
                        <span>${user.name}</span>
                    </div>
                </td>
                <td class="text-center">${user.average_score.toFixed(1)}%</td>
                <td class="text-center">${user.total_quizzes}</td>
                <td class="text-center">${user.current_streak} days</td>
                <td class="text-center">${user.best_score.toFixed(1)}%</td>
            </tr>
        `).join('') || `
            <tr>
                <td colspan="6" class="text-center py-4">
                    <i class="fas fa-users fa-3x mb-3" style="color: var(--text-muted);"></i>
                    <p class="mb-0" style="color: var(--text-muted);">No leaderboard data available</p>
                </td>
            </tr>
        `;
    }

    // Function to update achievements
    function updateAchievements(achievements) {
        const achievementsGrid = document.querySelector('.achievements-grid');
        if (!achievementsGrid) return;

        achievementsGrid.innerHTML = achievements.map(achievement => `
            <div class="achievement-card ${achievement.unlocked ? 'unlocked' : 'locked'}">
                <div class="achievement-icon">
                    <i class="fas ${achievement.icon}"></i>
                </div>
                <div class="achievement-info">
                    <h4>${achievement.title}</h4>
                    <p class="mb-0">${achievement.description}</p>
                    <small class="text-muted">
                        ${achievement.unlocked 
                            ? 'Unlocked ' + new Date(achievement.unlocked_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                            : 'Locked'
                        }
                    </small>
                </div>
            </div>
        `).join('') || `
            <div class="text-center py-4">
                <i class="fas fa-trophy fa-3x mb-3" style="color: var(--text-muted);"></i>
                <p class="mb-0" style="color: var(--text-muted);">No achievements available</p>
            </div>
        `;
    }

    // Helper function to show toast notifications
    function showToast(type, message) {
        const toastContainer = document.getElementById('toast-container') || createToastContainer();
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-header">
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'} me-2"></i>
                <strong class="me-auto">${type === 'error' ? 'Error' : 'Success'}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>
        `;
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    // Helper function to create toast container
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }
}); 