document.addEventListener('DOMContentLoaded', function() {
    
    animateStats();
    
    
    initScrollAnimation();
    
    
    initDetailsButtons();
    
    
    restoreDetailsState();
});


function animateStats() {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    statNumbers.forEach(stat => {
        const target = parseInt(stat.getAttribute('data-target')) || 0;
        const hasCurrency = stat.getAttribute('data-currency') === 'true';
        let current = 0;
        const duration = 1000;
        const stepTime = 20;
        const steps = duration / stepTime;
        const increment = target / steps;
        
        const updateNumber = () => {
            current += increment;
            if (current >= target) {
                if (hasCurrency) {
                    stat.innerText = target.toFixed(2) + ' €';
                } else {
                    stat.innerText = Math.round(target);
                }
            } else {
                if (hasCurrency) {
                    stat.innerText = current.toFixed(2) + ' €';
                } else {
                    stat.innerText = Math.floor(current);
                }
                setTimeout(updateNumber, stepTime);
            }
        };
        
        updateNumber();
    });
}


function initScrollAnimation() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.commande-card').forEach(card => {
        observer.observe(card);
    });
}


function initDetailsButtons() {
    const detailsButtons = document.querySelectorAll('.btn-details');
    
    detailsButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const commandeId = this.getAttribute('data-id');
            if (!commandeId) return;
            
            const detailsDiv = document.getElementById(`details-${commandeId}`);
            if (!detailsDiv) return;
            
            if (detailsDiv.style.display === 'none') {
                detailsDiv.style.display = 'block';
                this.classList.add('active');
                localStorage.setItem(`commande_details_${commandeId}`, 'open');
            } else {
                detailsDiv.style.display = 'none';
                this.classList.remove('active');
                localStorage.setItem(`commande_details_${commandeId}`, 'closed');
            }
        });
    });
}


function restoreDetailsState() {
    document.querySelectorAll('.commande-card').forEach(card => {
        const commandeId = card.getAttribute('data-commande-id');
        if (!commandeId) return;
        
        const savedState = localStorage.getItem(`commande_details_${commandeId}`);
        
        if (savedState === 'open') {
            const detailsDiv = document.getElementById(`details-${commandeId}`);
            const button = card.querySelector('.btn-details');
            if (detailsDiv && button) {
                detailsDiv.style.display = 'block';
                button.classList.add('active');
            }
        }
    });
}


setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => {
            if (alert.parentNode) alert.remove();
        }, 500);
    });
}, 5000);