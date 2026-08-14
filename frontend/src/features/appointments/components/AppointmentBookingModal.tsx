import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { useId } from 'react';

import type { AvailabilitySlot, Prestation } from '@/features/appointments/types/appointments';
import { formatEuroCents } from '@/shared/lib/formatters';
import { BlockingModal } from '@/shared/components/ui/BlockingModal';

type AppointmentBookingModalProps = {
  booking: boolean;
  modalMode: 'recap' | 'submitting';
  selectedPrestation: Prestation | null;
  selectedSlot: AvailabilitySlot | null;
  isRescheduling?: boolean;
  errorMessage?: string | null;
  onClose: () => void;
  onConfirm: () => void;
};

export const AppointmentBookingModal = ({
  booking,
  modalMode,
  selectedPrestation,
  selectedSlot,
  isRescheduling = false,
  errorMessage = null,
  onClose,
  onConfirm,
}: AppointmentBookingModalProps) => {
  const titleId = useId();
  const descriptionId = useId();
  const modalModeLabel =
    modalMode === 'recap'
      ? isRescheduling
        ? 'Confirmer le report du rendez-vous'
        : 'Récapitulatif du rendez-vous'
      : isRescheduling
        ? 'Report du rendez-vous en cours'
        : 'Confirmation du rendez-vous en cours';

  return (
    <BlockingModal
      labelledBy={titleId}
      describedBy={descriptionId}
      panelClassName="modal-container"
    >
      {modalMode === 'recap' ? (
        <>
          <h2 id={titleId}>{modalModeLabel}</h2>
          <p id={descriptionId}>
            {isRescheduling
              ? 'Vous allez remplacer votre créneau actuel par celui affiché ci-dessous. Vérifiez bien la nouvelle date et l’horaire avant de confirmer le report.'
              : 'Confirmez vos informations avant d’envoyer la réservation.'}
          </p>
          {errorMessage ? (
            <p
              role="alert"
              aria-live="assertive"
              aria-atomic="true"
              className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            >
              {errorMessage}
            </p>
          ) : null}
          <ul className="recap-list">
            <li>
              <strong>Prestation</strong>
              <span>{selectedPrestation?.name}</span>
            </li>
            <li>
              <strong>Date</strong>
              <span>{selectedSlot && format(new Date(selectedSlot.start), 'EEEE dd MMM yyyy', { locale: fr })}</span>
            </li>
            <li>
              <strong>Heure</strong>
              <span>
                {selectedSlot && format(new Date(selectedSlot.start), 'HH:mm', { locale: fr })} -{' '}
                {selectedSlot && format(new Date(selectedSlot.end), 'HH:mm', { locale: fr })}
              </span>
            </li>
            <li>
              <strong>Durée</strong>
              <span>{selectedPrestation?.durationMinutes} min</span>
            </li>
            <li>
              <strong>Tarif</strong>
              <span>{selectedPrestation ? formatEuroCents(selectedPrestation.priceCents) : formatEuroCents(0)}</span>
            </li>
          </ul>

          <div className="modal-actions">
            <button onClick={onClose} className="register-form__back">
              Retour
            </button>
            <button onClick={onConfirm} disabled={booking} className="register-form__submit">
              {booking
                ? isRescheduling
                  ? 'Report en cours...'
                  : 'Réservation en cours...'
                : isRescheduling
                  ? 'Confirmer le report'
                  : 'Confirmer'}
            </button>
          </div>
        </>
      ) : modalMode === 'submitting' ? (
        <>
          <h2 id={titleId}>{modalModeLabel}</h2>
          <p id={descriptionId} role="status" aria-live="assertive" aria-atomic="true">
            {isRescheduling
              ? 'Le report de votre rendez-vous est en cours. Veuillez patienter, cette fenêtre se mettra à jour automatiquement dès que le report sera confirmé.'
              : 'La confirmation de votre rendez-vous est en cours. Veuillez patienter, cette fenêtre se mettra à jour automatiquement dès que la réservation sera enregistrée.'}
          </p>
        </>
      ) : null}
    </BlockingModal>
  );
};
