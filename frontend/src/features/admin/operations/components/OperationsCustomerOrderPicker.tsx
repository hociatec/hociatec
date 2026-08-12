import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';

import { fetchAdminCustomerById, fetchAdminCustomers, type AdminCustomerSummaryDto } from '@/features/admin/customers/api';
import type { OrderDto } from '@/features/orders/publicApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
import { Field, operationsUi } from '@/features/admin/operations/components/AdminOperationsWidgets';
import { formatEuroCents, formatFrenchDateTime } from '@/shared/lib/formatters';

const { inputClass, secondaryActionClass } = operationsUi;

type OperationsCustomerOrderPickerProps = {
  customerId: string;
  orderId: string;
  onCustomerChange: (customerId: string) => void;
  onOrderChange: (orderId: string) => void;
  orderLabel: string;
  orderHelper?: string;
  orderRequired?: boolean;
};

export const OperationsCustomerOrderPicker = ({
  customerId,
  orderId,
  onCustomerChange,
  onOrderChange,
  orderLabel,
  orderHelper,
  orderRequired = false,
}: OperationsCustomerOrderPickerProps) => {
  const [customerSearch, setCustomerSearch] = useState('');
  const [orderSearch, setOrderSearch] = useState('');
  const debouncedCustomerSearch = useDebounce(customerSearch.trim(), 250);
  const debouncedOrderSearch = useDebounce(orderSearch.trim(), 250);
  const selectedCustomerId = parseNullablePositiveInteger(customerId);
  const selectedOrderId = parseNullablePositiveInteger(orderId);

  const customerSearchQuery = useQuery({
    queryKey: ['admin', 'operations', 'customer-search', debouncedCustomerSearch],
    queryFn: () => fetchAdminCustomers(debouncedCustomerSearch, 'recent_order', 1, 8),
    enabled: debouncedCustomerSearch.length >= 2,
  });

  const selectedCustomerQuery = useQuery({
    queryKey: ['admin', 'operations', 'customer-selected', selectedCustomerId],
    queryFn: () => fetchAdminCustomerById(selectedCustomerId as number, { orderPerPage: 1 }),
    enabled: selectedCustomerId !== null,
  });

  const customerOrdersQuery = useQuery({
    queryKey: ['admin', 'operations', 'customer-orders', selectedCustomerId, debouncedOrderSearch],
    queryFn: () =>
      fetchAdminCustomerById(selectedCustomerId as number, {
        orderPerPage: 12,
        orderQuery: debouncedOrderSearch,
      }),
    enabled: selectedCustomerId !== null,
  });

  useEffect(() => {
    if (selectedCustomerQuery.data?.customer) {
      const customer = selectedCustomerQuery.data.customer;
      setCustomerSearch(`${customer.fullName} · ${customer.email}`);
    }
  }, [selectedCustomerQuery.data]);

  const matchingCustomers = customerSearchQuery.data?.items ?? [];
  const matchingOrders = customerOrdersQuery.data?.orders.items ?? [];
  const selectedCustomer = selectedCustomerQuery.data?.customer ?? null;
  const selectedOrderIdString = selectedOrderId !== null ? String(selectedOrderId) : '';
  const selectedOrder =
    matchingOrders.find((item) => String(item.id) === selectedOrderIdString) ??
    customerOrdersQuery.data?.orders.items.find((item) => String(item.id) === selectedOrderIdString) ??
    null;

  const selectCustomer = (customer: AdminCustomerSummaryDto) => {
    onCustomerChange(String(customer.id));
    onOrderChange('');
    setOrderSearch('');
    setCustomerSearch(`${customer.firstName} ${customer.lastName}`.trim() + ` · ${customer.email}`);
  };

  const clearCustomer = () => {
    onCustomerChange('');
    onOrderChange('');
    setCustomerSearch('');
    setOrderSearch('');
  };

  const selectOrder = (order: OrderDto) => {
    onOrderChange(String(order.id));
    setOrderSearch(order.number);
  };

  return (
    <div className="space-y-4 sm:col-span-2">
      <Field
        label="Client"
        helper="Recherche par nom, prénom ou email. Les résultats viennent du backend."
      >
        <input
          className={inputClass}
          placeholder="Ex. Ada Lovelace ou ada@example.test"
          value={customerSearch}
          onChange={(event) => {
            setCustomerSearch(event.target.value);
            if (selectedCustomerId !== null) {
              onCustomerChange('');
              onOrderChange('');
            }
          }}
        />
      </Field>

      {customerSearchQuery.error ? (
        <p className="text-sm text-red-600">
          {getHttpErrorMessage(customerSearchQuery.error, 'Impossible de rechercher les clients.')}
        </p>
      ) : null}

      {debouncedCustomerSearch.length >= 2 && selectedCustomerId === null ? (
        <div className="space-y-2 rounded-xl border border-brand-100 bg-brand-50 p-3">
          {matchingCustomers.length === 0 ? (
            <p className="text-sm text-stone-500">Aucun client trouvé.</p>
          ) : (
            matchingCustomers.map((customer) => (
              <button
                key={customer.id}
                className="block w-full rounded-xl border border-brand-100 bg-white px-3 py-2 text-left text-sm transition hover:border-brand-300"
                type="button"
                onClick={() => selectCustomer(customer)}
              >
                <div className="font-medium text-brand-900">
                  {customer.firstName} {customer.lastName}
                </div>
                <div className="text-stone-500">
                  #{customer.id} · {customer.email} · {customer.ordersCount} commande
                  {customer.ordersCount > 1 ? 's' : ''}
                </div>
              </button>
            ))
          )}
        </div>
      ) : null}

      {selectedCustomer ? (
        <div className="rounded-xl border border-brand-100 bg-white p-3">
          <div className="flex items-start justify-between gap-3">
            <div>
              <div className="font-medium text-brand-900">
                {selectedCustomer.fullName}
              </div>
              <div className="text-sm text-stone-500">
                #{selectedCustomer.id} · {selectedCustomer.email}
              </div>
            </div>
            <button
              className={secondaryActionClass}
              type="button"
              onClick={clearCustomer}
            >
              Changer
            </button>
          </div>
        </div>
      ) : null}

      {selectedCustomerId !== null ? (
        <div className="space-y-3">
          <Field label={orderLabel} {...(orderHelper ? { helper: orderHelper } : {})}>
            <input
              className={inputClass}
              placeholder="Filtrer par numero, nom de produit ou SKU"
              value={orderSearch}
              onChange={(event) => {
                setOrderSearch(event.target.value);
                onOrderChange('');
              }}
            />
          </Field>

          {customerOrdersQuery.error ? (
            <p className="text-sm text-red-600">
              {getHttpErrorMessage(customerOrdersQuery.error, 'Impossible de charger les commandes du client.')}
            </p>
          ) : null}

          <div className="space-y-2 rounded-xl border border-brand-100 bg-brand-50 p-3">
            {matchingOrders.length === 0 ? (
              <p className="text-sm text-stone-500">
                {orderRequired ? 'Aucune commande correspondante pour ce client.' : 'Aucune commande correspondante. Vous pouvez laisser vide.'}
              </p>
            ) : (
              matchingOrders.map((order) => (
                <button
                  key={order.id}
                  className={`block w-full rounded-xl border px-3 py-2 text-left text-sm transition ${String(order.id) === selectedOrderIdString ? 'border-brand-500 bg-brand-100' : 'border-brand-100 bg-white hover:border-brand-300'}`}
                  type="button"
                  onClick={() => selectOrder(order)}
                >
                  <div className="font-medium text-brand-900">
                    {order.number} · {formatEuroCents(order.totalPriceCents)}
                  </div>
                  <div className="text-stone-500">
                    {order.statusLabel ?? order.status} · {formatFrenchDateTime(order.createdAt)}
                  </div>
                  {order.items.length > 0 ? (
                    <div className="text-xs text-stone-500">
                      {order.items.slice(0, 2).map((item) => `${item.quantity}× ${item.productName}`).join(' · ')}
                    </div>
                  ) : null}
                </button>
              ))
            )}
          </div>

          {selectedOrder ? (
            <div className="rounded-xl border border-brand-100 bg-white p-3 text-sm text-brand-900">
              Commande sélectionnée : <strong>{selectedOrder.number}</strong> (#{selectedOrder.id})
            </div>
          ) : null}
        </div>
      ) : null}
    </div>
  );
};
