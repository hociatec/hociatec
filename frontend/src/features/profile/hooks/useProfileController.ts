import type { ChangeEvent, FormEvent } from 'react';
import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router';

import { formatOptionalFrenchDate } from '@/shared/lib/formatters';
import { ApiResponseError, getHttpErrorMessage } from '@/shared/lib/httpClient';
import { omitUndefinedProperties } from '@/shared/lib/object';
import { useAuth } from '../../auth/hooks/useAuth';
import type { AccountAccessSession } from '../../auth/api/authApi';
import {
  formatRole,
  normalizeEmail,
  PASSWORD_RULE,
  type ProfileFeedback,
} from '../lib/profileFormatters';

const emptyForm = {
  firstName: '',
  lastName: '',
  email: '',
  birthDate: '',
  phoneNumber: '',
  gender: '',
  password: '',
  currentPassword: '',
};

export type ProfileFormState = typeof emptyForm;

export const useProfileController = () => {
  const { user, updateProfile, deleteAccount, revokeAllSessions, listAccessSessions, revokeAccessSession } = useAuth();
  const navigate = useNavigate();
  const [feedback, setFeedback] = useState<ProfileFeedback>(null);
  const [isEditing, setIsEditing] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [isRevokingSessions, setIsRevokingSessions] = useState(false);
  const [accessSessions, setAccessSessions] = useState<AccountAccessSession[]>([]);
  const [isLoadingAccessSessions, setIsLoadingAccessSessions] = useState(false);
  const [isAccessManagerOpen, setIsAccessManagerOpen] = useState(false);
  const [revokingSessionId, setRevokingSessionId] = useState<number | null>(null);
  const [form, setForm] = useState(emptyForm);

  const buildForm = () =>
    user
      ? {
          firstName: user.firstName,
          lastName: user.lastName,
          email: user.email,
          birthDate: user.birthDate,
          phoneNumber: user.phoneNumber,
          gender: user.gender,
          password: '',
          currentPassword: '',
        }
      : emptyForm;

  useEffect(() => {
    if (user) setForm(buildForm());
  }, [user]);

  useEffect(() => {
    if (!user) {
      setAccessSessions([]);
      return;
    }

    let cancelled = false;

    const load = async () => {
      setIsLoadingAccessSessions(true);
      try {
        const payload = await listAccessSessions();
        if (!cancelled) {
          setAccessSessions(payload.items);
        }
      } catch (error) {
        if (!cancelled) {
          const details =
            error instanceof ApiResponseError && Array.isArray(error.details)
              ? error.details
              : [];
          setFeedback({
            type: 'error',
            message: getHttpErrorMessage(error, 'Impossible de charger vos accès actifs.'),
            details,
          });
        }
      } finally {
        if (!cancelled) {
          setIsLoadingAccessSessions(false);
        }
      }
    };

    void load();

    return () => {
      cancelled = true;
    };
  }, [listAccessSessions, user]);

  const initials = useMemo(
    () =>
      user
        ? [user.firstName, user.lastName]
            .filter(Boolean)
            .map((part) => part.trim().charAt(0).toUpperCase())
            .slice(0, 2)
            .join('')
        : '',
    [user],
  );
  const formattedRoles = useMemo(
    () =>
      user?.roles?.length
        ? Array.from(new Set(user.roles)).map(formatRole).join(', ')
        : 'Utilisateur',
    [user],
  );
  const formattedBirthDate = useMemo(() => {
    if (!user?.birthDate) return 'Non renseignée';
    return formatOptionalFrenchDate(user.birthDate);
  }, [user]);

  const resetForm = () => setForm(buildForm());
  const handleFieldChange = (event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = event.target;
    setForm((previous) => ({ ...previous, [name]: value }));
  };
  const handleStartEditing = () => {
    resetForm();
    setFeedback(null);
    setIsEditing(true);
  };
  const handleCancelEditing = () => {
    resetForm();
    setFeedback(null);
    setIsEditing(false);
  };

  const handleSubmitProfile = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!user) return;
    if (!form.gender)
      return setFeedback({
        type: 'error',
        message: 'Veuillez sélectionner une option pour le champ sexe.',
      });
    const hasEmailChanged = normalizeEmail(form.email) !== normalizeEmail(user.email);
    const hasNewPassword = form.password.trim() !== '';
    const requiresCurrentPassword = hasEmailChanged || hasNewPassword;
    if (hasNewPassword && !PASSWORD_RULE.test(form.password))
      return setFeedback({
        type: 'error',
        message:
          'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.',
      });
    if (requiresCurrentPassword && form.currentPassword.trim() === '')
      return setFeedback({ type: 'error', message: 'Veuillez saisir votre mot de passe actuel.' });
    setIsSaving(true);
    setFeedback(null);
    try {
      await updateProfile(omitUndefinedProperties({
        ...form,
        password: hasNewPassword ? form.password : undefined,
        currentPassword: requiresCurrentPassword ? form.currentPassword : undefined,
      }));
      setFeedback({ type: 'success', message: 'Votre profil a été mis à jour avec succès.' });
      setIsEditing(false);
      setForm((previous) => ({ ...previous, password: '', currentPassword: '' }));
    } catch (error) {
      const details =
        error instanceof ApiResponseError && Array.isArray(error.details)
          ? error.details
          : [];
      setFeedback({
        type: 'error',
        message: getHttpErrorMessage(error, 'Impossible de mettre à jour votre profil pour le moment.'),
        details,
      });
    } finally {
      setIsSaving(false);
    }
  };

  const handleConfirmDelete = async () => {
    setFeedback(null);
    setIsDeleting(true);
    try {
      await deleteAccount();
      navigate('/', { replace: true });
    } catch (error) {
      const details =
        error instanceof ApiResponseError && Array.isArray(error.details)
          ? error.details
          : [];
      setFeedback({
        type: 'error',
        message: getHttpErrorMessage(error, 'Impossible de supprimer votre compte actuellement.'),
        details,
      });
      setIsDeleting(false);
    }
  };

  const handleConfirmRevokeAllSessions = async () => {
    setFeedback(null);
    setIsRevokingSessions(true);
    try {
      await revokeAllSessions();
      navigate('/', { replace: true });
    } catch (error) {
      const details =
        error instanceof ApiResponseError && Array.isArray(error.details)
          ? error.details
          : [];
      setFeedback({
        type: 'error',
        message: getHttpErrorMessage(error, 'Impossible de révoquer vos accès actuellement.'),
        details,
      });
      setIsRevokingSessions(false);
    }
  };

  const handleToggleAccessManager = () => {
    setIsAccessManagerOpen((previous) => !previous);
  };

  const handleRevokeSession = async (sessionId: number) => {
    setFeedback(null);
    setRevokingSessionId(sessionId);

    try {
      const result = await revokeAccessSession(sessionId);
      if (result.revokedCurrentSession) {
        navigate('/', { replace: true });
        return;
      }

      setAccessSessions((previous) => previous.filter((session) => session.id !== sessionId));
      setFeedback({ type: 'success', message: 'Accès révoqué.' });
    } catch (error) {
      const details =
        error instanceof ApiResponseError && Array.isArray(error.details)
          ? error.details
          : [];
      setFeedback({
        type: 'error',
        message: getHttpErrorMessage(error, 'Impossible de révoquer cet accès pour le moment.'),
        details,
      });
    } finally {
      setRevokingSessionId(null);
    }
  };

  return {
    user,
    feedback,
    isEditing,
    isSaving,
    isDeleting,
    isRevokingSessions,
    accessSessions,
    isLoadingAccessSessions,
    isAccessManagerOpen,
    revokingSessionId,
    form,
    initials,
    formattedRoles,
    formattedBirthDate,
    handleFieldChange,
    handleStartEditing,
    handleCancelEditing,
    handleSubmitProfile,
    handleConfirmDelete,
    handleConfirmRevokeAllSessions,
    handleToggleAccessManager,
    handleRevokeSession,
    hasCurrentPasswordRequirement: user
      ? normalizeEmail(form.email) !== normalizeEmail(user.email) || form.password.trim() !== ''
      : false,
  };
};
