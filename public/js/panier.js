document.addEventListener('DOMContentLoaded', function() {
    
    
    async function updateQuantity(id, quantite) {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('id', id);
        formData.append('quantite', quantite);
        
        try {
            const response = await fetch('panier.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                
                location.reload();
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }
    
    
    function removeItem(id) {
        if (confirm('Supprimer cet article de votre panier ?')) {
            window.location.href = `panier.php?remove=${id}`;
        }
    }
    
    
    function clearCart() {
        if (confirm('Vider complètement votre panier ?')) {
            window.location.href = 'panier.php?clear=1';
        }
    }
    
    
    
    
    document.querySelectorAll('.qte-moins').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const input = document.querySelector(`.qte-input[data-id="${id}"]`);
            if (input && parseInt(input.value) > 1) {
                const newValue = parseInt(input.value) - 1;
                input.value = newValue;
                updateQuantity(id, newValue);
            }
        });
    });
    
    document.querySelectorAll('.qte-plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const input = document.querySelector(`.qte-input[data-id="${id}"]`);
            const max = parseInt(input.max);
            if (input && parseInt(input.value) < max) {
                const newValue = parseInt(input.value) + 1;
                input.value = newValue;
                updateQuantity(id, newValue);
            }
        });
    });
    
    
    document.querySelectorAll('.qte-input').forEach(input => {
        input.addEventListener('change', function() {
            const id = this.dataset.id;
            let value = parseInt(this.value);
            const max = parseInt(this.max);
            const min = parseInt(this.min);
            
            if (isNaN(value)) value = min;
            if (value < min) value = min;
            if (value > max) value = max;
            
            this.value = value;
            updateQuantity(id, value);
        });
    });
    
    
    document.querySelectorAll('.btn-remove').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            removeItem(id);
        });
    });
    
    
    const viderPanierBtn = document.getElementById('viderPanier');
    if (viderPanierBtn) {
        viderPanierBtn.addEventListener('click', clearCart);
    }
});