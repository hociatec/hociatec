import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { useNavigate } from 'react-router';
import { PageContainer } from '@/shared/components/PageContainer';
import { fetchBetaProfileChoices, fetchMyBetaProfile, updateMyBetaProfile, type BetaProfileChoices } from '../api/betaApi';
import { SiteLayout } from '@/shared/components/SiteLayout';

type EditableProfile = {
  motivation: string;
  testingExperience: string[];
  bugDescriptionAbility: string[];
  technicalKnowledge: string[];
  availability: string[];
  accessibilityNeed: string;
  assistiveTools: string[];
  devices: string[];
  browsers: string[];
  testingTypes: string[];
  betaConsent: boolean;
};

const listFromProfile = (value: unknown, fallback: string[] = []) => Array.isArray(value)
  ? value.filter((item): item is string => typeof item === 'string')
  : fallback;

const normalizeCheckboxSelection = (
  name: keyof EditableProfile,
  value: string,
  checked: boolean,
  current: string[],
) => {
  const next = checked ? [...current, value] : current.filter((item) => item !== value);

  if (name !== 'assistiveTools') {
    return next;
  }

  if (checked && value === 'none') {
    return ['none'];
  }

  return next.filter((item) => item !== 'none');
};

export const BetaProfilePage = () => {
  const navigate = useNavigate();
  const [form, setForm] = useState<EditableProfile | null>(null);
  const [choices, setChoices] = useState<BetaProfileChoices | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadProfile = async () => {
      try {
        const profileChoices = await fetchBetaProfileChoices();
        setChoices(profileChoices);

        try {
          const profile = await fetchMyBetaProfile();
          setForm({
            motivation: String(profile.motivation ?? ''),
            testingExperience: listFromProfile(profile.testingExperience),
            bugDescriptionAbility: listFromProfile(profile.bugDescriptionAbility),
            technicalKnowledge: listFromProfile(profile.technicalKnowledge),
            availability: listFromProfile(profile.availability, ['flexible']),
            accessibilityNeed: String(profile.accessibilityNeed ?? 'none'),
            assistiveTools: listFromProfile(profile.assistiveTools),
            devices: listFromProfile(profile.devices, ['windows']),
            browsers: listFromProfile(profile.browsers, ['chrome']),
            testingTypes: listFromProfile(profile.testingTypes, ['bugs']),
            betaConsent: true,
          });

          return;
        } catch {}

        setForm({
          motivation: '',
          testingExperience: [],
          bugDescriptionAbility: [],
          technicalKnowledge: [],
          availability: ['flexible'],
          accessibilityNeed: 'none',
          assistiveTools: [],
          devices: ['windows'],
          browsers: ['chrome'],
          testingTypes: ['bugs'],
          betaConsent: true,
        });
      } catch {
        setError('Impossible de charger les choix du profil bêta.');
      }
    };

    void loadProfile();
  }, []);

  const save = async (event: FormEvent) => {
    event.preventDefault();
    if (!form) return;

    if (
      !form.motivation.trim() ||
      !form.testingExperience.length ||
      !form.bugDescriptionAbility.length ||
      !form.technicalKnowledge.length ||
      !form.availability.length ||
      !form.assistiveTools.length ||
      !form.devices.length ||
      !form.browsers.length ||
      !form.testingTypes.length
    ) {
      setError('Veuillez remplir tous les choix obligatoires (*) du profil bêta.');
      return;
    }

    try {
      await updateMyBetaProfile(form);
      navigate('/beta');
    } catch (reason) {
      setError(
        reason instanceof Error ? reason.message : 'Impossible de mettre à jour le profil.',
      );
    }
  };

  if (!form || !choices) {
    return (
      <SiteLayout headerVariant="light">
        <PageContainer title="Mon profil bêta">
          {error ? <p className="text-red-700">{error}</p> : <p>Chargement…</p>}
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
          <CheckboxGroup
            name="availability"
            label="Disponibilités"
            options={choices.availability}
            form={form}
            setForm={setForm}
            required
          />

          <div className="space-y-4">
            <label className="block text-sm font-semibold text-stone-700">
              Motivation *
              <textarea
                className="mt-1 w-full rounded-lg border border-stone-300 p-3 text-sm focus:outline-none focus:border-brand-700"
                rows={4}
                value={form.motivation}
                onChange={(e) => setForm({ ...form, motivation: e.target.value })}
                required
              />
            </label>

            <CheckboxGroup
              name="testingExperience"
              label="Expérience des tests"
              options={choices.testingExperience ?? []}
              form={form}
              setForm={setForm}
              required
            />

            <CheckboxGroup
              name="bugDescriptionAbility"
              label="Capacité à décrire un bug"
              options={choices.bugDescriptionAbility ?? []}
              form={form}
              setForm={setForm}
              required
            />

            <CheckboxGroup
              name="technicalKnowledge"
              label="Connaissances techniques"
              options={choices.technicalKnowledge ?? []}
              form={form}
              setForm={setForm}
              required
            />

          </div>

          <CheckboxGroup
            name="assistiveTools"
            label="Outils utilisés"
            options={choices.assistiveTools}
            form={form}
            setForm={setForm}
            required
          />

          <CheckboxGroup
            name="devices"
            label="Matériel *"
            options={choices.devices}
            form={form}
            setForm={setForm}
            required
          />

          <CheckboxGroup
            name="browsers"
            label="Navigateurs *"
            options={choices.browsers}
            form={form}
            setForm={setForm}
            required
          />

          <CheckboxGroup
            name="testingTypes"
            label="Types de tests souhaités *"
            options={choices.testingTypes}
            form={form}
            setForm={setForm}
            required
          />

          <label className="flex items-start gap-2.5 text-sm cursor-pointer select-none">
            <input
              name="betaConsent"
              type="checkbox"
              checked={form.betaConsent}
              onChange={(e) => setForm({ ...form, betaConsent: e.target.checked })}
              required
              aria-label="J’accepte de participer au programme bêta et l’utilisation de ces informations à cette fin."
              className="mt-0.5 rounded border-stone-300 text-brand-700 focus:ring-brand-500 h-4 w-4"
            />
            <span aria-hidden="true" className="text-stone-600">
              J’accepte de participer au programme bêta et l’utilisation de ces informations à cette fin.
            </span>
          </label>

          {error && <p className="text-sm font-semibold text-red-600">{error}</p>}

          <div className="flex flex-wrap gap-3 pt-4 border-t border-stone-150">
            <button
              type="submit"
              className="rounded-lg bg-brand-700 px-5 py-2.5 font-semibold text-white hover:bg-brand-800 transition shadow-sm"
            >
              Enregistrer
            </button>
          </div>
        </form>
      </PageContainer>
    </SiteLayout>
  );
};

