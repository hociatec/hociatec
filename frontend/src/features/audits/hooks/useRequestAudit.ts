import { useState } from 'react';
import { useToast } from '@/shared/components/ui/toast';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { createAuditRequest, type AuditType } from '../api/auditsApi';

export const useRequestAudit = () => {
  const toast = useToast();
  const [type, setType] = useState<AuditType>('accessibility');
  const [url, setUrl] = useState('');
  const [objectives, setObjectives] = useState('');
  const [loading, setLoading] = useState(false);
  const [createdNumber, setCreatedNumber] = useState<string | null>(null);
  const onSubmit = async () => {
    setLoading(true);
    try { const result = await createAuditRequest({ type, url, objectives }); setCreatedNumber(result.number); toast.show('Votre demande a été enregistrée.', { variant: 'success' }); setUrl(''); setObjectives(''); }
    catch (error) { toast.show(getHttpErrorMessage(error, 'Impossible de créer la demande.'), { variant: 'error' }); }
    finally { setLoading(false); }
  };
  return { type, setType, url, setUrl, objectives, setObjectives, loading, createdNumber, onSubmit };
};
