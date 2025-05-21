// Admin Dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Load dashboard stats
    if (document.getElementById('totalUsers')) {
        loadDashboardStats();
    }

    // User Management
    if (document.getElementById('usersTable')) {
        loadUsers();
        setupUserSearch();
        setupUserFilter();
        setupUserActions();
        setupAddUserForm();
    }

    // Quiz Management
    if (document.getElementById('quizzesTable')) {
        loadQuizzes();
        setupQuizSearch();
        setupQuizFilter();
        setupQuizActions();
    }
});

// Dashboard Stats
async function loadDashboardStats() {
    try {
        const response = await fetch('/codingabcs/api/public/admin/stats', {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.status === 'success' && result.data) {
            document.getElementById('totalUsers').textContent = result.data.totalUsers;
            document.getElementById('totalQuizzes').textContent = result.data.totalQuizzes;
            document.getElementById('activeUsers').textContent = result.data.activeUsers;
        } else {
            throw new Error('Invalid response format');
        }
    } catch (error) {
        document.getElementById('totalUsers').textContent = 'Error';
        document.getElementById('totalQuizzes').textContent = 'Error';
        document.getElementById('activeUsers').textContent = 'Error';
    }
}

// User Management
async function loadUsers() {
    try {
        const response = await fetch('/codingabcs/api/public/admin/users', {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        });
        
        const responseText = await response.text();
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            throw new Error('Invalid JSON response from server');
        }
        
        const users = (result.status === 'success' && result.data?.users) ? result.data.users : [];
        
        const tbody = document.querySelector('#usersTable tbody');
        tbody.innerHTML = '';

        users.forEach(user => {
            const tr = document.createElement('tr');
            tr.className = 'align-middle';
            tr.innerHTML = `
                <td class="fw-medium">${user.id}</td>
                <td>${user.username}</td>
                <td class="d-none d-md-table-cell">${user.email}</td>
                <td class="d-none d-md-table-cell text-capitalize">${user.role}</td>
                <td class="d-none d-md-table-cell">
                    <span class="status-badge status-${user.is_active ? 'active' : 'inactive'}">
                        ${user.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="d-none d-md-table-cell">${new Date(user.created_at).toLocaleDateString()}</td>
                <td>
                    <div class="action-buttons d-flex gap-2">
                        <button class="btn btn-sm btn-primary edit-user" data-id="${user.id}" title="Edit" style="min-width: 40px; padding: 0.25rem 0.5rem;">
                            <i class="fas fa-edit" style="font-size: 1rem;"></i>
                            <span class="d-none d-md-inline ms-1">Edit</span>
                        </button>
                        <button class="btn btn-sm btn-danger delete-user" data-id="${user.id}" title="Delete" style="min-width: 40px; padding: 0.25rem 0.5rem;">
                            <i class="fas fa-trash" style="font-size: 1rem;"></i>
                            <span class="d-none d-md-inline ms-1">Delete</span>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        const tbody = document.querySelector('#usersTable tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="alert alert-danger">
                        Error loading users: ${error.message}
                        <br>
                        Please check your network connection and try again.
                    </div>
                </td>
            </tr>
        `;
    }
}

function setupUserSearch() {
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
            const searchTerm = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                const username = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                const visible = username.includes(searchTerm) || email.includes(searchTerm);
                row.style.display = visible ? '' : 'none';
            });
        }, 300));
    }
}

function setupUserFilter() {
    const filterSelect = document.getElementById('userFilter');
    if (filterSelect) {
        filterSelect.addEventListener('change', () => {
            const filterValue = filterSelect.value;
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                const role = row.cells[3].textContent.trim().toLowerCase();
                const status = row.cells[4].textContent.trim().toLowerCase();
                let visible = true;

                switch (filterValue) {
                    case 'admin':
                        visible = role === 'admin';
                        break;
                    case 'user':
                        visible = role === 'user';
                        break;
                    case 'active':
                        visible = status === 'active';
                        break;
                    case 'inactive':
                        visible = status === 'inactive';
                        break;
                    case 'all':
                    default:
                        visible = true;
                }

                row.style.display = visible ? '' : 'none';
            });
        });
    }
}

