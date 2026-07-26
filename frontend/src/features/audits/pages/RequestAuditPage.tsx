import type { FormEvent } from 'react';
import { SiteLayout } from '@/shared/components/SiteLayout';
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
    <SiteLayout>
      <div className="container mx-auto max-w-2xl p-4">
        <h1 className="text-2xl font-semibold mb-4">Demander un audit</h1>
        <form onSubmit={handleSubmit} className="space-y-3">
          <div>
            <label className="block text-sm mb-1">Type d'audit</label>
            <select
              className="w-full border rounded p-2"
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
            <label className="block text-sm mb-1">URL ou accès</label>
            <input
              className="w-full border rounded p-2"
              placeholder="https://exemple.com"
              value={url}
              onChange={(e) => setUrl(e.target.value)}
              required
            />
          </div>
          <div>
            <label className="block text-sm mb-1">Objectifs et points d'attention</label>
            <textarea
              className="w-full border rounded p-2 h-40"
              placeholder="Expliquez vos objectifs et points à vérifier"
              value={objectives}
              onChange={(e) => setObjectives(e.target.value)}
            />
          </div>
          <button
            disabled={loading}
            className="bg-brand-600 text-white px-4 py-2 rounded disabled:opacity-60"
          >
            {loading ? 'Envoi…' : 'Envoyer la demande'}
          </button>
        </form>
        {createdNumber && (
          <div className="mt-4 p-3 border rounded bg-green-50 text-green-900">
            Dossier créé: {createdNumber}. Vous pouvez suivre l'avancement dans « Mes audits ».
          </div>
        )}
      </div>
    </SiteLayout>
  );
};
