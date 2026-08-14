import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { useId } from 'react';

import type { AvailabilitySlot, Prestation } from '@/features/appointments/types/appointments';
import { formatEuroCents } from '@/shared/lib/formatters';
import { BlockingModal } from '@/shared/components/ui/BlockingModal';

type AppointmentBookingModalProps = {
  booking: boolean;
  modalMode: 'recap' | 'success';
  selectedPrestation: Prestation | null;
  selectedSlot: AvailabilitySlot | null;
  isRescheduling?: boolean;
  onClose: () => void;
  onConfirm: () => void;
};

export const AppointmentBookingModal = ({
  booking,
  modalMode,
  selectedPrestation,
  selectedSlot,
  isRescheduling = false,
  onClose,
  onConfirm,
}: AppointmentBookingModalProps) => {
  const titleId = useId();
  const descriptionId = useId();
  const canDismiss = modalMode === 'recap' && !booking;
  const modalModeLabel =
    modalMode === 'recap'
      ? isRescheduling
        ? 'Récapitulatif du report'
        : 'Récapitulatif du rendez-vous'
      : isRescheduling
        ? 'Rendez-vous reporté'
        : 'Rendez-vous confirmé';

  const modalProps = canDismiss ? { onClose } : {};

  return (
    <BlockingModal
      {...modalProps}
      labelledBy={titleId}
      describedBy={descriptionId}
      panelClassName="modal-container"
    >
      {modalMode === 'recap' ? (
        <>
          <h2 id={titleId}>{modalModeLabel}</h2>
          <p id={descriptionId}>
            {isRescheduling
              ? 'Confirmez le nouveau créneau avant d’envoyer le report.'
              : 'Confirmez vos informations avant d’envoyer la réservation.'}
          </p>
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
              Annuler
            </button>
            <button onClick={onConfirm} disabled={booking} className="register-form__submit">
              {booking ? (isRescheduling ? 'Report...' : 'Réservation...') : 'Confirmer'}
            </button>
          </div>
        </>
      ) : (
        <>
          <h2 id={titleId}>{modalModeLabel}</h2>
          <p id={descriptionId}>
            Votre rendez-vous pour <strong>{selectedPrestation?.name}</strong> est {isRescheduling ? 'reporté' : 'confirmé'} le{' '}
            {selectedSlot &&
              format(new Date(selectedSlot.start), "EEEE dd MMM yyyy 'à' HH:mm", { locale: fr })}
            .
          </p>
          <button onClick={onClose} className="register-form__submit">
            Fermer
          </button>
        </>
      )}
    </BlockingModal>
  );
};
