import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { formatGender } from '../lib/profileFormatters';
import { ProfileDangerZone } from '../components/ProfileDangerZone';
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

          <section
            className="profile-card profile-card--highlight profile-card--main"
            aria-labelledby="profile-info-heading"
          >
            <div className="profile-card__header">
              <h2 id="profile-info-heading">Informations personnelles</h2>
              {!isEditing ? (
                <button type="button" className="profile-card__edit" onClick={handleStartEditing}>
                  Modifier
                </button>
              ) : null}
            </div>

            {isEditing ? (
              <form className="profile-form" onSubmit={handleSubmitProfile}>
                <div className="profile-form__fields">
                  <label className="profile-form__field">
                    <span>Prénom</span>
                    <input
                      type="text"
                      name="firstName"
                      value={form.firstName}
                      onChange={handleFieldChange}
                      maxLength={50}
                      required
                    />
                  </label>
                  <label className="profile-form__field">
                    <span>Nom</span>
                    <input
                      type="text"
                      name="lastName"
                      value={form.lastName}
                      onChange={handleFieldChange}
                      maxLength={50}
                      required
                    />
                  </label>
                  <label className="profile-form__field">
                    <span>Adresse e-mail</span>
                    <input
                      type="email"
                      name="email"
                      value={form.email}
                      onChange={handleFieldChange}
                      required
                    />
                  </label>
                  <label className="profile-form__field">
                    <span>Date de naissance</span>
                    <input
                      type="date"
                      name="birthDate"
                      value={form.birthDate}
                      onChange={handleFieldChange}
                      required
                    />
                  </label>
                  <label className="profile-form__field">
                    <span>Numéro de téléphone</span>
                    <input
                      type="tel"
                      name="phoneNumber"
                      value={form.phoneNumber}
                      onChange={handleFieldChange}
                      maxLength={20}
                      required
                    />
                  </label>
                  <label className="profile-form__field">
                    <span>Sexe</span>
                    <select
                      name="gender"
                      value={form.gender}
                      onChange={handleFieldChange}
                      className="profile-form__select"
                      required
                    >
                      <option value="" disabled>
                        Sélectionnez
                      </option>
                      <option value="homme">Homme</option>
                      <option value="femme">Femme</option>
                      <option value="autre">Autre</option>
                    </select>
                  </label>
                  <label className="profile-form__field">
                    <span>Nouveau mot de passe (optionnel)</span>
                    <input
                      type="password"
                      name="password"
                      value={form.password}
                      onChange={handleFieldChange}
                      minLength={8}
                      placeholder="Laisser vide pour conserver l'actuel"
                    />
                  </label>
                  {hasCurrentPasswordRequirement ? (
                    <label className="profile-form__field">
                      <span>Mot de passe actuel</span>
                      <input
                        type="password"
                        name="currentPassword"
                        value={form.currentPassword}
                        onChange={handleFieldChange}
                        autoComplete="current-password"
                        required
                      />
                    </label>
                  ) : null}
                </div>
                <div className="profile-form__actions">
                  <button
                    type="button"
                    className="profile-form__button profile-form__button--ghost"
                    onClick={handleCancelEditing}
                    disabled={isSaving}
                  >
                    Annuler
                  </button>
                  <button
                    type="submit"
                    className="profile-form__button profile-form__button--primary"
                    disabled={isSaving}
                  >
                    {isSaving ? 'Enregistrement...' : 'Enregistrer'}
                  </button>
                </div>
              </form>
            ) : (
              <div className="profile-detail-groups">
                <dl className="profile-details">
                  <div>
                    <dt>Nom complet</dt>
                    <dd>
                      {user.firstName} {user.lastName}
                    </dd>
                  </div>
                  <div>
                    <dt>Date de naissance</dt>
                    <dd>{formattedBirthDate}</dd>
                  </div>
                  <div>
                    <dt>Sexe</dt>
                    <dd>{formatGender(user.gender)}</dd>
                  </div>
                </dl>
                <dl className="profile-details">
                  <div>
                    <dt>Adresse e-mail</dt>
                    <dd>{user.email}</dd>
                  </div>
                  <div>
                    <dt>Téléphone</dt>
                    <dd>{user.phoneNumber}</dd>
                  </div>
                  <div>
                    <dt>Rôle</dt>
                    <dd>{formattedRoles}</dd>
                  </div>
                </dl>
              </div>
            )}
          </section>
        </div>

        <ProfileDangerZone isDeleting={isDeleting} onConfirmDelete={handleConfirmDelete} />
      </div>
    </SiteLayout>
  );
};
