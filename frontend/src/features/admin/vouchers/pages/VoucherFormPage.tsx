import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  createVoucher,
  fetchVoucher,
  updateVoucher,
  type VoucherPayload,
} from '@/features/admin/vouchers/api';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useToast } from '@/shared/components/ui/toast';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import {
  formatApiDateForDateTimeInput,
  formatEuroInputFromCents,
  parseEuroInputToCents,
} from '@/shared/lib/formatters';
import { parseNonNegativeInteger, parseNullablePositiveInteger } from '@/shared/lib/parsers';
import { slugify } from '@/shared/lib/slugify';
import {
  VoucherFormFields,
  type VoucherFormState,
} from '@/features/admin/vouchers/components/VoucherFormFields';
import { adminVoucherQueryKeys } from '@/features/admin/vouchers/queryKeys';
import { createRandomCodeSuffix } from '@/shared/lib/random';

const emptyForm: VoucherFormState = {
  name: '',
  code: '',
  description: '',
  discountType: 'fixed_cents',
  discountValue: '',
  isActive: true,
  startsAt: '',
  endsAt: '',
};

const generateCode = (name: string) => {
  const base = ((name.trim() ? slugify(name).toUpperCase() : 'BON').slice(0, 16) || 'BON');
  return `${base}-${createRandomCodeSuffix(2)}`;
};

export const VoucherFormPage = () => {
  const { voucherId } = useParams();
  const editingId = parseNullablePositiveInteger(voucherId);
  const safeVoucherId = editingId ?? 0;
  const isEdit = editingId !== null;
  useDocumentTitle(isEdit ? 'Admin - Modifier un bon' : 'Admin - Nouveau bon');
  const navigate = useNavigate();
  const toast = useToast();
  const queryClient = useQueryClient();
  const [form, setForm] = useState<VoucherFormState>(emptyForm);
  const [error, setError] = useState<string | null>(null);
  const voucherQuery = useQuery({
    queryKey: adminVoucherQueryKeys.detail(editingId),
    queryFn: () => fetchVoucher(safeVoucherId),
    enabled: isEdit,
  });

  useEffect(() => {
    if (!voucherQuery.data) return;
    const voucher = voucherQuery.data;
    setForm({
      name: voucher.name,
      code: voucher.code,
      description: voucher.description ?? '',
      discountType: voucher.discountType,
      discountValue:
        voucher.discountType === 'fixed_cents'
          ? formatEuroInputFromCents(voucher.discountValue)
          : String(voucher.discountValue),
      isActive: voucher.isActive,
      startsAt: formatApiDateForDateTimeInput(voucher.startsAt),
      endsAt: formatApiDateForDateTimeInput(voucher.endsAt),
    });
  }, [voucherQuery.data]);

  const payload = useMemo<VoucherPayload>(
    () => ({
      name: form.name.trim(),
      code: form.code.trim().toUpperCase(),
      description: form.description.trim() || null,
      discountType: form.discountType,
      discountValue:
        form.discountType === 'fixed_cents'
          ? parseEuroInputToCents(form.discountValue)
          : parseNonNegativeInteger(form.discountValue, 0),
      isActive: form.isActive,
      startsAt: form.startsAt || null,
      endsAt: form.endsAt || null,
    }),
    [form],
  );

  const saveMutation = useMutation({
    mutationFn: () => {
      const nextPayload = {
        ...payload,
        code: form.code.trim() || generateCode(form.name),
      };
      if (isEdit && editingId !== null) {
        return updateVoucher(safeVoucherId, nextPayload);
      }
      return createVoucher(nextPayload);
    },
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminVoucherQueryKeys.list() });
      toast.show(
        response.message ??
          (isEdit
            ? 'Le bon de réduction a bien été mis à jour.'
            : 'Le bon de réduction a bien été créé.'),
        { variant: 'success' },
      );
      navigate('/admin/vouchers');
    },
    onError: (err) => {
      const message = getHttpErrorMessage(err, 'Enregistrement impossible.');
      setError(message);
      toast.show(message, { variant: 'error' });
    },
  });

  const handleSave = async () => {
    setError(null);
    saveMutation.mutate();
  };

  return (
    <PageContainer
      size="admin"
      title={isEdit ? 'Modifier un bon' : 'Créer un bon'}
      headerActions={
        <button
          type="button"
          className="catalog-admin-actions__edit"
          onClick={() => navigate('/admin/vouchers')}
        >
          Retour à la liste
        </button>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          Formulaire dédié à la création et à la modification d’un bon de réduction.
        </p>
        <p className="text-sm text-stone-500">
          Si le code est vide, il sera généré automatiquement à l’enregistrement.
        </p>
      </div>

      {(error || voucherQuery.error) && (
        <FeedbackMessage>
          {error ?? getHttpErrorMessage(voucherQuery.error, 'Impossible de charger le bon.')}
        </FeedbackMessage>
      )}

      {voucherQuery.isLoading ? (
        <LoadingState>Chargement...</LoadingState>
      ) : (
        <form
          className="register-form-card form-card-grid"
          onSubmit={(event) => {
            event.preventDefault();
            void handleSave();
          }}
        >
          <VoucherFormFields form={form} setForm={setForm} />

          <div className="flex gap-3">
            <button type="submit" className="register-form__submit" disabled={saveMutation.isPending}>
              {saveMutation.isPending
                ? 'Enregistrement...'
                : isEdit
                  ? 'Mettre à jour le bon'
                  : 'Créer le bon'}
            </button>
          </div>
        </form>
      )}
    </PageContainer>
  );
};
