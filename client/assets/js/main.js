// Common utility functions
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in and redirect from auth pages
    const isAuthPage = window.location.pathname.includes('/login') || window.location.pathname.includes('/register');
    const isHomePage = window.location.pathname.includes('/home');
    const isPublicPage = isAuthPage || isHomePage;

    if (!isPublicPage) {
        const token = window.Auth?.getToken();
        if (!token) {
            window.location.href = '/codingabcs/client/public/login';
            return;
        }

        // Validate token
        fetch('/codingabcs/api/public/auth/validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ token })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.user) {
                window.Auth?.removeToken();
                window.location.href = '/codingabcs/client/public/login';
            }
        })
        .catch(error => {
            window.Auth?.removeToken();
            window.location.href = '/codingabcs/client/public/login';
        });
    } else if (isAuthPage) {
        const token = window.Auth?.getToken();
        if (token) {
            // Validate token
            fetch('/codingabcs/api/public/auth/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ token })
            })
            .then(response => response.json())
            .then(data => {
                if (data.user) {
                    window.location.href = '/codingabcs/client/public/dashboard';
                }
            })
            .catch(error => {
                window.Auth?.removeToken();
                console.log('Token validation error:', error);
            });
        }
    }

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Handle flash messages
    const flashMessages = document.querySelectorAll('.alert-dismissible');
    flashMessages.forEach(function(message) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(message);
            bsAlert.close();
        }, 5000);
    });
}); 