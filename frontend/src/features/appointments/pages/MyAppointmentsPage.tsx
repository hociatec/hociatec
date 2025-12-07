import { useEffect, useState } from 'react';
import { PageContainer } from '../../../shared/components/PageContainer';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { cancelAppointment, fetchMyAppointments } from '../api';
import type { AppointmentItem } from '../types';

const formatDate = (iso: string) => new Date(iso).toLocaleString();
const formatPrice = (priceCents: number) => (priceCents / 100).toFixed(2);

export const MyAppointmentsPage = () => {
  useDocumentTitle('Mes rendez-vous');

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [upcoming, setUpcoming] = useState<AppointmentItem[]>([]);
  const [past, setPast] = useState<AppointmentItem[]>([]);
  const [actionError, setActionError] = useState<string | null>(null);
  const [cancellingId, setCancellingId] = useState<number | null>(null);

  useEffect(() => {
    (async () => {
      try {
        const data = await fetchMyAppointments();
        setUpcoming(data.upcoming);
        setPast(data.past);
      } catch (err: any) {
        setError(err?.message || 'Erreur lors du chargement de mes rendez-vous');
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const handleCancel = async (appointmentId: number) => {
    const confirmed = window.confirm('Voulez-vous annuler ce rendez-vous ?');

    if (!confirmed) {
      return;
    }

    setActionError(null);
    setCancellingId(appointmentId);

    try {
      const updated = await cancelAppointment(appointmentId);

      setUpcoming((current) => current.map((item) => (item.id === appointmentId ? updated : item)));
    } catch (err: any) {
      setActionError(err?.message || 'Erreur lors de l\'annulation du rendez-vous');
    } finally {
      setCancellingId(null);
    }
  };

  const renderList = (items: AppointmentItem[], options: { allowCancel?: boolean } = {}) => (
    <ul style={{ listStyle: 'none', padding: 0, margin: 0, display: 'grid', gap: 12 }}>
      {items.map((appointment) => (
        <li
          key={appointment.id}
          style={{ padding: 12, border: '1px solid rgba(148,163,184,.35)', borderRadius: 8 }}
        >
          <div style={{ display: 'flex', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap' }}>
            <strong>{appointment.prestation.name}</strong>
            <span className="muted">
              {appointment.prestation.durationMinutes} min · {formatPrice(appointment.prestation.priceCents)} EUR
            </span>
          </div>
          <div style={{ marginTop: 4 }}>
            {formatDate(appointment.startAt)} - {formatDate(appointment.endAt)}
          </div>
          {appointment.status && (
            <div className="muted" style={{ marginTop: 4 }}>
              Statut: {appointment.status}
            </div>
          )}
          {options.allowCancel && appointment.isCancelable && (
            <button
              type="button"
              onClick={() => handleCancel(appointment.id)}
              disabled={cancellingId === appointment.id}
              style={{
                marginTop: 8,
                border: '1px solid #ef4444',
                color: '#ef4444',
                background: 'transparent',
                borderRadius: 6,
                padding: '6px 12px',
                cursor: cancellingId === appointment.id ? 'not-allowed' : 'pointer',
              }}
            >
              {cancellingId === appointment.id ? 'Annulation...' : 'Annuler ce rendez-vous'}
            </button>
          )}
        </li>
      ))}
    </ul>
  );

  return (
    <SiteLayout>
      <PageContainer title="Mes rendez-vous">
        {loading && <p>Chargement...</p>}
        {error && <div className="register-form__alert">{error}</div>}
        {actionError && <div className="register-form__alert">{actionError}</div>}

        {!loading && !error && (
          <div style={{ display: 'grid', gap: 24 }}>
            <section>
              <h2>A venir</h2>
              {upcoming.length === 0 ? (
                <p className="muted">Aucun rendez-vous a venir.</p>
              ) : (
                renderList(upcoming, { allowCancel: true })
              )}
            </section>

            <section>
              <h2>Passes</h2>
              {past.length === 0 ? (
                <p className="muted">Aucun rendez-vous passe.</p>
              ) : (
                renderList(past)
              )}
            </section>
          </div>
        )}
      </PageContainer>
    </SiteLayout>
  );
};
