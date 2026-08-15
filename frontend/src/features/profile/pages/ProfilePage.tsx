import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { ProfileDangerZone } from '../components/ProfileDangerZone';
import { ProfileEditDialog } from '../components/ProfileEditDialog';
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
    isRevokingSessions,
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
    handleConfirmRevokeAllSessions,
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

        {feedback && (!isEditing || feedback.type === 'success') ? (
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
            formattedRoles={formattedRoles}
            formattedBirthDate={formattedBirthDate}
            onStartEditing={handleStartEditing}
          />
        </div>

        <ProfileDangerZone
          isDeleting={isDeleting}
          isRevokingSessions={isRevokingSessions}
          onConfirmDelete={handleConfirmDelete}
          onConfirmRevokeAllSessions={handleConfirmRevokeAllSessions}
        />
        {isEditing ? (
          <ProfileEditDialog
            feedback={feedback}
            form={form}
            hasCurrentPasswordRequirement={hasCurrentPasswordRequirement}
            isSaving={isSaving}
            onCancel={handleCancelEditing}
            onFieldChange={handleFieldChange}
            onSubmit={handleSubmitProfile}
          />
        ) : null}
      </div>
    </SiteLayout>
  );
};
