// Auth functionality
(() => {
    class Auth {
      constructor() {
        this.loginForm = document.getElementById('loginForm');
        this.registerForm = document.getElementById('registerForm');
        this.logoutButton = document.getElementById('logoutButton');
        this.validator = new Validator();
        this.init();
      }
  
      init() {
        if (this.loginForm) this.loginForm.addEventListener('submit', e => this.handleLogin(e));
        if (this.registerForm) this.registerForm.addEventListener('submit', e => this.handleRegister(e));
        if (this.logoutButton) this.logoutButton.addEventListener('click', e => this.handleLogout(e));
      }
  
      // --- Token management ---
      setToken(token) {
        const expires = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toUTCString();
        document.cookie = `jwt_token=${token}; expires=${expires}; path=/codingabcs; domain=localhost; secure; samesite=strict`;
      }
  
      getToken() {
        return document.cookie
          .split('; ')
          .find(row => row.startsWith('jwt_token='))
          ?.split('=')[1] || '';
      }
  
      removeToken() {
        document.cookie = 'jwt_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; secure; samesite=strict';
      }
  
      // --- Helpers ---
      _renderFieldError(input, message) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        let err = input.nextElementSibling;
        if (!err || !err.classList.contains('error-message')) {
          err = document.createElement('div');
          err.className = 'error-message';
          input.after(err);
        }
        err.textContent = message;
      }
  
      _toggleButton(form, { text, disable }) {
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
          btn.disabled = disable;
          btn.innerHTML = text;
        }
      }
  
      async _jsonFetch(url, options = {}) {
        const opts = {
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          ...options,
        };
        if (opts.body && typeof opts.body !== 'string') opts.body = JSON.stringify(opts.body);
        const resp = await fetch(url, opts);
        const data = await resp.json().catch(() => null);
        return { resp, data };
      }
  
      // --- Handlers ---
      async handleLogin(event) {
        event.preventDefault();
        if (this.loginForm.classList.contains('submitting')) return;
        const errors = this.validator.validateForm(this.loginForm);
        if (errors.length) {
          Validator.showPopup(errors, 'error');
          return;
        }
  
        this.loginForm.classList.add('submitting');
        this._toggleButton(this.loginForm, { text: '<span class="spinner-border spinner-border-sm me-2"></span>Logging in...', disable: true });
  
        try {
          const payload = Object.fromEntries(new FormData(this.loginForm).entries());
          const { resp, data } = await this._jsonFetch(this.loginForm.action, { method: this.loginForm.method, body: payload });
  
          if (!resp.ok) {
            if (resp.status === 401) {
              ['email', 'password'].forEach(id => {
                const fld = this.loginForm.querySelector(`#${id}`);
                if (fld) this._renderFieldError(fld, 'Please check your credentials');
              });
              this.loginForm.classList.add('was-validated');
              Validator.showPopup('Incorrect email or password.', 'error');
            } else if (resp.status === 422 && data?.errors) {
              Object.entries(data.errors).forEach(([field, msg]) => {
                const fld = this.loginForm.querySelector(`[name="${field}"]`);
                if (fld) this._renderFieldError(fld, msg);
              });
              Validator.showPopup(Object.values(data.errors), 'error');
            } else {
              Validator.showPopup(data?.message || 'Login failed', 'error');
            }
          } else if (data?.status === 'success') {
            if (data.data?.token) {
              this.setToken(data.data.token);
              Validator.showPopup('Login successful! Redirecting...', 'success');
              setTimeout(() => window.location.href = '/codingabcs/client/public/dashboard', 1500);
            } else {
              Validator.showPopup('No token received.', 'error');
            }
          }
        } catch (error) {
          Validator.showPopup('An error occurred during login. Please try again.', 'error');
        } finally {
          this.loginForm.classList.remove('submitting');
          this._toggleButton(this.loginForm, { text: 'Login', disable: false });
        }
      }
  
      async handleRegister(event) {
        event.preventDefault();
        if (!this.registerForm || this.registerForm.classList.contains('submitting')) return;
        const errors = this.validator.validateForm(this.registerForm);
        if (errors.length) {
          Validator.showPopup(errors, 'error');
          return;
        }
  
        this.registerForm.classList.add('submitting');
        this._toggleButton(this.registerForm, { text: '<span class="spinner-border spinner-border-sm me-2"></span>Registering...', disable: true });
  
        try {
          const payload = Object.fromEntries(new FormData(this.registerForm).entries());
          const { resp, data } = await this._jsonFetch(this.registerForm.action, { method: this.registerForm.method, body: payload });
  
          if (!resp.ok || data?.status === 'error') {
            if (data?.errors) {
              Object.entries(data.errors).forEach(([field, msg]) => {
                const fld = this.registerForm.querySelector(`[name="${field}"]`);
                if (fld) this._renderFieldError(fld, msg);
              });
              Validator.showPopup(Object.values(data.errors), 'error');
            } else {
              Validator.showPopup(data?.message || 'Registration failed', 'error');
            }
          } else {
            Validator.showPopup('Registration successful! Redirecting...', 'success');
            setTimeout(() => window.location.href = '/codingabcs/client/public/login', 2000);
          }
        } catch (error) {
          Validator.showPopup('An error occurred during registration. Please try again.', 'error');
        } finally {
          this.registerForm.classList.remove('submitting');
          this._toggleButton(this.registerForm, { text: 'Register', disable: false });
        }
      }
  
      async handleLogout(event) {
        event.preventDefault();
        const token = this.getToken();
        if (!token) {
          window.location.href = '/codingabcs/login';
          return;
        }
  
        const { resp, data } = await this._jsonFetch('/codingabcs/api/public/index.php/logout', { method: 'POST', headers: { 'Authorization': `Bearer ${token}` } });
        if (resp.ok && data?.status === 'success') {
          this.removeToken();
          window.location.href = '/codingabcs/login';
        } else {
          Validator.showPopup(data?.message || 'Logout failed', 'error');
        }
      }
  
      // Convenience fetch for authenticated endpoints
      static async fetchWithAuth(url, options = {}) {
        const auth = window.Auth;
        const token = auth.getToken();
        if (!token) throw new Error('No auth token');
        options.headers = { ...(options.headers || {}), 'Authorization': `Bearer ${token}`, 'X-Requested-With': 'XMLHttpRequest' };
        return fetch(url, options);
      }
    }
  
    if (!window.Auth) window.Auth = new Auth();
  })();
  