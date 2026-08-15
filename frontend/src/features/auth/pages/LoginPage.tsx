import type { ChangeEvent, FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { isAxiosError } from 'axios';
import { Link, Navigate, useLocation, useNavigate } from 'react-router';

import { useAuth } from '../hooks/useAuth';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { PRIVATE_ROBOTS_CONTENT, SITE_URL } from '@/shared/config/seoConfig';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useToast } from '@/shared/components/ui/toast';
import { LoginForm, type LoginFormState } from '@/features/auth/components/LoginForm';
import { logger } from '@/shared/lib/logger';
import { writeSessionStorage } from '@/shared/lib/http/storage';
import { isSafeInternalRedirectPath } from '@/shared/lib/redirects';

import './LoginPage.css';

interface LocationState {
  registered?: boolean;
  registrationMessage?: string;
  sessionCheckFailed?: boolean;
  sessionCheckMessage?: string;
  redirectTo?: string;
  from?: {
    pathname?: string;
    search?: string;
    hash?: string;
  };
  redirectState?: unknown;
}

const DEFAULT_AUTHENTICATED_PATH = '/mon-espace';

const getAuthenticatedRedirect = (state: LocationState | null) => {
  if (isSafeInternalRedirectPath(state?.redirectTo)) {
    return state?.redirectTo ?? DEFAULT_AUTHENTICATED_PATH;
  }

  const fromPath = state?.from?.pathname;
  if (isSafeInternalRedirectPath(fromPath)) {
    return `${fromPath}${state?.from?.search ?? ''}${state?.from?.hash ?? ''}`;
  }

  return DEFAULT_AUTHENTICATED_PATH;
};

export const LoginPage = () => {
  useDocumentTitle('Connexion');
  useMetaTags({
    title: 'Connexion — Hociatec',
    description: 'Connectez-vous à votre espace client Hociatec.',
    canonicalUrl: `${SITE_URL}/login`,
    robots: PRIVATE_ROBOTS_CONTENT,
  });

  const navigate = useNavigate();
  const location = useLocation();
  const { login, status } = useAuth();
  const toast = useToast();

  const [form, setForm] = useState<LoginFormState>({
    email: '',
    password: '',
    rememberSession: false,
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
    const state = location.state as LocationState | null;

    if (state?.registered) {
      setNotice(state?.registrationMessage ?? 'Votre compte est prêt. Vous pouvez désormais vous connecter.');
      try {
        toast.show(state?.registrationMessage ?? 'Votre compte est prêt. Vous pouvez désormais vous connecter.', {
          variant: 'info',
        });
      } catch (error) {
        logger.warn('Unable to display registration notice toast.', { error });
      }
    }

    if (state?.sessionCheckFailed) {
      const message = state.sessionCheckMessage ?? 'Votre session doit être vérifiée à nouveau avant de continuer.';
      setNotice(message);
      try {
        toast.show(message, { variant: 'info' });
      } catch (error) {
        logger.warn('Unable to display session verification notice toast.', { error });
      }
    }
  }, [location.state, toast]);

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
      const loginMessage = await login({
        email: form.email.trim(),
        password: form.password,
        rememberSession: form.rememberSession,
      });
      const state = location.state as LocationState | null;
      const redirectState = state?.redirectState;
      const redirectTo = getAuthenticatedRedirect(state);
      try {
        toast.show(loginMessage ?? 'Connexion réussie.', { variant: 'success' });
      } catch (error) {
        logger.warn('Unable to display login success toast.', { error });
      }
      writeSessionStorage(
        'hociatec.a11y.route-announcement',
        redirectTo === DEFAULT_AUTHENTICATED_PATH
          ? 'Connexion réussie. Vous êtes dans votre espace.'
          : 'Connexion réussie. Page demandée chargée.',
      );
      navigate(redirectTo, { replace: true, state: redirectState });
    } catch (loginError) {
      logger.warn('Login failed.', { error: loginError });

      if (isAxiosError(loginError) && loginError.response?.data?.message) {
        const msg = String(loginError.response.data.message);
        setError(msg);
        const details = loginError.response.data.details;
        if (Array.isArray(details)) {
          setErrorDetails(details.map((detail: unknown) => String(detail)));
        }
        try {
          toast.show(msg, { variant: 'error' });
        } catch (error) {
          logger.warn('Unable to display login error toast.', { error });
        }
      } else if (loginError instanceof Error) {
        setError(loginError.message);
        try {
          toast.show(loginError.message, { variant: 'error' });
        } catch (error) {
          logger.warn('Unable to display login error toast.', { error });
        }
      } else {
        const msg = 'Impossible de vérifier vos identifiants.';
        setError(msg);
        try {
          toast.show(msg, { variant: 'error' });
        } catch (error) {
          logger.warn('Unable to display login error toast.', { error });
        }
      }
    } finally {
      setIsSubmitting(false);
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
        <LoginForm
          form={form}
          error={error}
          errorDetails={parsedErrorDetails}
          notice={notice}
          showPassword={showPassword}
          loginErrorId={loginErrorId}
          loginNoticeId={loginNoticeId}
          onChange={handleChange}
          onSubmit={handleSubmit}
          onTogglePassword={() => setShowPassword((prev) => !prev)}
        />
      </PageContainer>
    </SiteLayout>
  );
};
