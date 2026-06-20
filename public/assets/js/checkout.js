// ============================================================
// FILE: public/assets/js/checkout.js
// PURPOSE: Checkout form validation
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    const form = document.getElementById('checkoutForm');
    const submitBtn = document.getElementById('placeOrderBtn');
    
    // ============================================
    // REAL-TIME VALIDATION
    // ============================================
    
    const fullName = document.getElementById('full_name');
    const email = document.getElementById('email');
    const phone = document.getElementById('phone');
    const address = document.getElementById('address');
    const city = document.getElementById('city');
    const postalCode = document.getElementById('postal_code');
    
    function validateFullName() {
        const value = fullName.value.trim();
        fullName.classList.remove('is-invalid', 'is-valid');
        
        if (value.length < 2) {
            fullName.classList.add('is-invalid');
            showFieldError(fullName, 'Full name must be at least 2 characters.');
            return false;
        }
        
        fullName.classList.add('is-valid');
        removeFieldError(fullName);
        return true;
    }
    
    function validateEmail() {
        const value = email.value.trim();
        email.classList.remove('is-invalid', 'is-valid');
        
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            email.classList.add('is-invalid');
            showFieldError(email, 'Please enter a valid email address.');
            return false;
        }
        
        email.classList.add('is-valid');
        removeFieldError(email);
        return true;
    }
    
    function validatePhone() {
        const value = phone.value.trim();
        phone.classList.remove('is-invalid', 'is-valid');
        
        if (!/^[0-9+\-\s()]{10,20}$/.test(value)) {
            phone.classList.add('is-invalid');
            showFieldError(phone, 'Please enter a valid phone number.');
            return false;
        }
        
        phone.classList.add('is-valid');
        removeFieldError(phone);
        return true;
    }
    
    function validateAddress() {
        const value = address.value.trim();
        address.classList.remove('is-invalid', 'is-valid');
        
        if (value.length < 3) {
            address.classList.add('is-invalid');
            showFieldError(address, 'Address is required.');
            return false;
        }
        
        address.classList.add('is-valid');
        removeFieldError(address);
        return true;
    }
    
    function validateCity() {
        const value = city.value.trim();
        city.classList.remove('is-invalid', 'is-valid');
        
        if (value.length < 2) {
            city.classList.add('is-invalid');
            showFieldError(city, 'City is required.');
            return false;
        }
        
        city.classList.add('is-valid');
        removeFieldError(city);
        return true;
    }
    
    function validatePostalCode() {
        const value = postalCode.value.trim();
        postalCode.classList.remove('is-invalid', 'is-valid');
        
        if (!/^[0-9]{5,10}$/.test(value)) {
            postalCode.classList.add('is-invalid');
            showFieldError(postalCode, 'Please enter a valid postal code (5-10 digits).');
            return false;
        }
        
        postalCode.classList.add('is-valid');
        removeFieldError(postalCode);
        return true;
    }
    
    function showFieldError(input, message) {
        let errorElement = input.parentElement.querySelector('.error-message');
        
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            input.parentElement.appendChild(errorElement);
        }
        
        errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    }
    
    function removeFieldError(input) {
        const errorElement = input.parentElement.querySelector('.error-message');
        if (errorElement) {
            errorElement.remove();
        }
    }
    
    // Add event listeners
    fullName.addEventListener('input', validateFullName);
    fullName.addEventListener('blur', validateFullName);
    
    email.addEventListener('input', validateEmail);
    email.addEventListener('blur', validateEmail);
    
    phone.addEventListener('input', validatePhone);
    phone.addEventListener('blur', validatePhone);
    
    address.addEventListener('input', validateAddress);
    address.addEventListener('blur', validateAddress);
    
    city.addEventListener('input', validateCity);
    city.addEventListener('blur', validateCity);
    
    postalCode.addEventListener('input', validatePostalCode);
    postalCode.addEventListener('blur', validatePostalCode);
    
    // ============================================
    // FORM SUBMISSION
    // ============================================
    
    form.addEventListener('submit', function(e) {
        // Prevent default submission
        e.preventDefault();
        
        // Run all validations
        const isNameValid = validateFullName();
        const isEmailValid = validateEmail();
        const isPhoneValid = validatePhone();
        const isAddressValid = validateAddress();
        const isCityValid = validateCity();
        const isPostalValid = validatePostalCode();
        
        // Check if all valid
        if (isNameValid && isEmailValid && isPhoneValid && 
            isAddressValid && isCityValid && isPostalValid) {
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = 'Processing Order...';
            
            // Submit the form
            form.submit();
        } else {
            // Scroll to first error
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.focus();
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Shake animation
                firstError.classList.add('shake');
                setTimeout(() => {
                    firstError.classList.remove('shake');
                }, 500);
            }
        }
    });
    
    // ============================================
    // SHAKE ANIMATION
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
    
    console.log('✅ Checkout form loaded successfully!');
});