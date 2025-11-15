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
      });
    }
  }, [user]);

  const initials = useMemo(() => {
    if (!user) {
      return '';
    }

    const segments = [user.firstName, user.lastName].filter(Boolean);
    return segments
      .map((segment) => segment.trim().charAt(0).toUpperCase())
      .slice(0, 2)
      .join('');
  }, [user]);

  const formattedRoles = useMemo(() => {
    if (!user?.roles?.length) {
      return 'Utilisateur';
    }

    const uniqueRoles = Array.from(new Set(user.roles)).map(formatRole);
    return uniqueRoles.join(', ');
  }, [user]);

  const formattedBirthDate = useMemo(() => {
    if (!user?.birthDate) {
      return 'Non renseignée';
    }

    const date = new Date(user.birthDate);
    if (Number.isNaN(date.getTime())) {
      return user.birthDate;
    }

    return new Intl.DateTimeFormat('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: '2-digit',
    }).format(date);
  }, [user]);

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

  const resetForm = () => {
    if (!user) {
      return;
    }

    setForm({
      firstName: user.firstName,
      lastName: user.lastName,
      email: user.email,
      birthDate: user.birthDate,
      phoneNumber: user.phoneNumber,
      gender: user.gender,
      password: '',
    });
  };

  const handleCancelEditing = () => {
    resetForm();
    setFeedback(null);
    setIsEditing(false);
  };

  const handleSubmitProfile = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (!user) {
      return;
    }

    if (!form.gender) {
      setFeedback({
        type: 'error',
        message: 'Veuillez sélectionner une option pour le champ sexe.',
      });
      return;
    }

    if (form.password && !PASSWORD_RULE.test(form.password)) {
      setFeedback({
        type: 'error',
        message:
          'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.',
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
        password: form.password ? form.password : undefined,
      });

      setFeedback({
        type: 'success',
        message: 'Votre profil a été mis à jour avec succès.',
      });
      setIsEditing(false);
      setForm((prev) => ({
        ...prev,
        password: '',
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

  if (!user) {
    return null;
  }

  return (
    <SiteLayout headerVariant="light">
      <div className="profile-page">
        <header className="profile-hero">
          <div className="profile-hero__identity">
            <span className="profile-avatar" aria-hidden="true">
              {initials}
            </span>
            <div>
              <p className="profile-hero__eyebrow">Mon espace</p>
              <h1 className="profile-hero__title">
                Bonjour {user.firstName} {user.lastName}
              </h1>
              <p className="profile-hero__subtitle">
                Retrouvez vos informations personnelles et pilotez vos services Hociatec.
              </p>
            </div>
          </div>
          <div className="profile-hero__actions">
            <button
              type="button"
              className="profile-action-button profile-action-button--primary"
              onClick={handleStartEditing}
              disabled={isEditing}
            >
              Modifier mon profil
            </button>
            <button
              type="button"
              className="profile-action-button profile-action-button--ghost"
              onClick={() => navigate('/profile/addresses')}
            >
              Gérer mes adresses
            </button>
            <button
              type="button"
              className="profile-action-button profile-action-button--ghost"
              onClick={() => navigate('/orders/me')}
            >
              Mes commandes
            </button>
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
                    Cette action entraine la suppression de votre compte et de vos acces aux services
                    Hociatec. Un membre de notre equipe vous recontactera pour finaliser la procedure.
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
          <section
            className="profile-card profile-card--highlight"
            aria-labelledby="profile-info-heading"
          >
            <h2 id="profile-info-heading">Informations personnelles</h2>
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
                  {/* Adresse gérée via page dédiée */}
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
                      placeholder="Laisser vide pour conserver l’actuel"
                    />
                  </label>
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
              <dl className="profile-details">
                <div>
                  <dt>Nom complet</dt>
                  <dd>
                    {user.firstName} {user.lastName}
                  </dd>
                </div>
                <div>
                  <dt>Adresse e-mail</dt>
                  <dd>{user.email}</dd>
                </div>
                <div>
                  <dt>Adresse postale</dt>
                  <dd>{user.address}</dd>
                </div>
                <div>
                  <dt>Code postal / Ville</dt>
                  <dd>
                    {user.postalCode} {user.city}
                  </dd>
                </div>
                <div>
                  <dt>Date de naissance</dt>
                  <dd>{formattedBirthDate}</dd>
                </div>
                <div>
                  <dt>Téléphone</dt>
                  <dd>{user.phoneNumber}</dd>
                </div>
                <div>
                  <dt>Sexe</dt>
                  <dd>{formatGender(user.gender)}</dd>
                </div>
                <div>
                  <dt>Rôles</dt>
                  <dd>{formattedRoles}</dd>
                </div>
              </dl>
            )}
          </section>
          <section className="profile-card" aria-labelledby="profile-security-heading">
            <h2 id="profile-security-heading">Sécurité et accès</h2>
            <ul className="profile-list">
              <li>
                Authentification sécurisée avec jeton personnel. Pensez à mettre à jour votre mot de
                passe régulièrement.
              </li>
              <li>
                Pour activer l&apos;authentification multi-facteurs, contactez votre interlocuteur
                Hociatec.
              </li>
              <li>
                Sur demande, nous pouvons fournir le journal des connexions associées à votre compte.
              </li>
            </ul>
          </section>
          <section className="profile-card" aria-labelledby="profile-support-heading">
            <h2 id="profile-support-heading">Support dédié</h2>
            <ul className="profile-list">
              <li>Support utilisateur disponible 24/7 : support@hociatec.com</li>
              <li>Escalade prioritaire pour les incidents critiques et bloqueurs</li>
              <li>Revues trimestrielles pour ajuster vos niveaux de service</li>
            </ul>
            <button
              type="button"
              className="profile-action-button profile-action-button--ghost"
              onClick={() => navigate('/')}
            >
              Retourner à l’accueil
            </button>
          </section>
        </div>
      </div>
    </SiteLayout>
  );
};
