// Carousel "Nos Menus" (Desktop & Mobile)
// Swipe natif via overflow-x, flèches pour faire défiler d'une carte.

document.addEventListener('DOMContentLoaded', () => {
  // On ne limite plus à une taille d'écran spécifique (Carousel partout)
  
  let initialized = false;

  function init() {
    if (initialized) return;

    const section = document.querySelector('.menus');
    if (!section) return;

    const list = section.querySelector('.menus__list');
    const prev = section.querySelector('.menus__arrow--prev');
    const next = section.querySelector('.menus__arrow--next');

    // Sécurité: si les éléments n'existent pas (ex: page sans menus), on arrête
    if (!list || !prev || !next) return;

    const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches;

    // Calcul dynamique de l'espace (gap) entre les cartes
    function getGapPx() {
      const styles = window.getComputedStyle(list);
      const gap = styles.gap || styles.columnGap || '0px';
      const parsed = Number.parseFloat(gap);
      return Number.isFinite(parsed) ? parsed : 0;
    }

    // Calcul de la taille d'un pas (largeur carte + gap)
    function getStep() {
      const card = list.querySelector('.menu-card');
      if (!card) return 0; // Si pas de carte (liste vide), pas de scroll
      return card.getBoundingClientRect().width + getGapPx();
    }

    // Mise à jour de l'état des boutons (désactivés aux extrémités)
    function updateDisabled() {
      // Tolérance d'1px pour les arrondis
      const maxScrollLeft = list.scrollWidth - list.clientWidth;
      const atStart = list.scrollLeft <= 1;
      const atEnd = list.scrollLeft >= maxScrollLeft - 1;
      
      prev.disabled = atStart;
      next.disabled = atEnd;
    }

    // Action de scroll
    function scrollByCard(direction) {
      const step = getStep();
      if (!step) return;
      
      list.scrollBy({
        left: direction * step,
        behavior: prefersReducedMotion ? 'auto' : 'smooth',
      });
    }

    prev.addEventListener('click', () => scrollByCard(-1));
    next.addEventListener('click', () => scrollByCard(1));
    
    // Écouteur de scroll pour mettre à jour les boutons en temps réel
    list.addEventListener('scroll', () => {
        // Debounce léger si nécessaire, mais updateDisabled est léger
        window.requestAnimationFrame(updateDisabled);
    }, { passive: true });
    
    window.addEventListener('resize', updateDisabled);

    // Initialisation
    updateDisabled();
    initialized = true;
    
    // On expose la fonction updateDisabled globalement pour pouvoir l'appeler 
    // quand on ajoute des menus dynamiquement (AJAX)
    window.refreshMenuCarousel = updateDisabled;
  }

  // Lancement
  init();
});
