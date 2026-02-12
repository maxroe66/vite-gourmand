document.addEventListener('DOMContentLoaded', async () => {
    const track = document.querySelector('.avis-clients__track');
    if (!track) return;

    // Charger les avis
    let reviews = [];
    try {
        const response = await fetch('/api/avis/public');
        if (response.ok) {
            const data = await response.json();
            reviews = data.data || [];
        }
    } catch (e) {
        console.error("Erreur chargement avis public", e);
    }

    if (reviews.length === 0) {
        track.innerHTML = '<div class="avis-clients__empty">Aucun avis pour le moment.</div>';
        return;
    }

    // Render avis - en respectant le style original (avis-clients__item)
    track.innerHTML = reviews.map(review => {
        const stars = renderStars(review.note); // ★★★★★
        return `
            <div class="avis-clients__item">
                <p>${escapeHtml(review.commentaire)}</p>
                <span>- Client vérifié <span class="avis-clients__stars">${stars}</span></span>
            </div>
        `;
    }).join('');

    initCarousel();
});

function renderStars(note) {
    let stars = '';
    for (let i = 0; i < 5; i++) {
        // En texte comme dans l'exemple, ou FontAwesome si on veut ? 
        // L'exemple utilisait ★ directement dans le HTML.
        if (i < note) stars += '★';
        else stars += '☆'; // Etoile vide optionnelle, ou rien
    }
    return stars;
}

function formatDate(isoStr) {
    if (!isoStr) return '';
    // MongoDB date usually returns a string with new DateTime in standard JSON
    // But if coming from Mongo Driver directly it might be object
    let date = new Date(isoStr);
    if(isoStr && typeof isoStr === 'object' && isoStr.date) {
         // PHP DateTime serialize
         date = new Date(isoStr.date);
    }
    return date.toLocaleDateString('fr-FR');
}


function initCarousel() {
    // Basic Carousel Logic
    const list = document.querySelector('.avis-clients__list'); // Wrapper avec overflow
    const track = document.querySelector('.avis-clients__track'); // Contenu
    const items = document.querySelectorAll('.avis-clients__item');
    const prevBtn = document.querySelector('.avis-clients__arrow--prev');
    const nextBtn = document.querySelector('.avis-clients__arrow--next');
    
    if(!list || items.length === 0 || !prevBtn || !nextBtn) return;

    let currentIndex = 0;
    
    // Adapt scroll amount based on item width
    function getItemWidth() {
         // items display inline-block or flex? 
         // Le JS ne connait pas le style original s'il n'est pas chargé mais on suppose
         // qu'on doit scroller d'un item à la fois.
         const style = window.getComputedStyle(items[0]);
         return items[0].offsetWidth + parseFloat(style.marginRight) + parseFloat(style.marginLeft);
    }

    nextBtn.addEventListener('click', () => {
        // Simple scroll logic
        list.scrollBy({ left: 300, behavior: 'smooth' }); // Valeur arbitraire si width inconnu, ou calculé
    });

    prevBtn.addEventListener('click', () => {
        list.scrollBy({ left: -300, behavior: 'smooth' });
    });
}
