import type { ChangeEvent, FormEvent } from 'react';
import { Link } from 'react-router';
import { useState } from 'react';

import { requestPasswordReset } from '../api/authApi';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { PageContainer } from '../../../shared/components/PageContainer';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useToast } from '@/shared/components/ui/toast';
import { FeedbackMessage } from '@/shared/components/ui/page-state';

import './LoginPage.css';

export const ForgotPasswordPage = () => {
  useDocumentTitle('Mot de passe oublié');

  const toast = useToast();
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
    setEmail(event.target.value);
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoading(true);
    setError(null);
    setMessage(null);

    try {
      const response = await requestPasswordReset(email);
      const nextMessage = response.message ?? 'Si un compte existe, un e-mail vient d’être envoyé.';
      setMessage(nextMessage);
      try {
        toast.show(nextMessage, { variant: 'success' });
      } catch {}
    } catch (submissionError) {
      const nextError =
        submissionError instanceof Error
          ? submissionError.message
          : 'Impossible de traiter votre demande pour le moment.';
      setError(nextError);
      try {
        toast.show(nextError, { variant: 'error' });
      } catch {}
    } finally {
      setLoading(false);
    }
  };

  return (
    <SiteLayout headerVariant="light">
      <PageContainer
        title="Mot de passe oublié"
        headerActions={
          <p className="notice">
            Vous avez retrouvé votre mot de passe ?{' '}
            <Link to="/login" className="link-button">
              Retour à la connexion
            </Link>
          </p>
        }
      >
        <p className="login-form__intro">
          Saisissez l&apos;adresse e-mail liée à votre compte. Si elle existe, vous recevrez un lien
          pour définir un nouveau mot de passe.
        </p>
        {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}
        {error && <FeedbackMessage>{error}</FeedbackMessage>}
        <form className="card__content" onSubmit={handleSubmit}>
          <div className="form-field">
            <label htmlFor="email">Email</label>
            <input
              id="email"
              name="email"
              type="email"
              autoComplete="email"
              value={email}
              onChange={handleChange}
              required
            />
          </div>
          <button className="button" type="submit" disabled={loading}>
            {loading ? 'Envoi en cours...' : 'Envoyer le lien de réinitialisation'}
          </button>
        </form>
      </PageContainer>
    </SiteLayout>
  );
};
