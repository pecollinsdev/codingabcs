/**
 * validation.js
 * Refactored Validator for CodingABCs API integration with real-time validation
 * Fixed validateLength to handle no min/max attributes, and only include length validation when appropriate.
 */

class Validator {
    constructor() {
      this.state = new Map();
      document.addEventListener('DOMContentLoaded', () => this.setupForms());
    }
  
    setupForms() {
      document.querySelectorAll('form[data-ajax]').forEach(form => {
        form.noValidate = true;
        const fields = Array.from(form.querySelectorAll('input, textarea, select'));
  
        fields.forEach(field => {
          const validators = [];
          if (field.required) validators.push('validateRequired');
          if (field.type === 'email') validators.push('validateEmail');
          if (field.type === 'password') {
            validators.push('validatePasswordStrength');
            if (/confirm/i.test(field.id)) validators.push('validateConfirmPassword');
          }
          // Only add length validation if minLength or maxLength attributes > 0
          if ((field.minLength || 0) > 0 || (field.maxLength || 0) > 0) {
            validators.push('validateLength');
          }
  
          this.state.set(field, { validators, valid: false });
          ['input', 'blur', 'change'].forEach(evt => {
            field.addEventListener(evt, () => {
              this.runValidators(field);
              this.toggleSubmit(form);
            });
          });
        });
  
        form.addEventListener('submit', e => this.handleSubmit(e, form, fields));
        this.toggleSubmit(form);
      });
    }
  
    // Allow external callers (e.g., Auth) to validate all fields
    validateForm(form) {
      const fields = Array.from(form.querySelectorAll('input, textarea, select'));
      const errors = [];
      fields.forEach(field => {
        this.runValidators(field);
        if (!this.state.get(field)?.valid) {
          const msg = field.nextElementSibling?.textContent || `${this.getLabel(field)} is invalid.`;
          errors.push(msg);
        }
      });
      return errors;
    }
  
    runValidators(field) {
      const cfg = this.state.get(field);
      if (!cfg) return;
      let ok = true;
      for (const name of cfg.validators) {
        if (typeof this[name] === 'function') {
          // pass actual attributes
          ok = this[name](field, field.minLength, field.maxLength);
          if (!ok) break;
        }
      }
      cfg.valid = ok;
    }
  
    toggleSubmit(form) {
      const btn = form.querySelector('button[type="submit"]');
      if (!btn) return;
      // only enable if every field is valid
      const allValid = Array.from(this.state.values()).every(s => s.valid);
      btn.disabled = !allValid;
    }
  
    async handleSubmit(e, form, fields) {
      e.preventDefault();
      fields.forEach(f => this.runValidators(f));
      this.toggleSubmit(form);
      const invalid = fields.filter(f => !this.state.get(f)?.valid);
      if (invalid.length) {
        Validator.showPopup('Please correct the errors before submitting.', 'error');
        return;
      }
      const payload = {};
      new FormData(form).forEach((v, k) => payload[k] = v);
      await this.submitJson(form, payload);
    }
  
