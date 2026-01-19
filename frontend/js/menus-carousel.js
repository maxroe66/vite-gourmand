// Carousel "Nos Menus" (mobile only)
// Swipe natif via overflow-x, flèches pour faire défiler d'une carte.

document.addEventListener('DOMContentLoaded', () => {
  const mq = window.matchMedia ? window.matchMedia('(max-width: 1024px)') : null;
  if (!mq) return;

  let initialized = false;

  function initIfNeeded() {
    if (!mq.matches) return;
    if (initialized) return;

    const section = document.querySelector('.menus');
    if (!section) return;

    const list = section.querySelector('.menus__list');
    const prev = section.querySelector('.menus__arrow--prev');
    const next = section.querySelector('.menus__arrow--next');

    if (!list || !prev || !next) return;

    const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches;

    function getGapPx() {
      const styles = window.getComputedStyle(list);
      const gap = styles.gap || styles.columnGap || '0px';
      const parsed = Number.parseFloat(gap);
      return Number.isFinite(parsed) ? parsed : 0;
    }

    function getStep() {
      const card = list.querySelector('.menu-card');
      if (!card) return 0;
      return card.getBoundingClientRect().width + getGapPx();
    }

    function updateDisabled() {
      const maxScrollLeft = list.scrollWidth - list.clientWidth;
      const atStart = list.scrollLeft <= 1;
      const atEnd = list.scrollLeft >= maxScrollLeft - 1;
      prev.disabled = atStart;
      next.disabled = atEnd;
    }

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
    list.addEventListener('scroll', updateDisabled, { passive: true });
    window.addEventListener('resize', updateDisabled);

    updateDisabled();
    initialized = true;
  }

  // Init immédiat si déjà en mobile
  initIfNeeded();

  // Init si on passe en mobile après coup (devtools, rotation, resize)
  mq.addEventListener?.('change', initIfNeeded);
});
