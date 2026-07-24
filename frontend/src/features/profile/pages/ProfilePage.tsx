import type { ChangeEvent, FormEvent } from 'react';
import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '../../../shared/components/ui/alert-dialog';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useAuth } from '../../auth/hooks/useAuth';

import './ProfilePage.css';

type FeedbackState =
  | {
      type: 'success' | 'error';
      message: string;
      details?: string[];
    }
  | null;

const PASSWORD_RULE = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

const normalizeEmail = (email: string) => email.trim().toLowerCase();

const formatRole = (role: string) => {
  switch (role) {
    case 'ROLE_ADMIN':
      return 'Administrateur';
    case 'ROLE_MANAGER':
      return 'Manager';
    default:
      return 'Utilisateur';
  }
};

const formatGender = (gender: string) => {
  switch (gender) {
    case 'homme':
      return 'Homme';
    case 'femme':
      return 'Femme';
    case 'autre':
      return 'Autre';
    default:
      return 'Non renseigné';
  }
};

const extractErrorDetails = (error: unknown): string[] => {
  if (error && typeof error === 'object' && 'details' in error) {
    const details = (error as { details?: unknown }).details;
    if (Array.isArray(details)) {
      return details.map((detail) => String(detail));
    }
  }

  return [];
};

const extractErrorMessage = (error: unknown, fallback: string) =>
  error instanceof Error ? error.message : fallback;

