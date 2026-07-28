import type { FormEvent } from 'react';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useRequestAudit } from '../hooks/useRequestAudit';
import type { AuditType } from '../api/auditsApi';
import { useAuditMetadata } from '../hooks/useAuditMetadata';

export const RequestAuditPage = () => {
  useDocumentTitle('Demander un audit');
  const {
    type,
    setType,
    url,
    setUrl,
    objectives,
    setObjectives,
    loading,
    createdNumber,
    onSubmit,
  } = useRequestAudit();
  const { types } = useAuditMetadata();

  const handleSubmit = (event: FormEvent) => {
    event.preventDefault();
    void onSubmit();
  };

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        eyebrow="Audit"
        title="Demander un audit"
        description="Décrivez le périmètre à analyser et les objectifs attendus. Hociatec vous recontacte avec un cadrage adapté."
      >
        <PublicPageSection>
        <form onSubmit={handleSubmit} className="space-y-5">
          <div>
            <label className="mb-1 block text-sm font-semibold text-brand-900">Type d'audit</label>
            <select
              className="w-full rounded-xl border border-brand-100 bg-white px-4 py-3 text-brand-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
              value={type}
              onChange={(e) => setType(e.target.value as AuditType)}
            >
              {types.map((t) => (
                <option key={t.value} value={t.value}>
                  {t.label}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-brand-900">URL ou accès</label>
            <input
              className="w-full rounded-xl border border-brand-100 bg-white px-4 py-3 text-brand-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
              placeholder="https://exemple.com"
              value={url}
              onChange={(e) => setUrl(e.target.value)}
              required
            />
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-brand-900">
              Objectifs et points d'attention
            </label>
            <textarea
              className="h-40 w-full rounded-xl border border-brand-100 bg-white px-4 py-3 text-brand-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
              placeholder="Expliquez vos objectifs et points à vérifier"
              value={objectives}
              onChange={(e) => setObjectives(e.target.value)}
            />
          </div>
          <button
            disabled={loading}
            className="inline-flex items-center justify-center rounded-full bg-brand-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-60"
          >
            {loading ? 'Envoi…' : 'Envoyer la demande'}
          </button>
        </form>
        </PublicPageSection>
        {createdNumber && (
          <div className="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-900">
            Dossier créé: {createdNumber}. Vous pouvez suivre l'avancement dans « Mes audits ».
          </div>
        )}
      </PublicPageShell>
    </SiteLayout>
  );
};
