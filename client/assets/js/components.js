// Main navigation functionality
document.addEventListener('DOMContentLoaded', function () {

    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    const mainContent = document.querySelector('.main-content');

    function openMenu() {
        document.body.classList.add('nav-expanded');
        navbarCollapse.classList.add('show');
        if (mainContent) {
            mainContent.style.marginTop = navbarCollapse.offsetHeight + 'px';
        }
    }

    function closeMenu() {
        document.body.classList.remove('nav-expanded');
        navbarCollapse.classList.remove('show');
        if (mainContent) {
            mainContent.style.marginTop = '0';
        }
    }

    function isMenuOpen() {
        return navbarCollapse.classList.contains('show');
    }

    // Toggle on click
    navbarToggler?.addEventListener('click', () => {
        isMenuOpen() ? closeMenu() : openMenu();
    });

    // Close menu when nav-link is clicked
    navbarCollapse?.addEventListener('click', (e) => {
        const target = e.target.closest('.nav-link');
        if (target && !target.classList.contains('dropdown-toggle')) {
            closeMenu();
        }
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (
            isMenuOpen() &&
            !navbarCollapse.contains(e.target) &&
            !navbarToggler.contains(e.target)
        ) {
            closeMenu();
        }
    });

    // Reset on resize if switching back to desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth > 992) {
            closeMenu();
        }
    });

    // Header logic
    const header = {
        init: function () {
            // Only validate token if we don't have user data from PHP
            if (!document.querySelector('.navbar-nav .dropdown-toggle')) {
                this.validateToken();
            }
            
            // Initialize logout button
            this.initializeLogout();
            
            // Initialize any existing dropdowns
            const dropdowns = document.querySelectorAll('.dropdown-toggle');
            dropdowns.forEach(dropdown => {
                if (!dropdown.dataset.bsToggle) {
                    new bootstrap.Dropdown(dropdown);
                }
            });
        },

        validateToken: function () {
            const authToken = this.getCookie('jwt_token');
            if (!authToken) {
                this.updateHeaderForGuest();
                return;
            }

            fetch('/codingabcs/api/public/auth/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ token: authToken })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.user) {
                        this.updateHeaderForLoggedInUser(data.user);
                    } else {
                        this.updateHeaderForGuest();
                    }
                })
                .catch(() => this.updateHeaderForGuest());
        },

        updateHeaderForLoggedInUser: function (user) {
            const navbarNav = document.querySelector('.navbar-nav');
            if (!navbarNav) return;

            // Only update if we don't already have the logged-in header
            if (navbarNav.querySelector('.dropdown-toggle')) {
                return;
            }

            const isDark = document.documentElement.classList.contains('dark-theme');
            const themeIconClass = isDark ? 'fas fa-sun' : 'fas fa-moon';

            navbarNav.innerHTML = `
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="dashboard"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button id="themeToggle" class="dropdown-item"><i class="${themeIconClass} me-2"></i>Theme</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="logoutBtn"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            `;

            this.initializeLogout();
        },

        updateHeaderForGuest: function () {
            const navbarNav = document.querySelector('.navbar-nav');
            if (!navbarNav) return;

            // Only update if we don't already have the guest header
            if (navbarNav.querySelector('a[href="login"]')) {
                return;
            }

            const isDark = document.documentElement.classList.contains('dark-theme');
            const themeIconClass = isDark ? 'fas fa-sun' : 'fas fa-moon';

            navbarNav.innerHTML = `
                <li class="nav-item">
                    <button id="themeToggle" class="btn btn-link nav-link theme-switch">
                        <i class="${themeIconClass}"></i>
                        <span class="ms-1">Theme</span>
                    </button>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="login">
                        <i class="fas fa-sign-in-alt"></i>
                        <span class="ms-1">Login</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="register">
                        <i class="fas fa-user-plus"></i>
                        <span class="ms-1">Register</span>
                    </a>
                </li>
            `;
        },

        initializeLogout: function() {
            // Try to find the logout button
            const logoutBtn = document.getElementById('logoutBtn');
            
            if (!logoutBtn) {
                return;
            }

            // Remove any existing click handlers
            const newLogoutBtn = logoutBtn.cloneNode(true);
            logoutBtn.parentNode.replaceChild(newLogoutBtn, logoutBtn);

            newLogoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                
                const token = this.getCookie('jwt_token');
                
                if (!token) {
                    window.location.href = '/codingabcs/client/public/home';
                    return;
                }

                fetch('/codingabcs/api/public/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    credentials: 'include'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Logout failed');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        // Clear the JWT token cookie
                        document.cookie = 'jwt_token=; path=/codingabcs; expires=Thu, 01 Jan 1970 00:00:00 GMT; secure; samesite=strict';
                        // Redirect to home page
                        window.location.href = '/codingabcs/client/public/home';
                    } else {
                        throw new Error(data.message || 'Logout failed');
                    }
                })
                .catch(error => {
                    // Still clear the token and redirect even if the API call fails
                    document.cookie = 'jwt_token=; path=/codingabcs; expires=Thu, 01 Jan 1970 00:00:00 GMT; secure; samesite=strict';
                    window.location.href = '/codingabcs/client/public/home';
                });
            });
        },

        getCookie: function (name) {
            const nameEQ = name + '=';
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length);
            }
            return null;
        }
    };

    // Init user header display
    header.init();

    // Go back button functionality
    const goBackBtn = document.querySelector('.js-go-back');
    if (goBackBtn) {
        goBackBtn.addEventListener('click', function(e) {
            e.preventDefault();
            history.back();
        });
    }

    // Popup functionality
    const popup = {
        element: document.getElementById('customPopup'),
        titleElement: document.getElementById('popupTitle'),
        messageElement: document.getElementById('popupMessage'),
        actionButton: document.getElementById('popupActionBtn'),
        closeButton: document.getElementById('popupCloseBtn'),

        init: function() {
            if (!this.element) return;
            
            // Close on close button click
            this.closeButton?.addEventListener('click', () => this.hide());
            
            // Close on action button click
            this.actionButton?.addEventListener('click', () => this.hide());
            
            // Close on clicking outside
            this.element.addEventListener('click', (e) => {
                if (e.target === this.element) {
                    this.hide();
                }
            });
            
            // Close on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isVisible()) {
                    this.hide();
                }
            });
        },

        show: function(title, message, actionText = 'Close') {
            if (!this.element) return;
            
            this.titleElement.textContent = title;
            this.messageElement.textContent = message;
            this.actionButton.textContent = actionText;
            
            // Show the popup
            this.element.classList.add('visible');
            
            // Focus the close button for accessibility
            this.closeButton.focus();
        },

        hide: function() {
            if (!this.element) return;
            this.element.classList.remove('visible');
        },

        isVisible: function() {
            return this.element?.classList.contains('visible');
        }
    };

    // Initialize popup
    popup.init();

    // Make popup globally accessible
    window.showPopup = (title, message, actionText) => popup.show(title, message, actionText);
    window.hidePopup = () => popup.hide();
});
