import type { ChangeEvent, FormEvent } from 'react';

import { BlockingModal } from '@/shared/components/ui/BlockingModal';
import type { ProfileFormState } from '../hooks/useProfileController';
import type { ProfileFeedback } from '../lib/profileFormatters';

type ProfileEditDialogProps = {
  feedback: ProfileFeedback;
  form: ProfileFormState;
  hasCurrentPasswordRequirement: boolean;
  isSaving: boolean;
  onCancel: () => void;
  onFieldChange: (event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export const ProfileEditDialog = ({
  feedback,
  form,
  hasCurrentPasswordRequirement,
  isSaving,
  onCancel,
  onFieldChange,
  onSubmit,
}: ProfileEditDialogProps) => (
  <BlockingModal
    labelledBy="profile-edit-title"
    describedBy="profile-edit-description"
    panelClassName="profile-dialog__panel"
  >
    <header className="profile-dialog__header">
      <div>
        <h2 id="profile-edit-title">Modifier le profil</h2>
        <p id="profile-edit-description">
          Mettez à jour vos informations de compte. Le mot de passe actuel est demandé pour les
          changements sensibles.
        </p>
      </div>
    </header>

    {feedback?.type === 'error' ? (
      <div className="profile-feedback profile-feedback--error" role="alert">
        <p>{feedback.message}</p>
        {feedback.details?.map((detail) => (
          <p key={detail} className="profile-feedback__detail">
            {detail}
          </p>
        ))}
      </div>
    ) : null}

    <form className="profile-form" onSubmit={onSubmit} aria-busy={isSaving}>
      <div className="profile-form__fields">
        <label className="profile-form__field">
          <span>Prénom</span>
          <input
            type="text"
            name="firstName"
            value={form.firstName}
            onChange={onFieldChange}
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
            onChange={onFieldChange}
            maxLength={50}
            required
          />
        </label>
        <label className="profile-form__field">
          <span>Adresse e-mail</span>
          <input type="email" name="email" value={form.email} onChange={onFieldChange} required />
        </label>
        <label className="profile-form__field">
          <span>Date de naissance</span>
          <input
            type="date"
            name="birthDate"
            value={form.birthDate}
            onChange={onFieldChange}
            required
          />
        </label>
        <label className="profile-form__field">
          <span>Numéro de téléphone</span>
          <input
            type="tel"
            name="phoneNumber"
            value={form.phoneNumber}
            onChange={onFieldChange}
            maxLength={20}
            required
          />
        </label>
        <label className="profile-form__field">
          <span>Sexe</span>
          <select
            name="gender"
            value={form.gender}
            onChange={onFieldChange}
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
            onChange={onFieldChange}
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
              onChange={onFieldChange}
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
          onClick={onCancel}
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
  </BlockingModal>
);
