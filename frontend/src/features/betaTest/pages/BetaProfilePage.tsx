import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { PageContainer } from '@/shared/components/PageContainer';
import { fetchMyBetaProfile, updateMyBetaProfile, leaveBetaProgram } from '../api/betaApi';
import { SiteLayout } from '@/shared/components/SiteLayout';

type EditableProfile = {
  motivation: string;
  testingExperience: string;
  bugDescriptionAbility: string;
  technicalKnowledge: string;
  availability: string[];
  accessibilityNeed: string;
  assistiveTools: string[];
  devices: string[];
  browsers: string[];
  testingTypes: string[];
  betaConsent: boolean;
};

const choices = {
  availability: [
    ['weekdays', 'En semaine'],
    ['evenings', 'En soirée'],
    ['weekends', 'Le week-end'],
    ['flexible', 'Flexible'],
  ],
  assistiveTools: [
    ['nvda', 'NVDA'],
    ['jaws', 'JAWS'],
    ['voiceover', 'VoiceOver'],
    ['talkback', 'TalkBack'],
    ['narrator', 'Narrator'],
    ['magnifier', 'Loupe'],
    ['keyboard', 'Navigation au clavier'],
    ['braille', 'Plage braille'],
    ['other', 'Autre'],
  ],
  devices: [
    ['windows', 'Windows'],
    ['macos', 'macOS'],
    ['linux', 'Linux'],
    ['android', 'Android'],
    ['ios', 'iPhone/iPad'],
  ],
  browsers: [
    ['chrome', 'Chrome'],
    ['firefox', 'Firefox'],
    ['edge', 'Edge'],
    ['safari', 'Safari'],
    ['other', 'Autre'],
  ],
  testingTypes: [
    ['bugs', 'Bugs'],
    ['accessibility', 'Accessibilité'],
    ['usability', 'Ergonomie'],
    ['mobile', 'Mobile'],
    ['performance', 'Performances'],
    ['features', 'Nouvelles fonctionnalités'],
  ],
} as const;

export const BetaProfilePage = () => {
  const navigate = useNavigate();
  const [form, setForm] = useState<EditableProfile | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    void fetchMyBetaProfile()
      .then((profile) =>
        setForm({
          motivation: String(profile.motivation ?? ''),
          testingExperience: String(profile.testingExperience ?? ''),
          bugDescriptionAbility: String(profile.bugDescriptionAbility ?? ''),
          technicalKnowledge: String(profile.technicalKnowledge ?? ''),
          availability: Array.isArray(profile.availability)
            ? (profile.availability as string[])
            : ['flexible'],
          accessibilityNeed: String(profile.accessibilityNeed ?? 'none'),
          assistiveTools: Array.isArray(profile.assistiveTools)
            ? (profile.assistiveTools as string[])
            : [],
          devices: Array.isArray(profile.devices) ? (profile.devices as string[]) : ['windows'],
          browsers: Array.isArray(profile.browsers) ? (profile.browsers as string[]) : ['chrome'],
          testingTypes: Array.isArray(profile.testingTypes)
            ? (profile.testingTypes as string[])
            : ['bugs'],
          betaConsent: true,
        }),
      )
      .catch(() => {
        // En cas de profil non existant, on propose un profil vierge à créer
        setForm({
          motivation: '',
          testingExperience: '',
          bugDescriptionAbility: '',
          technicalKnowledge: '',
          availability: ['flexible'],
          accessibilityNeed: 'none',
          assistiveTools: [],
          devices: ['windows'],
          browsers: ['chrome'],
          testingTypes: ['bugs'],
          betaConsent: true,
        });
      });
  }, []);

  const save = async (event: FormEvent) => {
    event.preventDefault();
    if (!form) return;

    if (
      !form.motivation.trim() ||
      !form.testingExperience.trim() ||
      !form.bugDescriptionAbility.trim() ||
      !form.availability.length ||
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

  if (!form) {
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

            <label className="block text-sm font-semibold text-stone-700">
              Expérience des tests *
              <textarea
                className="mt-1 w-full rounded-lg border border-stone-300 p-3 text-sm focus:outline-none focus:border-brand-700"
                rows={4}
                value={form.testingExperience}
                onChange={(e) => setForm({ ...form, testingExperience: e.target.value })}
                required
              />
            </label>

            <label className="block text-sm font-semibold text-stone-700">
              Capacité à décrire un bug *
              <textarea
                className="mt-1 w-full rounded-lg border border-stone-300 p-3 text-sm focus:outline-none focus:border-brand-700"
                rows={4}
                value={form.bugDescriptionAbility}
                onChange={(e) => setForm({ ...form, bugDescriptionAbility: e.target.value })}
                required
                placeholder="Étapes de reproduction, résultat attendu…"
              />
            </label>

            <label className="block text-sm font-semibold text-stone-700">
              Connaissances techniques <small className="font-normal text-stone-500">(facultatif)</small>
              <textarea
                className="mt-1 w-full rounded-lg border border-stone-300 p-3 text-sm focus:outline-none focus:border-brand-700"
                rows={3}
                value={form.technicalKnowledge}
                onChange={(e) => setForm({ ...form, technicalKnowledge: e.target.value })}
              />
            </label>

            <label className="block text-sm font-semibold text-stone-700">
              Accessibilité *
              <select
                name="accessibilityNeed"
                value={form.accessibilityNeed}
                onChange={(e) => setForm({ ...form, accessibilityNeed: e.target.value })}
                required
                className="mt-1 w-full rounded-lg border border-stone-300 p-3 text-sm bg-white focus:outline-none focus:border-brand-700"
              >
                <option value="">Sélectionnez une option</option>
                <option value="blind">Non-voyante</option>
                <option value="low_vision">Malvoyante</option>
                <option value="none">Aucun besoin particulier</option>
              </select>
            </label>
          </div>

          <CheckboxGroup
            name="assistiveTools"
            label="Outils utilisés"
            options={choices.assistiveTools}
            form={form}
            setForm={setForm}
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
              className="mt-0.5 rounded border-stone-300 text-brand-700 focus:ring-brand-500 h-4 w-4"
            />
            <span className="text-stone-600">
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
            <button
              type="button"
              className="rounded-lg border border-red-300 px-5 py-2.5 text-red-700 font-semibold hover:bg-red-50 transition"
              onClick={() => {
                if (window.confirm('Supprimer vos données bêta ?')) {
                  void leaveBetaProgram().then(() => navigate('/'));
                }
              }}
            >
              Quitter le programme
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
  options: readonly (readonly [string, string])[];
  form: EditableProfile;
  setForm: React.Dispatch<React.SetStateAction<EditableProfile | null>>;
  required?: boolean;
}) => {
  const current = Array.isArray(form[name]) ? (form[name] as string[]) : [];
  return (
    <fieldset className="border border-stone-200 rounded-lg p-4 bg-stone-50">
      <legend className="text-sm font-semibold text-stone-700 px-2">
        {label} {required ? '*' : ''}
      </legend>
      <div className="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2">
        {options.map(([value, text]) => (
          <label
            key={value}
            className="flex items-center gap-2 text-sm cursor-pointer hover:text-stone-900 select-none"
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
                    [name]: event.target.checked
                      ? [...current, value]
                      : current.filter((item) => item !== value),
                  };
                })
              }
              className="rounded border-stone-300 text-brand-700 focus:ring-brand-500 h-4 w-4"
            />
            <span className="text-stone-600">{text}</span>
          </label>
        ))}
      </div>
    </fieldset>
  );
};
