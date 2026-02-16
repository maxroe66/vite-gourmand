/**
 * Skeleton Screens — Placeholders de chargement pulsants.
 * API similaire au pattern toast.js : fonctions globales utilitaires.
 *
 * Usage :
 *   // Injecter des skeleton cards dans un conteneur
 *   Skeleton.renderCards(container, 3);
 *
 *   // Injecter un skeleton de page détail
 *   Skeleton.renderDetail(container);
 *
 *   // Injecter un skeleton de formulaire
 *   Skeleton.renderForm(container, 3);
 *
 *   // Injecter un skeleton modal (lignes de texte)
 *   Skeleton.renderModalContent(container);
 *
 *   // Nettoyer tous les skeletons d'un conteneur
 *   Skeleton.clear(container);
 */

const Skeleton = {

    /**
     * Crée un élément skeleton de base.
     * @param {string} modifier - Classe BEM modifier (ex: '--text', '--title')
     * @param {string} [extraClass] - Classe CSS supplémentaire optionnelle
     * @returns {HTMLElement}
     */
    _createBlock(modifier, extraClass) {
        const el = document.createElement('div');
        el.classList.add('skeleton', `skeleton${modifier}`);
        if (extraClass) el.classList.add(extraClass);
        return el;
    },

    /**
     * Marque un élément comme skeleton (pour le nettoyage).
     * @param {HTMLElement} el
     * @returns {HTMLElement}
     */
    _markSkeleton(el) {
        el.setAttribute('data-skeleton', '');
        return el;
    },

    /**
     * Injecte N skeleton cards (type commandes / liste).
     * @param {HTMLElement} container - Conteneur cible
     * @param {number} [count=3] - Nombre de cartes
     */
    renderCards(container, count = 3) {
        for (let i = 0; i < count; i++) {
            const card = document.createElement('div');
            card.classList.add('skeleton-card');
            this._markSkeleton(card);

            const body = document.createElement('div');
            body.classList.add('skeleton-card__body');
            body.appendChild(this._createBlock('--title'));
            body.appendChild(this._createBlock('--text'));
            body.appendChild(this._createBlock('--text-short'));

            const actions = document.createElement('div');
            actions.classList.add('skeleton-card__actions');
            actions.appendChild(this._createBlock('--button'));

            card.appendChild(body);
            card.appendChild(actions);
            container.appendChild(card);
        }
    },

    /**
     * Injecte un skeleton de page détail (image + texte).
     * @param {HTMLElement} container - Conteneur cible
     */
    renderDetail(container) {
        const wrapper = document.createElement('div');
        wrapper.classList.add('skeleton-detail');
        this._markSkeleton(wrapper);

        // Colonne galerie
        const gallery = document.createElement('div');
        gallery.classList.add('skeleton-detail__gallery');
        gallery.appendChild(this._createBlock('--image'));

        // Colonne contenu
        const content = document.createElement('div');
        content.classList.add('skeleton-detail__content');
        content.appendChild(this._createBlock('--title'));
        content.appendChild(this._createBlock('--text'));
        content.appendChild(this._createBlock('--text'));
        content.appendChild(this._createBlock('--text-short'));
        content.appendChild(this._createBlock('--title'));
        content.appendChild(this._createBlock('--text'));
        content.appendChild(this._createBlock('--text-short'));
        content.appendChild(this._createBlock('--button'));

        wrapper.appendChild(gallery);
        wrapper.appendChild(content);
        container.appendChild(wrapper);
    },

    /**
     * Injecte un skeleton de formulaire (label + input × N).
     * @param {HTMLElement} container - Conteneur cible
     * @param {number} [fields=3] - Nombre de champs
     */
    renderForm(container, fields = 3) {
        const wrapper = document.createElement('div');
        wrapper.classList.add('skeleton-form');
        this._markSkeleton(wrapper);

        wrapper.appendChild(this._createBlock('--title'));

        for (let i = 0; i < fields; i++) {
            const field = document.createElement('div');
            field.classList.add('skeleton-form__field');

            const label = document.createElement('div');
            label.classList.add('skeleton', 'skeleton-form__label');

            const input = document.createElement('div');
            input.classList.add('skeleton', 'skeleton-form__input');

            field.appendChild(label);
            field.appendChild(input);
            wrapper.appendChild(field);
        }

        wrapper.appendChild(this._createBlock('--button'));
        container.appendChild(wrapper);
    },

    /**
     * Injecte un skeleton pour le corps d'une modale (lignes de texte).
     * @param {HTMLElement} container - Conteneur cible
     */
    renderModalContent(container) {
        const wrapper = document.createElement('div');
        this._markSkeleton(wrapper);

        wrapper.appendChild(this._createBlock('--title'));
        wrapper.appendChild(this._createBlock('--text'));
        wrapper.appendChild(this._createBlock('--text'));
        wrapper.appendChild(this._createBlock('--text-short'));

        container.appendChild(wrapper);
    },

    /**
     * Supprime tous les skeletons d'un conteneur.
     * @param {HTMLElement} container - Conteneur à nettoyer
     */
    clear(container) {
        const skeletons = container.querySelectorAll('[data-skeleton]');
        skeletons.forEach(s => s.remove());
    },

    /**
     * Applique un effet d'entrée échelonné (stagger) sur les enfants d'un conteneur.
     * Utilise les classes CSS .anim-fade-in-up et .anim-delay-*.
     * @param {HTMLElement} container - Parent contenant les éléments à animer
     * @param {string} selector - Sélecteur des enfants cibles
     * @param {number} [maxDelay=5] - Nombre maximum de paliers (1 à 5)
     */
    staggerChildren(container, selector, maxDelay = 5) {
        const children = container.querySelectorAll(selector);
        children.forEach((child, i) => {
            child.classList.add('anim-fade-in-up');
            const delay = Math.min(i + 1, maxDelay);
            child.classList.add(`anim-delay-${delay}`);
        });
    }
};