const CheckboxGroup = ({
  name,
  label,
  options,
  form,
  setForm,
  required = false,
}: {
  name: keyof EditableProfile;
  label: string;
  options: readonly { value: string; label: string }[];
  form: EditableProfile;
  setForm: React.Dispatch<React.SetStateAction<EditableProfile | null>>;
  required?: boolean;
}) => {
  const current = Array.isArray(form[name]) ? (form[name] as string[]) : [];
  return (
    <section className="rounded-lg border border-stone-200 bg-stone-50 p-4">
      <h2 className="text-sm font-semibold text-stone-700">
        {label} {required ? '*' : ''}
      </h2>
      <div className="mt-3 grid gap-2 sm:grid-cols-2">
        {options.map(({ value, label: text }) => (
          <label
            key={value}
            className="grid grid-cols-[1rem_1fr] items-center gap-3 rounded-lg bg-white px-3 py-2 text-sm text-stone-700 cursor-pointer select-none hover:text-stone-950"
          >
            <input
              type="checkbox"
              name={String(name)}
              value={value}
              checked={current.includes(value)}
              onChange={(event) =>
                setForm((previous) => {
                  if (!previous) return null;
                  return {
                    ...previous,
                    [name]: normalizeCheckboxSelection(
                      name,
                      value,
                      event.target.checked,
                      current,
                    ),
                  };
                })
              }
              aria-label={text}
              className="h-4 w-4 shrink-0 rounded border-stone-300 text-brand-700 focus:ring-brand-500"
            />
            <span aria-hidden="true" className="leading-5">{text}</span>
          </label>
        ))}
      </div>
    </section>
  );
};
