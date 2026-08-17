import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.Chart = Chart;

/* ------------------------------------------------------------------ Palette */
// Charte Benin Petro. Aucun bleu : la gamme reste verte, ambre et rouge.
window.paletteBeninPetro = {
    primaire: '#01582D',
    accent: '#01C96D',
    clair: '#9ADB5A',
    teal: '#0A7D4B',
    ardoise: '#8B9A94',
    ambre: '#D9A404',
    rouge: '#C2412D',
};

Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.color = '#647570';

/* ------------------------------------------------------------ Notifications */
// Toutes les alertes de la plateforme passent par SweetAlert2, aux couleurs
// de la charte. Un seul point de reglage pour toute l'application.
const baseSwal = Swal.mixin({
    confirmButtonColor: '#01582D',
    cancelButtonColor: '#8B9A94',
    buttonsStyling: true,
    customClass: {
        popup: 'rounded-xl2',
        title: 'font-display',
    },
});

/** Bandeau discret en haut a droite, pour les confirmations d'action. */
const toast = baseSwal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true,
    didOpen: (el) => {
        el.addEventListener('mouseenter', Swal.stopTimer);
        el.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

window.notifier = {
    succes: (message, titre = null) =>
        toast.fire({ icon: 'success', title: titre ?? message, text: titre ? message : undefined }),

    erreur: (message, titre = 'Une erreur est survenue') =>
        baseSwal.fire({ icon: 'error', title: titre, text: message, confirmButtonText: 'Fermer' }),

    info: (message, titre = null) =>
        toast.fire({ icon: 'info', title: titre ?? message, text: titre ? message : undefined }),

    attention: (message, titre = 'Attention') =>
        baseSwal.fire({ icon: 'warning', title: titre, text: message, confirmButtonText: 'J\'ai compris' }),

    /** Liste d'erreurs de validation, regroupees dans une seule fenetre. */
    validation: (erreurs) =>
        baseSwal.fire({
            icon: 'error',
            title: 'Corrigez les points suivants',
            html: '<ul style="text-align:left;margin:0;padding-left:1.1rem">'
                + erreurs.map((e) => `<li>${e}</li>`).join('')
                + '</ul>',
            confirmButtonText: 'Fermer',
        }),

    /**
     * Confirmation avant une action irreversible.
     * Retourne une promesse resolue a true si l'utilisateur confirme.
     */
    confirmer: async ({
        titre = 'Confirmer l\'action',
        message = 'Cette action est définitive.',
        bouton = 'Confirmer',
        danger = false,
    } = {}) => {
        const r = await baseSwal.fire({
            icon: danger ? 'warning' : 'question',
            title: titre,
            text: message,
            showCancelButton: true,
            confirmButtonText: bouton,
            cancelButtonText: 'Annuler',
            reverseButtons: true,
            confirmButtonColor: danger ? '#C2412D' : '#01582D',
        });

        return r.isConfirmed;
    },
};

window.Swal = Swal;

/* ------------------------------------------- Confirmation declarative */
// Un formulaire portant l'attribut data-confirmer declenche une demande de
// confirmation avant envoi, sans une ligne de JavaScript dans les vues.
document.addEventListener('submit', async (e) => {
    const form = e.target;

    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirmer')) {
        return;
    }
    if (form.dataset.confirme === 'oui') {
        return; // deja confirme : on laisse partir
    }

    e.preventDefault();

    const confirme = await window.notifier.confirmer({
        titre: form.dataset.confirmerTitre || 'Confirmer l\'action',
        message: form.dataset.confirmer || 'Cette action est définitive.',
        bouton: form.dataset.confirmerBouton || 'Confirmer',
        danger: form.dataset.confirmerDanger !== undefined,
    });

    if (confirme) {
        form.dataset.confirme = 'oui';
        form.submit();
    }
}, true);

Alpine.start();
