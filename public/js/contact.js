document.addEventListener('DOMContentLoaded', function() {
    
    const form = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    
    initRealTimeValidation();
    
    
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            } else {
                showLoadingState();
            }
        });
    }
    
    
    autoHideMessages();
    
    
    animateFieldsOnFocus();
    
    
    function initRealTimeValidation() {
        inputs.forEach(input => {
            
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            
            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    removeError(this);
                }
            });
        });
        
        
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                if (this.value.trim() !== '' && isValidEmail(this.value)) {
                    removeError(this);
                }
            });
        }
    }
    
    
    function validateField(field) {
        const value = field.value.trim();
        const fieldName = field.getAttribute('name');
        
        if (value === '') {
            showError(field, 'Ce champ est requis');
            return false;
        }
        
        if (fieldName === 'email' && !isValidEmail(value)) {
            showError(field, 'Email invalide');
            return false;
        }
        
        if (fieldName === 'telephone' && value !== '' && !isValidPhone(value)) {
            showError(field, 'Numéro de téléphone invalide');
            return false;
        }
        
        removeError(field);
        return true;
    }
    
    
    function validateForm() {
        let isValid = true;
        
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });
        
        
        const sujet = document.getElementById('sujet');
        if (sujet && sujet.value === '') {
            showError(sujet, 'Veuillez sélectionner un sujet');
            isValid = false;
        }
        
        return isValid;
    }
    
    
    function showError(field, message) {
        field.classList.add('error');
        
        
        const existingError = field.parentElement.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.cssText = 'color: #c62828; font-size: 0.7rem; margin-top: 5px;';
        errorDiv.textContent = message;
        
        field.parentElement.appendChild(errorDiv);
    }
    
    
    function removeError(field) {
        field.classList.remove('error');
        const errorMsg = field.parentElement.querySelector('.error-message');
        if (errorMsg) {
            errorMsg.remove();
        }
    }
    
    
    function isValidEmail(email) {
        const regex = /^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/;
        return regex.test(email);
    }
    
    
    function isValidPhone(phone) {
        const regex = /^[0-9\s\+\-\(\)]{10,}$/;
        return regex.test(phone);
    }
    
    
    function showLoadingState() {
        submitBtn.classList.add('loading');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span>⏳</span> Envoi en cours...';
        
        
        setTimeout(() => {
            if (submitBtn.classList.contains('loading')) {
                submitBtn.classList.remove('loading');
                submitBtn.innerHTML = originalText;
            }
        }, 3000);
    }
    
    
    function autoHideMessages() {
        const successMsg = document.getElementById('successMessage');
        const errorMsg = document.getElementById('errorMessage');
        
        if (successMsg) {
            setTimeout(() => {
                successMsg.style.transition = 'opacity 0.5s ease';
                successMsg.style.opacity = '0';
                setTimeout(() => {
                    if (successMsg.parentNode) successMsg.remove();
                }, 500);
            }, 5000);
        }
        
        if (errorMsg) {
            setTimeout(() => {
                errorMsg.style.transition = 'opacity 0.5s ease';
                errorMsg.style.opacity = '0';
                setTimeout(() => {
                    if (errorMsg.parentNode) errorMsg.remove();
                }, 500);
            }, 5000);
        }
    }
    
    
    function animateFieldsOnFocus() {
        const allFields = document.querySelectorAll('.form-group input, .form-group select, .form-group textarea');
        
        allFields.forEach(field => {
            field.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateX(5px)';
                this.parentElement.style.transition = 'transform 0.2s ease';
            });
            
            field.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateX(0)';
            });
        });
    }
    
    
    function saveFormData() {
        const formData = {};
        const allInputs = document.querySelectorAll('#contactForm input, #contactForm select, #contactForm textarea');
        
        allInputs.forEach(input => {
            if (input.name) {
                formData[input.name] = input.value;
            }
        });
        
        sessionStorage.setItem('contactFormData', JSON.stringify(formData));
    }
    
    
    function restoreFormData() {
        const savedData = sessionStorage.getItem('contactFormData');
        if (savedData) {
            const formData = JSON.parse(savedData);
            Object.keys(formData).forEach(key => {
                const input = document.querySelector(`[name="${key}"]`);
                if (input && !input.value) {
                    input.value = formData[key];
                }
            });
        }
    }
    
    
    const formInputs = document.querySelectorAll('#contactForm input, #contactForm select, #contactForm textarea');
    formInputs.forEach(input => {
        input.addEventListener('input', saveFormData);
    });
    
    
    restoreFormData();
    
    
    if (document.getElementById('successMessage')) {
        sessionStorage.removeItem('contactFormData');
    }
    
    
    const messageField = document.getElementById('message');
    if (messageField) {
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.style.cssText = 'font-size: 0.7rem; color: #999; text-align: right; margin-top: 5px;';
        messageField.parentElement.appendChild(counter);
        
        function updateCounter() {
            const length = messageField.value.length;
            const max = 1000;
            counter.textContent = `${length} / ${max} caractères`;
            
            if (length > max) {
                counter.style.color = '#c62828';
            } else if (length > max * 0.9) {
                counter.style.color = '#ff9800';
            } else {
                counter.style.color = '#999';
            }
        }
        
        messageField.addEventListener('input', updateCounter);
        updateCounter();
    }
});