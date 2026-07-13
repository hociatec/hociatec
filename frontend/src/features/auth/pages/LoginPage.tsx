import type { ChangeEvent, FormEvent } from 'react';
import { useEffect, useMemo, useState } from 'react';
import { isAxiosError } from 'axios';
import { Link, useLocation, useNavigate } from 'react-router-dom';

import { useAuth } from '../hooks/useAuth';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { PageContainer } from '../../../shared/components/PageContainer';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useToast } from '@/shared/components/ui/toast';

interface LoginFormState {
  email: string;
  password: string;
}

interface LocationState {
  registered?: boolean;
}

export const LoginPage = () => {
  useDocumentTitle('Connexion');

  const navigate = useNavigate();
  const location = useLocation();
  const { login, status } = useAuth();
  const toast = useToast();

  const [form, setForm] = useState<LoginFormState>({
    email: '',
    password: '',
  });
  const [error, setError] = useState<string | null>(null);
  const [errorDetails, setErrorDetails] = useState<string[]>([]);
  const [notice, setNotice] = useState<string | null>(null);

  const parsedErrorDetails = useMemo(
    () => (errorDetails.length > 0 ? [...errorDetails] : []),
    [errorDetails],
  );

  useEffect(() => {
    const state = location.state as LocationState | null;

    if (state?.registered) {
      setNotice('Votre compte est prêt. Vous pouvez désormais vous connecter.');
      try { toast.show('Compte créé. Vérifiez vos emails pour activer votre compte.', { variant: 'info' }); } catch {}
    }
  }, [location.state]);

  const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
    const { name, value } = event.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setErrorDetails([]);
    setNotice(null);

    try {
      await login(form);
      const state: any = location.state as any;
      const redirectTo = (state?.redirectTo as string | undefined)
        || (state?.from?.pathname as string | undefined);
      const redirectState = state?.redirectState as any | undefined;
      try { toast.show('Connexion réussie. Bienvenue !', { variant: 'success' }); } catch {}
      if (redirectTo) {
        navigate(redirectTo, { replace: true, state: redirectState });
      } else {
        navigate('/', { replace: true });
      }
    } catch (loginError) {
      console.error(loginError);

      if (isAxiosError(loginError) && loginError.response?.data?.message) {
        const msg = String(loginError.response.data.message);
        setError(msg);
        const details = loginError.response.data.details;
        if (Array.isArray(details)) {
          setErrorDetails(details.map((detail: unknown) => String(detail)));
        }
        try { toast.show(msg, { variant: 'error' }); } catch {}
      } else if (loginError instanceof Error) {
        setError(loginError.message);
        try { toast.show(loginError.message, { variant: 'error' }); } catch {}
      } else {
        const msg = 'Impossible de vérifier vos identifiants.';
        setError(msg);
        try { toast.show(msg, { variant: 'error' }); } catch {}
      }
    }
  };

  return (
    <SiteLayout headerVariant="light">
      <PageContainer
        title="Connexion"
        headerActions={
          <p className="notice">
            Pas encore de compte ?{' '}
            <Link to="/register" className="link-button">
              Créer un compte
            </Link>
          </p>
        }
      >
        {notice && (
          <div className="register-form__alert" role="status">
            {notice}
          </div>
        )}
        {error && (
          <div className="register-form__alert register-form__alert--error" role="alert">
            <p>{error}</p>
            {parsedErrorDetails.length > 0 && (
              <ul className="mt-2 list-disc pl-5 text-sm">
                {parsedErrorDetails.map((detail) => (
                  <li key={detail}>{detail}</li>
                ))}
              </ul>
            )}
          </div>
        )}
        <form className="card__content" onSubmit={handleSubmit}>
          <div className="form-field">
            <label htmlFor="email">Email</label>
            <input
              id="email"
              name="email"
              type="email"
              autoComplete="email"
              value={form.email}
              onChange={handleChange}
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="password">Mot de passe</label>
            <input
              id="password"
              name="password"
              type="password"
              autoComplete="current-password"
              value={form.password}
              onChange={handleChange}
              required
            />
          </div>
          <button className="button" type="submit" disabled={status === 'loading'}>
            {status === 'loading' ? 'Connexion en cours...' : 'Se connecter'}
          </button>
        </form>
      </PageContainer>
    </SiteLayout>
  );
};
