import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

import type { AvailabilitySlot, Prestation } from '@/features/appointments/types/appointments';
import { formatEuroCents } from '@/shared/lib/formatters';

type AppointmentBookingModalProps = {
  booking: boolean;
  modalMode: 'recap' | 'success';
  selectedPrestation: Prestation | null;
  selectedSlot: AvailabilitySlot | null;
  onClose: () => void;
  onConfirm: () => void;
};

export const AppointmentBookingModal = ({
  booking,
  modalMode,
  selectedPrestation,
  selectedSlot,
  onClose,
  onConfirm,
}: AppointmentBookingModalProps) => (
  <div className="modal-backdrop" onClick={() => !booking && onClose()}>
    <div className="modal-container" onClick={(event) => event.stopPropagation()}>
      {modalMode === 'recap' && (
        <>
          <h2>Récapitulatif du rendez-vous</h2>
          <ul className="recap-list">
            <li>
              <strong>Prestation :</strong> {selectedPrestation?.name}
            </li>
            <li>
              <strong>Date :</strong>{' '}
              {selectedSlot &&
                format(new Date(selectedSlot.start), 'EEEE dd MMM yyyy', { locale: fr })}
            </li>
            <li>
              <strong>Heure :</strong>{' '}
              {selectedSlot && format(new Date(selectedSlot.start), 'HH:mm', { locale: fr })} -{' '}
              {selectedSlot && format(new Date(selectedSlot.end), 'HH:mm', { locale: fr })}
            </li>
            <li>
              <strong>Durée :</strong> {selectedPrestation?.durationMinutes} min
            </li>
            <li>
              <strong>Tarif :</strong>{' '}
              {selectedPrestation ? formatEuroCents(selectedPrestation.priceCents) : formatEuroCents(0)}
            </li>
          </ul>

          <div className="modal-actions">
            <button onClick={onClose} className="register-form__back">
              Annuler
            </button>
            <button onClick={onConfirm} disabled={booking} className="register-form__submit">
              {booking ? 'Réservation...' : 'Confirmer'}
            </button>
          </div>
        </>
      )}

      {modalMode === 'success' && (
        <>
          <h2>Rendez-vous confirmé ✅</h2>
          <p>
            Votre rendez-vous pour <strong>{selectedPrestation?.name}</strong> est confirmé le{' '}
            {selectedSlot &&
              format(new Date(selectedSlot.start), "EEEE dd MMM yyyy 'à' HH:mm", { locale: fr })}
            .
          </p>
          <button onClick={onClose} className="register-form__submit">
            Fermer
          </button>
        </>
      )}
    </div>
  </div>
);
