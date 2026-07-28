import type { ChangeEvent, FormEvent } from 'react';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';

import { registerUser, type RegisterPayload } from '../api/authApi';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { RegisterIntro } from '@/features/auth/components/RegisterIntro';
import { RegisterFormFields } from '@/features/auth/components/RegisterFormFields';
import { useToast } from '@/shared/components/ui/toast';
import './RegisterPage.css';

type FormState = RegisterPayload;
const PASSWORD_RULE = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

export const RegisterPage = () => {
  useDocumentTitle('Inscription');
  const navigate = useNavigate();
  const toast = useToast();
  const isBetaTester = new URLSearchParams(window.location.search).get('beta') === '1';
  const [form, setForm] = useState<FormState>({ email: '', password: '', confirmPassword: '', firstName: '', lastName: '', birthDate: '', phoneNumber: '', gender: '', isBetaTester, betaConsent: false, availability: [], motivation: '', testingExperience: [], bugDescriptionAbility: [], technicalKnowledge: [], accessibilityNeed: 'none', assistiveTools: [], devices: [], browsers: [], testingTypes: [] });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [errorDetails, setErrorDetails] = useState<string[]>([]);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const errorId = 'register-form-error';
  const passwordHelpId = 'register-password-help';
  const hasErrorDetails = (value: unknown): value is Error & { details: string[] } => typeof value === 'object' && value !== null && 'details' in value && Array.isArray((value as { details?: unknown }).details);
  const handleChange = (event: ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => { const target = event.target; setForm((current) => ({ ...current, [target.name]: target instanceof HTMLInputElement && target.type === 'checkbox' ? target.checked : target.value })); };
  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault(); setError(null); setErrorDetails([]);
    const validationError = form.password !== form.confirmPassword ? 'Les mots de passe doivent être identiques.' : !PASSWORD_RULE.test(form.password) ? 'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.' : !form.gender ? 'Veuillez sélectionner une option pour le champ sexe.' : isBetaTester && (!form.betaConsent || !form.availability?.length || !form.motivation?.trim() || !form.testingExperience?.length || !form.bugDescriptionAbility?.length || !form.technicalKnowledge?.length || !form.assistiveTools?.length || !form.devices?.length || !form.browsers?.length || !form.testingTypes?.length) ? 'Complétez tous les champs obligatoires du profil bêta.' : null;
    if (validationError) { setError(validationError); try { toast.show(validationError, { variant: 'error' }); } catch {} return; }
    setLoading(true);
    try { const response = await registerUser(form); try { toast.show(response.message ?? 'Compte créé.', { variant: 'success' }); } catch {} navigate('/login', { state: { registered: true, registrationMessage: response.message } }); }
    catch (submissionError) { console.error(submissionError); const message = submissionError instanceof Error ? submissionError.message || "Impossible de finaliser l'inscription pour le moment." : "Impossible de finaliser l'inscription pour le moment."; setError(message); if (submissionError instanceof Error && hasErrorDetails(submissionError)) setErrorDetails(submissionError.details); try { toast.show(message, { variant: 'error' }); } catch {} }
    finally { setLoading(false); }
  };
  return <SiteLayout headerVariant="light"><div className="register-page"><RegisterIntro /><section className="register-form-card" aria-labelledby="register-form-title"><header className="register-form-card__header"><h2 id="register-form-title">{isBetaTester ? 'Créer mon espace bêta-testeur' : 'Informations de compte'}</h2><p>{isBetaTester ? 'Votre compte vous permettra de participer aux campagnes et de suivre vos signalements.' : 'Complétez ce formulaire pour créer votre espace sécurisé.'}</p></header><form className="register-form" onSubmit={handleSubmit} noValidate aria-describedby={error ? errorId : undefined}>{error ? <FeedbackMessage id={errorId} aria-live="assertive" aria-atomic="true"><p>{error}</p>{errorDetails.map((detail) => <p key={detail} className="register-form__alert-detail">{detail}</p>)}</FeedbackMessage> : null}<RegisterFormFields form={form} setForm={setForm} handleChange={handleChange} error={error} passwordHelpId={passwordHelpId} showPassword={showPassword} showConfirmPassword={showConfirmPassword} setShowPassword={setShowPassword} setShowConfirmPassword={setShowConfirmPassword} isBetaTester={isBetaTester} /><button className="register-form__submit" type="submit" disabled={loading}>{loading ? 'Création en cours...' : isBetaTester ? 'Rejoindre le programme bêta' : 'Créer mon espace'}</button></form></section></div></SiteLayout>;
};
