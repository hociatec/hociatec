import { useEffect, useState } from 'react';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { PublicSelect, PublicSubmitButton } from '@/shared/components/forms/PublicForm';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { type AuditRequestFormValues, useRequestAudit } from '../hooks/useRequestAudit';
import { useAuditMetadata } from '../hooks/useAuditMetadata';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { FormField, TextareaField, TextInputField, fieldA11yProps } from '@/shared/forms/FormField';
import { applyServerFieldErrors } from '@/shared/forms/serverErrors';
import { focusFirstInvalidField } from '@/shared/forms/focusFirstInvalidField';
import { useUnsavedChangesWarning } from '@/shared/forms/useUnsavedChangesWarning';

const auditRequestSchema = z.object({
  objectives: z.string().max(5000, 'Objectifs trop longs.'),
  type: z.enum(['performance', 'security', 'ux', 'seo', 'technical', 'accessibility']),
  url: z.string().trim().min(1, 'URL ou accès requis.').max(2048, 'URL trop longue.'),
});

export const RequestAuditPage = () => {
  useDocumentTitle('Demander un audit');
  const { loading, createdNumber, getSubmitError, onSubmit } = useRequestAudit();
  const { types } = useAuditMetadata();
  const [submitError, setSubmitError] = useState<string | null>(null);
  const {
    formState: { errors, isDirty },
    handleSubmit,
    register,
    reset,
    setError,
    setFocus,
    watch,
  } = useForm<AuditRequestFormValues>({
    defaultValues: {
      objectives: '',
      type: 'accessibility',
      url: '',
    },
    resolver: zodResolver(auditRequestSchema),
  });
  useUnsavedChangesWarning(isDirty && !loading);
  const type = watch('type');

  useEffect(() => {
    focusFirstInvalidField(errors, setFocus);
  }, [errors, setFocus]);

  const handleValidSubmit = handleSubmit(async (values) => {
    setSubmitError(null);
    try {
      await onSubmit(values);
      reset({ objectives: '', type: values.type, url: '' });
    } catch (error) {
      applyServerFieldErrors(error, setError);
      setSubmitError(getSubmitError(error));
    }
  });

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        title="Demander un audit"
        description="Décrivez le périmètre à analyser et les objectifs attendus. Hociatec vous recontacte avec un cadrage adapté."
      >
        <PublicPageSection>
          <form onSubmit={handleValidSubmit} className="space-y-5">
            <FormField id="audit-type" label="Type d'audit" error={errors.type}>
              <PublicSelect
                id="audit-type"
                value={type}
                {...fieldA11yProps('audit-type', errors.type)}
                {...register('type')}
                options={types.map((auditType) => ({
                  value: auditType.value,
                  label: auditType.label,
                }))}
              />
            </FormField>
            <TextInputField
              id="audit-url"
              label="URL ou accès"
              placeholder="https://exemple.com"
              error={errors.url}
              {...register('url')}
            />
            <TextareaField
              id="audit-objectives"
              label="Objectifs et points d'attention"
              className="h-40"
              placeholder="Expliquez vos objectifs et points à vérifier"
              error={errors.objectives}
              {...register('objectives')}
            />
            <PublicSubmitButton disabled={loading}>
              {loading ? 'Envoi…' : 'Envoyer la demande'}
            </PublicSubmitButton>
          </form>
        </PublicPageSection>
        {submitError ? <FeedbackMessage>{submitError}</FeedbackMessage> : null}
        {createdNumber && (
          <FeedbackMessage variant="success">
            Dossier créé: {createdNumber}. Vous pouvez suivre l'avancement dans « Mes audits ».
          </FeedbackMessage>
        )}
      </PublicPageShell>
    </SiteLayout>
  );
};
