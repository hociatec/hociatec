import { useState } from 'react';
import { useToast } from '@/shared/components/ui/toast';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { createAuditRequest, type AuditType } from '../api/auditsApi';
import { notifyMutationError, notifyMutationSuccess } from '@/shared/lib/notificationConventions';

export type AuditRequestFormValues = {
  objectives: string;
  type: AuditType;
  url: string;
};

export const useRequestAudit = () => {
  const toast = useToast();
  const [loading, setLoading] = useState(false);
  const [createdNumber, setCreatedNumber] = useState<string | null>(null);
  const onSubmit = async (values: AuditRequestFormValues) => {
    setLoading(true);
    try {
      const objectives = values.objectives.trim();
      const result = await createAuditRequest({
        ...(objectives ? { objectives } : {}),
        type: values.type,
        url: values.url,
      });
      setCreatedNumber(result.data.number);
      notifyMutationSuccess(
        toast,
        result.message ?? 'Votre demande d’audit a bien été enregistrée.',
      );
    } catch (error) {
      notifyMutationError(toast, error, 'Impossible de créer la demande.');
      throw error;
    } finally {
      setLoading(false);
    }
  };
  return {
    loading,
    createdNumber,
    getSubmitError: (error: unknown) => getHttpErrorMessage(error, 'Impossible de créer la demande.'),
    onSubmit,
  };
};
