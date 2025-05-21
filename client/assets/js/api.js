// Global API error handling and authentication
(() => {
    class API {
        constructor() {
            this.init();
        }

        init() {
            // Intercept all fetch requests
            const originalFetch = window.fetch;
            window.fetch = async (url, options = {}) => {
                // Add auth token to all API requests
                if (url.includes('/api/') && !url.includes('/api/register') && !url.includes('/api/login')) {
                    const token = window.Auth?.getToken();
                    if (token) {
                        options.headers = {
                            ...options.headers,
                            'Authorization': `Bearer ${token}`,
                            'X-Requested-With': 'XMLHttpRequest'
                        };
                    }
                }

                try {
                    const response = await originalFetch(url, options);
                    
                    // Handle 401 Unauthorized responses
                    if (response.status === 401) {
                        window.Auth?.removeToken();
                        
                        // Don't redirect if we're already on login/register page
                        if (!window.location.pathname.includes('/login') && 
                            !window.location.pathname.includes('/register')) {
                            window.location.href = '/codingabcs/client/public/login';
                        }
                        
                        return Promise.reject(new Error('Unauthorized'));
                    }

                    return response;
                } catch (error) {
                    console.error('API Error:', error);
                    return Promise.reject(error);
                }
            };
        }

        // Helper method to make authenticated API calls
        static async fetch(url, options = {}) {
            return window.fetch(url, options);
        }
    }

    if (!window.API) window.API = new API();
})(); 