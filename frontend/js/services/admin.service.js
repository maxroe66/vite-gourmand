/**
 * Service pour les fonctionnalités Administrateur
 */
const AdminService = {

    /**
     * Récupère la liste des employés
     * @returns {Promise<Array>}
     */
    async getEmployees() {
        // GET /api/admin/employees
        const response = await fetch('/api/admin/employees', AuthService.getFetchOptions());
        if (!response.ok) throw new Error('Erreur lors de la récupération des employés');
        return await response.json();
    },

    /**
     * Crée un nouvel employé
     * @param {Object} employeeData {email, password, firstName, ...}
     */
    async createEmployee(employeeData) {
        // POST /api/admin/employees
        const response = await fetch('/api/admin/employees', AuthService.getFetchOptions({
            method: 'POST',
            body: JSON.stringify(employeeData)
        }));
        
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Erreur lors de la création de l\'employé');
        }
        return data;
    },

    /**
     * Désactive un compte utilisateur (admin ou employé)
     * @param {number} userId
     */
    async disableUser(userId) {
        // PATCH /api/admin/users/{id}/disable
        const response = await fetch(`/api/admin/users/${userId}/disable`, AuthService.getFetchOptions({
            method: 'PATCH'
        }));
        
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Erreur lors de la désactivation');
        }
        return data;
    },

    /**
     * Récupère les statistiques
     * @param {Object} filters {startDate, endDate, menuId}
     */
    async getStats(filters = {}) {
        const params = new URLSearchParams();
        if (filters.startDate) params.append('startDate', filters.startDate);
        if (filters.endDate) params.append('endDate', filters.endDate);
        if (filters.menuId) params.append('menuId', filters.menuId);

        const response = await fetch(`/api/menues-commandes-stats?${params.toString()}`, AuthService.getFetchOptions());
        
        if (!response.ok) throw new Error('Erreur récupération statistiques');
        return await response.json();
    }
};
