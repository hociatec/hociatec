import type { FormEvent } from 'react';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import {
  PublicFormField,
  PublicSelect,
  PublicSubmitButton,
  PublicTextInput,
  PublicTextarea,
} from '@/shared/components/forms/PublicForm';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useRequestAudit } from '../hooks/useRequestAudit';
import type { AuditType } from '../api/auditsApi';
import { useAuditMetadata } from '../hooks/useAuditMetadata';
import { FeedbackMessage } from '@/shared/components/ui/page-state';

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
            <PublicFormField label="Type d'audit">
              <PublicSelect
              value={type}
              onChange={(e) => setType(e.target.value as AuditType)}
                options={types.map((auditType) => ({
                  value: auditType.value,
                  label: auditType.label,
                }))}
              />
            </PublicFormField>
            <PublicFormField label="URL ou accès">
              <PublicTextInput
                placeholder="https://exemple.com"
                value={url}
                onChange={(e) => setUrl(e.target.value)}
                required
              />
            </PublicFormField>
            <PublicFormField label="Objectifs et points d'attention">
              <PublicTextarea
                className="h-40"
                placeholder="Expliquez vos objectifs et points à vérifier"
                value={objectives}
                onChange={(e) => setObjectives(e.target.value)}
              />
            </PublicFormField>
            <PublicSubmitButton disabled={loading}>
              {loading ? 'Envoi…' : 'Envoyer la demande'}
            </PublicSubmitButton>
          </form>
        </PublicPageSection>
        {createdNumber && (
          <FeedbackMessage variant="success">
            Dossier créé: {createdNumber}. Vous pouvez suivre l'avancement dans « Mes audits ».
          </FeedbackMessage>
        )}
      </PublicPageShell>
    </SiteLayout>
  );
};
