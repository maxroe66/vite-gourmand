class AvisService {
    static API_URL = '/api/avis';

    /**
     * Crée un nouvel avis pour une commande terminée
     * @param {Object} data { commandeId, note, commentaire }
     * @returns {Promise<Object>}
     */
    static async createAvis(data) {
        const response = await fetch(this.API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
            credentials: 'include'
        });

        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || "Impossible d'enregistrer l'avis");
        }
        return await response.json();
    }

    /**
     * Récupère les avis (filtrage possible par statut pour admin)
     * @param {string} status 'EN_ATTENTE' | 'VALIDE' | 'REFUSE'
     */
    static async getAvis(status = null) {
        let url = this.API_URL;
        if (status) {
            url += `?status=${status}`;
        }
        
        const response = await fetch(url, {
            credentials: 'include'
        });

        if (!response.ok) throw new Error("Impossible de récupérer les avis");
        return await response.json();
    }

    /**
     * Valide (ou refuse/supprime) un avis
     * @param {number} id 
     * @param {Object} data { isValidated: true/false } ou status 'VALIDE'
     */
    static async validateAvis(id) {
        const response = await fetch(`${this.API_URL}/${id}/validate`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include'
        });

        if (!response.ok) throw new Error("Erreur validation avis");
        return await response.json();
    }

    /**
     * Refuse (supprime) un avis
     * @param {number} id 
     */
    static async deleteAvis(id) {
        const response = await fetch(`${this.API_URL}/${id}`, {
            method: 'DELETE',
            credentials: 'include'
        });

        if (!response.ok) throw new Error("Erreur suppression avis");
        return await response.json();
    }
}

window.AvisService = AvisService;
