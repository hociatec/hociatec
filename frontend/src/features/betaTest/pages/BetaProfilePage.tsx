import { useEffect, useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { fetchBetaProfileChoices, fetchMyBetaProfile, updateMyBetaProfile, type BetaProfileChoices } from '../api/betaApi';
import { BetaProfileCheckboxGroup } from '../components/BetaProfileCheckboxGroup';
import {
  buildBetaProfileForm,
  emptyBetaProfileForm,
  isBetaProfileComplete,
  type EditableProfile,
} from '../lib/betaProfileForm';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { logger } from '@/shared/lib/logger';
import { betaQueryKeys } from '@/shared/lib/queryKeys';

export const BetaProfilePage = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [form, setForm] = useState<EditableProfile | null>(null);
  const [error, setError] = useState<string | null>(null);
  const profileFormQuery = useQuery<{ choices: BetaProfileChoices; form: EditableProfile }, Error>({
    queryKey: betaQueryKeys.profileForm(),
    queryFn: async () => {
      const choices = await fetchBetaProfileChoices();
      try {
        const profile = await fetchMyBetaProfile();
        return { choices, form: buildBetaProfileForm(profile) };
      } catch (loadProfileError) {
        logger.warn('Unable to load existing beta profile.', { error: loadProfileError });
        return { choices, form: emptyBetaProfileForm() };
      }
    },
  });
  const choices = profileFormQuery.data?.choices ?? null;
  const saveMutation = useMutation({
    mutationFn: updateMyBetaProfile,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: betaQueryKeys.profile() });
      void queryClient.invalidateQueries({ queryKey: betaQueryKeys.profileForm() });
      navigate('/beta');
    },
    onError: (reason) =>
      setError(reason instanceof Error ? reason.message : 'Impossible de mettre à jour le profil.'),
  });

  useEffect(() => {
    if (profileFormQuery.data) {
      setForm(profileFormQuery.data.form);
      setError(null);
    }
  }, [profileFormQuery.data]);

  const save = async (event: FormEvent) => {
    event.preventDefault();
    if (!form) return;

    if (!isBetaProfileComplete(form)) {
      setError('Veuillez remplir tous les choix obligatoires (*) du profil bêta.');
      return;
    }

    saveMutation.mutate(form);
  };

  if (!form || !choices) {
    return (
      <SiteLayout headerVariant="light">
        <PageContainer title="Mon profil bêta">
          {error || profileFormQuery.error ? (
            <p className="text-red-700">
              {error ?? 'Impossible de charger les choix du profil bêta.'}
            </p>
          ) : (
            <p className="sr-only">Chargement…</p>
          )}
        </PageContainer>
      </SiteLayout>
    );
  }

  return (
    <SiteLayout headerVariant="light">
      <PageContainer title="Mon profil bêta">
        <p className="mb-6 text-stone-600">
          Complétez ou mettez à jour votre profil de bêta-testeur pour recevoir des campagnes adaptées.
        </p>

        <form onSubmit={save} className="max-w-2xl space-y-6 rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
          <BetaProfileCheckboxGroup
            name="availability"
            label="Disponibilités"
            options={choices.availability ?? []}
            form={form}
            setForm={setForm}
            required
          />

          <div className="space-y-4">
            <label className="block text-sm font-semibold text-stone-700">
              Motivation *
              <textarea
                className="mt-1 w-full rounded-lg border border-stone-300 p-3 text-sm focus:border-brand-700 focus:outline-none"
                rows={4}
                value={form.motivation}
                onChange={(event) => setForm({ ...form, motivation: event.target.value })}
                required
              />
            </label>

            <BetaProfileCheckboxGroup name="testingExperience" label="Expérience des tests" options={choices.testingExperience ?? []} form={form} setForm={setForm} required />
            <BetaProfileCheckboxGroup name="bugDescriptionAbility" label="Capacité à décrire un bug" options={choices.bugDescriptionAbility ?? []} form={form} setForm={setForm} required />
            <BetaProfileCheckboxGroup name="technicalKnowledge" label="Connaissances techniques" options={choices.technicalKnowledge ?? []} form={form} setForm={setForm} required />
          </div>

          <BetaProfileCheckboxGroup name="assistiveTools" label="Outils utilisés" options={choices.assistiveTools ?? []} form={form} setForm={setForm} required />
          <BetaProfileCheckboxGroup name="devices" label="Matériel *" options={choices.devices ?? []} form={form} setForm={setForm} required />
          <BetaProfileCheckboxGroup name="browsers" label="Navigateurs *" options={choices.browsers ?? []} form={form} setForm={setForm} required />
          <BetaProfileCheckboxGroup name="testingTypes" label="Types de tests souhaités *" options={choices.testingTypes ?? []} form={form} setForm={setForm} required />

          <label className="flex cursor-pointer select-none items-start gap-2.5 text-sm">
            <input
              name="betaConsent"
              type="checkbox"
              checked={form.betaConsent}
              onChange={(event) => setForm({ ...form, betaConsent: event.target.checked })}
              required
              aria-label="J’accepte de participer au programme bêta et l’utilisation de ces informations à cette fin."
              className="mt-0.5 h-4 w-4 rounded border-stone-300 text-brand-700 focus:ring-brand-500"
            />
            <span aria-hidden="true" className="text-stone-600">
              J’accepte de participer au programme bêta et l’utilisation de ces informations à cette fin.
            </span>
          </label>

          {error && <p className="text-sm font-semibold text-red-600">{error}</p>}

          <div className="flex flex-wrap gap-3 border-t border-stone-150 pt-4">
            <button
              type="submit"
              disabled={saveMutation.isPending}
              className="rounded-lg bg-brand-700 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-800"
            >
              Enregistrer
            </button>
          </div>
        </form>
      </PageContainer>
    </SiteLayout>
  );
};