function setupUserActions() {
    const usersTable = document.querySelector('#usersTable');
    if (usersTable) {
        usersTable.addEventListener('click', async (e) => {
            const editButton = e.target.closest('.edit-user');
            const deleteButton = e.target.closest('.delete-user');
            
            if (editButton) {
                const userId = editButton.dataset.id;
                await showEditUserModal(userId);
            }
            
            if (deleteButton) {
                const userId = deleteButton.dataset.id;
                const confirmModalEl = document.getElementById('confirmModal');
                if (!confirmModalEl) return;

                const confirmModal = new bootstrap.Modal(confirmModalEl, {
                    backdrop: true,
                    keyboard: true
                });

                const confirmButton = document.getElementById('confirmButton');
                const confirmMessage = document.getElementById('confirmMessage');
                
                if (!confirmButton || !confirmMessage) return;

                // Remove any existing event listeners
                const newConfirmButton = confirmButton.cloneNode(true);
                confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
                
                confirmMessage.textContent = 'Are you sure you want to delete this user?';
                
                // Handle modal cleanup
                confirmModalEl.addEventListener('hidden.bs.modal', function () {
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                });

                // Handle close buttons
                const closeButtons = confirmModalEl.querySelectorAll('[data-bs-dismiss="modal"]');
                closeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        confirmModal.hide();
                    });
                });
                
                newConfirmButton.addEventListener('click', async function() {
                    try {
                        const response = await fetch(`/codingabcs/api/public/admin/users/${userId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'include'
                        });
                        
                        const result = await response.json();
                        
                        if (!response.ok) {
                            throw new Error(result.message || `HTTP error! status: ${response.status}`);
                        }
                        
                        if (result.status === 'success') {
                            confirmModal.hide();
                            loadUsers(); // Reload users instead of page refresh
                        } else {
                            throw new Error(result.message || 'Failed to delete user');
                        }
                    } catch (error) {
                        confirmModal.hide();
                        showAlert('Failed to delete user: ' + error.message);
                    }
                });
                
                confirmModal.show();
            }
        });
    }
}

async function showEditUserModal(userId) {
    const editUserModalEl = document.getElementById('editUserModal');
    if (!editUserModalEl) return;

    const editUserModal = new bootstrap.Modal(editUserModalEl, {
        backdrop: true,
        keyboard: true
    });

    // Handle modal cleanup
    editUserModalEl.addEventListener('hidden.bs.modal', function () {
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });

    // Handle close buttons
    const closeButtons = editUserModalEl.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            editUserModal.hide();
        });
    });

    try {
        const response = await fetch(`/codingabcs/api/public/admin/users/${userId}`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        if (result.status !== 'success' || !result.data) {
            throw new Error('Failed to fetch user data');
        }

        const user = result.data;
        
        // Populate form
        const editUserId = document.getElementById('editUserId');
        const editUsername = document.getElementById('editUsername');
        const editEmail = document.getElementById('editEmail');
        const editRole = document.getElementById('editRole');
        const editStatus = document.getElementById('editStatus');
        const updateUserBtn = document.getElementById('updateUserBtn');

        if (editUserId) editUserId.value = user.id;
        if (editUsername) editUsername.value = user.username;
        if (editEmail) editEmail.value = user.email;
        if (editRole) editRole.value = user.role;
        if (editStatus) editStatus.value = user.is_active ? '1' : '0';

        if (updateUserBtn) {
            updateUserBtn.onclick = async () => {
                try {
                    const formData = {
                        username: editUsername ? editUsername.value : '',
                        email: editEmail ? editEmail.value : '',
                        role: editRole ? editRole.value : '',
                        is_active: editStatus ? editStatus.value === '1' : false
                    };

                    const updateResponse = await fetch(`/codingabcs/api/public/admin/users/${userId}`, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        credentials: 'include',
                        body: JSON.stringify(formData)
                    });

                    if (!updateResponse.ok) {
                        throw new Error(`HTTP error! status: ${updateResponse.status}`);
                    }

                    const updateResult = await updateResponse.json();
                    if (updateResult.status !== 'success') {
                        throw new Error(updateResult.message || 'Failed to update user');
                    }

                    editUserModal.hide();
                    loadUsers();
                } catch (error) {
                    showAlert('Failed to update user: ' + error.message);
                }
            };
        }

        editUserModal.show();
    } catch (error) {
        showAlert('Failed to load user data: ' + error.message);
    }
}

function showAlert(message) {
    const alertModalEl = document.getElementById('alertModal');
    if (!alertModalEl) return;

    const alertModal = new bootstrap.Modal(alertModalEl, {
        backdrop: true,
        keyboard: true
    });

    const alertMessage = document.getElementById('alertMessage');
    if (alertMessage) alertMessage.textContent = message;

    // Handle modal cleanup
    alertModalEl.addEventListener('hidden.bs.modal', function () {
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });

    // Handle close buttons
    const closeButtons = alertModalEl.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            alertModal.hide();
        });
    });

    alertModal.show();
}

// Quiz Management
async function loadQuizzes() {
    try {
        const response = await fetch('/codingabcs/api/public/quizzes', {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        const quizzes = (result.status === 'success' && result.data?.quizzes) ? result.data.quizzes : [];
        
        const tbody = document.querySelector('#quizzesTable tbody');
        tbody.innerHTML = '';

        quizzes.forEach(quiz => {
            const tr = document.createElement('tr');
            tr.className = 'align-middle';
            tr.innerHTML = `
                <td class="fw-medium">${quiz.id}</td>
                <td>${quiz.title}</td>
                <td class="d-none d-md-table-cell">${quiz.category}</td>
                <td class="d-none d-md-table-cell">${quiz.question_count}</td>
                <td class="d-none d-md-table-cell">
                    <span class="status-badge status-${quiz.is_active ? 'active' : 'inactive'}">
                        ${quiz.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="d-none d-md-table-cell">${new Date(quiz.created_at).toLocaleDateString()}</td>
                <td>
                    <div class="action-buttons d-flex gap-2">
                        <a href="/codingabcs/client/public/admin_quiz_edit?id=${quiz.id}" class="btn btn-sm btn-primary" title="Edit" style="min-width: 40px; padding: 0.25rem 0.5rem;">
                            <i class="fas fa-edit" style="font-size: 1rem;"></i>
                            <span class="d-none d-md-inline ms-1">Edit</span>
                        </a>
                        <button class="btn btn-sm btn-danger delete-quiz" data-id="${quiz.id}" title="Delete" style="min-width: 40px; padding: 0.25rem 0.5rem;">
                            <i class="fas fa-trash" style="font-size: 1rem;"></i>
                            <span class="d-none d-md-inline ms-1">Delete</span>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        const tbody = document.querySelector('#quizzesTable tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="alert alert-danger">
                        Error loading quizzes: ${error.message}
                        <br>
                        Please check your network connection and try again.
                    </div>
                </td>
            </tr>
        `;
    }
}

function setupQuizSearch() {
    const searchInput = document.getElementById('quizSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
            const searchTerm = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('#quizzesTable tbody tr');
            
            rows.forEach(row => {
                const title = row.cells[1].textContent.toLowerCase();
                const category = row.cells[2].textContent.toLowerCase();
                const visible = title.includes(searchTerm) || category.includes(searchTerm);
                row.style.display = visible ? '' : 'none';
            });
        }, 300));
    }
}

function setupQuizFilter() {
    const filterSelect = document.getElementById('quizFilter');
    if (filterSelect) {
        filterSelect.addEventListener('change', () => {
            const filterValue = filterSelect.value;
            const rows = document.querySelectorAll('#quizzesTable tbody tr');
            
            rows.forEach(row => {
                const status = row.cells[4].textContent.trim().toLowerCase();
                const visible = filterValue === 'all' || 
                              (filterValue === 'active' && status === 'active') ||
                              (filterValue === 'inactive' && status === 'inactive');
                row.style.display = visible ? '' : 'none';
            });
        });
    }
}

function setupQuizActions() {
    const quizzesTable = document.querySelector('#quizzesTable');
    if (quizzesTable) {
        quizzesTable.addEventListener('click', async (e) => {
            const editButton = e.target.closest('.edit-quiz');
            const deleteButton = e.target.closest('.delete-quiz');
            
            if (editButton) {
                const quizId = editButton.dataset.id;
                window.location.href = `/codingabcs/client/public/admin_quiz_edit?id=${quizId}`;
            }
            
            if (deleteButton) {
                const quizId = deleteButton.dataset.id;
                const confirmModalEl = document.getElementById('confirmModal');
                if (!confirmModalEl) return;

                const confirmModal = new bootstrap.Modal(confirmModalEl, {
                    backdrop: true,
                    keyboard: true
                });

                const confirmButton = document.getElementById('confirmButton');
                const confirmMessage = document.getElementById('confirmMessage');
                
                if (!confirmButton || !confirmMessage) return;

                // Remove any existing event listeners
                const newConfirmButton = confirmButton.cloneNode(true);
                confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
                
                // First check if the quiz has questions
                try {
                    const questionsResponse = await fetchWithRetry(`/codingabcs/api/public/quizzes/${quizId}/questions`);
                    const questionsResult = await questionsResponse.json();
                    const hasQuestions = questionsResult.status === 'success' && questionsResult.data?.questions?.length > 0;
                    
                    confirmMessage.textContent = hasQuestions 
                        ? 'This quiz has questions. Are you sure you want to delete this quiz and all its questions? This action cannot be undone.'
                        : 'Are you sure you want to delete this quiz? This action cannot be undone.';
                } catch (error) {
                    confirmMessage.textContent = 'Are you sure you want to delete this quiz? This action cannot be undone.';
                }
                
                // Handle modal cleanup
                confirmModalEl.addEventListener('hidden.bs.modal', function () {
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                });

                // Handle close buttons
                const closeButtons = confirmModalEl.querySelectorAll('[data-bs-dismiss="modal"]');
                closeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        confirmModal.hide();
                    });
                });
                
                newConfirmButton.addEventListener('click', async function() {
                    try {
                        const response = await fetchWithRetry(`/codingabcs/api/public/quizzes/${quizId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Authorization': `Bearer ${getCookie('jwt_token')}`
                            },
                            credentials: 'include'
                        });

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                        }

                        const result = await response.json();
                        if (result.status === 'success') {
                            showAlert('Quiz deleted successfully!');
                            confirmModal.hide();
                            await loadQuizzes();
                        } else {
                            throw new Error(result.message || 'Quiz could not be deleted');
                        }
                    } catch (error) {
                        confirmModal.hide();
                        showAlert('Error deleting quiz: ' + error.message);
                    }
                });
                
                confirmModal.show();
            }
        });
    }
}

