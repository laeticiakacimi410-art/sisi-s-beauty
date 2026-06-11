let messagesData = [];
let currentMessageId = null;

document.addEventListener('DOMContentLoaded', function() {
    
    document.querySelectorAll('.message-card').forEach(card => {
        messagesData.push({
            id: parseInt(card.dataset.id),
            nom: card.dataset.nom,
            prenom: card.dataset.prenom,
            email: card.dataset.email,
            telephone: card.dataset.telephone,
            sujet: card.dataset.sujet,
            message: card.dataset.message,
            date: card.dataset.date
        });
    });
    
    
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
    
    
    initFilters();
});

function viewMessage(id) {
    const msg = messagesData.find(m => m.id === id);
    if (!msg) return;
    
    currentMessageId = id;
    
    document.getElementById('viewSender').innerHTML = `${msg.prenom} ${msg.nom}`;
    document.getElementById('viewEmail').innerHTML = msg.email;
    document.getElementById('viewPhone').innerHTML = msg.telephone || 'Non fourni';
    document.getElementById('viewSubject').innerHTML = msg.sujet;
    document.getElementById('viewDate').innerHTML = msg.date;
    document.getElementById('viewMessage').innerHTML = msg.message.replace(/\n/g, '<br>');
    
    document.getElementById('viewModal').classList.add('active');
    
    
    fetch(`?read=${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    
    const card = document.querySelector(`.message-card[data-id="${id}"]`);
    if (card && card.classList.contains('unread')) {
        card.classList.remove('unread');
        const badge = card.querySelector('.badge-unread');
        if (badge) badge.remove();
    }
}

function closeViewModal() {
    document.getElementById('viewModal').classList.remove('active');
}

function replyFromView() {
    if (currentMessageId) {
        closeViewModal();
        replyMessage(currentMessageId);
    }
}

function replyMessage(id) {
    const msg = messagesData.find(m => m.id === id);
    if (!msg) return;
    
    document.getElementById('replyId').value = id;
    document.getElementById('replyTo').innerHTML = `${msg.prenom} ${msg.nom} &lt;${msg.email}&gt;`;
    document.getElementById('replyOriginal').innerHTML = msg.message;
    document.getElementById('replyMessage').value = '';
    
    document.getElementById('replyModal').classList.add('active');
}

function closeReplyModal() {
    document.getElementById('replyModal').classList.remove('active');
}

function initFilters() {
    const btns = document.querySelectorAll('.filter-btn');
    
    btns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            btns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const cards = document.querySelectorAll('.message-card');
            let visible = 0;
            
            cards.forEach(card => {
                const isUnread = card.classList.contains('unread');
                const isUnreplied = card.classList.contains('unreplied');
                
                let show = false;
                if (filter === 'all') show = true;
                else if (filter === 'unread') show = isUnread;
                else if (filter === 'unreplied') show = isUnreplied;
                else if (filter === 'replied') show = !isUnreplied;
                
                card.style.display = show ? 'block' : 'none';
                if (show) visible++;
            });
            
            
            let emptyMsg = document.querySelector('.filter-empty');
            if (visible === 0 && !emptyMsg) {
                const div = document.createElement('div');
                div.className = 'empty-state filter-empty';
                div.innerHTML = '<div class="empty-icon">🔍</div><p>Aucun message</p>';
                document.querySelector('.messages-list').appendChild(div);
            } else if (visible > 0 && emptyMsg) {
                emptyMsg.remove();
            }
        });
    });
}


document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeViewModal();
        closeReplyModal();
    }
});


document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', e => {
        if (e.target === modal) {
            closeViewModal();
            closeReplyModal();
        }
    });
});