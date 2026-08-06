import type { ChangeEvent, FormEvent } from 'react';
import { useState } from 'react';
import { useNavigate } from 'react-router';

import { registerUser, type RegisterPayload } from '../api/authApi';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { PRIVATE_ROBOTS_CONTENT, SITE_URL } from '@/shared/config/seoConfig';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { RegisterIntro } from '@/features/auth/components/RegisterIntro';
import { RegisterFormFields } from '@/features/auth/components/RegisterFormFields';
import { useToast } from '@/shared/components/ui/toast';
import { logger } from '@/shared/lib/logger';
import { isFeatureEnabled } from '@/shared/config/featureFlags';
import { isRecord } from '@/shared/lib/contractValidation';

import './RegisterPage.css';

type FormState = RegisterPayload;

const PASSWORD_RULE = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

const createInitialForm = (isBetaTester: boolean): FormState => ({
  email: '',
  password: '',
  confirmPassword: '',
  firstName: '',
  lastName: '',
  birthDate: '',
  phoneNumber: '',
  gender: '',
  isBetaTester,
  betaConsent: false,
  availability: [],
  motivation: '',
  testingExperience: [],
  bugDescriptionAbility: [],
  technicalKnowledge: [],
  accessibilityNeed: 'none',
  assistiveTools: [],
  devices: [],
  browsers: [],
  testingTypes: [],
});

type ErrorWithDetails = {
  details: string[];
};

const hasErrorDetails = (value: unknown): value is Error & ErrorWithDetails => {
  if (!(value instanceof Error)) return false;
  if (!isRecord(value)) return false;

  return Array.isArray(value.details) && value.details.every((item): item is string => typeof item === 'string');
};

const validateRegisterForm = (form: FormState, isBetaTester: boolean) => {
  if (form.password !== form.confirmPassword) {
    return 'Les mots de passe doivent être identiques.';
  }

  if (!PASSWORD_RULE.test(form.password)) {
    return 'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.';
  }

  if (!form.gender) {
    return 'Veuillez sélectionner une option pour le champ sexe.';
  }

  if (
    isBetaTester &&
    (!form.betaConsent ||
      !form.availability?.length ||
      !form.motivation?.trim() ||
      !form.testingExperience?.length ||
      !form.bugDescriptionAbility?.length ||
      !form.technicalKnowledge?.length ||
      !form.assistiveTools?.length ||
      !form.devices?.length ||
      !form.browsers?.length ||
      !form.testingTypes?.length)
  ) {
    return 'Complétez tous les champs obligatoires du profil bêta.';
  }

  return null;
};

export const RegisterPage = () => {
  useDocumentTitle('Inscription');
  useMetaTags({
    title: 'Inscription — Hociatec',
    description: 'Créez votre compte client Hociatec.',
    canonicalUrl: `${SITE_URL}/register`,
    robots: PRIVATE_ROBOTS_CONTENT,
  });

  const navigate = useNavigate();
  const toast = useToast();
  const isBetaTester =
    isFeatureEnabled('betaProgram') && new URLSearchParams(window.location.search).get('beta') === '1';
  const [form, setForm] = useState<FormState>(() => createInitialForm(isBetaTester));
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [errorDetails, setErrorDetails] = useState<string[]>([]);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const errorId = 'register-form-error';
  const passwordHelpId = 'register-password-help';

  const showToast = (message: string, variant: 'success' | 'error') => {
    try {
      toast.show(message, { variant });
    } catch (toastError) {
      logger.warn('Unable to display registration toast.', { error: toastError });
    }
  };

  const handleChange = (
    event: ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>,
  ) => {
    const target = event.target;
    setForm((current) => ({
      ...current,
      [target.name]:
        target instanceof HTMLInputElement && target.type === 'checkbox'
          ? target.checked
          : target.value,
    }));
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setErrorDetails([]);

    const validationError = validateRegisterForm(form, isBetaTester);
    if (validationError) {
      setError(validationError);
      showToast(validationError, 'error');
      return;
    }

    setLoading(true);

    try {
      const response = await registerUser(form);
      showToast(response.message ?? 'Compte créé.', 'success');
      navigate('/login', {
        state: { registered: true, registrationMessage: response.message },
      });
    } catch (submissionError) {
      logger.warn('Registration failed.', { error: submissionError });
      const message =
        submissionError instanceof Error
          ? submissionError.message || "Impossible de finaliser l'inscription pour le moment."
          : "Impossible de finaliser l'inscription pour le moment.";
      setError(message);
      if (submissionError instanceof Error && hasErrorDetails(submissionError)) {
        setErrorDetails(submissionError.details);
      }
      showToast(message, 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SiteLayout headerVariant="light">
      <div className="register-page">
        <RegisterIntro />
        <section className="register-form-card" aria-labelledby="register-form-title">
          <header className="register-form-card__header">
            <h2 id="register-form-title">
              {isBetaTester ? 'Créer mon espace bêta-testeur' : 'Informations de compte'}
            </h2>
            <p>
              {isBetaTester
                ? 'Votre compte vous permettra de participer aux campagnes et de suivre vos signalements.'
                : 'Complétez ce formulaire pour créer votre espace sécurisé.'}
            </p>
          </header>
          <form
            className="register-form"
            onSubmit={handleSubmit}
            noValidate
            aria-describedby={error ? errorId : undefined}
          >
            {error ? (
              <FeedbackMessage id={errorId} aria-live="assertive" aria-atomic="true">
                <p>{error}</p>
                {errorDetails.map((detail) => (
                  <p key={detail} className="register-form__alert-detail">
                    {detail}
                  </p>
                ))}
              </FeedbackMessage>
            ) : null}
            <RegisterFormFields
              form={form}
              setForm={setForm}
              handleChange={handleChange}
              error={error}
              passwordHelpId={passwordHelpId}
              showPassword={showPassword}
              showConfirmPassword={showConfirmPassword}
              setShowPassword={setShowPassword}
              setShowConfirmPassword={setShowConfirmPassword}
              isBetaTester={isBetaTester}
            />
            <button className="register-form__submit" type="submit" disabled={loading}>
              {loading
                ? 'Création en cours...'
                : isBetaTester
                  ? 'Rejoindre le programme bêta'
                  : 'Créer mon espace'}
            </button>
          </form>
        </section>
      </div>
    </SiteLayout>
  );
};
