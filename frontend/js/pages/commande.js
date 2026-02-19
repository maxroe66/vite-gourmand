document.addEventListener('DOMContentLoaded', async () => {
    // DOM Elements
    const loader = document.getElementById('loader');
    const content = document.getElementById('commande-content');
    const form = document.getElementById('order-form');
    const elMenuTitle = document.getElementById('menu-title-summary');
    const minGuestsMsg = document.getElementById('min-guests-msg');
    const btnSubmit = form.querySelector('button[type="submit"]');
    const errorMsg = document.getElementById('error-message');

    // Afficher un skeleton pendant le chargement
    loader.textContent = '';
    Skeleton.renderForm(loader, 3);

    // Summary Elements
    const elUnitPrice = document.getElementById('unit-price');
    const elNbPersons = document.getElementById('nb-persons');
    const elSubTotal = document.getElementById('sub-total');
    const elReductionRow = document.getElementById('reduction-row');
    const elReductionAmount = document.getElementById('reduction-amount');
    const elDeliveryFee = document.getElementById('delivery-fee');
    const elTotalPrice = document.getElementById('total-price');

    // State
    const urlParams = new URLSearchParams(window.location.search);
    const menuId = urlParams.get('menuId');
    let currentMenu = null;
    let currentUser = null;
    let calculationTimer = null;

    if (!menuId) {
        showToast('Menu non spécifié.', 'error');
        window.location.href = '/';
        return;
    }

    // 1. Check Auth & Get Profile
    try {
        currentUser = await AuthService.getUser();
        if (!currentUser) {
            // Redirection Login
            const returnUrl = encodeURIComponent(window.location.pathname + window.location.search);
            window.location.href = `/frontend/pages/connexion.html?redirect=${returnUrl}`;
            return;
        }

        // Prefill Form with User Data
        if (currentUser.nom) document.getElementById('client-lastname').value = currentUser.nom;
        if (currentUser.prenom) document.getElementById('client-firstname').value = currentUser.prenom;
        if (currentUser.email) document.getElementById('client-email').value = currentUser.email;
        if (currentUser.adresse_postale) document.getElementById('address').value = currentUser.adresse_postale;
        if (currentUser.ville) document.getElementById('city').value = currentUser.ville;
        if (currentUser.code_postal) document.getElementById('zipcode').value = currentUser.code_postal;
        if (currentUser.gsm) document.getElementById('gsm').value = currentUser.gsm;

    } catch (e) {
        Logger.error("Auth Error", e);
    }

    // 2. Load Menu Details
    try {
        currentMenu = await MenuService.getMenuDetails(menuId);
        
        // Init UI
        elMenuTitle.textContent = `Menu : ${currentMenu.titre}`;
        elUnitPrice.textContent = formatPrice(currentMenu.prix);
        
        const minGuests = currentMenu.nombre_personne_min;
        const inputGuests = document.getElementById('guests');
        inputGuests.min = minGuests;
        inputGuests.value = minGuests; // Default to min
        minGuestsMsg.textContent = `Minimum ${minGuests} personnes.`;

        // Hide loader, show content with animation
        loader.classList.add('u-hidden');
        Skeleton.clear(loader);
        content.classList.add('is-visible');
        content.classList.add('anim-fade-in-up');

        // Initial Calculation
        calculate();

    } catch (e) {
        loader.textContent = "Erreur de chargement du menu.";
        Logger.error(e);
    }

    // 3. Listeners for Calculation
    const calcInputs = ['address', 'zipcode', 'city', 'guests'];
    calcInputs.forEach(id => {
        document.getElementById(id).addEventListener('input', () => {
            clearTimeout(calculationTimer);
            calculationTimer = setTimeout(() => calculate(), 500); // Debounce 500ms
        });
    });

    // 4. Handle Submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        btnSubmit.disabled = true;
        btnSubmit.textContent = "Traitement en cours...";
        errorMsg.textContent = "";

        const formData = {
            menuId: parseInt(menuId),
            adresseLivraison: document.getElementById('address').value,
            ville: document.getElementById('city').value,
            codePostal: document.getElementById('zipcode').value,
            gsm: document.getElementById('gsm').value,
            datePrestation: document.getElementById('date').value,
            heureLivraison: document.getElementById('time').value + ":00", // Ensure HH:MM:SS format if needed or HH:MM
            nombrePersonnes: parseInt(document.getElementById('guests').value)
        };

        try {
            const result = await CommandeService.createOrder(formData);
            if (result.success) {
                // Success -> Redirect to My Orders or Confirmation
                showToast('Commande validée avec succès !', 'success');
                setTimeout(() => { window.location.href = '/frontend/pages/profil.html'; }, 1500); // Or separate confirmation page
            }
        } catch (err) {
            Logger.error(err);
            errorMsg.textContent = err.message || "Une erreur est survenue.";
            btnSubmit.disabled = false;
            btnSubmit.textContent = "Valider la commande";
        }
    });

    // Functions
    async function calculate() {
        const address = document.getElementById('address').value;
        const city = document.getElementById('city').value;
        const nb = parseInt(document.getElementById('guests').value);

        if (!address || !city || !nb || nb < currentMenu.nombre_personne_min) {
            // Not enough info to calculate delivery or invalid qty
            updateSummaryDisplay(null);
            return;
        }

        const fullAddress = `${address}, ${document.getElementById('zipcode').value} ${city}`;
        
        try {
            const pricing = await CommandeService.calculatePrice({
                menuId: parseInt(menuId),
                nombrePersonnes: nb,
                adresseLivraison: fullAddress
            });
            
            updateSummaryDisplay(pricing);

        } catch (e) {
            Logger.warn("Calculation error", e);
            // Maybe show error in summary?
        }
    }

    function updateSummaryDisplay(pricing) {
        if (!pricing) {
            // Reset to basic calculation without API or just wait
            const nb = parseInt(document.getElementById('guests').value) || 0;
            const sub = nb * (currentMenu ? currentMenu.prix : 0);
            elNbPersons.textContent = nb;
            elSubTotal.textContent = formatPrice(sub);
            elDeliveryFee.textContent = "...";
            elTotalPrice.textContent = "...";
            elReductionRow.classList.remove('is-visible');
            return;
        }

        elNbPersons.textContent = document.getElementById('guests').value;
        elSubTotal.textContent = formatPrice(pricing.prixMenuTotal);
        
        if (pricing.reductionAppliquee) {
            elReductionRow.classList.add('is-visible');
            elReductionAmount.textContent = `- ${formatPrice(pricing.montantReduction)}`;
        } else {
            elReductionRow.classList.remove('is-visible');
        }

        const fraisText = pricing.horsBordeaux 
            ? `${formatPrice(pricing.fraisLivraison)} (${pricing.distanceKm} km)` 
            : `${formatPrice(pricing.fraisLivraison)} (Zone Bordeaux)`;
        
        elDeliveryFee.textContent = fraisText;
        elTotalPrice.textContent = formatPrice(pricing.prixTotal);
    }

});
