import type { ChangeEvent, FormEvent } from 'react';
import { useEffect, useMemo, useState } from 'react';
import { isAxiosError } from 'axios';
import { Link, useLocation, useNavigate } from 'react-router-dom';

import { useAuth } from '../hooks/useAuth';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { PageContainer } from '../../../shared/components/PageContainer';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useToast } from '@/shared/components/ui/toast';

import './LoginPage.css';

interface LoginFormState {
  email: string;
  password: string;
  rememberMe: boolean;
}

interface LocationState {
  registered?: boolean;
}

export const LoginPage = () => {
  const REMEMBERED_EMAIL_KEY = 'hociatec.auth.remembered-email';
  useDocumentTitle('Connexion');

  const navigate = useNavigate();
  const location = useLocation();
  const { login, status } = useAuth();
  const toast = useToast();

  const [form, setForm] = useState<LoginFormState>({
    email: '',
    password: '',
    rememberMe: false,
  });
  const [error, setError] = useState<string | null>(null);
  const [errorDetails, setErrorDetails] = useState<string[]>([]);
  const [notice, setNotice] = useState<string | null>(null);
  const [showPassword, setShowPassword] = useState(false);

  const parsedErrorDetails = useMemo(
    () => (errorDetails.length > 0 ? [...errorDetails] : []),
    [errorDetails],
  );

  useEffect(() => {
    try {
      const rememberedEmail = window.localStorage.getItem(REMEMBERED_EMAIL_KEY);
      if (rememberedEmail) {
        setForm((prev) => ({ ...prev, email: rememberedEmail, rememberMe: true }));
      }
    } catch {
      /* noop */
    }
  }, [REMEMBERED_EMAIL_KEY]);

  useEffect(() => {
    const state = location.state as LocationState | null;

    if (state?.registered) {
      setNotice('Votre compte est prêt. Vous pouvez désormais vous connecter.');
      try { toast.show('Compte créé. Vérifiez vos emails pour activer votre compte.', { variant: 'info' }); } catch {}
    }
  }, [location.state]);

  const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
    const { name, value, checked, type } = event.target;
    setForm((prev) => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setErrorDetails([]);
    setNotice(null);

    try {
      try {
        if (form.rememberMe) {
          window.localStorage.setItem(REMEMBERED_EMAIL_KEY, form.email.trim());
        } else {
          window.localStorage.removeItem(REMEMBERED_EMAIL_KEY);
        }
      } catch {
        /* noop */
      }

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
            <div className="login-form__password-wrapper">
              <input
                id="password"
                name="password"
                type={showPassword ? 'text' : 'password'}
                autoComplete="current-password"
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
            <Link to="/forgot-password" className="login-form__help-link">
              Mot de passe oublié ?
            </Link>
          </div>
          <label className="login-form__remember">
            <input
              id="rememberMe"
              name="rememberMe"
              type="checkbox"
              checked={form.rememberMe}
              onChange={handleChange}
            />
            <span>Se souvenir de moi</span>
          </label>
          <button className="button" type="submit" disabled={status === 'loading'}>
            {status === 'loading' ? 'Connexion en cours...' : 'Se connecter'}
          </button>
        </form>
      </PageContainer>
    </SiteLayout>
  );
};
