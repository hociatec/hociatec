import { type Dispatch, type SetStateAction } from 'react';
import { type FulfillmentOrderDto } from '@/features/admin/operations/api';
import {
  type BulkForm,
  type RefundForm,
  type ShippingForms,
  type StockForm,
  type SupportForm,
} from './operationsTypes';
import { OperationsShippingQueue } from './OperationsShippingQueue';
import { OperationsSupportCard } from './OperationsSupportCard';
import { OperationsRefundCard } from './OperationsRefundCard';
import { OperationsStockCard } from './OperationsStockCard';
import { OperationsQuickActionsCard } from './OperationsQuickActionsCard';
import type { PaginationMeta } from '@/shared/types/api';

type OperationsActionsMode = 'all' | 'support' | 'refunds';

export const OperationsActionsSection = ({
  bulkForm,
  fulfillmentMeta,
  fulfillmentOrders,
  mode = 'all',
  setFulfillmentPage,
  quoteConversionMessage,
  quoteConversionStatus,
  quoteReference,
  refundForm,
  setBulkForm,
  setQuoteReference,
  setRefundForm,
  setShippingForms,
  setStockForm,
  setSupportForm,
  shippingForms,
  stockForm,
  submitBulk,
  submitQuoteConversion,
  submitRefund,
  submitShipOrder,
  submitStock,
  submitSupport,
  supportForm,
}: {
  bulkForm: BulkForm;
  fulfillmentMeta: PaginationMeta;
  fulfillmentOrders: FulfillmentOrderDto[];
  mode?: OperationsActionsMode;
  setFulfillmentPage: Dispatch<SetStateAction<number>>;
  quoteConversionMessage: string | null;
  quoteConversionStatus: 'idle' | 'loading' | 'error';
  quoteReference: string;
  refundForm: RefundForm;
  setBulkForm: Dispatch<SetStateAction<BulkForm>>;
  setQuoteReference: (value: string) => void;
  setRefundForm: Dispatch<SetStateAction<RefundForm>>;
  setShippingForms: Dispatch<SetStateAction<ShippingForms>>;
  setStockForm: Dispatch<SetStateAction<StockForm>>;
  setSupportForm: Dispatch<SetStateAction<SupportForm>>;
  shippingForms: ShippingForms;
  stockForm: StockForm;
  submitBulk: () => void;
  submitQuoteConversion: () => void;
  submitRefund: () => void;
  submitShipOrder: (orderId: number) => void;
  submitStock: () => void;
  submitSupport: () => void;
  supportForm: SupportForm;
}) => {
  if (mode === 'support') {
    return (
      <section className="mb-8 grid gap-6">
        <OperationsSupportCard
          supportForm={supportForm}
          setSupportForm={setSupportForm}
          submitSupport={submitSupport}
        />
      </section>
    );
  }

  if (mode === 'refunds') {
    return (
      <section className="mb-8 grid gap-6">
        <OperationsRefundCard
          refundForm={refundForm}
          setRefundForm={setRefundForm}
          submitRefund={submitRefund}
        />
      </section>
    );
  }

  return (
    <section className="mb-8 grid gap-6 xl:grid-cols-2">
      <OperationsShippingQueue fulfillmentMeta={fulfillmentMeta} fulfillmentOrders={fulfillmentOrders} setFulfillmentPage={setFulfillmentPage} shippingForms={shippingForms} setShippingForms={setShippingForms} submitShipOrder={submitShipOrder} />

      <OperationsSupportCard supportForm={supportForm} setSupportForm={setSupportForm} submitSupport={submitSupport} />

      <OperationsRefundCard refundForm={refundForm} setRefundForm={setRefundForm} submitRefund={submitRefund} />

      <OperationsStockCard stockForm={stockForm} setStockForm={setStockForm} submitStock={submitStock} />

      <OperationsQuickActionsCard bulkForm={bulkForm} quoteConversionMessage={quoteConversionMessage} quoteConversionStatus={quoteConversionStatus} quoteReference={quoteReference} setBulkForm={setBulkForm} setQuoteReference={setQuoteReference} submitBulk={submitBulk} submitQuoteConversion={submitQuoteConversion} />
    </section>
  );
};
