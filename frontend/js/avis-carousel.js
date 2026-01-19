// Carousel "Avis Clients" (mobile only)
// Swipe natif via overflow-x, flèches pour faire défiler d'une carte.

document.addEventListener('DOMContentLoaded', () => {
  const isMobile = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
  if (!isMobile) return;

  const section = document.querySelector('.avis-clients');
  if (!section) return;

  const list = section.querySelector('.avis-clients__list');
  const prev = section.querySelector('.avis-clients__arrow--prev');
  const next = section.querySelector('.avis-clients__arrow--next');

  if (!list || !prev || !next) return;

  const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches;

  function getGapPx() {
    const styles = window.getComputedStyle(list);
    const gap = styles.gap || styles.columnGap || '0px';
    const parsed = Number.parseFloat(gap);
    return Number.isFinite(parsed) ? parsed : 0;
  }

  function getStep() {
    const card = list.querySelector('.avis-clients__item');
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

  updateDisabled();
  window.addEventListener('resize', updateDisabled);
});
