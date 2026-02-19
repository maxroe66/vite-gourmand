/**
 * Footer Horaires Loader
 * Charge dynamiquement les horaires depuis l'API et les affiche dans le footer.
 * Écoute l'événement `componentsLoaded` (émis par components.js après injection du footer).
 */

(function () {
    'use strict';

    /** Abréviations des jours pour l'affichage compact */
    const JOURS_ABBR = {
        'LUNDI': 'Lun',
        'MARDI': 'Mar',
        'MERCREDI': 'Mer',
        'JEUDI': 'Jeu',
        'VENDREDI': 'Ven',
        'SAMEDI': 'Sam',
        'DIMANCHE': 'Dim'
    };

    /**
     * Formate une heure "HH:MM:SS" ou "HH:MM" en affichage compact (ex: "9h", "9h30").
     * @param {string} time - Heure au format HH:MM(:SS)
     * @returns {string}
     */
    function formatTime(time) {
        if (!time) return '';
        const parts = time.split(':');
        const hours = parseInt(parts[0], 10);
        const minutes = parseInt(parts[1], 10);
        return minutes > 0 ? `${hours}h${String(minutes).padStart(2, '0')}` : `${hours}h`;
    }

    /**
     * Génère une clé de comparaison pour regrouper les jours consécutifs identiques.
     * @param {Object} h - Objet horaire
     * @returns {string}
     */
    function horaireKey(h) {
        if (h.ferme) return 'FERME';
        return `${h.heureOuverture || ''}-${h.heureFermeture || ''}`;
    }

    /**
     * Regroupe les jours consécutifs ayant les mêmes horaires.
     * @param {Array} horaires - Tableau d'horaires triés (Lun→Dim)
     * @returns {Array<{jours: string[], horaire: Object}>}
     */
    function groupConsecutiveDays(horaires) {
        const groups = [];
        let current = null;

        for (const h of horaires) {
            const key = horaireKey(h);
            if (current && current.key === key) {
                current.jours.push(h.jour);
            } else {
                current = { key, jours: [h.jour], horaire: h };
                groups.push(current);
            }
        }

        return groups;
    }

    /**
     * Formate un groupe de jours en texte compact.
     * Ex: ["LUNDI","MARDI","MERCREDI","JEUDI","VENDREDI"] → "Lun-Ven 9h-18h"
     * Ex: ["SAMEDI"] → "Sam 10h-16h"
     * Ex: ["DIMANCHE"] → "Dim fermé"
     * @param {Object} group - { jours: string[], horaire: Object }
     * @returns {string}
     */
    function formatGroup(group) {
        const { jours, horaire } = group;
        const first = JOURS_ABBR[jours[0]] || jours[0];
        const last = JOURS_ABBR[jours[jours.length - 1]] || jours[jours.length - 1];

        const dayLabel = jours.length > 1 ? `${first}-${last}` : first;

        if (horaire.ferme) {
            return `${dayLabel} fermé`;
        }

        const open = formatTime(horaire.heureOuverture);
        const close = formatTime(horaire.heureFermeture);
        return `${dayLabel} ${open}-${close}`;
    }

    /**
     * Charge les horaires depuis l'API et met à jour le footer.
     */
    async function loadFooterHoraires() {
        const el = document.getElementById('footer-horaires');
        if (!el) return;

        try {
            const response = await fetch('/api/horaires', { credentials: 'include' });
            if (!response.ok) throw new Error(`API error: ${response.status}`);

            const result = await response.json();
            const horaires = result.data || [];

            if (horaires.length === 0) {
                el.textContent = 'Horaires : non disponibles';
                return;
            }

            const groups = groupConsecutiveDays(horaires);
            const formatted = groups.map(formatGroup).join(' · ');
            el.textContent = `Horaires : ${formatted}`;

        } catch (error) {
            if (typeof Logger !== 'undefined') {
                Logger.warn('Footer horaires: impossible de charger les horaires', error);
            }
            el.textContent = 'Horaires : non disponibles';
        }
    }

    // Attendre que le footer soit injecté par components.js
    document.addEventListener('componentsLoaded', loadFooterHoraires);
})();
