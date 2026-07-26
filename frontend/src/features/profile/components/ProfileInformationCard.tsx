import type { ChangeEvent, FormEvent } from 'react';

import type { AuthUser } from '../../../shared/types/auth';
import { formatGender } from '../lib/profileFormatters';
import type { ProfileFormState } from '../hooks/useProfileController';

type ProfileInformationCardProps = {
  user: AuthUser;
  isEditing: boolean;
  isSaving: boolean;
  form: ProfileFormState;
  formattedRoles: string;
  formattedBirthDate: string;
  hasCurrentPasswordRequirement: boolean;
  onFieldChange: (event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => void;
  onStartEditing: () => void;
  onCancelEditing: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export const ProfileInformationCard = ({
  user,
  isEditing,
  isSaving,
  form,
  formattedRoles,
  formattedBirthDate,
  hasCurrentPasswordRequirement,
  onFieldChange,
  onStartEditing,
  onCancelEditing,
  onSubmit,
}: ProfileInformationCardProps) => (
  <section
    className="profile-card profile-card--highlight profile-card--main"
    aria-labelledby="profile-info-heading"
  >
    <div className="profile-card__header">
      <h2 id="profile-info-heading">Informations personnelles</h2>
      {!isEditing ? (
        <button type="button" className="profile-card__edit" onClick={onStartEditing}>
          Modifier
        </button>
      ) : null}
    </div>

    {isEditing ? (
      <form className="profile-form" onSubmit={onSubmit}>
        <div className="profile-form__fields">
          <label className="profile-form__field">
            <span>Prénom</span>
            <input type="text" name="firstName" value={form.firstName} onChange={onFieldChange} maxLength={50} required />
          </label>
          <label className="profile-form__field">
            <span>Nom</span>
            <input type="text" name="lastName" value={form.lastName} onChange={onFieldChange} maxLength={50} required />
          </label>
          <label className="profile-form__field">
            <span>Adresse e-mail</span>
            <input type="email" name="email" value={form.email} onChange={onFieldChange} required />
          </label>
          <label className="profile-form__field">
            <span>Date de naissance</span>
            <input type="date" name="birthDate" value={form.birthDate} onChange={onFieldChange} required />
          </label>
          <label className="profile-form__field">
            <span>Numéro de téléphone</span>
            <input type="tel" name="phoneNumber" value={form.phoneNumber} onChange={onFieldChange} maxLength={20} required />
          </label>
          <label className="profile-form__field">
            <span>Sexe</span>
            <select name="gender" value={form.gender} onChange={onFieldChange} className="profile-form__select" required>
              <option value="" disabled>Sélectionnez</option>
              <option value="homme">Homme</option>
              <option value="femme">Femme</option>
              <option value="autre">Autre</option>
            </select>
          </label>
          <label className="profile-form__field">
            <span>Nouveau mot de passe (optionnel)</span>
            <input type="password" name="password" value={form.password} onChange={onFieldChange} minLength={8} placeholder="Laisser vide pour conserver l'actuel" />
          </label>
          {hasCurrentPasswordRequirement ? (
            <label className="profile-form__field">
              <span>Mot de passe actuel</span>
              <input type="password" name="currentPassword" value={form.currentPassword} onChange={onFieldChange} autoComplete="current-password" required />
            </label>
          ) : null}
        </div>
        <div className="profile-form__actions">
          <button type="button" className="profile-form__button profile-form__button--ghost" onClick={onCancelEditing} disabled={isSaving}>Annuler</button>
          <button type="submit" className="profile-form__button profile-form__button--primary" disabled={isSaving}>{isSaving ? 'Enregistrement...' : 'Enregistrer'}</button>
        </div>
      </form>
    ) : (
      <div className="profile-detail-groups">
        <dl className="profile-details">
          <div><dt>Nom complet</dt><dd>{user.firstName} {user.lastName}</dd></div>
          <div><dt>Date de naissance</dt><dd>{formattedBirthDate}</dd></div>
          <div><dt>Sexe</dt><dd>{formatGender(user.gender)}</dd></div>
        </dl>
        <dl className="profile-details">
          <div><dt>Adresse e-mail</dt><dd>{user.email}</dd></div>
          <div><dt>Téléphone</dt><dd>{user.phoneNumber}</dd></div>
          <div><dt>Rôle</dt><dd>{formattedRoles}</dd></div>
        </dl>
      </div>
    )}
  </section>
);
