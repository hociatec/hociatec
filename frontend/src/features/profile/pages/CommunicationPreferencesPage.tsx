import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { Link } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import {
  fetchCommunicationPreferences,
  updateCommunicationPreferences,
  type CommunicationPreferenceChoice,
  type CommunicationPreferencesPayload,
} from '@/features/profile/api/communicationPreferencesApi';
import { profileQueryKeys } from '@/shared/lib/queryKeys';

const togglePreference = (preferences: string[], value: string, checked: boolean) =>
  checked
    ? Array.from(new Set([...preferences, value]))
    : preferences.filter((preference) => preference !== value);

export const CommunicationPreferencesPage = () => {
  useDocumentTitle('Préférences de communication');
  const queryClient = useQueryClient();
  const [preferences, setPreferences] = useState<string[]>([]);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const preferencesQuery = useQuery<CommunicationPreferencesPayload, Error>({
    queryKey: profileQueryKeys.communicationPreferences(),
    queryFn: fetchCommunicationPreferences,
  });
  const choices: CommunicationPreferenceChoice[] = preferencesQuery.data?.choices ?? [];
  const loading = preferencesQuery.isLoading;
  const saveMutation = useMutation({
    mutationFn: updateCommunicationPreferences,
    onSuccess: (payload) => {
      queryClient.setQueryData(profileQueryKeys.communicationPreferences(), payload);
      setPreferences(payload.preferences);
      setMessage('Préférences enregistrées.');
    },
    onError: (reason) =>
      setError(reason instanceof Error ? reason.message : 'Enregistrement impossible.'),
  });

  useEffect(() => {
    if (preferencesQuery.data) {
      setPreferences(preferencesQuery.data.preferences);
      setError(null);
    }
  }, [preferencesQuery.data]);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setMessage(null);
    setError(null);

    if (preferences.length === 0) {
      setError('Sélectionnez au moins un moyen de communication.');
      return;
    }

    saveMutation.mutate(preferences);
  };

  return (
    <SiteLayout headerVariant="light">
      <main className="mx-auto max-w-3xl px-4 py-10">
        <Link to="/mon-espace" className="text-sm font-semibold text-brand-700 underline">
          Retour à mon espace
        </Link>

        <header className="mt-6 rounded-2xl border border-brand-100 bg-white p-6 shadow-sm">
          <h1 className="text-2xl font-bold text-brand-900">Préférences de communication</h1>
          <p className="mt-2 text-stone-600">
            Choisissez les moyens utilisés par Hociatec pour vous prévenir lors d’un suivi important :
            nouveau message, changement d’état d’un signalement ou information utile liée à votre compte.
          </p>
        </header>

        <form onSubmit={submit} className="mt-6 rounded-2xl border border-brand-100 bg-white p-6 shadow-sm">
          {loading ? (
            <p className="sr-only">Chargement…</p>
          ) : (
            <div className="space-y-3">
              <h2 className="text-lg font-semibold text-brand-900">Moyens de communication</h2>
              {choices.map((choice) => (
                <label
                  key={choice.value}
                  className="grid cursor-pointer grid-cols-[1rem_1fr] gap-3 rounded-xl border border-stone-200 p-4 text-stone-700 transition hover:bg-stone-50"
                >
                  <input
                    type="checkbox"
                    className="mt-1 h-4 w-4 rounded border-stone-300 text-brand-700 focus:ring-brand-500"
                    checked={preferences.includes(choice.value)}
                    onChange={(event) =>
                      setPreferences((current) =>
                        togglePreference(current, choice.value, event.target.checked),
                      )
                    }
                    aria-label={choice.label}
                  />
                  <span aria-hidden="true">
                    <span className="block font-semibold text-stone-900">{choice.label}</span>
                    <span className="mt-1 block text-sm text-stone-600">{choice.description}</span>
                  </span>
                </label>
              ))}
            </div>
          )}

          {message ? <p className="mt-4 text-sm font-semibold text-green-700">{message}</p> : null}
          {preferencesQuery.error || error ? (
            <p className="mt-4 text-sm font-semibold text-red-700">
              {error ?? preferencesQuery.error?.message ?? 'Chargement impossible.'}
            </p>
          ) : null}

          <div className="mt-6 flex justify-end">
            <button
              type="submit"
              disabled={loading || saveMutation.isPending}
              className="rounded-lg bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:opacity-60"
            >
              {saveMutation.isPending ? 'Enregistrement…' : 'Enregistrer mes préférences'}
            </button>
          </div>
        </form>
      </main>
    </SiteLayout>
  );
};

export default CommunicationPreferencesPage;
