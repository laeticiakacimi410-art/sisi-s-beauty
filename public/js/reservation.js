const prestationsData = window.prestationsData || {};
const prestationIdFromUrl = window.prestationIdFromUrl;


let currentStep = 1;
let selectedPrestation = null;
let selectedDate = null;
let selectedHeure = null;
let prestationDuree = 0;
let currentDate = new Date();


document.addEventListener('DOMContentLoaded', function() {
    currentDate.setHours(0, 0, 0, 0);
    initEventListeners();
    genererCalendrier();
});

function initEventListeners() {
    
    document.querySelectorAll('.prestation-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.prestation-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            
            selectedPrestation = {
                id: this.dataset.id,
                nom: this.dataset.nom,
                duree: parseInt(this.dataset.duree),
                prix: parseFloat(this.dataset.prix)
            };
            prestationDuree = selectedPrestation.duree;
            
            const nextBtn = document.querySelector('.step-1 .btn-next-step');
            if (nextBtn) nextBtn.disabled = false;
        });
        
        
        if (prestationIdFromUrl && card.dataset.id == prestationIdFromUrl) {
            card.click();
        }
    });
    
    
    const step1Next = document.querySelector('.step-1 .btn-next-step');
    if (step1Next) {
        step1Next.addEventListener('click', () => {
            if (selectedPrestation) {
                document.getElementById('selected-prestation-nom').textContent = selectedPrestation.nom;
                document.getElementById('selected-prestation-duree').textContent = selectedPrestation.duree;
                document.getElementById('selected-prestation-nom2').textContent = selectedPrestation.nom;
                nextStep();
            }
        });
    }
    
    
    const calendarPrev = document.querySelector('.calendar-prev');
    const calendarNext = document.querySelector('.calendar-next');
    if (calendarPrev) calendarPrev.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        genererCalendrier();
    });
    if (calendarNext) calendarNext.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        genererCalendrier();
    });
    
    
    const step2Next = document.querySelector('.step-2 .btn-next-step');
    if (step2Next) {
        step2Next.addEventListener('click', () => {
            if (selectedDate) {
                document.getElementById('selected-date-display').textContent = selectedDate;
                genererCreneaux();
                nextStep();
            }
        });
    }
    
    
    const step3Next = document.querySelector('.step-3 .btn-next-step');
    if (step3Next) {
        step3Next.addEventListener('click', () => {
            if (selectedHeure) {
                
                document.getElementById('recap-prestation').textContent = selectedPrestation.nom;
                document.getElementById('recap-date').textContent = new Date(selectedDate).toLocaleDateString('fr-FR');
                document.getElementById('recap-heure').textContent = selectedHeure;
                document.getElementById('recap-prix').textContent = selectedPrestation.prix;
                
                
                document.getElementById('form-prestation-id').value = selectedPrestation.id;
                document.getElementById('form-date').value = selectedDate;
                document.getElementById('form-heure').value = selectedHeure;
                
                nextStep();
            }
        });
    }
    
    
    document.querySelectorAll('.btn-prev-step').forEach(btn => {
        btn.addEventListener('click', prevStep);
    });
}

function updateSteps() {
    document.querySelectorAll('.reservation-step').forEach((step, index) => {
        if (index + 1 === currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
}

function nextStep() {
    if (currentStep < 4) {
        currentStep++;
        updateSteps();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        updateSteps();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function genererCalendrier() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDayOfMonth = new Date(year, month, 1);
    let startDay = firstDayOfMonth.getDay();
    startDay = startDay === 0 ? 6 : startDay - 1;
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    const monthYearSpan = document.querySelector('.calendar-month-year');
    if (monthYearSpan) {
        monthYearSpan.textContent = `${monthNames[month]} ${year}`;
    }
    
    let calendarHtml = '';
    
    for (let i = 0; i < startDay; i++) {
        calendarHtml += '<div class="calendar-day disabled"></div>';
    }
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    for (let day = 1; day <= daysInMonth; day++) {
        const dateObj = new Date(year, month, day);
        const dateStr = dateObj.toISOString().split('T')[0];
        const isPast = dateObj < today;
        
        let classes = 'calendar-day';
        if (isPast) classes += ' past';
        
        calendarHtml += `<div class="${classes}" data-date="${dateStr}">${day}</div>`;
    }
    
    const calendarDays = document.getElementById('calendar-days');
    if (calendarDays) {
        calendarDays.innerHTML = calendarHtml;
    }
    
    
    document.querySelectorAll('.calendar-day:not(.disabled):not(.past)').forEach(day => {
        day.addEventListener('click', function() {
            document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('selected'));
            this.classList.add('selected');
            selectedDate = this.dataset.date;
            const step2Next = document.querySelector('.step-2 .btn-next-step');
            if (step2Next) step2Next.disabled = false;
        });
    });
}

async function genererCreneaux() {
    const horairesGrid = document.getElementById('horaires-grid');
    if (!horairesGrid) return;
    
    horairesGrid.innerHTML = '<p class="loading">Chargement des créneaux...</p>';
    
    try {
        const response = await fetch(`/api/slots.php?prestation_id=${selectedPrestation.id}&date=${selectedDate}`);
        const data = await response.json();
        
        if (data.error) {
            horairesGrid.innerHTML = `<p class="no-creneaux">${data.error}</p>`;
            return;
        }
        
        if (data.slots && data.slots.length > 0) {
            horairesGrid.innerHTML = '';
            data.slots.forEach(slot => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'creneau-btn';
                btn.textContent = slot;
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.creneau-btn').forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedHeure = slot;
                    const step3Next = document.querySelector('.step-3 .btn-next-step');
                    if (step3Next) step3Next.disabled = false;
                });
                horairesGrid.appendChild(btn);
            });
        } else {
            horairesGrid.innerHTML = '<p class="no-creneaux">Aucun créneau disponible pour cette date.</p>';
        }
    } catch (error) {
        console.error('Erreur:', error);
        horairesGrid.innerHTML = '<p class="no-creneaux">Erreur lors du chargement des créneaux.</p>';
    }
}