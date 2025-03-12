// The Validator class handles form validation, including setting valid/invalid states and error messages.
class Validator {
    constructor() {}

    // Set as valid: Clears error message, removes 'invalid' class, adds 'valid' class, sets green border
    setValid(field) {
        let errorElement = field.nextElementSibling;
        if (errorElement && errorElement.classList.contains('error-message')) {
            errorElement.innerHTML = ''; // Clear existing error message
        }
        field.classList.remove('invalid');
        field.classList.add('valid');
        field.style.border = "2px solid green";
    }

    // Set as invalid: Displays error message, removes 'valid' class, adds 'invalid' class, sets red border
    setInvalid(field, message) {
        let errorElement = field.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('error-message')) {
            errorElement = document.createElement('span');
            errorElement.classList.add('error-message');
            field.after(errorElement);
        }
        errorElement.innerHTML = message;
        field.classList.remove('valid');
        field.classList.add('invalid');
        field.style.border = "2px solid red";
    }

    // Generic validation method that takes validator function names as an array
    validate(field, validatorNames, extraParams = {}) {
        for (let validatorName of validatorNames) {
            if (typeof this[validatorName] === 'function') {
                if (!this[validatorName](field, extraParams.min, extraParams.max)) return false;
            }
        }
        return true;
    }
    

    // Validate Required: Ensures the field is not empty
    validateRequired(field) {
        if (field.value.trim() === '') {
            this.setInvalid(field, `${field.id} is required.`);
            return false;
        } else {
            this.setValid(field);
            return true;
        }
    }

    // Validate Length: Generic function for any field requiring min/max length
    validateLength(field, min, max) {
        const fieldLength = field.value.length;

        // Check if min and max are defined before applying
        min = min || 0;  // Default min is 0 if undefined
        max = max || Infinity;  // Default max is Infinity if undefined

        if (fieldLength >= min && fieldLength <= max) {
            return true;
        } else if (fieldLength < min) {
            this.setInvalid(field, `${field.id} must be at least ${min} character(s) long.`);
            return false;
        } else {
            this.setInvalid(field, `${field.id} must be shorter than ${max} characters.`);
            return false;
        }
    }

    // Validate Regex Pattern: Generic function for pattern validation
    validateRegexPattern(field, pattern, errorMessage) {
        if (pattern.test(field.value.trim())) {
            this.setValid(field);
            return true;
        } else {
            this.setInvalid(field, errorMessage);
            return false;
        }
    }

    // Validate Letters and Spaces: Generic function for letters and spaces validation
    validateLettersAndSpaces(field) {
        return this.validateRegexPattern(field, /^[A-Za-z\s]+$/, `${field.id} must only contain letters and spaces.`);
    }

    // Validate Alphanumeric: Generic function for alphanumeric validation
    validateAlphanumeric(field) {
        return this.validateRegexPattern(field, /^[_A-z0-9]*((-|\s)*[_A-z0-9])*$/, `${field.id} must only contain letters, numbers, and underscores.`);
    }

    // Validate Number: Generic function for number validation
    validateNumber(field) {
        return this.validateRegexPattern(field, /^\d+$/, `${field.id} must only contain numbers.`);
    }    

    // Validate Number Range: Generic function for number range validation
    validateNumberRange(field, min, max) {
        const value = Number(field.value);
        if (value >= min && value <= max) {
            this.setValid(field);
            return true;
        } else {
            this.setInvalid(field, `${field.id} must be between ${min} and ${max}.`);
            return false;
        }
    }

    // Validate Date: YYYY-MM-DD format
    validateDate(field) {
        this.validateRegexPattern(field, /^\d{4}-\d{2}-\d{2}$/, 'Date must be in YYYY-MM-DD format.');
    }

    // Validate Full Name: Contains only letters and spaces, 3-50 characters
    validateFullName(field) {
        return this.validateLength(field, 3, 50) && this.validateLettersAndSpaces(field);
    }

    // Validate Username: Alphanumeric, 3-20 characters
    validateUsername(field) {
        return this.validateLength(field, 3, 20) && this.validateAlphanumeric(field);
    }

    // Validate Email: Standard email format
    validateEmail(field) {
        return this.validateRegexPattern(field, /^[^\s@]+@[^\s@]+\.[^\s@]+$/, `Please enter a valid ${field.id}.`);
    }

    // Validate Password: Must meet password strength criteria
    validatePassword(field) {
        this.validateConfirmPassword(document.getElementById('RePassword'));
        return this.validatePasswordStrength(field);
    }

    // Validate Password Strength: At least 8 characters, one uppercase, one lowercase, one number, one special character
    validatePasswordStrength(field) {
        const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        return this.validateRegexPattern(field, passwordPattern, 
            `${field.id} must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.`);
    }

    // Validate Confirm Password: Matches Password field and is required
    validateConfirmPassword(field) {
        const passwordField = document.getElementById('Password');
        if (!passwordField || field.value.trim() === "") {
            this.setInvalid(field, 'Confirm Password is required.');
            return false;
        }
        if (field.value !== passwordField.value) {
            this.setInvalid(field, 'Passwords do not match.');
            return false;
        }
        this.setValid(field);
        return true;
    }

    // Validate Phone Number: Standard US format, auto-add hyphens (XXX-XXX-XXXX)
    validatePhoneNumber(field) {
        let cleaned = field.value.replace(/[^0-9]/g, '');
        if (cleaned.length === 10) {
            field.value = `${cleaned.slice(0, 3)}-${cleaned.slice(3, 6)}-${cleaned.slice(6)}`;
            this.setValid(field);
            return true;
        } else {
            this.setInvalid(field, 'Please enter a valid phone number (XXX-XXX-XXXX).');
            return false;
        }
    }

    // Validate Checkbox: Must be checked
    validateCheckbox(field) {
        if (field.checked) {
            this.setValid(field);
            return true;
        } else {
            this.setInvalid(field, 'This box must be checked');
            return false;
        }
    }

    // Validate Radio Buttons: At least one must be selected
    validateRadioButtons(field) {
        let radioButtons = document.querySelectorAll(`input[name="${field.id}"]`);
        let isChecked = Array.from(radioButtons).some(radio => radio.checked);
        if (isChecked) {
            this.setValid(field);
            return true;
        } else {
            this.setInvalid(field, 'Please select an option.');
            return false;
        }
    }

    // Validate Dropdown: Ensures an option other than the default is selected
    validateDropDown(field) {
        if (field.value && field.value !== "default") {
            this.setValid(field);
            return true;
        } else {
            this.setInvalid(field, `Please select a valid option for ${field.id}.`);
            return false;
        }
    }
    
    static submission() {
        document.addEventListener("DOMContentLoaded", () => {
            document.addEventListener("submit", (event) => {
                const form = event.target;
    
                if (!form || !form.hasAttribute("data-ajax")) return;
    
                event.preventDefault(); // Prevent normal form submission
                let isValid = true;
                const errorFields = {};
    
                form.querySelectorAll("input, select, textarea").forEach(field => {
                    const fieldConfig = formFields.find(f => f.id === field.id);
                    if (fieldConfig) {
                        const extraParams = { min: fieldConfig.min, max: fieldConfig.max };
                        if (!validator.validate(field, fieldConfig.validators, extraParams)) {
                            isValid = false;
                            errorFields[field.id] = `${field.id} is invalid`;
                        }
                    }
                });
    
                console.log("Validation complete. Is form valid?", isValid); // Debug validation result
    
                if (!isValid) {
                    let errorMessages = Object.values(errorFields).map(error => `<p>${error}</p>`).join("");
                    console.log("❌ Showing error popup:", errorMessages); // Debug invalid form
                    Validator.showPopup(errorMessages, "error");
                    return;
                }
    
                // Debug before `fetch()` starts
                console.log("📡 Sending AJAX request to:", form.action);
    
                fetch(form.action, {
                    method: "POST",
                    body: new FormData(form),
                    headers: { "X-Requested-With": "XMLHttpRequest" }
                })
                .then(response => {
                    console.log("🔄 Received response:", response);
                    return response.json();
                })
                .then(data => {
                    console.log("🚀 AJAX Response:", data);
    
                    if (data.status === "error") {
                        Object.keys(data.errors).forEach(fieldId => {
                            let field = document.querySelector(`[name="${fieldId}"]`);
                            if (field) {
                                validator.setInvalid(field, data.errors[fieldId]); // Use setInvalid to highlight fields
                            }
                        });
                    
                        let errorMessages = Object.values(data.errors).map(error => `<p>${error}</p>`).join("");
                        console.log("❌ Showing error popup:", errorMessages);
                        Validator.showPopup(errorMessages, "error");
                    }
                    
                    if (data.status === "success") {
                        console.log("✅ Showing success popup:", data.message);
                        Validator.showPopup(data.message + " Click Close to continue.", "success");
                    }
                })
                .catch(error => {
                    console.error("⚠️ Fetch error:", error);
                    Validator.showPopup("An error occurred while processing your request.", "error");
                });
            });
        });
    }

    // Custom popup for validation messages
    static showPopup(message, type) {
        const popup = document.getElementById("customPopup");
        const popupMessage = document.getElementById("popupMessage");
        const popupCloseBtn = document.getElementById("popupCloseBtn");

        if (!popup || !popupMessage || !popupCloseBtn) return;

        // Set popup content
        popupMessage.innerHTML = message;
        popup.classList.add("visible");

        // Adjust text color based on type
        popupMessage.classList.remove("error-text", "success-text");
        popupMessage.classList.add(type === "error" ? "error-text" : "success-text");

        // Save popup state in sessionStorage to persist visibility
        sessionStorage.setItem("popupVisible", "true");
        sessionStorage.setItem("popupMessage", message);
        sessionStorage.setItem("popupType", type);

        popupCloseBtn.onclick = () => {
            popup.classList.remove("visible");
            sessionStorage.removeItem("popupVisible");
            sessionStorage.removeItem("popupMessage");
            sessionStorage.removeItem("popupType");
        
            // If the popup was for a success message, redirect on close
            if (type === "success") {
                console.log("✅ Redirecting after success..."); // Debugging output
        
                // Correct redirect based on BASE_URL
                const redirectUrl = window.BASE_URL && window.BASE_URL !== "undefined" 
                ? `${window.BASE_URL}/dashboard` 
                : "http://localhost/codingabcs/public/dashboard";
            
                window.location.href = redirectUrl;
            }
        };
    }        
}

// Instantiate the Validator class
const validator = new Validator();
Validator.submission();

// Configuration for form fields, including IDs, validators, and events
const formFields = [
    // Validators can go here if not using formBuilder.php
];

// Add event listeners to each field
formFields.forEach(fieldConfig => {
    const field = document.getElementById(fieldConfig.id);
    if (field) {
        fieldConfig.events.forEach(event => {
            field.addEventListener(event, () => 
                validator.validate(field, fieldConfig.validators, { min: fieldConfig.min, max: fieldConfig.max })
            );
        });
    }
});