// Utility function to handle API requests with retry
async function fetchWithRetry(url, options = {}, retries = 1) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getCookie('jwt_token')}`,
                ...options.headers
            },
            credentials: 'include'
        });

        if (response.status === 401 && retries > 0) {
            // Token might be expired, try to refresh
            const refreshResponse = await fetch('/codingabcs/api/public/auth/refresh', {
                method: 'POST',
                credentials: 'include'
            });
            
            if (refreshResponse.ok) {
                // Retry the original request with new token
                return fetchWithRetry(url, options, retries - 1);
            }
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response;
    } catch (error) {
        if (retries > 0) {
            // Wait for 1 second before retrying
            await new Promise(resolve => setTimeout(resolve, 1000));
            return fetchWithRetry(url, options, retries - 1);
        }
        throw error;
    }
}

// Utility function to get cookie value
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Utility function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function setupAddUserForm() {
    const saveUserBtn = document.getElementById('saveUserBtn');
    const addUserForm = document.getElementById('addUserForm');
    const alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
    const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
    let isSubmitting = false;

    if (saveUserBtn && addUserForm) {
        // Prevent form submission on enter key
        addUserForm.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });

        // Handle save button click
        saveUserBtn.addEventListener('click', async function(e) {
            e.preventDefault(); // Prevent default button behavior
            
            if (isSubmitting) return; // Prevent double submission
            
            // Get form elements
            const username = document.getElementById('username');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const role = document.getElementById('role');

            // Basic validation
            if (!username.value.trim()) {
                document.getElementById('alertMessage').textContent = 'Please enter a username';
                alertModal.show();
                return;
            }
            if (!email.value.trim()) {
                document.getElementById('alertMessage').textContent = 'Please enter an email';
                alertModal.show();
                return;
            }
            if (!password.value) {
                document.getElementById('alertMessage').textContent = 'Please enter a password';
                alertModal.show();
                return;
            }

            // Set submitting state
            isSubmitting = true;
            saveUserBtn.disabled = true;
            saveUserBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

            const formData = {
                username: username.value.trim(),
                email: email.value.trim(),
                password: password.value,
                role: role.value
            };

            try {
                const response = await fetch('/codingabcs/api/public/admin/users', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include',
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (!response.ok) {
                    // Handle validation errors
                    if (response.status === 422 && result.errors) {
                        const errorMessages = Object.values(result.errors).join('\n');
                        throw new Error(errorMessages);
                    }
                    throw new Error(result.message || 'Failed to create user');
                }

                if (result.status === 'success') {
                    // Clear form
                    username.value = '';
                    email.value = '';
                    password.value = '';
                    role.value = 'user';

                    // Show success message and close modal
                    document.getElementById('alertMessage').textContent = 'User created successfully!';
                    alertModal.show();
                    addUserModal.hide();
                    await loadUsers();
                } else {
                    throw new Error(result.message || 'Failed to create user');
                }
            } catch (error) {
                document.getElementById('alertMessage').textContent = error.message;
                alertModal.show();
            } finally {
                // Reset submitting state
                isSubmitting = false;
                saveUserBtn.disabled = false;
                saveUserBtn.innerHTML = 'Save';
            }
        });

        // Reset form when modal is closed
        addUserModal._element.addEventListener('hidden.bs.modal', function () {
            const form = document.getElementById('addUserForm');
            if (form) {
                form.reset();
            }
            isSubmitting = false;
            saveUserBtn.disabled = false;
            saveUserBtn.innerHTML = 'Save';
        });
    }
} 