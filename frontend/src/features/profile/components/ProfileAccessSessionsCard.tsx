import { formatOptionalFrenchDate } from '@/shared/lib/formatters';
import type { AccountAccessSession } from '../../auth/api/authApi';

export const ProfileAccessSessionsCard = ({
  sessions,
  isLoading,
  isOpen,
  revokingSessionId,
  onToggle,
  onRevoke,
}: {
  sessions: AccountAccessSession[];
  isLoading: boolean;
  isOpen: boolean;
  revokingSessionId: number | null;
  onToggle: () => void;
  onRevoke: (id: number) => void;
}) => (
  <section className="profile-card profile-card--sessions" aria-labelledby="profile-sessions-heading">
    <div className="profile-card__header">
      <div>
        <h2 id="profile-sessions-heading">Accès actifs ({sessions.length})</h2>
        <p>Choisissez précisément quel accès doit être révoqué.</p>
      </div>
      <button type="button" className="profile-card__edit" aria-expanded={isOpen} onClick={onToggle}>
        {isOpen ? 'Masquer' : `Révoquer les accès (${sessions.length})`}
      </button>
    </div>

    {isOpen ? (
      isLoading ? (
        <p className="profile-sessions__status">Chargement des accès…</p>
      ) : sessions.length === 0 ? (
        <p className="profile-sessions__status">Aucun accès actif détecté.</p>
      ) : (
        <ul className="profile-sessions__list">
          {sessions.map((session) => (
            <li key={session.id} className="profile-sessions__item">
              <div className="profile-sessions__meta">
                <div className="profile-sessions__topline">
                  <strong>{session.deviceLabel}</strong>
                  {session.current ? <span className="profile-sessions__badge">Cet appareil</span> : null}
                </div>
                <p>{session.platformLabel} • {session.clientLabel}</p>
                <p>{session.locationLabel}</p>
                <p>Dernière activité : {formatOptionalFrenchDate(session.lastUsedAt) ?? 'Inconnue'}</p>
              </div>
              <button
                type="button"
                className="profile-action-button profile-action-button--warning"
                disabled={revokingSessionId === session.id}
                onClick={() => onRevoke(session.id)}
              >
                {revokingSessionId === session.id ? 'Révocation...' : 'Révoquer cet accès'}
              </button>
            </li>
          ))}
        </ul>
      )
    ) : null}
  </section>
);
