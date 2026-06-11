document.addEventListener('DOMContentLoaded', function() {
    
    
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const resetBtn = document.getElementById('resetBtn');
    
    
    const successMsg = document.getElementById('successMessage');
    if (successMsg) {
        setTimeout(() => {
            successMsg.style.opacity = '0';
            setTimeout(() => successMsg.remove(), 300);
        }, 3000);
    }
    
    const errorMsg = document.getElementById('errorMessage');
    if (errorMsg) {
        setTimeout(() => {
            errorMsg.style.opacity = '0';
            setTimeout(() => errorMsg.remove(), 3000);
        }, 3000);
    }
    
    
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const value = this.value.trim();
            
            searchTimeout = setTimeout(() => {
                const url = new URL(window.location.href);
                if (value) {
                    url.searchParams.set('recherche', value);
                    url.searchParams.delete('categorie');
                } else {
                    url.searchParams.delete('recherche');
                }
                window.location.href = url.toString();
            }, 500);
        });
        
        
        if (searchBtn) {
            searchBtn.addEventListener('click', function() {
                const value = searchInput.value.trim();
                const url = new URL(window.location.href);
                if (value) {
                    url.searchParams.set('recherche', value);
                    url.searchParams.delete('categorie');
                } else {
                    url.searchParams.delete('recherche');
                }
                window.location.href = url.toString();
            });
        }
        
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const value = this.value.trim();
                const url = new URL(window.location.href);
                if (value) {
                    url.searchParams.set('recherche', value);
                    url.searchParams.delete('categorie');
                } else {
                    url.searchParams.delete('recherche');
                }
                window.location.href = url.toString();
            }
        });
    }
    
    
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 30px rgba(0,0,0,0.05)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
    
    
    const addButtons = document.querySelectorAll('.btn-add-to-cart');
    addButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            
            
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
    
    console.log('Boutique JS chargé');
});