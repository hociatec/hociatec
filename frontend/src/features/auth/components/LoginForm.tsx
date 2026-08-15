import type { ChangeEvent, FormEvent } from 'react';
import { Link } from 'react-router';

export type LoginFormState = {
  email: string;
  password: string;
  rememberSession: boolean;
};

type LoginFormProps = {
  form: LoginFormState;
  error: string | null;
  errorDetails: string[];
  notice: string | null;
  showPassword: boolean;
  loginErrorId: string;
  loginNoticeId: string;
  onChange: (event: ChangeEvent<HTMLInputElement>) => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
  onTogglePassword: () => void;
};

export const LoginForm = ({
  form,
  error,
  errorDetails,
  notice,
  showPassword,
  loginErrorId,
  loginNoticeId,
  onChange,
  onSubmit,
  onTogglePassword,
}: LoginFormProps) => (
  <>
    {notice && (
      <div id={loginNoticeId} className="notice" role="status" aria-live="polite" aria-atomic="true">
        {notice}
      </div>
    )}
    {error && (
      <div id={loginErrorId} className="notice notice--error" role="alert" aria-atomic="true">
        <p>{error}</p>
        {errorDetails.length > 0 && (
          <ul className="mt-2 list-disc pl-5 text-sm">
            {errorDetails.map((detail) => <li key={detail}>{detail}</li>)}
          </ul>
        )}
      </div>
    )}
    <form
      className="card__content"
      onSubmit={onSubmit}
      aria-describedby={
        [error ? loginErrorId : null, notice ? loginNoticeId : null].filter(Boolean).join(' ') || undefined
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
          onChange={onChange}
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
            onChange={onChange}
            aria-invalid={error ? true : undefined}
            required
          />
          <button
            type="button"
            className="login-form__password-toggle"
            onClick={onTogglePassword}
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
          id="rememberSession"
          name="rememberSession"
          type="checkbox"
          checked={form.rememberSession}
          onChange={onChange}
        />
        <span>Rester connecté</span>
      </label>
      <button className="button" type="submit">
        Se connecter
      </button>
    </form>
  </>
);
