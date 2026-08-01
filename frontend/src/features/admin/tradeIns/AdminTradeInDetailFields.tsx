type PaymentOption = { value: string; label: string };

export const PaymentMethodFields = ({
  options,
  value,
  onChange,
}: {
  options: PaymentOption[];
  value: string;
  onChange: (value: string) => void;
}) => (
  <fieldset className="space-y-2">
    <legend className="text-sm font-semibold text-stone-700">Mode de règlement</legend>
    <div className="grid gap-2 sm:grid-cols-2">
      {options.map(({ value: method, label }) => (
        <label key={method} className="flex items-center gap-2">
          <input
            type="radio"
            name="trade-in-payment-method"
            value={method}
            checked={value === method}
            onChange={() => onChange(method)}
          />
          <span>{label}</span>
        </label>
      ))}
    </div>
  </fieldset>
);

export const PaymentStatusFields = ({
  options,
  paymentMethod,
  value,
  onChange,
}: {
  options: PaymentOption[];
  paymentMethod: string;
  value: string;
  onChange: (value: string) => void;
}) => (
  <fieldset className="space-y-2">
    <legend className="text-sm font-semibold text-stone-700">État du règlement</legend>
    <div className="grid gap-2 sm:grid-cols-2">
      {options.map(({ value: status, label }) => (
        <label key={status} className="flex items-center gap-2">
          <input
            type="radio"
            name="trade-in-payment-status"
            value={status}
            checked={value === status}
            disabled={paymentMethod === 'store_credit' && status === 'pending'}
            onChange={() => onChange(status)}
          />
          <span>{label}</span>
        </label>
      ))}
    </div>
    {paymentMethod === 'store_credit' && (
      <p className="text-sm text-emerald-800">
        Un avoir client est considéré comme remis dès sa création.
      </p>
    )}
  </fieldset>
);

export const InfoItem = ({ label, value }: { label: string; value: string }) => (
  <div>
    <dt className="text-xs font-semibold uppercase tracking-wide text-stone-500">{label}</dt>
    <dd className="mt-1 break-words font-medium text-stone-900">{value}</dd>
  </div>
);
