import type { ChangeEvent, FormEvent } from 'react';
import { useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';

import { registerUser, type RegisterPayload } from '../api/authApi';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useToast } from '@/shared/components/ui/toast';

import './RegisterPage.css';

type FormState = RegisterPayload;

const PASSWORD_RULE = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

export const RegisterPage = () => {
  useDocumentTitle('Inscription');

  const navigate = useNavigate();
  const toast = useToast();
  const [form, setForm] = useState<FormState>({
    email: '',
    password: '',
    confirmPassword: '',
    firstName: '',
    lastName: '',
    birthDate: '',
    phoneNumber: '',
    gender: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [errorDetails, setErrorDetails] = useState<string[]>([]);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const hasErrorDetails = (value: unknown): value is Error & { details: string[] } =>
    typeof value === 'object' &&
    value !== null &&
    'details' in value &&
    Array.isArray((value as { details?: unknown }).details);

  const parsedErrorDetails = useMemo(
    () => (errorDetails.length > 0 ? [...errorDetails] : []),
    [errorDetails],
  );

  const handleChange = (event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = event.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setErrorDetails([]);

    if (form.password !== form.confirmPassword) {
      setError('Les mots de passe doivent etre identiques.');
      try { toast.show('Les mots de passe doivent etre identiques.', { variant: 'error' }); } catch {}
      return;
    }

    if (!PASSWORD_RULE.test(form.password)) {
      setError(
        'Le mot de passe doit contenir au moins 8 caracteres, une majuscule et un chiffre.',
      );
      try { toast.show('Le mot de passe doit contenir au moins 8 caracteres, une majuscule et un chiffre.', { variant: 'error' }); } catch {}
      return;
    }

    setLoading(true);

    try {
      if (!form.gender) {
        setError('Veuillez sélectionner une option pour le champ sexe.');
        return;
      }

      await registerUser(form);
      try { toast.show('Compte cr��. V�rifiez vos emails pour activer votre compte.', { variant: 'success' }); } catch {}
      navigate('/login', { state: { registered: true } });
    } catch (submissionError) {
      console.error(submissionError);
      if (submissionError instanceof Error) {
        setError(
          submissionError.message ||
            "Impossible de finaliser l'inscription pour le moment.",
        );

        if (hasErrorDetails(submissionError)) {
          setErrorDetails(submissionError.details);
        }
      } else {
        setError("Impossible de finaliser l'inscription pour le moment.");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <SiteLayout headerVariant="light">
      <div className="register-page">
        <section className="register-intro">
          <p className="register-intro__eyebrow">Rejoindre Hociatec</p>
          <h1 className="register-intro__title">
            Créez votre espace client et accédez à nos services numériques
          </h1>
          <p className="register-intro__subtitle">
            En quelques minutes, activez un compte sécurisé pour piloter vos projets, suivre vos
            demandes de support et collaborer avec notre équipe d&apos;experts.
          </p>
          <ul className="register-highlights">
            <li>Suivi de vos projets en temps réel</li>
            <li>Support prioritaire et notifications personnalisées</li>
            <li>Tableaux de bord et documentations centralisés</li>
          </ul>
          <p className="register-intro__switch">
            Déjà client ?{' '}
            <Link to="/login" className="register-intro__switch-link">
              Se connecter
            </Link>
          </p>
        </section>

        <section className="register-form-card" aria-labelledby="register-form-title">
          <header className="register-form-card__header">
            <h2 id="register-form-title">Informations de compte</h2>
            <p>Complétez ce formulaire pour créer votre espace sécurisé.</p>
          </header>
          <form className="register-form" onSubmit={handleSubmit} noValidate>
            {error ? (
              <div className="register-form__alert">
                <p>{error}</p>
                {parsedErrorDetails.map((detail) => (
                  <p key={detail} className="register-form__alert-detail">
                    {detail}
                  </p>
                ))}
              </div>
            ) : null}
            <div className="register-form__grid">
              <label className="register-form__field">
                <span>Prénom</span>
                <input
                  name="firstName"
                  type="text"
                  autoComplete="given-name"
                  value={form.firstName}
                  onChange={handleChange}
                  maxLength={50}
                  required
                />
              </label>
              <label className="register-form__field">
                <span>Nom</span>
                <input
                  name="lastName"
                  type="text"
                  autoComplete="family-name"
                  value={form.lastName}
                  onChange={handleChange}
                  maxLength={50}
                  required
                />
              </label>
            </div>
            <label className="register-form__field">
              <span>Adresse e-mail</span>
              <input
                name="email"
                type="email"
                autoComplete="email"
                value={form.email}
                onChange={handleChange}
                required
              />
            </label>
            
            
            <div className="register-form__grid">
              <label className="register-form__field">
                <span>Date de naissance</span>
                <input
                  name="birthDate"
                  type="date"
                  value={form.birthDate}
                  onChange={handleChange}
                  required
                />
              </label>
              <label className="register-form__field">
                <span>Numéro de téléphone</span>
                <input
                  name="phoneNumber"
                  type="tel"
                  autoComplete="tel"
                  value={form.phoneNumber}
                  onChange={handleChange}
                  maxLength={20}
                  required
                />
              </label>
              <label className="register-form__field">
                <span>Sexe</span>
                <select
                  name="gender"
                  value={form.gender}
                  onChange={handleChange}
                  required
                  className="register-form__select"
                >
                  <option value="" disabled>
                    Sélectionnez une option
                  </option>
                  <option value="homme">Homme</option>
                  <option value="femme">Femme</option>
                  <option value="autre">Autre</option>
                </select>
              </label>
            </div>
            <div className="register-form__grid">
              <label className="register-form__field">
                <span>Mot de passe</span>
                <div className="register-form__password-wrapper">
                  <input
                    name="password"
                    type={showPassword ? 'text' : 'password'}
                    autoComplete="new-password"
                    value={form.password}
                    onChange={handleChange}
                    minLength={8}
                    required
                  />
                  <button
                    type="button"
                    className="register-form__password-toggle"
                    onClick={() => setShowPassword((prev) => !prev)}
                    aria-label={showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
                  >
                    {showPassword ? 'Masquer' : 'Afficher'}
                  </button>
                </div>
              </label>
              <label className="register-form__field">
                <span>Confirmation</span>
                <div className="register-form__password-wrapper">
                  <input
                    name="confirmPassword"
                    type={showConfirmPassword ? 'text' : 'password'}
                    autoComplete="new-password"
                    value={form.confirmPassword}
                    onChange={handleChange}
                    minLength={8}
                    required
                  />
                  <button
                    type="button"
                    className="register-form__password-toggle"
                    onClick={() => setShowConfirmPassword((prev) => !prev)}
                    aria-label={
                      showConfirmPassword
                        ? 'Masquer la confirmation du mot de passe'
                        : 'Afficher la confirmation du mot de passe'
                    }
                  >
                    {showConfirmPassword ? 'Masquer' : 'Afficher'}
                  </button>
                </div>
              </label>
            </div>
            <div className="register-form__guidelines">
              <p>Le mot de passe doit respecter les critères suivants :</p>
              <ul>
                <li>Au moins 8 caracteres</li>
                <li>Au moins une lettre majuscule</li>
                <li>Au moins un chiffre</li>
              </ul>
            </div>
            <button className="register-form__submit" type="submit" disabled={loading}>
              {loading ? 'Création en cours...' : 'Créer mon espace'}
            </button>
          </form>
        </section>
      </div>
    </SiteLayout>
  );
};
