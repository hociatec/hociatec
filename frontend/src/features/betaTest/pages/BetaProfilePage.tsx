import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { PageContainer } from '@/shared/components/PageContainer';
import { fetchMyBetaProfile, updateMyBetaProfile, leaveBetaProgram } from '../api/betaApi';

type EditableProfile = { motivation: string; testingExperience: string; bugDescriptionAbility: string; technicalKnowledge: string; availability: string[]; accessibilityNeed: string; assistiveTools: string[]; devices: string[]; browsers: string[]; testingTypes: string[]; betaConsent: boolean };

export const BetaProfilePage = () => {
  const navigate = useNavigate();
  const [form, setForm] = useState<EditableProfile | null>(null);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => {
    void fetchMyBetaProfile()
      .then(profile => setForm({
        motivation: String(profile.motivation ?? ''),
        testingExperience: String(profile.testingExperience ?? ''),
        bugDescriptionAbility: String(profile.bugDescriptionAbility ?? ''),
        technicalKnowledge: String(profile.technicalKnowledge ?? ''),
        availability: Array.isArray(profile.availability) ? profile.availability as string[] : ['flexible'],
        accessibilityNeed: String(profile.accessibilityNeed ?? 'none'),
        assistiveTools: Array.isArray(profile.assistiveTools) ? profile.assistiveTools as string[] : [],
        devices: Array.isArray(profile.devices) ? profile.devices as string[] : ['windows'],
        browsers: Array.isArray(profile.browsers) ? profile.browsers as string[] : ['chrome'],
        testingTypes: Array.isArray(profile.testingTypes) ? profile.testingTypes as string[] : ['bugs'],
        betaConsent: true
      }))
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
          betaConsent: true
        });
      });
  }, []);
  const save = async (event: FormEvent) => { event.preventDefault(); if (!form) return; try { await updateMyBetaProfile(form); navigate('/beta'); } catch (reason) { setError(reason instanceof Error ? reason.message : 'Impossible de mettre à jour le profil.'); } };
  if (!form) return <PageContainer title="Mon profil bêta">{error ? <p className="text-red-700">{error}</p> : <p>Chargement…</p>}</PageContainer>;
  return <PageContainer title="Mon profil bêta"><form onSubmit={save} className="max-w-2xl space-y-4 rounded border bg-white p-6">{(['motivation','testingExperience','bugDescriptionAbility','technicalKnowledge'] as const).map(field => <label key={field} className="block">{field === 'motivation' ? 'Motivation' : field === 'testingExperience' ? 'Expérience des tests' : field === 'bugDescriptionAbility' ? 'Capacité à décrire un bug' : 'Connaissances techniques'}<textarea className="mt-1 w-full rounded border p-3" rows={4} value={form[field]} onChange={event => setForm({ ...form, [field]: event.target.value })} required={field !== 'technicalKnowledge'} /></label>)}{error && <p className="text-red-700">{error}</p>}<div className="flex gap-3"><button className="rounded bg-brand-700 px-4 py-2 font-semibold text-white">Enregistrer</button><button type="button" className="rounded border border-red-300 px-4 py-2 text-red-700" onClick={() => { if (window.confirm('Supprimer vos données bêta ?')) void leaveBetaProgram().then(() => navigate('/')); }}>Quitter le programme</button></div></form></PageContainer>;
};
