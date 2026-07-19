import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import {
  fetchAdminPayments,
  formatPaymentStatusFr,
  formatStripeFailureCodeFr,
  formatStripeEventTypeFr,
  formatStripePaymentStatusFr,
  type AdminPaymentDto,
} from '@/features/orders/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';

const formatPrice = (cents: number, currency = 'EUR') =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency }).format((cents ?? 0) / 100);

export const PaymentsListPage = () => {
  useDocumentTitle('Admin - Paiements');

  const [items, setItems] = useState<AdminPaymentDto[]>([]);
  const [status, setStatus] = useState<'all' | 'open' | 'paid' | 'expired' | 'failed'>('all');
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    void fetchAdminPayments(status, search)
      .then(setItems)
      .catch((e: unknown) => setError(e instanceof Error ? e.message : 'Impossible de charger les paiements.'))
      .finally(() => setLoading(false));
  }, [status, search]);

  return (
    <PageContainer title="Paiements">
      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">{items.length} paiement{items.length > 1 ? 's' : ''} affiché{items.length > 1 ? 's' : ''}.</p>
        <p className="text-sm text-slate-500">Suivi Stripe, statuts, échecs et lien vers la commande quand elle existe.</p>
      </div>

      <FilterBar>
        <SearchFilter value={search} onChange={setSearch} placeholder="Rechercher par client, email, session Stripe..." />
        <SelectFilter
          value={status}
          onChange={(value) => setStatus(value as typeof status)}
          options={[
            { value: 'all', label: 'Tous les paiements' },
            { value: 'open', label: 'Ouverts' },
            { value: 'paid', label: 'Payés' },
            { value: 'failed', label: 'Échoués' },
            { value: 'expired', label: 'Expirés' },
          ]}
          ariaLabel="Statut de paiement"
        />
      </FilterBar>

      {loading ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Chargement des paiements...
        </div>
      ) : error ? (
        <div className="register-form__alert">{error}</div>
      ) : items.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Aucun paiement.
        </div>
      ) : (
        <div className="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm">
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Date</th>
                <th scope="col">Client</th>
                <th scope="col">Montant</th>
                <th scope="col">Statut</th>
                <th scope="col">Motif échec</th>
                <th scope="col">Commande</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {items.map((payment) => (
                <tr key={payment.id}>
                  <td>
                    <div>{new Date(payment.createdAt).toLocaleDateString('fr-FR')}</div>
                    <div className="muted">{new Date(payment.createdAt).toLocaleTimeString('fr-FR')}</div>
                  </td>
                  <td>
                    <div><strong>{payment.customerFullName || '-'}</strong></div>
                    <div className="muted">{payment.customerEmail}</div>
                  </td>
                  <td>{formatPrice(payment.totalPriceCents, payment.currencyCode)}</td>
                  <td>
                    <div>{payment.statusLabel ?? formatPaymentStatusFr(payment.status)}</div>
                    <div className="muted">
                      {payment.stripePaymentStatusLabel
                        ?? (payment.stripePaymentStatus
                          ? formatStripePaymentStatusFr(payment.stripePaymentStatus)
                          : formatStripeEventTypeFr(payment.lastStripeEventType))}
                    </div>
                  </td>
                  <td>
                    {payment.failureMessage || payment.failureCode ? (
                      <div>
                        <div>{payment.failureMessage || formatStripeFailureCodeFr(payment.failureCode)}</div>
                        {payment.failureCode ? <div className="muted">{payment.failureCode}</div> : null}
                      </div>
                    ) : (
                      '-'
                    )}
                  </td>
                  <td>
                    {payment.orderId ? (
                      <Link to={`/admin/orders/${payment.orderId}`} className="catalog-admin-table__primary-link">
                        #{payment.orderId}
                      </Link>
                    ) : (
                      '-'
                    )}
                  </td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link to={`/admin/payments/${payment.id}`} className="catalog-admin-actions__edit">
                        Voir
                      </Link>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </PageContainer>
  );
};
