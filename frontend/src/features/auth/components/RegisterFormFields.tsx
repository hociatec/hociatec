import type { ChangeEvent, Dispatch, SetStateAction } from 'react';

import type { RegisterPayload } from '@/features/auth/api/authApi';

type FormState = RegisterPayload;
type FieldChange = (event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => void;

export const RegisterFormFields = ({ form, handleChange, error, passwordHelpId, showPassword, showConfirmPassword, setShowPassword, setShowConfirmPassword }: {
  form: FormState;
  handleChange: FieldChange;
  error: string | null;
  passwordHelpId: string;
  showPassword: boolean;
  showConfirmPassword: boolean;
  setShowPassword: Dispatch<SetStateAction<boolean>>;
  setShowConfirmPassword: Dispatch<SetStateAction<boolean>>;
}) => <>
  <div className="register-form__grid"><label className="register-form__field"><span>Prénom</span><input name="firstName" type="text" autoComplete="given-name" value={form.firstName} onChange={handleChange} maxLength={50} required /></label><label className="register-form__field"><span>Nom</span><input name="lastName" type="text" autoComplete="family-name" value={form.lastName} onChange={handleChange} maxLength={50} required /></label></div>
  <label className="register-form__field"><span>Adresse e-mail</span><input name="email" type="email" autoComplete="email" maxLength={180} value={form.email} onChange={handleChange} aria-invalid={error ? true : undefined} required /></label>
  <div className="register-form__grid"><label className="register-form__field"><span>Date de naissance</span><input name="birthDate" type="date" value={form.birthDate} onChange={handleChange} aria-invalid={error ? true : undefined} required /></label><label className="register-form__field"><span>Numéro de téléphone</span><input name="phoneNumber" type="tel" autoComplete="tel" value={form.phoneNumber} onChange={handleChange} aria-invalid={error ? true : undefined} maxLength={20} required /></label><label className="register-form__field"><span>Sexe</span><select name="gender" value={form.gender} onChange={handleChange} aria-invalid={error && !form.gender ? true : undefined} required className="register-form__select"><option value="" disabled>Sélectionnez une option</option><option value="homme">Homme</option><option value="femme">Femme</option><option value="autre">Autre</option></select></label></div>
  <div className="register-form__grid"><PasswordField label="Mot de passe" name="password" value={form.password} visible={showPassword} onChange={handleChange} onToggle={() => setShowPassword((current) => !current)} helpId={passwordHelpId} error={error} /><PasswordField label="Confirmation" name="confirmPassword" value={form.confirmPassword} visible={showConfirmPassword} onChange={handleChange} onToggle={() => setShowConfirmPassword((current) => !current)} helpId={passwordHelpId} error={error} /></div>
  <div id={passwordHelpId} className="register-form__guidelines"><p>Le mot de passe doit respecter les critères suivants :</p><ul><li>Au moins 8 caractères</li><li>Au moins une lettre majuscule</li><li>Au moins un chiffre</li></ul></div>
</>;

const PasswordField = ({ label, name, value, visible, onChange, onToggle, helpId, error }: { label: string; name: 'password' | 'confirmPassword'; value: string; visible: boolean; onChange: FieldChange; onToggle: () => void; helpId: string; error: string | null }) => <label className="register-form__field"><span>{label}</span><div className="register-form__password-wrapper"><input name={name} type={visible ? 'text' : 'password'} autoComplete="new-password" value={value} onChange={onChange} aria-describedby={helpId} aria-invalid={error ? true : undefined} minLength={8} maxLength={4096} required /><button type="button" className="register-form__password-toggle" onClick={onToggle} aria-label={visible ? `Masquer ${label.toLowerCase()}` : `Afficher ${label.toLowerCase()}`}>{visible ? 'Masquer' : 'Afficher'}</button></div></label>;
