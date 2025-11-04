interface ResetFiltersButtonProps {
  onReset: () => void;
  label?: string;
}

export const ResetFiltersButton = ({ onReset, label = 'Réinitialiser les filtres' }: ResetFiltersButtonProps) => (
  <button
    type="button"
    onClick={onReset}
    style={{ borderRadius: 999, padding: '10px 18px', border: '1px solid rgba(148, 163, 184, 0.6)' }}
  >
    {label}
  </button>
);

