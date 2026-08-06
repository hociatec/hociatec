import { useEffect, useState } from 'react';
import { useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  createCustomerVoucher,
  fetchAdminCustomerById,
  type AdminCustomerDetailDto,
  type AdminCustomerVoucherDto,
  type CustomerVoucherPayload,
} from '../api';
import { deleteVoucher } from '@/features/admin/vouchers/api';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';
import { adminCustomerQueryKeys, adminVoucherQueryKeys } from '@/features/admin/customers/queryKeys';
import { omitUndefinedProperties } from '@/shared/lib/object';

export type VoucherFormState = {
  name: string;
  code: string;
  description: string;
  discountType: 'percent' | 'fixed_cents';
  discountValue: string;
  isActive: boolean;
  startsAt: string;
  endsAt: string;
  sendEmail: boolean;
};
export const emptyVoucherForm: VoucherFormState = {
  name: '',
  code: '',
  description: '',
  discountType: 'fixed_cents',
  discountValue: '',
  isActive: true,
  startsAt: '',
  endsAt: '',
  sendEmail: true,
};
const buildPayload = (form: VoucherFormState): CustomerVoucherPayload => omitUndefinedProperties({
  name: form.name.trim(),
  code: form.code.trim() || undefined,
  description: form.description.trim() || null,
  discountType: form.discountType,
  discountValue:
    form.discountType === 'fixed_cents'
      ? Math.max(
          0,
          Math.round((Number.parseFloat(form.discountValue.replace(',', '.')) || 0) * 100),
        )
      : Math.max(0, Number.parseInt(form.discountValue, 10) || 0),
  isActive: form.isActive,
  startsAt: form.startsAt || null,
  endsAt: form.endsAt || null,
  sendEmail: form.sendEmail,
});

export const useAdminCustomerVouchers = () => {
  const { customerId: rawCustomerId } = useParams();
  const customerId = Number(rawCustomerId);
  const confirm = useConfirm();
  const toast = useToast();
  const queryClient = useQueryClient();
  const [form, setForm] = useState<VoucherFormState>(emptyVoucherForm);
  const [error, setError] = useState<string | null>(null);
  const customerQuery = useQuery({
    queryKey: adminCustomerQueryKeys.vouchers(Number.isFinite(customerId) ? customerId : null),
    queryFn: () => fetchAdminCustomerById(customerId),
    enabled: Number.isFinite(customerId) && customerId > 0,
  });
  const customer: AdminCustomerDetailDto | null = customerQuery.data?.customer ?? null;
  const vouchers: AdminCustomerVoucherDto[] = customerQuery.data?.vouchers ?? [];
  const createMutation = useMutation({
    mutationFn: () => {
      if (!customer) throw new Error('Client invalide.');
      return createCustomerVoucher(customer.id, buildPayload(form));
    },
    onSuccess: (result) => {
      setForm(emptyVoucherForm);
      queryClient.setQueryData<Awaited<ReturnType<typeof fetchAdminCustomerById>>>(
        adminCustomerQueryKeys.vouchers(customerId),
        (current) =>
          current
            ? {
                ...current,
                vouchers: [result.voucher, ...current.vouchers],
              }
            : current,
      );
      toast.show(
        `Bon ${result.voucher.code} créé${result.emailSent ? ' et envoyé par e-mail.' : '.'}`,
        { variant: 'success' },
      );
    },
    onError: (e) =>
      toast.show(e instanceof Error ? e.message : 'Impossible de créer le bon de réduction.', {
        variant: 'error',
      }),
  });
  const deleteMutation = useMutation({
    mutationFn: deleteVoucher,
    onSuccess: (response, voucherId) => {
      queryClient.setQueryData<Awaited<ReturnType<typeof fetchAdminCustomerById>>>(
        adminCustomerQueryKeys.vouchers(customerId),
        (current) =>
          current
            ? {
                ...current,
                vouchers: current.vouchers.filter((item) => item.id !== voucherId),
              }
            : current,
      );
      void queryClient.invalidateQueries({ queryKey: adminVoucherQueryKeys.list() });
      toast.show(response.message ?? 'Le bon de réduction a bien été supprimé.', {
        variant: 'success',
      });
    },
    onError: (e) =>
      toast.show(e instanceof Error ? e.message : 'Impossible de supprimer le bon.', {
        variant: 'error',
      }),
  });

  useEffect(() => {
    if (!customerId) {
      setError('Client invalide.');
      return;
    }
  }, [customerId]);

  useEffect(() => {
    if (!customerQuery.data) return;
    setForm((current) => ({
      ...current,
      name: current.name || `Offre client ${customerQuery.data.customer.lastName}`,
    }));
  }, [customerQuery.data]);

  const handleSubmit = () => {
    if (!customer) return;
    createMutation.mutate();
  };
  const handleDelete = async (voucherId: number) => {
    const voucher = vouchers.find((item) => item.id === voucherId);
    if (
      !(await confirm({
        title: 'Supprimer le bon',
        description: `Supprimer ${voucher ? `"${voucher.name}" (${voucher.code})` : 'ce bon de réduction'} ?`,
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      }))
    )
      return;
    deleteMutation.mutate(voucherId);
  };
  return {
    customer,
    vouchers,
    form,
    setForm,
    status: customerQuery.isLoading ? 'loading' : error || customerQuery.error ? 'error' : 'success',
    error: error ?? customerQuery.error?.message ?? null,
    saving: createMutation.isPending,
    handleSubmit,
    handleDelete,
  };
};