export const ProfilePage = () => {
  useDocumentTitle('Profil');

  const { user, updateProfile, deleteAccount } = useAuth();
  const navigate = useNavigate();

  const [feedback, setFeedback] = useState<FeedbackState>(null);
  const [isEditing, setIsEditing] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [form, setForm] = useState({
    firstName: '',
    lastName: '',
    email: '',
    birthDate: '',
    phoneNumber: '',
    gender: '',
    password: '',
    currentPassword: '',
  });

  useEffect(() => {
    if (user) {
      setForm({
        firstName: user.firstName,
        lastName: user.lastName,
        email: user.email,
        birthDate: user.birthDate,
        phoneNumber: user.phoneNumber,
        gender: user.gender,
        password: '',
        currentPassword: '',
      });
    }
  }, [user]);

  const initials = useMemo(() => {
    if (!user) return '';

    return [user.firstName, user.lastName]
      .filter(Boolean)
      .map((segment) => segment.trim().charAt(0).toUpperCase())
      .slice(0, 2)
      .join('');
  }, [user]);

  const formattedRoles = useMemo(() => {
    if (!user?.roles?.length) return 'Utilisateur';

    return Array.from(new Set(user.roles)).map(formatRole).join(', ');
  }, [user]);

  const formattedBirthDate = useMemo(() => {
    if (!user?.birthDate) return 'Non renseignée';

    const date = new Date(user.birthDate);
    if (Number.isNaN(date.getTime())) return user.birthDate;

    return new Intl.DateTimeFormat('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: '2-digit',
    }).format(date);
  }, [user]);

  const resetForm = () => {
    if (!user) return;

    setForm({
      firstName: user.firstName,
      lastName: user.lastName,
      email: user.email,
      birthDate: user.birthDate,
      phoneNumber: user.phoneNumber,
      gender: user.gender,
      password: '',
      currentPassword: '',
    });
  };

  const handleFieldChange = (
    event: ChangeEvent<HTMLInputElement | HTMLSelectElement>,
  ) => {
    const { name, value } = event.target;
    setForm((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleStartEditing = () => {
    resetForm();
    setFeedback(null);
    setIsEditing(true);
  };

  const handleCancelEditing = () => {
    resetForm();
    setFeedback(null);
    setIsEditing(false);
  };

  const handleSubmitProfile = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (!user) return;

    if (!form.gender) {
      setFeedback({
        type: 'error',
        message: 'Veuillez sélectionner une option pour le champ sexe.',
      });
      return;
    }

    const hasEmailChanged = normalizeEmail(form.email) !== normalizeEmail(user.email);
    const hasNewPassword = form.password.trim() !== '';
    const requiresCurrentPassword = hasEmailChanged || hasNewPassword;

    if (hasNewPassword && !PASSWORD_RULE.test(form.password)) {
      setFeedback({
        type: 'error',
        message: 'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.',
      });
      return;
    }

    if (requiresCurrentPassword && form.currentPassword.trim() === '') {
      setFeedback({
        type: 'error',
        message: 'Veuillez saisir votre mot de passe actuel.',
      });
      return;
    }

    setIsSaving(true);
    setFeedback(null);

    try {
      await updateProfile({
        firstName: form.firstName,
        lastName: form.lastName,
        email: form.email,
        birthDate: form.birthDate,
        phoneNumber: form.phoneNumber,
        gender: form.gender,
        password: hasNewPassword ? form.password : undefined,
        currentPassword: requiresCurrentPassword ? form.currentPassword : undefined,
      });

      setFeedback({
        type: 'success',
        message: 'Votre profil a été mis à jour avec succès.',
      });
      setIsEditing(false);
      setForm((prev) => ({
        ...prev,
        password: '',
        currentPassword: '',
      }));
    } catch (error) {
      setFeedback({
        type: 'error',
        message: extractErrorMessage(
          error,
          'Impossible de mettre à jour votre profil pour le moment.',
        ),
        details: extractErrorDetails(error),
      });
    } finally {
      setIsSaving(false);
    }
  };

  const handleConfirmDelete = async () => {
    setFeedback(null);
    setIsDeleting(true);

    try {
      await deleteAccount();
      navigate('/', { replace: true });
    } catch (error) {
      setFeedback({
        type: 'error',
        message: extractErrorMessage(
          error,
          'Impossible de supprimer votre compte actuellement.',
        ),
        details: extractErrorDetails(error),
      });
      setIsDeleting(false);
    }
  };

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
          <div
            className={`profile-feedback profile-feedback--${feedback.type}`}
            role="status"
          >
            <p>{feedback.message}</p>
            {feedback.details?.map((detail) => (
              <p key={detail} className="profile-feedback__detail">
                {detail}
              </p>
            ))}
          </div>
        ) : null}

        <div className="profile-grid">
          <aside className="profile-summary-card" aria-label="Résumé du profil">
            <span className="profile-avatar" aria-hidden="true">
              {initials}
            </span>
            <div>
              <strong>{user.firstName} {user.lastName}</strong>
              <span>{user.email}</span>
            </div>
          </aside>

          <section
            className="profile-card profile-card--highlight profile-card--main"
            aria-labelledby="profile-info-heading"
          >
            <div className="profile-card__header">
              <h2 id="profile-info-heading">Informations personnelles</h2>
              {!isEditing ? (
                <button
                  type="button"
                  className="profile-card__edit"
                  onClick={handleStartEditing}
                >
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
                  {(normalizeEmail(form.email) !== normalizeEmail(user.email) || form.password.trim() !== '') ? (
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
                    <dd>{user.firstName} {user.lastName}</dd>
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

        <section className="profile-danger-zone" aria-labelledby="profile-danger-heading">
          <div>
            <h2 id="profile-danger-heading">Zone sensible</h2>
            <p>La suppression du compte est définitive et nécessite une confirmation.</p>
          </div>
          <AlertDialog>
            <AlertDialogTrigger asChild>
              <button
                type="button"
                className="profile-action-button profile-action-button--danger"
                disabled={isDeleting}
              >
                Supprimer mon compte
              </button>
            </AlertDialogTrigger>
            <AlertDialogContent>
              <AlertDialogHeader>
                <AlertDialogTitle>Confirmer la suppression</AlertDialogTitle>
                <AlertDialogDescription>
                  Cette action entraîne la suppression de votre compte et de vos accès aux services
                  Hociatec. Un membre de notre équipe vous recontactera pour finaliser la procédure.
                </AlertDialogDescription>
              </AlertDialogHeader>
              <AlertDialogFooter>
                <AlertDialogCancel disabled={isDeleting}>Annuler</AlertDialogCancel>
                <AlertDialogAction onClick={handleConfirmDelete} disabled={isDeleting}>
                  {isDeleting ? 'Suppression...' : 'Confirmer'}
                </AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        </section>
      </div>
    </SiteLayout>
  );
};
