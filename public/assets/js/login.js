// ============================================================
// FILE: public/assets/js/login.js
// PURPOSE: Client-side validation for login form
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // DOM REFERENCES
    // ============================================
    
    const form = document.getElementById('loginForm');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const submitButton = form.querySelector('button[type="submit"]');
    
    // ============================================
    // PASSWORD TOGGLE VISIBILITY
    // ============================================
    
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
    
    // ============================================
    // VALIDATION FUNCTIONS
    // ============================================
    
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
        
        if (value.length === 0) {
            password.classList.add('is-invalid');
            showError(password, 'Password is required.');
            return false;
        }
        
        if (value.length < 8) {
            password.classList.add('is-invalid');
            showError(password, 'Password must be at least 8 characters.');
            return false;
        }
        
        password.classList.add('is-valid');
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
    
    // ============================================
    // EVENT LISTENERS
    // ============================================
    
    email.addEventListener('input', function() {
        if (this.value.length > 0) {
            validateEmail();
        }
    });
    
    email.addEventListener('blur', function() {
        validateEmail();
    });
    
    password.addEventListener('input', function() {
        if (this.value.length > 0) {
            validatePassword();
        }
    });
    
    password.addEventListener('blur', function() {
        if (this.value.length > 0) {
            validatePassword();
        }
    });
    
    // ============================================
    // FORM SUBMISSION
    // ============================================
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        
        if (isEmailValid && isPasswordValid) {
            // Show loading state
            submitButton.disabled = true;
            submitButton.classList.add('loading');
            submitButton.innerHTML = 'Logging in...';
            
            // Submit the form
            form.submit();
        } else {
            // Scroll to first error
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.focus();
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            // Shake animation
            form.querySelectorAll('.is-invalid').forEach(function(field) {
                field.classList.add('shake');
                setTimeout(function() {
                    field.classList.remove('shake');
                }, 500);
            });
        }
    });
    
    // ============================================
    // ADD SHAKE ANIMATION
    // ============================================
    
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
    
    console.log('✅ Login form initialized successfully!');
});