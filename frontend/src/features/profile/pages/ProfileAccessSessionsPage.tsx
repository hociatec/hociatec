import { useEffect } from 'react';
import { Link } from 'react-router';

import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { ProfileAccessSessionsCard } from '../components/ProfileAccessSessionsCard';
import { useProfileController } from '../hooks/useProfileController';

import './ProfilePage.css';

export const ProfileAccessSessionsPage = () => {
  useDocumentTitle('Révoquer les accès');

  const {
    user,
    feedback,
    accessSessions,
    isLoadingAccessSessions,
    isAccessManagerOpen,
    revokingSessionId,
    handleToggleAccessManager,
    handleRevokeSession,
  } = useProfileController();

  useEffect(() => {
    if (!isAccessManagerOpen) {
      handleToggleAccessManager();
    }
  }, [handleToggleAccessManager, isAccessManagerOpen]);

  if (!user) return null;

  return (
    <SiteLayout headerVariant="light">
      <div className="profile-page">
        <header className="profile-header">
          <div>
            <h1>Révoquer les accès</h1>
            <p>Gérez précisément les sessions ouvertes sur le site web et sur iPhone.</p>
          </div>
          <Link className="profile-card__edit" to="/mon-espace">
            Retour à mon espace
          </Link>
        </header>

        {feedback ? (
          <div className={`profile-feedback profile-feedback--${feedback.type}`} role="status">
            <p>{feedback.message}</p>
            {feedback.details?.map((detail) => (
              <p key={detail} className="profile-feedback__detail">
                {detail}
              </p>
            ))}
          </div>
        ) : null}

        <ProfileAccessSessionsCard
          sessions={accessSessions}
          isLoading={isLoadingAccessSessions}
          isOpen={isAccessManagerOpen}
          revokingSessionId={revokingSessionId}
          onToggle={handleToggleAccessManager}
          onRevoke={handleRevokeSession}
        />
      </div>
    </SiteLayout>
  );
};
