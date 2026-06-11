document.addEventListener("DOMContentLoaded", function() {

    console.log("JS chargé ✔");

    
    const cards = document.querySelectorAll(".service-card");

    cards.forEach(card => {
        card.addEventListener("mouseenter", () => {
            card.style.transform = "scale(1.05)";
            card.style.transition = "0.3s";
        });

        card.addEventListener("mouseleave", () => {
            card.style.transform = "scale(1)";
        });
    });

    
    const btnHero = document.querySelector(".btn-hero");

    if (btnHero) {
        btnHero.addEventListener("click", () => {
            console.log("Utilisateur clique sur réserver");
        });
    }

    
    console.log("Bienvenue sur Sisi's Beauty ✨");

});