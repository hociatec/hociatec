import {
  type AdminCustomerAddressDto,
  type AdminCustomerDetailDto,
} from '@/features/admin/customers/api';

export type OrderFilter = 'all' | 'open' | 'delivered' | 'cancelled';

export type CustomerEmailFormState = {
  subject: string;
  message: string;
};

export type EmailTemplatePreset = {
  id: string;
  label: string;
  subject: (customer: AdminCustomerDetailDto) => string;
  message: (customer: AdminCustomerDetailDto) => string;
};

export const customerEmailPresets: EmailTemplatePreset[] = [
  {
    id: 'followup-order',
    label: 'Suivi commande',
    subject: (customer) => `Point sur votre commande ${customer.fullName}`,
    message: (customer) =>
      `Bonjour ${customer.firstName},\n\nJe reviens vers vous concernant votre commande.\n\nNous vous confirmons que votre dossier est bien pris en charge. Si vous avez besoin d’un point précis sur le traitement, la livraison ou la facture, vous pouvez répondre à cet e-mail.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'delivery-delay',
    label: 'Retard livraison',
    subject: () => 'Mise à jour sur votre livraison',
    message: (customer) =>
      `Bonjour ${customer.firstName},\n\nNous vous informons qu’un délai supplémentaire peut impacter votre livraison.\n\nNous suivons la situation de près et reviendrons vers vous dès qu’une nouvelle date fiable sera confirmée.\n\nMerci pour votre compréhension.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'invoice-available',
    label: 'Facture disponible',
    subject: () => 'Votre facture est disponible',
    message: (customer) =>
      `Bonjour ${customer.firstName},\n\nVotre facture est désormais disponible dans votre espace client.\n\nSi vous avez besoin d’un renvoi ou d’une précision complémentaire, indiquez-le-nous simplement en réponse.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'missing-info',
    label: 'Infos manquantes',
    subject: () => 'Informations complémentaires nécessaires',
    message: (customer) =>
      `Bonjour ${customer.firstName},\n\nAfin de finaliser le traitement de votre dossier, nous avons besoin d’informations complémentaires.\n\nMerci de nous répondre avec les éléments manquants afin que nous puissions poursuivre rapidement.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'after-sales',
    label: 'SAV',
    subject: () => 'Prise en charge de votre demande SAV',
    message: (customer) =>
      `Bonjour ${customer.firstName},\n\nNous confirmons la prise en charge de votre demande SAV.\n\nNotre équipe revient vers vous très rapidement avec la suite de la procédure et les prochaines étapes.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'commercial-gesture',
    label: 'Geste commercial',
    subject: () => 'Suite à votre demande',
    message: (customer) =>
      `Bonjour ${customer.firstName},\n\nAfin de faire suite à votre demande, nous revenons vers vous avec une solution commerciale adaptée.\n\nNous restons disponibles si vous souhaitez en échanger avant validation.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'payment-reminder',
    label: 'Relance paiement',
    subject: () => 'Rappel concernant votre règlement',
    message: (customer) =>
      `Bonjour ${customer.firstName},\n\nNous vous contactons au sujet d’un règlement restant à finaliser sur votre dossier.\n\nSi la situation a déjà été régularisée, vous pouvez ignorer ce message. Sinon, nous restons disponibles pour vous accompagner.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'quote-followup',
    label: 'Relance devis',
    subject: () => 'Relance suite à votre demande',
    message: (customer) =>
      `Bonjour ${customer.firstName},\n\nJe me permets de revenir vers vous suite à votre demande.\n\nSi vous souhaitez avancer sur votre projet ou obtenir un complément d’information, nous sommes disponibles pour vous répondre.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'appointment-confirmation',
    label: 'Confirmation RDV',
    subject: () => 'Confirmation de votre rendez-vous',
    message: (customer) =>
      `Bonjour ${customer.firstName},\n\nNous vous confirmons la bonne prise en compte de votre rendez-vous.\n\nSi vous devez le déplacer ou ajouter une précision utile avant l’échange, répondez directement à cet e-mail.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'thank-you',
    label: 'Remerciement',
    subject: () => 'Merci pour votre confiance',
    message: (customer) =>
      `Bonjour ${customer.firstName},\n\nMerci pour votre confiance.\n\nNous restons à votre disposition pour tout besoin complémentaire et serons ravis de vous accompagner à nouveau.\n\nCordialement,\nService client Hociatec`,
  },
];

export const formatAddress = (address: AdminCustomerAddressDto) =>
  [address.address, `${address.postalCode} ${address.city}`].filter(Boolean).join(', ');

export const normalizePhoneLink = (phoneNumber: string) => phoneNumber.replace(/[^+\d]/g, '');
