import { useState } from 'react';
import type { FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { PageContainer } from '@/shared/components/PageContainer';
import { createBugReport } from '../api/betaApi';

export const BetaBugReportPage = () => {
  const navigate = useNavigate();
  const [form, setForm] = useState({ title: '', description: '', expectedBehavior: '', actualBehavior: '', severity: 'normal', screenshots: [] as File[] });
  const [error, setError] = useState<string | null>(null);
  const submit = async (event: FormEvent) => { event.preventDefault(); try { await createBugReport(form); navigate('/beta'); } catch (reason) { setError(reason instanceof Error ? reason.message : 'Impossible d’envoyer le signalement.'); } };
  return <PageContainer title="Nouveau signalement"><form onSubmit={submit} className="max-w-2xl space-y-5 rounded-lg border border-stone-200 bg-white p-6"><label className="block">Titre<input className="mt-1 w-full rounded border p-3" value={form.title} onChange={e => setForm({ ...form, title: e.target.value })} required maxLength={180} /></label><label className="block">Description<textarea className="mt-1 w-full rounded border p-3" rows={6} value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} required /></label><label className="block">Résultat attendu<textarea className="mt-1 w-full rounded border p-3" rows={3} value={form.expectedBehavior} onChange={e => setForm({ ...form, expectedBehavior: e.target.value })} /></label><label className="block">Résultat observé<textarea className="mt-1 w-full rounded border p-3" rows={3} value={form.actualBehavior} onChange={e => setForm({ ...form, actualBehavior: e.target.value })} /></label><label className="block">Gravité<select className="mt-1 w-full rounded border p-3" value={form.severity} onChange={e => setForm({ ...form, severity: e.target.value })}><option value="low">Faible</option><option value="normal">Normale</option><option value="high">Haute</option><option value="critical">Critique</option></select></label><label className="block">Captures d’écran (PNG, JPEG ou WebP, 5 fichiers maximum)<input className="mt-1 w-full" type="file" accept="image/png,image/jpeg,image/webp" multiple onChange={e => setForm({ ...form, screenshots: Array.from(e.target.files ?? []).slice(0,5) })} /></label>{error && <p className="text-red-700">{error}</p>}<button className="rounded bg-brand-700 px-5 py-3 font-semibold text-white">Envoyer</button></form></PageContainer>;
};
