import type { ChangeEvent, FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { isAxiosError } from 'axios';
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom';

import { useAuth } from '../hooks/useAuth';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { PageContainer } from '../../../shared/components/PageContainer';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useToast } from '@/shared/components/ui/toast';
import { FeedbackMessage } from '@/shared/components/ui/page-state';

import './LoginPage.css';

interface LoginFormState {
  email: string;
  password: string;
  rememberMe: boolean;
}

interface LocationState {
  registered?: boolean;
  redirectTo?: string;
  from?: {
    pathname?: string;
    search?: string;
    hash?: string;
  };
  redirectState?: unknown;
}

const DEFAULT_AUTHENTICATED_PATH = '/mon-espace';
const AUTH_PAGE_PATHS = new Set(['/login', '/register', '/forgot-password']);

const isSafeRedirectPath = (path?: string | null) =>
  Boolean(path?.startsWith('/') && !path.startsWith('//') && !AUTH_PAGE_PATHS.has(path));

const getAuthenticatedRedirect = (state: LocationState | null) => {
  if (isSafeRedirectPath(state?.redirectTo)) {
    return state?.redirectTo ?? DEFAULT_AUTHENTICATED_PATH;
  }

  const fromPath = state?.from?.pathname;
  if (isSafeRedirectPath(fromPath)) {
    return `${fromPath}${state?.from?.search ?? ''}${state?.from?.hash ?? ''}`;
  }

  return DEFAULT_AUTHENTICATED_PATH;
};

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
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const loginErrorId = 'login-form-error';
  const loginNoticeId = 'login-form-notice';

  const parsedErrorDetails = errorDetails;

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
      try {
        toast.show('Compte créé. Vérifiez vos emails pour activer votre compte.', {
          variant: 'info',
        });
      } catch {}
    }
  }, [location.state]);

  const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
    const { name, value, checked, type } = event.target;
    setForm((prev) => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (isSubmitting) {
      return;
    }

    if (document.activeElement instanceof HTMLElement) {
      document.activeElement.blur();
    }

    setIsSubmitting(true);
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
      const state = location.state as LocationState | null;
      const redirectState = state?.redirectState;
      const redirectTo = getAuthenticatedRedirect(state);
      try {
        toast.show('Connexion réussie. Bienvenue !', { variant: 'success' });
      } catch {}
      try {
        window.sessionStorage.setItem(
          'hociatec.a11y.route-announcement',
          redirectTo === DEFAULT_AUTHENTICATED_PATH
            ? 'Connexion réussie. Vous êtes dans votre espace.'
            : 'Connexion réussie. Page demandée chargée.',
        );
      } catch {
        /* noop */
      }
      navigate(redirectTo, { replace: true, state: redirectState });
    } catch (loginError) {
      setIsSubmitting(false);
      console.error(loginError);

      if (isAxiosError(loginError) && loginError.response?.data?.message) {
        const msg = String(loginError.response.data.message);
        setError(msg);
        const details = loginError.response.data.details;
        if (Array.isArray(details)) {
          setErrorDetails(details.map((detail: unknown) => String(detail)));
        }
        try {
          toast.show(msg, { variant: 'error' });
        } catch {}
      } else if (loginError instanceof Error) {
        setError(loginError.message);
        try {
          toast.show(loginError.message, { variant: 'error' });
        } catch {}
      } else {
        const msg = 'Impossible de vérifier vos identifiants.';
        setError(msg);
        try {
          toast.show(msg, { variant: 'error' });
        } catch {}
      }
    }
  };

  const locationState = location.state as LocationState | null;

  if (status === 'authenticated') {
    return (
      <Navigate
        to={getAuthenticatedRedirect(locationState)}
        replace
        state={locationState?.redirectState}
      />
    );
  }

  if (status === 'idle' || status === 'loading' || isSubmitting) {
    return (
      <SiteLayout headerVariant="light">
        <PageContainer title="Connexion">
          <p className="notice" role="status" aria-live="polite">
            Connexion à votre espace...
          </p>
        </PageContainer>
      </SiteLayout>
    );
  }

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
          <FeedbackMessage
            id={loginNoticeId}
            variant="success"
            aria-live="polite"
            aria-atomic="true"
          >
            {notice}
          </FeedbackMessage>
        )}
        {error && (
          <FeedbackMessage id={loginErrorId} aria-live="assertive" aria-atomic="true">
            <p>{error}</p>
            {parsedErrorDetails.length > 0 && (
              <ul className="mt-2 list-disc pl-5 text-sm">
                {parsedErrorDetails.map((detail) => (
                  <li key={detail}>{detail}</li>
                ))}
              </ul>
            )}
          </FeedbackMessage>
        )}
        <form
          className="card__content"
          onSubmit={handleSubmit}
          aria-describedby={
            [error ? loginErrorId : null, notice ? loginNoticeId : null]
              .filter(Boolean)
              .join(' ') || undefined
          }
        >
          <div className="form-field">
            <label htmlFor="email">Email</label>
            <input
              id="email"
              name="email"
              type="email"
              autoComplete="email"
              value={form.email}
              onChange={handleChange}
              aria-invalid={error ? true : undefined}
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
                aria-invalid={error ? true : undefined}
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
            <span>Se souvenir de mon email</span>
          </label>
          <button className="button" type="submit">
            Se connecter
          </button>
        </form>
      </PageContainer>
    </SiteLayout>
  );
};