    async submitJson(form, payload) {
      const btn = form.querySelector('button[type="submit"]');
      const orig = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = 'Processing...';
      try {
        const resp = await fetch(form.action, {
          method: form.method,
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await resp.json().catch(() => ({}));
        if (!resp.ok || data.errors) {
          const msgs = data.errors ? Object.values(data.errors) : [data.message || 'Submission failed.'];
          if (data.errors) {
            Object.entries(data.errors).forEach(([key, msg]) => {
              const f = form.querySelector(`[name="${key}"]`);
              if (f) this.setInvalid(f, msg);
            });
          }
          Validator.showPopup(msgs, 'error');
        } else {
          Validator.showPopup(data.message || 'Success!', 'success');
          if (data.redirect) setTimeout(() => window.location.href = data.redirect, 3000);
        }
      } catch (err) {
        console.error(err);
        Validator.showPopup('Network or server error.', 'error');
      } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
      }
    }
  
    setInvalid(f, msg) {
      f.classList.add('is-invalid');
      f.classList.remove('is-valid');
      const errorContainer = f.parentElement.querySelector('.error-message-container');
      if (errorContainer) {
        const errorMessage = errorContainer.querySelector('.error-message');
        if (errorMessage) {
          errorMessage.textContent = msg;
          errorMessage.classList.add('visible');
        }
      }
    }
  
    setValid(f) {
      f.classList.remove('is-invalid');
      f.classList.add('is-valid');
      const errorContainer = f.parentElement.querySelector('.error-message-container');
      if (errorContainer) {
        const errorMessage = errorContainer.querySelector('.error-message');
        if (errorMessage) {
          errorMessage.textContent = '';
          errorMessage.classList.remove('visible');
        }
      }
    }
  
    // Validators
    validateRequired(f) {
      if (f.value.trim()) { this.setValid(f); return true; }
      this.setInvalid(f, `Please enter your ${this.getLabel(f).toLowerCase()}`);
      return false;
    }
  
    validateEmail(f) {
      const re = /^[^@]+@[^@]+\.[^@]+$/;
      if (re.test(f.value)) { this.setValid(f); return true; }
      this.setInvalid(f, 'Please enter a valid email address (e.g., user@example.com)');
      return false;
    }
  
    validateLength(f, min, max) {
      const actualMin = min >= 0 ? min : 0;
      const actualMax = max > 0 ? max : Infinity;
      const l = f.value.length;
      if (l >= actualMin && l <= actualMax) { this.setValid(f); return true; }
      this.setInvalid(f,
        l < actualMin
          ? `${this.getLabel(f)} must be at least ${actualMin} characters long`
          : `${this.getLabel(f)} must be no more than ${actualMax} characters long`
      );
      return false;
    }
  
    validatePasswordStrength(f) {
      const v = f.value;
      if (v.length < 8) return this._setAndReturn(f, 'Password must be at least 8 characters long');
      if (!/[A-Z]/.test(v)) return this._setAndReturn(f, 'Password must contain at least one uppercase letter');
      if (!/[a-z]/.test(v)) return this._setAndReturn(f, 'Password must contain at least one lowercase letter');
      if (!/\d/.test(v)) return this._setAndReturn(f, 'Password must contain at least one number');
      if (!/[@$!%*?&]/.test(v)) return this._setAndReturn(f, 'Password must contain at least one special character (@$!%*?&)');
      this.setValid(f);
      return true;
    }
  
    validateConfirmPassword(f) {
      const pw = document.querySelector('input[type="password"]:not([id*=confirm])');
      if (pw && f.value === pw.value) { this.setValid(f); return true; }
      this.setInvalid(f, 'Passwords do not match. Please make sure both passwords are identical');
      return false;
    }
  
    _setAndReturn(f, msg) {
      this.setInvalid(f, msg);
      return false;
    }
  
    getLabel(f) {
      return f.labels?.[0]?.textContent.replace(/\*$/, '').trim() || f.placeholder || f.name;
    }
  
    static showPopup(msg, type) {
      const pop = document.getElementById('customPopup');
      if (!pop) return;
  
      // Set title and icon based on type
      const title = pop.querySelector('#popupTitle');
      const message = pop.querySelector('#popupMessage');
      const actionBtn = pop.querySelector('#popupActionBtn');
      const closeBtn = pop.querySelector('#popupCloseBtn');
  
      // Clear previous classes
      message.className = 'popup-body';
      title.innerHTML = '';
  
      // Set title and icon
      if (type === 'error') {
          title.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
          message.classList.add('error');
      } else {
          title.innerHTML = '<i class="fas fa-check-circle"></i> Success';
          message.classList.add('success');
      }
  
      // Set message content
      message.innerHTML = Array.isArray(msg) 
          ? msg.map(m => `<p><i class="fas fa-${type === 'error' ? 'times' : 'check'}"></i> ${m}</p>`).join('')
          : `<p><i class="fas fa-${type === 'error' ? 'times' : 'check'}"></i> ${msg}</p>`;
  
      // Show popup
      pop.style.display = 'block';
      setTimeout(() => pop.classList.add('visible'), 10);
  
      // Handle close button
      const closePopup = () => {
          pop.classList.remove('visible');
          setTimeout(() => {
              pop.style.display = 'none';
              // Re-enable form after popup closes
              document.querySelectorAll('form').forEach(form => {
                  form.classList.remove('submitting');
                  const submitBtn = form.querySelector('button[type="submit"]');
                  if (submitBtn) {
                      submitBtn.disabled = false;
                      submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Submit';
                  }
              });
          }, 300);
      };
  
      // Add event listeners
      actionBtn.onclick = closePopup;
      closeBtn.onclick = closePopup;
  
      // Auto-close success messages after 3 seconds
      if (type === 'success') {
          setTimeout(closePopup, 3000);
      }
    }
  }
  
  // Initialize
  new Validator();
  