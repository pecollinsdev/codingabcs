(function() {
    // Base URL for API calls
    const API_BASE = '/codingabcs/api/public/index.php';
  
    document.addEventListener('DOMContentLoaded', () => {
  
      // Cache DOM elements
      const searchInput      = document.getElementById('searchInput');
      const categoryFilter   = document.getElementById('categoryFilter');
      const difficultyFilter = document.getElementById('difficultyFilter');
      const sortSelect       = document.getElementById('sortBy');
      const quizzesGrid      = document.getElementById('quizzesGrid');
      const paginationContainer = document.getElementById('paginationContainer');
      
      // Modals and their elements
      const startModalElement     = document.getElementById('startQuizModal');
      const resumeModalElement    = document.getElementById('resumeQuizModal');
      const startQuizModal        = new bootstrap.Modal(startModalElement);
      const resumeQuizModal       = new bootstrap.Modal(resumeModalElement);
      const modalQuizTitle        = document.getElementById('modalQuizTitle');
      const modalQuizDescription  = document.getElementById('modalQuizDescription');
      const modalResumeTitle      = document.getElementById('modalResumeTitle');
      const modalResumeQuestion   = document.getElementById('modalResumeQuestion');
      const startQuizBtn          = document.getElementById('startQuizBtn');
      const restartQuizBtn        = document.getElementById('restartQuizBtn');
      const resumeQuizBtn         = document.getElementById('resumeQuizBtn');
  
      // Utility: get URL parameters
      function getURLParams() {
        const url = new URL(window.location.href);
        return {
          search:     url.searchParams.get('search')     || '',
          category:   url.searchParams.get('category')   || '',
          difficulty: url.searchParams.get('difficulty') || '',
          sort:       url.searchParams.get('sort')       || 'newest',
          limit:      parseInt(url.searchParams.get('limit'))  || 9,
          offset:     parseInt(url.searchParams.get('offset')) || 0
        };
      }
  
      // Utility: update URL without reload
      function updateURLParams(params) {
        const url = new URL(window.location.href);
        Object.entries(params).forEach(([key, value]) => {
          if (value) url.searchParams.set(key, value);
          else      url.searchParams.delete(key);
        });
        window.history.pushState({}, '', url);
      }
  
      // Utility: read cookie
      function getCookie(name) {
        return document.cookie.split('; ').reduce((acc, pair) => {
          const [k, v] = pair.split('=');
          return k === name ? v : acc;
        }, null);
      }
  
      // Fetch quizzes and include progress
      async function fetchQuizzes(params = {}) {
        try {
          const token = getCookie('jwt_token');
          if (!token) throw new Error('Not authenticated');
  
          // Use initial data if available and no filters are applied
          if (window.initialQuizzesData && 
              Object.keys(params).length === 0 && 
              !searchInput.value && 
              !categoryFilter.value && 
              !difficultyFilter.value && 
              sortSelect.value === 'newest') {
            return window.initialQuizzesData;
          }
  
          // Fetch quizzes list
          const listRes = await fetch(
            `${API_BASE}/quizzes?${new URLSearchParams(params)}`,
            { 
              headers: { 'Authorization': `Bearer ${token}` },
              credentials: 'include'
            }
          );
          if (!listRes.ok) throw new Error(`Fetch error: ${listRes.status}`);
          const listJson = await listRes.json();
          const { quizzes, total } = listJson.data;
  
          // Fetch all progress data in a single call
          const progressRes = await fetch(`${API_BASE}/quizzes/progress`, {
            headers: { 'Authorization': `Bearer ${token}` },
            credentials: 'include'
          });
          const progressData = progressRes.ok ? await progressRes.json() : { data: {} };
  
          // Enrich quizzes with progress data
          const enrichedQuizzes = quizzes.map(q => ({
            ...q,
            has_progress: !!progressData.data[q.id],
            current_question: progressData.data[q.id]?.current_question,
            last_updated: progressData.data[q.id]?.last_updated
          }));
  
          return { quizzes: enrichedQuizzes, total };
        } catch (err) {
          showError('Error fetching quizzes. Please try again later.');
          return { quizzes: [], total: 0 };
        }
      }
  
      // Render quizzes grid and pagination
      async function updateQuizzes() {
        const params = getURLParams();
        quizzesGrid.innerHTML = `<div class="col-12 text-center py-5">`
          + `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>`
          + `</div>`;
  
        const data = await fetchQuizzes(params);
  
        if (!data.quizzes.length) {
          quizzesGrid.innerHTML = `
            <div class="col-12 text-center py-5">
              <i class="fas fa-search fa-3x text-muted"></i>
              <h4 class="text-muted">No quizzes found</h4>
              <p class="text-muted">Try adjusting your search or filters</p>
            </div>`;
        } else {
          quizzesGrid.innerHTML = data.quizzes.map(q => buildQuizCard(q)).join('');
          setupCardEventListeners();
          renderPagination(data.total, params.limit, params.offset);
          updateCategoryFilter(data.quizzes);
        }
      }
  
      // Build HTML for a single quiz card
      function buildQuizCard(q) {
        const hasQuestions = q.question_count && q.question_count > 0;
        const button = !hasQuestions ? `
          <button class="btn btn-outline-secondary" disabled>
            <i class="fas fa-ban me-2"></i>No Questions
          </button>
        ` : q.has_progress ? `
          <a href="#" class="btn btn-primary resume-quiz-btn" data-quiz-id="${q.id}" data-quiz-title="${encodeHTML(q.title)}" data-current-question="${q.current_question}">
            <i class="fas fa-redo me-2"></i>Resume (Q${q.current_question+1})
          </a>
        ` : `
          <button class="btn btn-outline-primary start-quiz-btn" data-quiz-id="${q.id}" data-quiz-title="${encodeHTML(q.title)}" data-quiz-description="${encodeHTML(q.description || 'No description available.')}">
            <i class="fas fa-play me-2"></i>Start Quiz
          </button>
        `;
  
        return `
          <div class="col-md-4 quiz-card">
            <div class="card h-100">
              <div class="card-body">
                ${q.language ? `<div class="language-badge ${q.language.toLowerCase()}"><i class="${getLanguageIcon(q.language)}"></i></div>` : ''}
                <span class="badge difficulty-badge ${getDifficultyClass(q.level)}">${capitalize(q.level)}</span>
                <h5 class="card-title">${encodeHTML(q.title)}</h5>
                <p class="text-muted mb-2"><i class="fas fa-layer-group me-1"></i>${encodeHTML(q.category)}</p>
                <p class="text-muted mb-2"><i class="fas fa-signal me-1"></i>${capitalize(q.level)}</p>
                <p class="text-muted mb-3"><i class="fas fa-question-circle me-1"></i>${hasQuestions ? q.question_count : 'No'} questions</p>
                <div class="d-grid gap-2">
                  ${button}
                </div>
              </div>
            </div>
          </div>
        `;
      }
  
      // HTML escape helper
      function encodeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
      }
  
      // Setup event listeners on Start/Resume buttons
      function setupCardEventListeners() {
        document.querySelectorAll('.start-quiz-btn').forEach(btn => {
          btn.onclick = (e) => {
            e.preventDefault();
            modalQuizTitle.textContent       = btn.dataset.quizTitle;
            modalQuizDescription.textContent = btn.dataset.quizDescription;
            startQuizBtn.href                = `/codingabcs/client/public/quiz/${btn.dataset.quizId}`;
            startQuizModal.show();
          };
        });
        document.querySelectorAll('.resume-quiz-btn').forEach(btn => {
          btn.onclick = (e) => {
            e.preventDefault();
            const quizId = btn.dataset.quizId;
            
            modalResumeTitle.textContent    = btn.dataset.quizTitle;
            modalResumeQuestion.textContent = Number(btn.dataset.currentQuestion) + 1;
            resumeQuizBtn.href              = `/codingabcs/client/public/quiz/${quizId}?question=${btn.dataset.currentQuestion}`;
            
            // Remove any existing click handlers and set up new one
            restartQuizBtn.onclick = async (e) => {
              e.preventDefault();
              
              try {
                const token = getCookie('jwt_token');
                if (!token) throw new Error('Not authenticated');

                // First clear the progress
                const clearRes = await fetch(`${API_BASE}/quizzes/${quizId}/progress`, {
                  method: 'DELETE',
                  headers: { 
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                  }
                });

                if (!clearRes.ok) throw new Error('Failed to clear progress');

                // Try to reset progress, but don't fail if it doesn't work
                try {
                  const resetRes = await fetch(`${API_BASE}/quizzes/${quizId}/progress`, {
                    method: 'POST',
                    headers: { 
                      'Authorization': `Bearer ${token}`,
                      'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                      data: {
                        current_question: 0,
                        answers: null,
                        last_updated: Math.floor(Date.now() / 1000)
                      }
                    })
                  });

                  if (!resetRes.ok) {
                    const errorData = await resetRes.json();
                  }
                } catch (resetErr) {
                }
                
                // Redirect to quiz since progress was cleared
                window.location.href = `/codingabcs/client/public/quiz/${quizId}`;
              } catch (err) {
                showError('Failed to restart quiz. Please try again.');
              }
            };
            
            resumeQuizModal.show();
          };
        });
      }
  
      // Render pagination controls
      function renderPagination(total, limit, offset) {
        // Get or create pagination container
        let container = document.getElementById('paginationContainer');
        if (!container) {
          container = document.createElement('div');
          container.id = 'paginationContainer';
          container.className = 'pagination-container';
          
          // Find the quizzes grid and insert the container after it
          const quizzesGrid = document.getElementById('quizzesGrid');
          if (quizzesGrid) {
            quizzesGrid.parentNode.insertBefore(container, quizzesGrid.nextSibling);
          } else {
            return;
          }
        }
        
        const totalPages  = Math.ceil(total / limit);
        const currentPage = Math.floor(offset / limit) + 1;
        
        if (totalPages <= 1) {
          container.innerHTML = '';
          return;
        }

        let html = '<nav aria-label="Quiz pagination"><ul class="pagination justify-content-center mb-0">';
        
        // Previous button
        if (currentPage > 1) {
          html += `
            <li class="page-item">
              <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                <i class="fas fa-chevron-left"></i>
              </a>
            </li>
          `;
        }

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
          if (
            i === 1 || // First page
            i === totalPages || // Last page
            (i >= currentPage - 2 && i <= currentPage + 2) // Pages around current
          ) {
            html += `
              <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
              </li>
            `;
          } else if (
            i === currentPage - 3 || // Before ellipsis
            i === currentPage + 3 // After ellipsis
          ) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
          }
        }

        // Next button
        if (currentPage < totalPages) {
          html += `
            <li class="page-item">
              <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                <i class="fas fa-chevron-right"></i>
              </a>
            </li>
          `;
        }

        html += '</ul></nav>';
        container.innerHTML = html;

        // Add click event listener to the container
        container.addEventListener('click', (e) => {
          const link = e.target.closest('.page-link');
          if (!link || link.closest('.disabled')) return;
          
          e.preventDefault();
          const page = parseInt(link.dataset.page, 10);
          if (isNaN(page)) return;
          
          const params = getURLParams();
          params.offset = (page - 1) * params.limit;
          updateURLParams(params);
          updateQuizzes();
        });
      }
  
      // Helpers
      function showError(msg) {
        const div = document.createElement('div');
        div.className = 'alert alert-danger';
        div.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i>${encodeHTML(msg)}`;
        quizzesGrid.before(div);
      }
  
      function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
      }
  
      function getDifficultyClass(level) {
        return ({ beginner: 'bg-success', intermediate: 'bg-warning', advanced: 'bg-danger' })[level.toLowerCase()] || 'bg-secondary';
      }
  
      function getLanguageIcon(lang) {
        return ({ html:'fa-html5', css:'fa-css3-alt', javascript:'fa-js', php:'fa-php', python:'fa-python', java:'fa-java' })[lang.toLowerCase()] || 'fa-code';
      }
  
      // Debounce helper
      function debounce(fn, wait=500) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
      }
  
      // Bind filter controls
      searchInput.oninput       = debounce(() => onFilterChange('search', searchInput.value));
      categoryFilter.onchange   = () => onFilterChange('category', categoryFilter.value);
      difficultyFilter.onchange = () => onFilterChange('difficulty', difficultyFilter.value);
      sortSelect.onchange       = () => onFilterChange('sort', sortSelect.value);
  
      function onFilterChange(key, value) {
        const params = getURLParams();
        params[key]   = value;
        params.offset = 0;
        updateURLParams(params);
        updateQuizzes();
      }
  
      // Update category filter options
      function updateCategoryFilter(quizzes) {
        // Get unique categories from quizzes
        const categories = [...new Set(quizzes.map(q => q.category).filter(Boolean))].sort();
        
        // Get current selected category
        const currentCategory = categoryFilter.value;
        
        // Update category filter options
        categoryFilter.innerHTML = `
          <option value="">All Categories</option>
          ${categories.map(cat => `
            <option value="${encodeHTML(cat)}" ${cat === currentCategory ? 'selected' : ''}>
              ${encodeHTML(cat)}
            </option>
          `).join('')}
        `;
      }
  
      // Kick off initial load
      updateQuizzes();
    });
  })();
  