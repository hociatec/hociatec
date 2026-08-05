import type { ChangeEvent, FormEvent } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { useState } from 'react';

import { resetPassword } from '../api/authApi';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { useToast } from '@/shared/components/ui/toast';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { logger } from '@/shared/lib/logger';

import './LoginPage.css';

const PASSWORD_RULE = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

interface FormState {
  password: string;
  confirmPassword: string;
}

export const ResetPasswordPage = () => {
  useDocumentTitle('Réinitialiser le mot de passe');

  const { token } = useParams<{ token: string }>();
  const navigate = useNavigate();
  const toast = useToast();
  const [form, setForm] = useState<FormState>({
    password: '',
    confirmPassword: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
    const { name, value } = event.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setMessage(null);

    if (!token) {
      const nextError = 'Lien de réinitialisation invalide.';
      setError(nextError);
      return;
    }

    if (form.password !== form.confirmPassword) {
      const nextError = 'Les mots de passe doivent être identiques.';
      setError(nextError);
      try {
        toast.show(nextError, { variant: 'error' });
      } catch (error) {
        logger.warn('Unable to display password mismatch toast.', { error });
      }
      return;
    }

    if (!PASSWORD_RULE.test(form.password)) {
      const nextError =
        'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.';
      setError(nextError);
      try {
        toast.show(nextError, { variant: 'error' });
      } catch (error) {
        logger.warn('Unable to display password rule toast.', { error });
      }
      return;
    }

    setLoading(true);

    try {
      const response = await resetPassword(token, form);
      const nextMessage = response.message ?? 'Votre mot de passe a été réinitialisé avec succès.';
      setMessage(nextMessage);
      try {
        toast.show(nextMessage, { variant: 'success' });
      } catch (error) {
        logger.warn('Unable to display password reset success toast.', { error });
      }
      window.setTimeout(() => navigate('/login', { replace: true }), 1200);
    } catch (submissionError) {
      const nextError =
        submissionError instanceof Error
          ? submissionError.message
          : 'Impossible de réinitialiser le mot de passe pour le moment.';
      setError(nextError);
      try {
        toast.show(nextError, { variant: 'error' });
      } catch (error) {
        logger.warn('Unable to display password reset error toast.', { error });
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <SiteLayout headerVariant="light">
      <PageContainer
        title="Nouveau mot de passe"
        headerActions={
          <p className="notice">
            Vous vous souvenez de votre mot de passe ?{' '}
            <Link to="/login" className="link-button">
              Retour à la connexion
            </Link>
          </p>
        }
      >
        <p className="login-form__intro">
          Choisissez un nouveau mot de passe sécurisé pour votre compte Hociatec.
        </p>
        {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}
        {error && <FeedbackMessage>{error}</FeedbackMessage>}
        <form className="card__content" onSubmit={handleSubmit}>
          <div className="form-field">
            <label htmlFor="password">Nouveau mot de passe</label>
            <div className="login-form__password-wrapper">
              <input
                id="password"
                name="password"
                type={showPassword ? 'text' : 'password'}
                autoComplete="new-password"
                value={form.password}
                onChange={handleChange}
                required
              />
              <button
                type="button"
                className="login-form__password-toggle"
                onClick={() => setShowPassword((prev) => !prev)}
                aria-label={showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
              >
                {showPassword ? 'Masquer' : 'Afficher'}
              </button>
            </div>
          </div>
          <div className="form-field">
            <label htmlFor="confirmPassword">Confirmation</label>
            <div className="login-form__password-wrapper">
              <input
                id="confirmPassword"
                name="confirmPassword"
                type={showConfirmPassword ? 'text' : 'password'}
                autoComplete="new-password"
                value={form.confirmPassword}
                onChange={handleChange}
                required
              />
              <button
                type="button"
                className="login-form__password-toggle"
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
          </div>
          <div className="login-form__guidelines">
            <p>Le mot de passe doit contenir au minimum :</p>
            <ul>
              <li>8 caractères</li>
              <li>une majuscule</li>
              <li>un chiffre</li>
            </ul>
          </div>
          <button className="button" type="submit" disabled={loading}>
            {loading ? 'Réinitialisation en cours...' : 'Enregistrer mon nouveau mot de passe'}
          </button>
        </form>
      </PageContainer>
    </SiteLayout>
  );
};
