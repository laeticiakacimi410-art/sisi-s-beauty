function goToStep(step) {
    window.location.href = 'commander.php?step=' + step;
}


document.addEventListener('DOMContentLoaded', function() {
    
    const activeStep = document.querySelector('.step-content.active');
    if (activeStep) {
        activeStep.style.opacity = '0';
        activeStep.style.transform = 'translateY(10px)';
        setTimeout(() => {
            activeStep.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            activeStep.style.opacity = '1';
            activeStep.style.transform = 'translateY(0)';
        }, 100);
    }
    
    
    const step1Form = document.querySelector('#step1 form');
    if (step1Form) {
        const inputs = step1Form.querySelectorAll('input[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#c62828';
                } else {
                    this.style.borderColor = '#c4a4a4';
                }
            });
            
            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.style.borderColor = '#ddd';
                }
            });
        });
    }
    
    
    const saveFormData = () => {
        const formData = {};
        const inputs = document.querySelectorAll('#step1 input, #step1 textarea');
        inputs.forEach(input => {
            if (input.name) {
                formData[input.name] = input.value;
            }
        });
        sessionStorage.setItem('commandeFormData', JSON.stringify(formData));
    };
    
    const inputs = document.querySelectorAll('#step1 input, #step1 textarea');
    inputs.forEach(input => {
        input.addEventListener('input', saveFormData);
    });
    
    
    const savedData = sessionStorage.getItem('commandeFormData');
    if (savedData && window.location.href.indexOf('step=') === -1) {
        const formData = JSON.parse(savedData);
        Object.keys(formData).forEach(key => {
            const input = document.querySelector(`[name="${key}"]`);
            if (input && !input.value) {
                input.value = formData[key];
            }
        });
    }
    
    
    const submitBtn = document.querySelector('button[name="step1"]');
    if (submitBtn) {
        submitBtn.addEventListener('click', () => {
            sessionStorage.removeItem('commandeFormData');
        });
    }
    
    
    const confirmBtn = document.querySelector('button[name="step3"]');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', (e) => {
            const confirmed = confirm('Confirmez-vous votre commande ? Vérifiez bien vos informations avant de valider.');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    }
});


window.goToStep = goToStep;