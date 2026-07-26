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

export const OperationsActionsSection = ({
  bulkForm,
  fulfillmentOrders,
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
  fulfillmentOrders: FulfillmentOrderDto[];
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
  return (
    <section className="mb-8 grid gap-6 xl:grid-cols-2">
      <OperationsShippingQueue fulfillmentOrders={fulfillmentOrders} shippingForms={shippingForms} setShippingForms={setShippingForms} submitShipOrder={submitShipOrder} />

      <OperationsSupportCard supportForm={supportForm} setSupportForm={setSupportForm} submitSupport={submitSupport} />

      <OperationsRefundCard refundForm={refundForm} setRefundForm={setRefundForm} submitRefund={submitRefund} />

      <OperationsStockCard stockForm={stockForm} setStockForm={setStockForm} submitStock={submitStock} />

      <OperationsQuickActionsCard bulkForm={bulkForm} quoteConversionMessage={quoteConversionMessage} quoteConversionStatus={quoteConversionStatus} quoteReference={quoteReference} setBulkForm={setBulkForm} setQuoteReference={setQuoteReference} submitBulk={submitBulk} submitQuoteConversion={submitQuoteConversion} />
    </section>
  );
};
