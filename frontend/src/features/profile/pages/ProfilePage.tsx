import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { ProfileDangerZone } from '../components/ProfileDangerZone';
import { ProfileInformationCard } from '../components/ProfileInformationCard';
import { ProfileSummaryCard } from '../components/ProfileSummaryCard';
import { useProfileController } from '../hooks/useProfileController';

import './ProfilePage.css';

export const ProfilePage = () => {
  useDocumentTitle('Profil');

  const {
    user,
    feedback,
    isEditing,
    isSaving,
    isDeleting,
    form,
    initials,
    formattedRoles,
    formattedBirthDate,
    hasCurrentPasswordRequirement,
    handleFieldChange,
    handleStartEditing,
    handleCancelEditing,
    handleSubmitProfile,
    handleConfirmDelete,
  } = useProfileController();

  if (!user) return null;

  return (
    <SiteLayout headerVariant="light">
      <div className="profile-page">
        <header className="profile-header">
          <div>
            <h1>Profil</h1>
            <p>Informations utilisées pour votre compte client Hociatec.</p>
          </div>
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

        <div className="profile-grid">
          <ProfileSummaryCard
            initials={initials}
            name={`${user.firstName} ${user.lastName}`}
            email={user.email}
          />

          <ProfileInformationCard
            user={user}
            isEditing={isEditing}
            isSaving={isSaving}
            form={form}
            formattedRoles={formattedRoles}
            formattedBirthDate={formattedBirthDate}
            hasCurrentPasswordRequirement={hasCurrentPasswordRequirement}
            onFieldChange={handleFieldChange}
            onStartEditing={handleStartEditing}
            onCancelEditing={handleCancelEditing}
            onSubmit={handleSubmitProfile}
          />
        </div>

        <ProfileDangerZone isDeleting={isDeleting} onConfirmDelete={handleConfirmDelete} />
      </div>
    </SiteLayout>
  );
};
