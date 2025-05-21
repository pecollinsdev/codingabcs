// Dashboard page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Add animation to stat cards on scroll
    const statCards = document.querySelectorAll('.stat-card');
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    statCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
    });

    // Resume Quiz Functionality
    const resumeQuizModalEl = document.getElementById('resumeQuizModal');
    let resumeQuizModal = null;
    
    if (resumeQuizModalEl) {
        resumeQuizModal = new bootstrap.Modal(resumeQuizModalEl, {
            backdrop: true,
            keyboard: true
        });

        // Proper modal cleanup
        resumeQuizModalEl.addEventListener('hidden.bs.modal', function () {
            // Remove any remaining backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            // Reset body class and styles
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            // Reset modal content
            if (resumeQuizInfo) resumeQuizInfo.classList.add('d-none');
            if (noActiveQuiz) noActiveQuiz.classList.add('d-none');
        });

        // Handle modal close button
        const closeButtons = resumeQuizModalEl.querySelectorAll('[data-bs-dismiss="modal"]');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (resumeQuizModal) {
                    resumeQuizModal.hide();
                }
            });
        });
    }

    const resumeQuizBtn = document.querySelector('a[href="quiz.php?resume=1"]');
    const modalResumeQuizBtn = document.getElementById('resumeQuizBtn');
    const resumeQuizTitle = document.getElementById('resumeQuizTitle');
    const resumeQuizProgress = document.getElementById('resumeQuizProgress');
    const resumeQuizInfo = document.getElementById('resumeQuizInfo');
    const noActiveQuiz = document.getElementById('noActiveQuiz');

    if (resumeQuizBtn) {
        resumeQuizBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            try {
                // Fetch active quiz data
                const response = await fetch('/codingabcs/api/public/index.php/quizzes/progress', {
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch quiz progress');
                }

                const data = await response.json();
                
                if (data.status === 'success' && data.data) {
                    const quiz = data.data.data;
                    
                    if (!quiz) {
                        if (resumeQuizInfo) resumeQuizInfo.classList.add('d-none');
                        if (noActiveQuiz) noActiveQuiz.classList.remove('d-none');
                        if (modalResumeQuizBtn) {
                            modalResumeQuizBtn.href = '/codingabcs/client/public/quiz';
                            modalResumeQuizBtn.textContent = 'Start New Quiz';
                        }
                        if (resumeQuizModal) resumeQuizModal.show();
                        return;
                    }
                    
                    const currentQuestion = parseInt(quiz.current_question) || 0;
                    const totalQuestions = parseInt(quiz.total_questions) || 0;
                    
                    if (resumeQuizTitle) resumeQuizTitle.textContent = quiz.title || 'Untitled Quiz';
                    if (resumeQuizProgress) resumeQuizProgress.textContent = `Progress: Question ${currentQuestion + 1} of ${totalQuestions}`;
                    if (resumeQuizInfo) resumeQuizInfo.classList.remove('d-none');
                    if (noActiveQuiz) noActiveQuiz.classList.add('d-none');
                    
                    if (modalResumeQuizBtn) {
                        modalResumeQuizBtn.href = `/codingabcs/client/public/quiz/${quiz.quiz_id}?question=${currentQuestion}`;
                        modalResumeQuizBtn.textContent = 'Resume Quiz';
                    }
                    
                    if (resumeQuizModal) resumeQuizModal.show();
                } else {
                    if (resumeQuizInfo) resumeQuizInfo.classList.add('d-none');
                    if (noActiveQuiz) noActiveQuiz.classList.remove('d-none');
                    if (modalResumeQuizBtn) {
                        modalResumeQuizBtn.href = '/codingabcs/client/public/quiz';
                        modalResumeQuizBtn.textContent = 'Start New Quiz';
                    }
                    if (resumeQuizModal) resumeQuizModal.show();
                }
            } catch (error) {
                if (resumeQuizInfo) resumeQuizInfo.classList.add('d-none');
                if (noActiveQuiz) {
                    noActiveQuiz.classList.remove('d-none');
                    noActiveQuiz.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Failed to load quiz information. Please try again.
                        </div>
                    `;
                }
                if (modalResumeQuizBtn) {
                    modalResumeQuizBtn.href = '/codingabcs/client/public/quiz';
                    modalResumeQuizBtn.textContent = 'Start New Quiz';
                }
                if (resumeQuizModal) resumeQuizModal.show();
            }
        });
    }

    // Function to show error message
    function showError(message, container) {
        if (!container) {
            return;
        }
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger alert-dismissible fade show';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        container.insertBefore(errorDiv, container.firstChild);
    }

    // Function to check for API errors in PHP-injected data
    function checkForErrors() {
        // Check stats data
        const statsData = window.statsData || {};
        if (statsData.error) {
            showError(`Stats Error: ${statsData.error}`, document.querySelector('.dashboard-container'));
        }

        // Check activity data
        const activityData = window.activityData || {};
        if (activityData.error) {
            showError(`Activity Error: ${activityData.error}`, document.querySelector('.activity-feed'));
        }

        // Check performance data
        const performanceData = window.performanceData || {};
        if (performanceData.error) {
            showError(`Performance Error: ${performanceData.error}`, document.querySelector('.performance-overview'));
        }

        // Check for authentication errors
        if (statsData.error === 'Authentication required' || 
            activityData.error === 'Authentication required' || 
            performanceData.error === 'Authentication required') {
            showError('Please login to view your dashboard data', document.querySelector('.dashboard-container'));
        }
    }

    // Check for errors when page loads
    checkForErrors();
});
