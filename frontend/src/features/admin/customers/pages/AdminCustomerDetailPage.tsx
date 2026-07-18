import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';

import {
  fetchAdminCustomerById,
  sendCustomerEmail,
  updateAdminCustomerAdminProfile,
  type AdminCustomerAddressDto,
  type AdminCustomerDetailDto,
} from '@/features/admin/customers/api';
import { formatOrderStatusFr, type OrderDto } from '@/features/orders/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useToast } from '@/shared/components/ui/toast';

const formatPrice = (valueInCents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(valueInCents / 100);

const formatAddress = (address: AdminCustomerAddressDto) =>
  [address.address, `${address.postalCode} ${address.city}`].filter(Boolean).join(', ');

const normalizePhoneLink = (phoneNumber: string) => phoneNumber.replace(/[^+\d]/g, '');
type OrderFilter = 'all' | 'open' | 'delivered' | 'cancelled';

type CustomerEmailFormState = {
  subject: string;
  message: string;
};

type EmailTemplatePreset = {
  id: string;
  label: string;
  subject: (customer: AdminCustomerDetailDto) => string;
  message: (customer: AdminCustomerDetailDto) => string;
};

const customerEmailPresets: EmailTemplatePreset[] = [
  {
    id: 'followup-order',
    label: 'Suivi commande',
    subject: (customer) => `Point sur votre commande ${customer.fullName}`,
    message: (customer) => `Bonjour ${customer.firstName},\n\nJe reviens vers vous concernant votre commande.\n\nNous vous confirmons que votre dossier est bien pris en charge. Si vous avez besoin d’un point précis sur le traitement, la livraison ou la facture, vous pouvez répondre à cet e-mail.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'delivery-delay',
    label: 'Retard livraison',
    subject: () => 'Mise à jour sur votre livraison',
    message: (customer) => `Bonjour ${customer.firstName},\n\nNous vous informons qu’un délai supplémentaire peut impacter votre livraison.\n\nNous suivons la situation de près et reviendrons vers vous dès qu’une nouvelle date fiable sera confirmée.\n\nMerci pour votre compréhension.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'invoice-available',
    label: 'Facture disponible',
    subject: () => 'Votre facture est disponible',
    message: (customer) => `Bonjour ${customer.firstName},\n\nVotre facture est désormais disponible dans votre espace client.\n\nSi vous avez besoin d’un renvoi ou d’une précision complémentaire, indiquez-le-nous simplement en réponse.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'missing-info',
    label: 'Infos manquantes',
    subject: () => 'Informations complémentaires nécessaires',
    message: (customer) => `Bonjour ${customer.firstName},\n\nAfin de finaliser le traitement de votre dossier, nous avons besoin d’informations complémentaires.\n\nMerci de nous répondre avec les éléments manquants afin que nous puissions poursuivre rapidement.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'after-sales',
    label: 'SAV',
    subject: () => 'Prise en charge de votre demande SAV',
    message: (customer) => `Bonjour ${customer.firstName},\n\nNous confirmons la prise en charge de votre demande SAV.\n\nNotre équipe revient vers vous très rapidement avec la suite de la procédure et les prochaines étapes.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'commercial-gesture',
    label: 'Geste commercial',
    subject: () => 'Suite à votre demande',
    message: (customer) => `Bonjour ${customer.firstName},\n\nAfin de faire suite à votre demande, nous revenons vers vous avec une solution commerciale adaptée.\n\nNous restons disponibles si vous souhaitez en échanger avant validation.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'payment-reminder',
    label: 'Relance paiement',
    subject: () => 'Rappel concernant votre règlement',
    message: (customer) => `Bonjour ${customer.firstName},\n\nNous vous contactons au sujet d’un règlement restant à finaliser sur votre dossier.\n\nSi la situation a déjà été régularisée, vous pouvez ignorer ce message. Sinon, nous restons disponibles pour vous accompagner.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'quote-followup',
    label: 'Relance devis',
    subject: () => 'Relance suite à votre demande',
    message: (customer) => `Bonjour ${customer.firstName},\n\nJe me permets de revenir vers vous suite à votre demande.\n\nSi vous souhaitez avancer sur votre projet ou obtenir un complément d’information, nous sommes disponibles pour vous répondre.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'appointment-confirmation',
    label: 'Confirmation RDV',
    subject: () => 'Confirmation de votre rendez-vous',
    message: (customer) => `Bonjour ${customer.firstName},\n\nNous vous confirmons la bonne prise en compte de votre rendez-vous.\n\nSi vous devez le déplacer ou ajouter une précision utile avant l’échange, répondez directement à cet e-mail.\n\nCordialement,\nService client Hociatec`,
  },
  {
    id: 'thank-you',
    label: 'Remerciement',
    subject: () => 'Merci pour votre confiance',
    message: (customer) => `Bonjour ${customer.firstName},\n\nMerci pour votre confiance.\n\nNous restons à votre disposition pour tout besoin complémentaire et serons ravis de vous accompagner à nouveau.\n\nCordialement,\nService client Hociatec`,
  },
];

export const AdminCustomerDetailPage = () => {
  const params = useParams();
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const toast = useToast();
  const customerId = Number(params.customerId);
  const [customer, setCustomer] = useState<AdminCustomerDetailDto | null>(null);
  const [addresses, setAddresses] = useState<AdminCustomerAddressDto[]>([]);
  const [orders, setOrders] = useState<OrderDto[]>([]);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [error, setError] = useState<string | null>(null);
  const [orderFilter, setOrderFilter] = useState<OrderFilter>('all');
  const [adminNotes, setAdminNotes] = useState('');
  const [adminTagsInput, setAdminTagsInput] = useState('');
  const [saveState, setSaveState] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [saveMessage, setSaveMessage] = useState<string | null>(null);
  const [emailOpen, setEmailOpen] = useState(false);
  const [emailForm, setEmailForm] = useState<CustomerEmailFormState>({ subject: '', message: '' });
  const [emailSending, setEmailSending] = useState(false);
  const emailOnlyView = searchParams.get('panel') === 'email';

  useEffect(() => {
    if (!customerId) {
      setStatus('error');
      setError('Client invalide.');
      return;
    }

    setStatus('loading');
    setError(null);
    void fetchAdminCustomerById(customerId)
      .then((data) => {
        setCustomer(data.customer);
        setAddresses(data.addresses);
        setOrders(data.orders);
        setAdminNotes(data.customer.adminNotes ?? '');
        setAdminTagsInput((data.customer.adminTags ?? []).join(', '));
        setEmailForm({
          subject: `Votre compte ${data.customer.fullName} sur Hociatec`,
          message: '',
        });
        setStatus('success');
      })
      .catch((e: unknown) => {
        setStatus('error');
        setError(e instanceof Error ? e.message : 'Impossible de charger ce client.');
      });
  }, [customerId]);

  useEffect(() => {
    if (searchParams.get('panel') === 'email') {
      setEmailOpen(true);
    }
  }, [searchParams]);

  const latestOrder = orders[0] ?? null;
  const filteredOrders = useMemo(() => {
    switch (orderFilter) {
      case 'open':
        return orders.filter((order) => order.status === 'pending' || order.status === 'confirmed');
      case 'delivered':
        return orders.filter((order) => order.status === 'delivered');
      case 'cancelled':
        return orders.filter((order) => order.status === 'cancelled');
      case 'all':
      default:
        return orders;
    }
  }, [orderFilter, orders]);

  const parsedTags = useMemo(
    () => adminTagsInput.split(',').map((tag) => tag.trim()).filter(Boolean),
    [adminTagsInput],
  );

  const handleSaveAdminProfile = () => {
    if (!customer) return;

    setSaveState('saving');
    setSaveMessage(null);
    void updateAdminCustomerAdminProfile(customer.id, {
      adminNotes,
      adminTags: parsedTags,
    })
      .then((result) => {
        setCustomer((current) => current ? {
          ...current,
          adminNotes: result.adminNotes ?? null,
          adminTags: result.adminTags,
        } : current);
        setAdminNotes(result.adminNotes ?? '');
        setAdminTagsInput(result.adminTags.join(', '));
        setSaveState('saved');
        setSaveMessage('Suivi interne enregistré.');
      })
      .catch((e: unknown) => {
        setSaveState('error');
        setSaveMessage(e instanceof Error ? e.message : 'Impossible d’enregistrer le suivi interne.');
      });
  };

  const handleSendEmail = () => {
    if (!customer) return;

    setEmailSending(true);
    void sendCustomerEmail(customer.id, emailForm)
      .then(() => {
        setEmailSending(false);
        toast.show('E-mail envoyé au client.', { variant: 'success' });
      })
      .catch((e: unknown) => {
        setEmailSending(false);
        toast.show(e instanceof Error ? e.message : 'Impossible d’envoyer l’email.', { variant: 'error' });
      });
  };

  const applyEmailPreset = (preset: EmailTemplatePreset) => {
    if (!customer) return;

    setEmailForm({
      subject: preset.subject(customer),
      message: preset.message(customer),
    });
    toast.show(`Modèle "${preset.label}" appliqué.`, { variant: 'info' });
  };

  const emailComposerSection = customer ? (
    <section className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div className="border-b border-slate-200 bg-slate-950 px-6 py-5 text-white">
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Messagerie client</p>
            <h2 className="mt-1 text-xl font-semibold">Envoyer un e-mail manuel</h2>
            <p className="mt-2 text-sm text-slate-300">
              Message direct vers {customer.fullName} ({customer.email}).
            </p>
          </div>
          <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
            Utilise un modèle, ajuste le message puis envoie-le sans quitter cette fiche.
          </div>
        </div>
      </div>

      <div className="grid gap-6 px-6 py-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5">
          <div className="flex items-center justify-between gap-3">
            <div>
              <div className="text-sm font-semibold text-slate-900">Modèles prêts à l’emploi</div>
              <p className="mt-1 text-sm text-slate-500">Préremplis les cas les plus fréquents sans repartir de zéro.</p>
            </div>
            <span className="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600">
              {customerEmailPresets.length} modèles
            </span>
          </div>
          <div className="mt-4 flex flex-wrap gap-2">
            {customerEmailPresets.map((preset) => (
              <button
                key={preset.id}
                type="button"
                className="rounded-full border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:border-slate-500 hover:bg-slate-100"
                onClick={() => applyEmailPreset(preset)}
              >
                {preset.label}
              </button>
            ))}
          </div>
        </div>

        <div className="space-y-4 rounded-3xl border border-slate-200 p-5">
          <div>
            <div className="text-sm font-semibold text-slate-900">Composition du message</div>
            <p className="mt-1 text-sm text-slate-500">Le sujet et le contenu sont envoyés tels quels au client.</p>
          </div>
          <label className="register-form__field">
            <span className="register-form__label">Sujet</span>
            <input
              className="register-form__input"
              value={emailForm.subject}
              onChange={(event) => setEmailForm((prev) => ({ ...prev, subject: event.target.value }))}
              placeholder="Sujet de l’email"
            />
          </label>
          <label className="register-form__field">
            <span className="register-form__label">Contenu</span>
            <textarea
              className="register-form__input"
              rows={10}
              value={emailForm.message}
              onChange={(event) => setEmailForm((prev) => ({ ...prev, message: event.target.value }))}
              placeholder="Rédige ton message ici..."
            />
          </label>
          <div className="flex flex-wrap gap-3 pt-2">
            <button
              type="button"
              className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-60"
              onClick={handleSendEmail}
              disabled={emailSending || emailForm.subject.trim() === '' || emailForm.message.trim() === ''}
            >
              {emailSending ? 'Envoi...' : 'Envoyer'}
            </button>
            {!emailOnlyView ? (
              <button
                type="button"
                className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500"
                onClick={() => {
                  setEmailOpen(false);
                  const nextParams = new URLSearchParams(searchParams);
                  nextParams.delete('panel');
                  setSearchParams(nextParams, { replace: true });
                }}
              >
                Fermer
              </button>
            ) : null}
          </div>
        </div>
      </div>
    </section>
  ) : null;

  return (
    <PageContainer
      title={emailOnlyView ? 'Envoyer un e-mail' : customer ? customer.fullName : 'Fiche client'}
      headerActions={
        <div className="flex items-center gap-4">
          {emailOnlyView && customer ? (
            <button
              type="button"
              className="underline text-sm"
              onClick={() => navigate(`/admin/customers/${customer.id}`)}
            >
              Retour à la fiche client
            </button>
          ) : null}
          <button type="button" className="underline text-sm" onClick={() => navigate('/admin/customers')}>
            Retour aux clients
          </button>
        </div>
      }
    >
      {status === 'loading' && <p>Chargement...</p>}
      {error && <div className="register-form__alert">{error}</div>}

      {status === 'success' && customer && emailOnlyView ? (
        emailComposerSection
      ) : null}

      {status === 'success' && customer && !emailOnlyView && (
        <div className="space-y-6">
          <section className="rounded-2xl border border-slate-200 p-4">
            <div className="flex flex-wrap gap-3">
              <button
                type="button"
                className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500"
                onClick={() => {
                  setEmailOpen((current) => !current);
                  if (emailOpen) {
                    const nextParams = new URLSearchParams(searchParams);
                    nextParams.delete('panel');
                    setSearchParams(nextParams, { replace: true });
                  } else {
                    setSearchParams({ panel: 'email' }, { replace: true });
                  }
                }}
              >
                Envoyer un e-mail
              </button>
              {customer.phoneNumber ? (
                <a className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500" href={`tel:${normalizePhoneLink(customer.phoneNumber)}`}>
                  Appeler le client
                </a>
              ) : null}
              {latestOrder ? (
                <Link className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500" to={`/admin/orders/${latestOrder.id}`}>
                  Ouvrir la dernière commande
                </Link>
              ) : null}
              {customer.ordersCount > 0 ? (
                <Link className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500" to={`/admin/orders?search=${encodeURIComponent(customer.email)}`}>
                  Rechercher ses commandes
                </Link>
              ) : null}
              <Link
                className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500"
                to={`/admin/customers/${customer.id}/vouchers/new`}
              >
                Gérer les bons de réduction
              </Link>
            </div>
          </section>

          {emailOpen ? emailComposerSection : null}

          <section className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div className="border-b border-slate-200 bg-slate-50/80 px-5 py-4">
              <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                  <h2 className="font-semibold text-slate-900">Suivi interne</h2>
                  <p className="mt-1 text-sm text-slate-500">
                    Centralise les repères utiles pour le support, les relances et le suivi commercial.
                  </p>
                </div>
                <div className="flex items-center gap-3">
                  {saveMessage ? (
                    <div className={`rounded-full px-3 py-1 text-xs font-medium ${saveState === 'error' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'}`}>
                      {saveMessage}
                    </div>
                  ) : null}
                  <div className="rounded-full bg-white px-3 py-1 text-xs text-slate-500 shadow-sm">
                    {parsedTags.length} tag{parsedTags.length > 1 ? 's' : ''}
                  </div>
                </div>
              </div>
            </div>
            <div className="px-5 py-5">
              <div className="mb-5 flex items-center justify-between gap-3">
                <div>
                  <div className="text-sm font-medium text-slate-900">Édition rapide</div>
                  <div className="text-xs text-slate-500">Mets à jour les tags et les notes, puis enregistre.</div>
                </div>
                <div className="flex items-center gap-3">
                  {saveState === 'saving' ? (
                    <div className="text-xs text-slate-500">Enregistrement en cours...</div>
                  ) : null}
                  <button
                    type="button"
                    className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                    onClick={handleSaveAdminProfile}
                    disabled={saveState === 'saving'}
                  >
                    {saveState === 'saving' ? 'Enregistrement...' : 'Enregistrer'}
                  </button>
                </div>
              </div>
              <div className="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(300px,0.85fr)]">
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                  <div className="mb-3">
                    <h3 className="text-sm font-semibold text-slate-900">Tags client</h3>
                    <p className="mt-1 text-xs text-slate-500">
                      Sépare les tags par des virgules pour classer rapidement le client.
                    </p>
                  </div>
                  <input
                    className="register-form__input"
                    value={adminTagsInput}
                    onChange={(event) => setAdminTagsInput(event.target.value)}
                    placeholder="vip, sav, relance, pro..."
                  />
                  <div className="mt-4 rounded-2xl bg-slate-50 p-4">
                    <div className="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                      Aperçu des tags
                    </div>
                    <div className="flex min-h-14 flex-wrap gap-2">
                      {parsedTags.length > 0 ? parsedTags.map((tag) => (
                        <span key={tag} className="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm">
                          {tag}
                        </span>
                      )) : (
                        <span className="text-sm text-slate-400">Aucun tag pour le moment.</span>
                      )}
                    </div>
                  </div>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                  <div className="mb-3">
                    <h3 className="text-sm font-semibold text-slate-900">Notes internes</h3>
                    <p className="mt-1 text-xs text-slate-500">
                      Historique SAV, préférences client, points de vigilance, contexte commercial.
                    </p>
                  </div>
                  <textarea
                    className="register-form__input min-h-52 bg-white"
                    value={adminNotes}
                    onChange={(event) => setAdminNotes(event.target.value)}
                    placeholder="Ex: client prioritaire, rappeler pour validation, attente de justificatif, sensible au délai..."
                  />
                </div>
              </div>
            </div>
          </section>

          <section className="grid gap-4 lg:grid-cols-4">
            <div className="rounded-2xl border border-slate-200 p-4">
              <div className="text-sm text-slate-500">Client</div>
              <div className="mt-2 text-lg font-semibold text-slate-900">{customer.fullName}</div>
              <div className="text-sm text-slate-600">{customer.email}</div>
              <div className="text-sm text-slate-600">{customer.phoneNumber}</div>
            </div>
            <div className="rounded-2xl border border-slate-200 p-4">
              <div className="text-sm text-slate-500">Commandes</div>
              <div className="mt-2 text-2xl font-semibold text-slate-900">{customer.ordersCount}</div>
              <div className="text-sm text-slate-600">
                Dernière: {customer.lastOrderNumber ?? 'Aucune'}
              </div>
            </div>
            <div className="rounded-2xl border border-slate-200 p-4">
              <div className="text-sm text-slate-500">Total dépensé</div>
              <div className="mt-2 text-2xl font-semibold text-slate-900">{formatPrice(customer.totalSpentCents)}</div>
              <div className="text-sm text-slate-600">
                Inscrit le {new Date(customer.createdAt).toLocaleDateString('fr-FR')}
              </div>
            </div>
            <div className="rounded-2xl border border-slate-200 p-4">
              <div className="text-sm text-slate-500">Compte</div>
              <div className="mt-2 text-lg font-semibold text-slate-900">
                {customer.isVerified ? 'Vérifié' : 'Non vérifié'}
              </div>
              <div className="text-sm text-slate-600">
                {customer.lastOrderAt ? `Dernière activité ${new Date(customer.lastOrderAt).toLocaleDateString('fr-FR')}` : 'Aucune activité de commande'}
              </div>
            </div>
          </section>

          <section className="rounded-2xl border border-slate-200 p-4">
            <h2 className="mb-3 font-semibold">Adresses</h2>
            {addresses.length === 0 ? (
              <p className="text-sm text-slate-500">Aucune adresse enregistrée.</p>
            ) : (
              <div className="grid gap-4 md:grid-cols-2">
                {addresses.map((address) => (
                  <div key={address.id} className="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                    <div className="font-semibold text-slate-900">
                      {address.name} {address.isDefault ? '· Par défaut' : ''}
                    </div>
                    <div>{formatAddress(address)}</div>
                    {address.company ? <div>Société: {address.company}</div> : null}
                    {address.companySiren ? <div>SIREN: {address.companySiren}</div> : null}
                    {address.companyVatNumber ? <div>TVA: {address.companyVatNumber}</div> : null}
                    {address.purchaseOrderNumber ? <div>BC: {address.purchaseOrderNumber}</div> : null}
                  </div>
                ))}
              </div>
            )}
          </section>


          <section className="rounded-2xl border border-slate-200 p-4">
            <div className="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <h2 className="font-semibold">Commandes</h2>
              <div className="flex flex-wrap gap-2 text-sm">
                <button
                  type="button"
                  className={`rounded-full px-3 py-1.5 ${orderFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'}`}
                  onClick={() => setOrderFilter('all')}
                >
                  Toutes ({orders.length})
                </button>
                <button
                  type="button"
                  className={`rounded-full px-3 py-1.5 ${orderFilter === 'open' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'}`}
                  onClick={() => setOrderFilter('open')}
                >
                  En cours ({orders.filter((order) => order.status === 'pending' || order.status === 'confirmed').length})
                </button>
                <button
                  type="button"
                  className={`rounded-full px-3 py-1.5 ${orderFilter === 'delivered' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'}`}
                  onClick={() => setOrderFilter('delivered')}
                >
                  Livrées ({orders.filter((order) => order.status === 'delivered').length})
                </button>
                <button
                  type="button"
                  className={`rounded-full px-3 py-1.5 ${orderFilter === 'cancelled' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'}`}
                  onClick={() => setOrderFilter('cancelled')}
                >
                  Annulées ({orders.filter((order) => order.status === 'cancelled').length})
                </button>
              </div>
            </div>
            {orders.length === 0 ? (
              <p className="text-sm text-slate-500">Aucune commande pour ce client.</p>
            ) : (
              <div className="space-y-3">
                {filteredOrders.map((order) => (
                  <div key={order.id} className="flex flex-col gap-3 rounded-2xl bg-slate-50 p-4 md:flex-row md:items-center md:justify-between">
                    <div>
                      <div className="font-semibold text-slate-900">{order.number}</div>
                      <div className="text-sm text-slate-600">
                        {new Date(order.createdAt).toLocaleString('fr-FR')} · {order.statusLabel ?? formatOrderStatusFr(order.status)}
                      </div>
                      {order.invoice?.number ? (
                        <div className="text-sm text-slate-500">Facture {order.invoice.number}</div>
                      ) : null}
                    </div>
                    <div className="flex items-center gap-4">
                      <div className="text-sm font-semibold text-slate-900">{formatPrice(order.totalPriceCents)}</div>
                      <Link className="underline text-sm" to={`/admin/orders/${order.id}`}>
                        Voir la commande
                      </Link>
                    </div>
                  </div>
                ))}
                {filteredOrders.length === 0 ? (
                  <p className="text-sm text-slate-500">Aucune commande dans ce filtre.</p>
                ) : null}
              </div>
            )}
          </section>
        </div>
      )}
    </PageContainer>
  );
};
