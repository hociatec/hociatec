import { useState } from 'react';
import type { FormEvent } from 'react';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useToast } from '@/shared/components/ui/toast';
import { createAuditRequest, type AuditType } from '../api';

const AUDIT_TYPES: { value: AuditType; label: string }[] = [
  { value: 'performance', label: 'Performance' },
  { value: 'security', label: 'Sécurité' },
  { value: 'ux', label: 'Expérience utilisateur (UX)' },
  { value: 'seo', label: 'SEO' },
  { value: 'technical', label: 'Technique complet' },
  { value: 'accessibility', label: 'Accessibilité numérique' },
];

export const RequestAuditPage = () => {
  useDocumentTitle('Demander un audit');
  const toast = useToast();
  const [type, setType] = useState<AuditType>('accessibility');
  const [url, setUrl] = useState('');
  const [objectives, setObjectives] = useState('');
  const [loading, setLoading] = useState(false);
  const [createdNumber, setCreatedNumber] = useState<string | null>(null);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      const out = await createAuditRequest({ type, url, objectives });
      setCreatedNumber(out.number);
      try { toast.show('Votre demande a été enregistrée.', { variant: 'success' }); } catch {}
      setUrl('');
      setObjectives('');
    } catch (err) {
      const msg = (err as Error)?.message ?? 'Impossible de créer la demande.';
      try { toast.show(msg, { variant: 'error' }); } catch {}
    } finally {
      setLoading(false);
    }
  };

  return (
    <SiteLayout>
      <div className="container mx-auto max-w-2xl p-4">
        <h1 className="text-2xl font-semibold mb-4">Demander un audit</h1>
        <form onSubmit={onSubmit} className="space-y-3">
          <div>
            <label className="block text-sm mb-1">Type d'audit</label>
            <select className="w-full border rounded p-2" value={type} onChange={(e) => setType(e.target.value as AuditType)}>
              {AUDIT_TYPES.map((t) => (
                <option key={t.value} value={t.value}>{t.label}</option>
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
          <button disabled={loading} className="bg-blue-600 text-white px-4 py-2 rounded disabled:opacity-60">
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

