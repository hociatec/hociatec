export const formatOrderStatusFr = (status: string) => {
  switch (status) {
    case 'pending':
      return 'En attente';
    case 'confirmed':
      return 'Confirmée';
    case 'delivered':
      return 'Livrée';
    case 'cancelled':
      return 'Annulée';
    default:
      return status;
  }
};

export const formatInvoiceStatusFr = (status: string) => {
  switch (status) {
    case 'issued':
      return 'Émise';
    case 'cancelled':
      return 'Annulée';
    default:
      return status;
  }
};

export const formatPaymentStatusFr = (status: string) => {
  switch (status) {
    case 'open':
      return 'Ouvert';
    case 'paid':
      return 'Payé';
    case 'expired':
      return 'Expiré';
    case 'failed':
      return 'Échoué';
    default:
      return status;
  }
};

export const formatStripePaymentStatusFr = (status?: string | null) => {
  switch (status) {
    case 'paid':
      return 'Payé';
    case 'unpaid':
      return 'Non payé';
    case 'no_payment_required':
      return 'Aucun paiement requis';
    case 'requires_payment_method':
      return 'Moyen de paiement requis';
    case 'requires_confirmation':
      return 'Confirmation requise';
    case 'requires_action':
      return 'Action requise';
    case 'processing':
      return 'En cours de traitement';
    case 'succeeded':
      return 'Réussi';
    case 'canceled':
      return 'Annulé';
    case undefined:
    case null:
    case '':
      return '-';
    default:
      return status;
  }
};

export const formatStripeFailureCodeFr = (code?: string | null) => {
  switch (code) {
    case 'insufficient_funds':
      return 'Fonds insuffisants';
    case 'card_declined':
      return 'Carte refusée';
    case 'expired_card':
      return 'Carte expirée';
    case 'incorrect_cvc':
      return 'Code CVC incorrect';
    case 'incorrect_number':
      return 'Numéro de carte incorrect';
    case 'incorrect_zip':
      return 'Code postal incorrect';
    case 'invalid_cvc':
      return 'Code CVC invalide';
    case 'invalid_expiry_month':
      return 'Mois d’expiration invalide';
    case 'invalid_expiry_year':
      return 'Année d’expiration invalide';
    case 'lost_card':
      return 'Carte déclarée perdue';
    case 'stolen_card':
      return 'Carte déclarée volée';
    case 'processing_error':
      return 'Erreur de traitement bancaire';
    case 'authentication_required':
      return 'Authentification bancaire requise';
    case 'approve_with_id':
      return 'Paiement à faire approuver par la banque';
    case 'call_issuer':
      return 'Banque émettrice à contacter';
    case 'do_not_honor':
      return 'Paiement refusé par la banque';
    case 'generic_decline':
      return 'Refus bancaire générique';
    case 'pickup_card':
      return 'Carte à retenir';
    case 'reenter_transaction':
      return 'Transaction à ressaisir';
    case 'try_again_later':
      return 'Paiement à réessayer plus tard';
    case undefined:
    case null:
    case '':
      return '-';
    default:
      return code;
  }
};

export const formatStripeEventTypeFr = (eventType?: string | null) => {
  switch (eventType) {
    case 'checkout.session.completed':
      return 'Session de paiement finalisée';
    case 'checkout.session.async_payment_succeeded':
      return 'Paiement asynchrone confirmé';
    case 'checkout.session.async_payment_failed':
      return 'Paiement asynchrone échoué';
    case 'checkout.session.expired':
      return 'Session de paiement expirée';
    case 'payment_intent.payment_failed':
      return 'Paiement refusé';
    case undefined:
    case null:
    case '':
      return '-';
    default:
      return eventType;
  }
};
