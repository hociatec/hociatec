import { tradeInFieldClassName } from '../../lib/tradeInForm';

export const SectionHeading = ({ title, description }: { title: string; description?: string }) => (
  <div>
    <h2 className="text-xl font-semibold text-brand-900">{title}</h2>
    {description ? <p className="mt-1 text-sm text-stone-600">{description}</p> : null}
  </div>
);

export const Field = ({
  label,
  value,
  onChange,
  type = 'text',
  placeholder,
  required = false,
  min,
  max,
  step,
}: {
  label: string;
  value: string;
  onChange: (value: string) => void;
  type?: string;
  placeholder?: string;
  required?: boolean;
  min?: string;
  max?: string;
  step?: string;
}) => (
  <label className="grid gap-1">
    <span className="text-sm font-semibold text-brand-900">{label}</span>
    <input
      className={tradeInFieldClassName}
      type={type}
      value={value}
      placeholder={placeholder}
      required={required}
      min={min}
      max={max}
      step={step}
      onChange={(event) => onChange(event.target.value)}
    />
  </label>
);

export const Check = ({
  label,
  checked,
  onChange,
}: {
  label: string;
  checked: boolean;
  onChange: (value: boolean) => void;
}) => (
  <label className="flex items-start gap-2 rounded-xl border border-brand-100 bg-brand-50 p-3 text-sm text-stone-700">
    <input type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} />
    <span>{label}</span>
  </label>
);
