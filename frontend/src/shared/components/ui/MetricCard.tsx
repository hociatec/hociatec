interface MetricCardProps {
  label: string;
  value: number | string;
  className?: string;
  labelClassName?: string;
  valueClassName?: string;
}

export const MetricCard = ({
  label,
  value,
  className = 'rounded-2xl border border-brand-100 bg-white p-5 shadow-sm',
  labelClassName = 'text-sm font-medium text-stone-500',
  valueClassName = 'mt-2 text-3xl font-bold text-brand-900',
}: MetricCardProps) => (
  <article className={className}>
    <p className={labelClassName}>{label}</p>
    <p className={valueClassName}>{value}</p>
  </article>
);
