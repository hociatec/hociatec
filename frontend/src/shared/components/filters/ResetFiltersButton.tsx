interface ResetFiltersButtonProps {
  onReset: () => void;
  label?: string;
}

export const ResetFiltersButton = ({ onReset, label = 'Réinitialiser les filtres' }: ResetFiltersButtonProps) => (
  <button type="button" className="reset-filters-button" onClick={onReset}>
    {label}
  </button>
);
