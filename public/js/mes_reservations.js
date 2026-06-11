document.addEventListener('DOMContentLoaded', function() {
    
    animateStats();
    
    
    initScrollAnimation();
    
    
    initTabs();
    
    
    initCancelForms();
    
    
    initCalendarButtons();
    
    
    restoreActiveTab();
    
    
    autoHideMessages();
});


function animateStats() {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    statNumbers.forEach(stat => {
        const target = parseInt(stat.getAttribute('data-target')) || 0;
        let current = 0;
        const duration = 1000;
        const stepTime = 20;
        const steps = duration / stepTime;
        const increment = target / steps;
        
        const updateNumber = () => {
            current += increment;
            if (current >= target) {
                stat.innerText = target;
            } else {
                stat.innerText = Math.floor(current);
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
    
    document.querySelectorAll('.reservation-card').forEach(card => {
        observer.observe(card);
    });
}


function initTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            
            this.classList.add('active');
            document.getElementById(`tab-${tabId}`).classList.add('active');
            
            
            localStorage.setItem('activeReservationsTab', tabId);
        });
    });
}


function initCancelForms() {
    const cancelForms = document.querySelectorAll('.cancel-form');
    
    cancelForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const confirmed = confirm('Êtes-vous sûr(e) de vouloir annuler ce rendez-vous ? Cette action est irréversible.');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    });
}


function initCalendarButtons() {
    const calendarBtns = document.querySelectorAll('.btn-calendar');
    
    calendarBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const eventDataStr = this.getAttribute('data-event');
            if (!eventDataStr) return;
            
            try {
                const eventData = JSON.parse(eventDataStr);
                addToCalendar(eventData);
            } catch (e) {
                console.error('Erreur lors du parsing des données:', e);
            }
        });
    });
}


function addToCalendar(eventData) {
    const startDateTime = new Date(`${eventData.date}T${eventData.heure}:00`);
    const dureeMinutes = parseInt(eventData.duree) || 60;
    const endDateTime = new Date(startDateTime.getTime() + dureeMinutes * 60000);
    
    const formatDate = (date) => {
        return date.toISOString().replace(/-|:|\.\d+/g, '');
    };
    
    const start = formatDate(startDateTime);
    const end = formatDate(endDateTime);
    
    const title = encodeURIComponent(`Sisi's Beauty - ${eventData.nom}`);
    const details = encodeURIComponent(`Rendez-vous chez Sisi's Beauty\nPrestation: ${eventData.nom}\nDurée: ${dureeMinutes} minutes`);
    
    const googleCalendarUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${start}/${end}&details=${details}`;
    
    window.open(googleCalendarUrl, '_blank');
}


function restoreActiveTab() {
    const savedTab = localStorage.getItem('activeReservationsTab');
    if (savedTab) {
        const btnToActivate = document.querySelector(`.tab-btn[data-tab="${savedTab}"]`);
        if (btnToActivate) {
            btnToActivate.click();
        }
    }
}


function autoHideMessages() {
    setTimeout(() => {
        const successMsg = document.getElementById('successMessage');
        const errorMsg = document.getElementById('errorMessage');
        
        if (successMsg) {
            successMsg.style.transition = 'opacity 0.5s ease';
            successMsg.style.opacity = '0';
            setTimeout(() => {
                if (successMsg.parentNode) successMsg.remove();
            }, 500);
        }
        
        if (errorMsg) {
            errorMsg.style.transition = 'opacity 0.5s ease';
            errorMsg.style.opacity = '0';
            setTimeout(() => {
                if (errorMsg.parentNode) errorMsg.remove();
            }, 500);
        }
    }, 5000);
}