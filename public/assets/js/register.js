// ============================================================
// FILE: public/assets/js/register.js
// PURPOSE: Client-side validation for registration form
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // DOM REFERENCES
    const form = document.getElementById('registerForm');
    const fullName = document.getElementById('fullName');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const termsCheckbox = document.getElementById('termsCheckbox');
    const submitButton = form.querySelector('button[type="submit"]');
    
    // Password requirements elements
    const reqLength = document.getElementById('req-length');
    const reqUppercase = document.getElementById('req-uppercase');
    const reqLowercase = document.getElementById('req-lowercase');
    const reqNumber = document.getElementById('req-number');
    const reqSpecial = document.getElementById('req-special');
    
    // Password match indicator
    const passwordMatch = document.getElementById('passwordMatch');
    const termsError = document.getElementById('termsError');
    
    // PASSWORD TOGGLE VISIBILITY
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const input = this.closest('.password-wrapper').querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                this.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                this.setAttribute('aria-label', 'Show password');
            }
        });
    });
    
    // VALIDATION FUNCTIONS
    function validateFullName() {
        const value = fullName.value.trim();
        const errorElement = fullName.parentElement.querySelector('.error-message');
        
        if (errorElement && !errorElement.id) {
            errorElement.remove();
        }
        
        fullName.classList.remove('is-invalid', 'is-valid');
        
        if (value.length === 0) {
            fullName.classList.add('is-invalid');
            showError(fullName, 'Full name is required.');
            return false;
        }
        
        if (value.length < 2) {
            fullName.classList.add('is-invalid');
            showError(fullName, 'Full name must be at least 2 characters.');
            return false;
        }
        
        if (value.length > 100) {
            fullName.classList.add('is-invalid');
            showError(fullName, 'Full name cannot exceed 100 characters.');
            return false;
        }
        
        if (!/^[a-zA-Z\s'-]+$/.test(value)) {
            fullName.classList.add('is-invalid');
            showError(fullName, 'Full name can only contain letters, spaces, hyphens, and apostrophes.');
            return false;
        }
        
        fullName.classList.add('is-valid');
        return true;
    }
    
    function validateEmail() {
        const value = email.value.trim();
        const errorElement = email.parentElement.querySelector('.error-message');
        
        if (errorElement && !errorElement.id) {
            errorElement.remove();
        }
        
        email.classList.remove('is-invalid', 'is-valid');
        
        if (value.length === 0) {
            email.classList.add('is-invalid');
            showError(email, 'Email address is required.');
            return false;
        }
        
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            email.classList.add('is-invalid');
            showError(email, 'Please enter a valid email address.');
            return false;
        }
        
        if (value.length > 150) {
            email.classList.add('is-invalid');
            showError(email, 'Email cannot exceed 150 characters.');
            return false;
        }
        
        email.classList.add('is-valid');
        return true;
    }
    
    function validatePassword() {
        const value = password.value;
        const errorElement = password.parentElement.parentElement.querySelector('.error-message');
        
        if (errorElement && !errorElement.id) {
            errorElement.remove();
        }
        
        password.classList.remove('is-invalid', 'is-valid');
        
        const hasLength = value.length >= 8;
        const hasUppercase = /[A-Z]/.test(value);
        const hasLowercase = /[a-z]/.test(value);
        const hasNumber = /[0-9]/.test(value);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(value);
        
        updateRequirement(reqLength, hasLength);
        updateRequirement(reqUppercase, hasUppercase);
        updateRequirement(reqLowercase, hasLowercase);
        updateRequirement(reqNumber, hasNumber);
        updateRequirement(reqSpecial, hasSpecial);
        
        const isValid = hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial;
        
        if (value.length > 0 && !isValid) {
            password.classList.add('is-invalid');
            showError(password, 'Password does not meet the requirements below.');
        } else if (value.length > 0 && isValid) {
            password.classList.add('is-valid');
        }
        
        if (confirmPassword.value.length > 0) {
            validateConfirmPassword();
        }
        
        return isValid;
    }
    
    function updateRequirement(element, isMet) {
        if (isMet) {
            element.classList.remove('unmet');
            element.classList.add('met');
            element.querySelector('i').className = 'fas fa-check-circle';
        } else {
            element.classList.remove('met');
            element.classList.add('unmet');
            element.querySelector('i').className = 'fas fa-circle';
        }
    }
    
    function validateConfirmPassword() {
        const value = confirmPassword.value;
        const errorElement = confirmPassword.parentElement.parentElement.querySelector('.error-message');
        
        if (errorElement && !errorElement.id) {
            errorElement.remove();
        }
        
        confirmPassword.classList.remove('is-invalid', 'is-valid');
        
        if (value.length === 0) {
            confirmPassword.classList.add('is-invalid');
            showError(confirmPassword, 'Please confirm your password.');
            passwordMatch.textContent = '';
            passwordMatch.className = 'password-match';
            return false;
        }
        
        if (value !== password.value) {
            confirmPassword.classList.add('is-invalid');
            showError(confirmPassword, 'Passwords do not match.');
            passwordMatch.textContent = '✗ Passwords do not match';
            passwordMatch.className = 'password-match no-match';
            return false;
        }
        
        confirmPassword.classList.add('is-valid');
        passwordMatch.textContent = '✓ Passwords match';
        passwordMatch.className = 'password-match match';
        return true;
    }
    
    function showError(input, message) {
        const parent = input.parentElement;
        let errorElement = parent.querySelector('.error-message');
        
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            parent.appendChild(errorElement);
        }
        
        errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    }
    
    // EVENT LISTENERS
    fullName.addEventListener('input', validateFullName);
    email.addEventListener('input', validateEmail);
    password.addEventListener('input', function() {
        validatePassword();
        if (confirmPassword.value.length > 0) {
            validateConfirmPassword();
        }
    });
    confirmPassword.addEventListener('input', validateConfirmPassword);
    
    fullName.addEventListener('blur', validateFullName);
    email.addEventListener('blur', validateEmail);
    password.addEventListener('blur', function() {
        if (this.value.length > 0) {
            validatePassword();
        }
    });
    confirmPassword.addEventListener('blur', function() {
        if (this.value.length > 0) {
            validateConfirmPassword();
        }
    });
    
    // TERMS CHECKBOX
    termsCheckbox.addEventListener('change', function() {
        if (this.checked) {
            termsError.style.display = 'none';
        }
    });
    
    // FORM SUBMISSION
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const isNameValid = validateFullName();
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        const isConfirmValid = validateConfirmPassword();
        const isTermsChecked = termsCheckbox.checked;
        
        if (!isTermsChecked) {
            termsError.style.display = 'flex';
        } else {
            termsError.style.display = 'none';
        }
        
        if (isNameValid && isEmailValid && isPasswordValid && isConfirmValid && isTermsChecked) {
            submitButton.disabled = true;
            submitButton.classList.add('loading');
            submitButton.innerHTML = 'Creating Account...';
            form.submit();
        } else {
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.focus();
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (!isTermsChecked) {
                termsCheckbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            form.querySelectorAll('.is-invalid').forEach(function(field) {
                field.classList.add('shake');
                setTimeout(function() {
                    field.classList.remove('shake');
                }, 500);
            });
        }
    });
    
    // ADD SHAKE ANIMATION
    const shakeStyles = document.createElement('style');
    shakeStyles.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }
        .shake {
            animation: shake 0.4s ease;
        }
    `;
    document.head.appendChild(shakeStyles);
    
    console.log('✅ Registration form initialized successfully!');
});