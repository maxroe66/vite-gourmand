/**
 * Scroll Snap Controller — Premium wheel-hijack scroll (homepage only).
 *
 * Intercepte les événements wheel/touch/clavier pour animer la transition
 * entre sections plein écran avec un easing cubic-bezier premium (~700ms).
 * Intègre les scroll-dots (indicateur de navigation latéral).
 *
 * Sections : hero → présentation → menus → footer (snap-end).
 * Actif uniquement au-dessus de 1024px. Respecte prefers-reduced-motion.
 */
document.addEventListener('DOMContentLoaded', () => {

    // ─── Configuration ──────────────────────────────────────────
    const ANIMATION_DURATION = 700;         // ms — durée de la transition
    const ANIMATION_DURATION_REDUCED = 80;  // ms — si prefers-reduced-motion
    const WHEEL_THRESHOLD = 50;             // deltaY cumulé avant déclenchement
    const WHEEL_LOCKOUT = 800;              // ms — délai après navigation avant d'accepter un nouveau scroll
    const TOUCH_THRESHOLD = 50;             // px — swipe minimum pour déclencher
    const MOBILE_BREAKPOINT = 1024;         // px — en dessous : scroll natif

    // ─── Détection du contexte ──────────────────────────────────
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const duration = prefersReduced ? ANIMATION_DURATION_REDUCED : ANIMATION_DURATION;

    // ─── Sections cibles ────────────────────────────────────────
    const sectionDefs = [
        { selector: '.hero',           label: 'Accueil' },
        { selector: '.presentation',   label: 'Présentation' },
        { selector: '.menus',          label: 'Menus' },
        { selector: '.footer-section', label: 'Contact' }
    ];

    const mainEl = document.querySelector('main');
    if (!mainEl) return;

    // Résoudre les éléments DOM
    const sections = sectionDefs
        .map(s => ({ ...s, el: document.querySelector(s.selector) }))
        .filter(s => s.el);

    if (sections.length < 2) return;

    // ─── État ───────────────────────────────────────────────────
    let currentIndex = 0;
    let isAnimating = false;
    let isLocked = false;               // Lockout après navigation pour éviter double-scroll
    let isActive = window.innerWidth > MOBILE_BREAKPOINT;
    let wheelAccumulator = 0;
    let touchStartY = 0;
    let touchStartTime = 0;

    // Cache le padding-top de main (compense la navbar fixe)
    const mainPaddingTop = parseInt(getComputedStyle(mainEl).paddingTop, 10) || 0;

    // ─── Easing : cubic-bezier premium (style Apple) ────────────
    /**
     * easeInOutCubic — accélération douce puis décélération douce.
     * @param {number} t - Progression normalisée [0, 1]
     * @returns {number} Valeur easée [0, 1]
     */
    function easeInOutCubic(t) {
        return t < 0.5
            ? 4 * t * t * t
            : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }

    // ─── Calcul du scrollTop cible ──────────────────────────────
    /**
     * Pour les sections 0-2 : snap-start (haut de la section aligné sous la navbar).
     * Pour le footer (dernière section) : snap-end (bas du footer aligné au bas du viewport).
     *
     * Le padding-top de main décale les sections dans le contenu scrollable.
     * On le soustrait pour que chaque section s'aligne visuellement sous la navbar.
     *
     * @param {number} index - Index de la section cible
     * @returns {number} scrollTop cible
     */
    function getTargetScrollTop(index) {
        const section = sections[index];
        if (!section) return 0;

        // Position absolue dans le contenu scrollable
        // (getBoundingClientRect + scrollTop = position dans le scroll content)
        const sectionTop = section.el.getBoundingClientRect().top
                         - mainEl.getBoundingClientRect().top
                         + mainEl.scrollTop;

        // Dernière section (footer) : snap-end
        // Aligne le BAS du footer avec le BAS du viewport
        if (index === sections.length - 1) {
            const sectionBottom = sectionTop + section.el.offsetHeight;
            return Math.max(0, sectionBottom - mainEl.clientHeight);
        }

        // Sections normales : snap-start (sous la navbar)
        // On soustrait le padding-top car il crée un espace en haut du scroll
        // qui est visuellement couvert par la navbar fixée
        return Math.max(0, sectionTop - mainPaddingTop);
    }

    // ─── Animation RAF ──────────────────────────────────────────
    /**
     * Anime le scrollTop de mainEl vers la valeur cible.
     * @param {number} targetIndex - Index de la section cible
     * @param {function} [callback] - Callback après animation
     */
    function animateToSection(targetIndex, callback) {
        if (targetIndex < 0 || targetIndex >= sections.length) return;
        if (targetIndex === currentIndex && !callback) return;

        isAnimating = true;
        const startScrollTop = mainEl.scrollTop;
        const targetScrollTop = getTargetScrollTop(targetIndex);
        const distance = targetScrollTop - startScrollTop;

        // Pas besoin d'animer si déjà en place
        if (Math.abs(distance) < 1) {
            currentIndex = targetIndex;
            updateDots();
            isAnimating = false;
            if (callback) callback();
            return;
        }

        const startTime = performance.now();

        function step(now) {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const easedProgress = easeInOutCubic(progress);

            mainEl.scrollTop = startScrollTop + distance * easedProgress;

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                // Fin de l'animation — garantir la position exacte
                mainEl.scrollTop = targetScrollTop;
                currentIndex = targetIndex;
                updateDots();
                isAnimating = false;
                if (callback) callback();
            }
        }

        requestAnimationFrame(step);
    }

    // ─── Navigation ─────────────────────────────────────────────
    /**
     * Navigue vers la section suivante ou précédente.
     * @param {number} direction - 1 (suivant) ou -1 (précédent)
     */
    function navigate(direction) {
        if (isAnimating) return;

        const nextIndex = currentIndex + direction;
        if (nextIndex < 0 || nextIndex >= sections.length) return;

        animateToSection(nextIndex);
    }

    /**
     * Navigue directement vers une section par index.
     * @param {number} index - Index cible
     */
    function goToSection(index) {
        if (isAnimating) return;
        if (index < 0 || index >= sections.length) return;
        animateToSection(index);
    }

    // ─── Wheel handler ──────────────────────────────────────────
    function onWheel(e) {
        if (!isActive) return;

        e.preventDefault();

        // Pendant l'animation ou le lockout, ignorer complètement
        if (isAnimating || isLocked) {
            wheelAccumulator = 0;
            return;
        }

        // Accumuler le delta
        wheelAccumulator += e.deltaY;

        // Seuil atteint → naviguer et verrouiller
        if (Math.abs(wheelAccumulator) >= WHEEL_THRESHOLD) {
            const direction = wheelAccumulator > 0 ? 1 : -1;
            wheelAccumulator = 0;
            isLocked = true;

            navigate(direction);

            // Déverrouiller après le lockout
            setTimeout(() => {
                isLocked = false;
                wheelAccumulator = 0;
            }, WHEEL_LOCKOUT);
        }
    }

    // ─── Touch handlers ─────────────────────────────────────────
    function onTouchStart(e) {
        if (!isActive) return;
        touchStartY = e.touches[0].clientY;
        touchStartTime = Date.now();
    }

    function onTouchMove(e) {
        if (!isActive) return;
        // Empêcher le scroll natif pendant le swipe
        if (isAnimating) {
            e.preventDefault();
        }
    }

    function onTouchEnd(e) {
        if (!isActive || isAnimating || isLocked) return;

        const touchEndY = e.changedTouches[0].clientY;
        const deltaY = touchStartY - touchEndY;
        const elapsed = Date.now() - touchStartTime;

        // Swipe suffisant (distance OU vélocité)
        const isSwipe = Math.abs(deltaY) > TOUCH_THRESHOLD ||
                         (Math.abs(deltaY) > 20 && elapsed < 300);

        if (isSwipe) {
            const direction = deltaY > 0 ? 1 : -1;
            isLocked = true;
            navigate(direction);
            setTimeout(() => {
                isLocked = false;
            }, WHEEL_LOCKOUT);
        }
    }

    // ─── Keyboard handler ───────────────────────────────────────
    function onKeyDown(e) {
        if (!isActive || isAnimating || isLocked) return;

        // Ne pas intercepter si un input/textarea est focus
        const tag = document.activeElement?.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;

        let navigated = false;

        switch (e.key) {
            case 'ArrowDown':
            case 'PageDown':
                e.preventDefault();
                navigate(1);
                navigated = true;
                break;
            case ' ': // Space
                // Seulement si pas de focus sur un bouton/lien interactif
                if (tag !== 'BUTTON' && tag !== 'A') {
                    e.preventDefault();
                    navigate(e.shiftKey ? -1 : 1);
                    navigated = true;
                }
                break;
            case 'ArrowUp':
            case 'PageUp':
                e.preventDefault();
                navigate(-1);
                navigated = true;
                break;
            case 'Home':
                e.preventDefault();
                goToSection(0);
                navigated = true;
                break;
            case 'End':
                e.preventDefault();
                goToSection(sections.length - 1);
                navigated = true;
                break;
        }

        if (navigated) {
            isLocked = true;
            setTimeout(() => { isLocked = false; }, WHEEL_LOCKOUT);
        }
    }

    // ─── Hash / anchor navigation ───────────────────────────────
    /**
     * Gère les clics sur les liens internes (ex: #menus, #presentation).
     * Intercepte le comportement natif pour animer vers la section.
     */
    function setupAnchorLinks() {
        document.addEventListener('click', (e) => {
            if (!isActive) return;

            const link = e.target.closest('a[href*="#"]');
            if (!link) return;

            const hash = link.hash || new URL(link.href, location.origin).hash;
            if (!hash) return;

            // Trouver la section correspondant au hash
            const targetId = hash.substring(1);
            const targetIndex = sections.findIndex(s =>
                s.el.id === targetId || s.el.classList.contains(targetId)
            );

            if (targetIndex !== -1) {
                e.preventDefault();
                goToSection(targetIndex);
            }
        });

        // Gérer le hash initial au chargement
        if (location.hash) {
            const targetId = location.hash.substring(1);
            const targetIndex = sections.findIndex(s =>
                s.el.id === targetId || s.el.classList.contains(targetId)
            );
            if (targetIndex !== -1) {
                // Petit délai pour laisser le layout se stabiliser
                requestAnimationFrame(() => {
                    animateToSection(targetIndex);
                });
            }
        }
    }

    // ─── Scroll Dots (indicateur de navigation) ─────────────────
    let dotsNav = null;

    function createDots() {
        dotsNav = document.createElement('nav');
        dotsNav.className = 'scroll-dots';
        dotsNav.setAttribute('role', 'navigation');
        dotsNav.setAttribute('aria-label', 'Navigation entre les sections');

        sections.forEach((section, index) => {
            const dot = document.createElement('button');
            dot.className = 'scroll-dots__dot';
            dot.setAttribute('data-label', section.label);
            dot.setAttribute('aria-label', `Aller à ${section.label}`);
            dot.setAttribute('type', 'button');

            dot.addEventListener('click', () => {
                goToSection(index);
            });

            if (index === 0) dot.classList.add('is-active');
            section.dot = dot;
            dotsNav.appendChild(dot);
        });

        document.body.appendChild(dotsNav);
    }

    function updateDots() {
        if (!dotsNav) return;
        sections.forEach((s, i) => {
            if (s.dot) {
                s.dot.classList.toggle('is-active', i === currentIndex);
            }
        });
    }

    function showDots() {
        if (dotsNav) dotsNav.style.display = '';
    }

    function hideDots() {
        if (dotsNav) dotsNav.style.display = 'none';
    }

    // ─── Activation / Désactivation ─────────────────────────────
    function activate() {
        if (isActive) return;
        isActive = true;

        // Trouver la section la plus proche de la position actuelle
        detectCurrentSection();

        // Snapper immédiatement à la section courante
        mainEl.scrollTop = getTargetScrollTop(currentIndex);
        updateDots();
        showDots();
    }

    function deactivate() {
        if (!isActive) return;
        isActive = false;
        hideDots();
    }

    /**
     * Détecte quelle section est actuellement la plus visible.
     * Utilisé lors de l'activation (resize mobile → desktop).
     */
    function detectCurrentSection() {
        const scrollTop = mainEl.scrollTop;
        let minDistance = Infinity;
        let nearestIndex = 0;

        sections.forEach((section, index) => {
            const target = getTargetScrollTop(index);
            const distance = Math.abs(scrollTop - target);
            if (distance < minDistance) {
                minDistance = distance;
                nearestIndex = index;
            }
        });

        currentIndex = nearestIndex;
    }

    // ─── Event binding ──────────────────────────────────────────
    function bindEvents() {
        // Wheel — { passive: false } pour pouvoir preventDefault
        mainEl.addEventListener('wheel', onWheel, { passive: false });

        // Touch
        mainEl.addEventListener('touchstart', onTouchStart, { passive: true });
        mainEl.addEventListener('touchmove', onTouchMove, { passive: false });
        mainEl.addEventListener('touchend', onTouchEnd, { passive: true });

        // Keyboard
        document.addEventListener('keydown', onKeyDown);

        // Anchor links
        setupAnchorLinks();

        // Resize — activer/désactiver selon le breakpoint
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (window.innerWidth > MOBILE_BREAKPOINT) {
                    activate();
                } else {
                    deactivate();
                }
            }, 150);
        });
    }

    // ─── Initialisation ─────────────────────────────────────────
    function init() {
        if (window.innerWidth <= MOBILE_BREAKPOINT) {
            // Créer les dots (masqués) pour qu'ils soient prêts au resize
            createDots();
            hideDots();
            isActive = false;
            // Bind les events quand même (ils vérifieront isActive)
            bindEvents();
            return;
        }

        createDots();
        bindEvents();

        // Position initiale (section 0)
        mainEl.scrollTop = getTargetScrollTop(0);
        updateDots();
    }

    init();
});
