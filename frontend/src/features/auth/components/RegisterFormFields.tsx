import { useEffect, useState, type ChangeEvent, type Dispatch, type SetStateAction } from 'react';

import type { RegisterPayload } from '@/features/auth/api/authApi';
import { fetchBetaProfileChoices, type BetaProfileChoices } from '@/features/betaTest/api/betaApi';

type FormState = RegisterPayload;
type FieldChange = (event: ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => void;

export const RegisterFormFields = ({ form, setForm, handleChange, error, passwordHelpId, showPassword, showConfirmPassword, setShowPassword, setShowConfirmPassword, isBetaTester }: { form: FormState; setForm: Dispatch<SetStateAction<FormState>>; handleChange: FieldChange; error: string | null; passwordHelpId: string; showPassword: boolean; showConfirmPassword: boolean; setShowPassword: Dispatch<SetStateAction<boolean>>; setShowConfirmPassword: Dispatch<SetStateAction<boolean>>; isBetaTester?: boolean }) => {
  const [choices, setChoices] = useState<BetaProfileChoices | null>(null);
  const [choicesError, setChoicesError] = useState(false);

  useEffect(() => {
    if (!isBetaTester) return;

    void fetchBetaProfileChoices()
      .then(setChoices)
      .catch(() => setChoicesError(true));
  }, [isBetaTester]);

  return <>
    <div className="register-form__grid"><label className="register-form__field"><span>Prénom</span><input name="firstName" type="text" autoComplete="given-name" value={form.firstName} onChange={handleChange} maxLength={50} required /></label><label className="register-form__field"><span>Nom</span><input name="lastName" type="text" autoComplete="family-name" value={form.lastName} onChange={handleChange} maxLength={50} required /></label></div>
    <label className="register-form__field"><span>Adresse e-mail</span><input name="email" type="email" autoComplete="email" maxLength={180} value={form.email} onChange={handleChange} aria-invalid={error ? true : undefined} required /></label>
    <div className="register-form__grid"><label className="register-form__field"><span>Date de naissance</span><input name="birthDate" type="date" value={form.birthDate} onChange={handleChange} required /></label><label className="register-form__field"><span>Numéro de téléphone</span><input name="phoneNumber" type="tel" autoComplete="tel" value={form.phoneNumber} onChange={handleChange} maxLength={20} required /></label><label className="register-form__field"><span>Sexe</span><select name="gender" value={form.gender} onChange={handleChange} required className="register-form__select"><option value="" disabled>Sélectionnez une option</option><option value="homme">Homme</option><option value="femme">Femme</option><option value="autre">Autre</option></select></label></div>
    <div className="register-form__grid"><PasswordField label="Mot de passe" name="password" value={form.password} visible={showPassword} onChange={handleChange} onToggle={() => setShowPassword((current) => !current)} helpId={passwordHelpId} error={error} /><PasswordField label="Confirmation" name="confirmPassword" value={form.confirmPassword} visible={showConfirmPassword} onChange={handleChange} onToggle={() => setShowConfirmPassword((current) => !current)} helpId={passwordHelpId} error={error} /></div>
    <div id={passwordHelpId} className="register-form__guidelines"><p>Le mot de passe doit respecter les critères suivants :</p><ul><li>Au moins 8 caractères</li><li>Au moins une lettre majuscule</li><li>Au moins un chiffre</li></ul></div>
    {isBetaTester && !choices && !choicesError ? <p className="register-form__guidelines">Chargement des choix du profil bêta…</p> : null}
    {isBetaTester && choicesError ? <p className="register-form__alert-detail">Impossible de charger les choix du profil bêta.</p> : null}
    {isBetaTester && choices ? <BetaFields form={form} setForm={setForm} handleChange={handleChange} choices={choices} /> : null}
  </>;
};

const BetaFields = ({ form, setForm, handleChange, choices }: { form: FormState; setForm: Dispatch<SetStateAction<FormState>>; handleChange: FieldChange; choices: BetaProfileChoices }) => <section className="register-form__beta" aria-labelledby="beta-fields-title"><h3 id="beta-fields-title">Profil bêta-testeur</h3><p className="register-form__guidelines">Ces informations nous aident à vous proposer les tests les plus pertinents.</p><CheckboxGroup name="availability" label="Disponibilités" options={choices.availability ?? []} form={form} setForm={setForm} required /><label className="register-form__field"><span>Motivation *</span><textarea name="motivation" value={form.motivation} onChange={handleChange} maxLength={5000} rows={4} required /></label><CheckboxGroup name="testingExperience" label="Expérience des tests" options={choices.testingExperience ?? []} form={form} setForm={setForm} required /><CheckboxGroup name="bugDescriptionAbility" label="Capacité à décrire un bug" options={choices.bugDescriptionAbility ?? []} form={form} setForm={setForm} required /><CheckboxGroup name="technicalKnowledge" label="Connaissances techniques" options={choices.technicalKnowledge ?? []} form={form} setForm={setForm} required /><CheckboxGroup name="assistiveTools" label="Outils utilisés" options={choices.assistiveTools ?? []} form={form} setForm={setForm} required /><CheckboxGroup name="devices" label="Matériel *" options={choices.devices ?? []} form={form} setForm={setForm} required /><CheckboxGroup name="browsers" label="Navigateurs *" options={choices.browsers ?? []} form={form} setForm={setForm} required /><CheckboxGroup name="testingTypes" label="Types de tests souhaités *" options={choices.testingTypes ?? []} form={form} setForm={setForm} required /><label className="register-form__beta-consent"><input name="betaConsent" type="checkbox" checked={Boolean(form.betaConsent)} onChange={handleChange} required aria-label="J’accepte de participer au programme bêta et l’utilisation de ces informations à cette fin." /><span aria-hidden="true">J’accepte de participer au programme bêta et l’utilisation de ces informations à cette fin.</span></label></section>;

const normalizeCheckboxSelection = (name: keyof FormState, value: string, checked: boolean, current: string[]) => {
  const next = checked ? [...current, value] : current.filter((item) => item !== value);

  if (name !== 'assistiveTools') {
    return next;
  }

  if (checked && value === 'none') {
    return ['none'];
  }

  return next.filter((item) => item !== 'none');
};

const CheckboxGroup = ({ name, label, options, form, setForm, required = false }: { name: keyof FormState; label: string; options: readonly { value: string; label: string }[]; form: FormState; setForm: Dispatch<SetStateAction<FormState>>; required?: boolean }) => <div className="register-form__checkbox-group"><h4 className="register-form__checkbox-title">{label}</h4><div>{options.map(({ value, label: text }) => { const current = Array.isArray(form[name]) ? form[name] as string[] : []; return <label key={value}><input type="checkbox" name={name} value={value} checked={current.includes(value)} onChange={(event) => setForm((previous) => ({ ...previous, [name]: normalizeCheckboxSelection(name, value, event.target.checked, current) }))} required={required && current.length === 0} aria-label={text} /><span aria-hidden="true">{text}</span></label>; })}</div></div>;

const PasswordField = ({ label, name, value, visible, onChange, onToggle, helpId, error }: { label: string; name: 'password' | 'confirmPassword'; value: string; visible: boolean; onChange: FieldChange; onToggle: () => void; helpId: string; error: string | null }) => <label className="register-form__field"><span>{label}</span><div className="register-form__password-wrapper"><input name={name} type={visible ? 'text' : 'password'} autoComplete="new-password" value={value} onChange={onChange} aria-describedby={helpId} aria-invalid={error ? true : undefined} minLength={8} maxLength={4096} required /><button type="button" className="register-form__password-toggle" onClick={onToggle} aria-label={visible ? `Masquer ${label.toLowerCase()}` : `Afficher ${label.toLowerCase()}`}>{visible ? 'Masquer' : 'Afficher'}</button></div></label>;
