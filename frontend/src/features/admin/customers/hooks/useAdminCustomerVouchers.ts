import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
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
const buildPayload = (form: VoucherFormState): CustomerVoucherPayload => ({
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
  const [customer, setCustomer] = useState<AdminCustomerDetailDto | null>(null);
  const [vouchers, setVouchers] = useState<AdminCustomerVoucherDto[]>([]);
  const [form, setForm] = useState<VoucherFormState>(emptyVoucherForm);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  useEffect(() => {
    if (!customerId) {
      setStatus('error');
      setError('Client invalide.');
      return;
    }
    setStatus('loading');
    void fetchAdminCustomerById(customerId)
      .then((data) => {
        setCustomer(data.customer);
        setVouchers(data.vouchers);
        setForm((current) => ({
          ...current,
          name: current.name || `Offre client ${data.customer.lastName}`,
        }));
        setStatus('success');
      })
      .catch((e) => {
        setStatus('error');
        setError(e instanceof Error ? e.message : 'Impossible de charger ce client.');
      });
  }, [customerId]);
  const handleSubmit = () => {
    if (!customer) return;
    setSaving(true);
    void createCustomerVoucher(customer.id, buildPayload(form))
      .then((result) => {
        setForm(emptyVoucherForm);
        setVouchers((items) => [result.voucher, ...items]);
        toast.show(
          `Bon ${result.voucher.code} créé${result.emailSent ? ' et envoyé par e-mail.' : '.'}`,
          { variant: 'success' },
        );
      })
      .catch((e) =>
        toast.show(e instanceof Error ? e.message : 'Impossible de créer le bon de réduction.', {
          variant: 'error',
        }),
      )
      .finally(() => setSaving(false));
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
    try {
      const response = await deleteVoucher(voucherId);
      setVouchers((items) => items.filter((item) => item.id !== voucherId));
      toast.show(response.message ?? 'Le bon de réduction a bien été supprimé.', { variant: 'success' });
    } catch (e) {
      toast.show(e instanceof Error ? e.message : 'Impossible de supprimer le bon.', {
        variant: 'error',
      });
    }
  };
  return {
    customer,
    vouchers,
    form,
    setForm,
    status,
    error,
    saving,
    handleSubmit,
    handleDelete,
  };
};
