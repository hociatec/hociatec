import { Pencil } from 'lucide-react';

import { formatEuroCents, formatFrenchNumber } from '@/shared/lib/formatters';
import type { AdminLoyaltyCustomerDto } from '@/features/loyalty/publicApi';

type AdminLoyaltyCustomerRowProps = {
  customer: AdminLoyaltyCustomerDto;
  onAdjust: (customer: AdminLoyaltyCustomerDto) => void;
};

export const AdminLoyaltyCustomerRow = ({ customer, onAdjust }: AdminLoyaltyCustomerRowProps) => (
  <tr>
    <th scope="row">
      <div className="font-semibold text-brand-900">{customer.fullName}</div>
      <div className="muted">{customer.email}</div>
    </th>
    <td>{formatFrenchNumber(customer.points)} pts</td>
    <td>{formatEuroCents(customer.euroCents)}</td>
    <td>
      <button
        type="button"
        className="catalog-admin-actions__edit inline-flex items-center gap-2"
        onClick={() => onAdjust(customer)}
      >
        <Pencil size={16} aria-hidden="true" />
        Ajuster
      </button>
    </td>
  </tr>
);
