import { FilterBar } from '@/shared/components/filters/FilterBar';
import { ResetFiltersButton } from '@/shared/components/filters/ResetFiltersButton';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import type { SelectOption } from '@/shared/components/filters/SelectFilter';
import { useId } from 'react';
import { toNullableNumber } from '@/features/trainings/lib/trainingCatalog';

interface RangeInputsProps {
  min: number | null;
  max: number | null;
  onChange: (next: { min: number | null; max: number | null }) => void;
  minLabel: string;
  maxLabel: string;
  minPlaceholder: string;
  maxPlaceholder: string;
  step?: number;
}

const RangeInputs = ({
  min,
  max,
  onChange,
  minLabel,
  maxLabel,
  minPlaceholder,
  maxPlaceholder,
  step = 1,
}: RangeInputsProps) => {
  const legendId = useId();
  const minId = useId();
  const maxId = useId();

  return (
    <fieldset className="flex items-center gap-2" aria-labelledby={legendId}>
      <legend id={legendId} className="sr-only">
        {minLabel} et {maxLabel}
      </legend>
      <label htmlFor={minId} className="sr-only">
        {minLabel}
      </label>
      <input
        id={minId}
        type="number"
        min={0}
        step={step}
        value={min ?? ''}
        onChange={(event) => onChange({ min: toNullableNumber(event.target.value), max })}
        placeholder={minPlaceholder}
        className="w-28 rounded-full border border-brand-200 px-4 py-2 text-sm"
      />
      <span className="text-sm text-stone-500" aria-hidden="true">
        à
      </span>
      <label htmlFor={maxId} className="sr-only">
        {maxLabel}
      </label>
      <input
        id={maxId}
        type="number"
        min={0}
        step={step}
        value={max ?? ''}
        onChange={(event) => onChange({ min, max: toNullableNumber(event.target.value) })}
        placeholder={maxPlaceholder}
        className="w-28 rounded-full border border-brand-200 px-4 py-2 text-sm"
      />
    </fieldset>
  );
};

type TrainingCatalogFiltersProps = {
  resultSummary: string;
  category: string;
  format: string;
  sort: string;
  minPrice: number | null;
  maxPrice: number | null;
  minDuration: number | null;
  maxDuration: number | null;
  categoryOptions: SelectOption[];
  formatOptions: SelectOption[];
  priceHint: string | null;
  durationHint: string | null;
  updateParam: (key: string, value: string | null) => void;
  updateRange: (
    minKey: string,
    maxKey: string,
    nextRange: { min: number | null; max: number | null },
  ) => void;
  resetFilters: () => void;
};

export const TrainingCatalogFilters = ({
  resultSummary,
  category,
  format,
  sort,
  minPrice,
  maxPrice,
  minDuration,
  maxDuration,
  categoryOptions,
  formatOptions,
  priceHint,
  durationHint,
  updateParam,
  updateRange,
  resetFilters,
}: TrainingCatalogFiltersProps) => (
  <section
    className="rounded-xl border border-brand-100 bg-white p-4 shadow-sm"
    aria-label="Filtres formations"
  >
    <p id="training-filter-summary" className="mb-3 text-sm font-medium text-stone-700">
      {resultSummary}
    </p>
    <FilterBar
      className="catalog-filter-bar catalog-filter-bar--stacked"
      rightActions={<ResetFiltersButton onReset={resetFilters} />}
    >
      <SelectFilter
        value={category}
        onChange={(next) => updateParam('category', next)}
        options={categoryOptions}
        ariaLabel="Catégorie de formation"
      />
      <SelectFilter
        value={format}
        onChange={(next) => updateParam('format', next)}
        options={formatOptions}
        ariaLabel="Format de formation"
      />
      <RangeInputs
        min={minPrice}
        max={maxPrice}
        onChange={(next) => updateRange('minPrice', 'maxPrice', next)}
        minLabel="Prix minimum en euros"
        maxLabel="Prix maximum en euros"
        minPlaceholder="Prix min"
        maxPlaceholder="Prix max"
        step={10}
      />
      <RangeInputs
        min={minDuration}
        max={maxDuration}
        onChange={(next) => updateRange('minDuration', 'maxDuration', next)}
        minLabel="Durée minimum en minutes"
        maxLabel="Durée maximum en minutes"
        minPlaceholder="Durée min"
        maxPlaceholder="Durée max"
        step={15}
      />
      <SelectFilter
        value={sort}
        onChange={(next) => updateParam('sort', next)}
        options={[
          { value: 'title_asc', label: 'Titre A à Z' },
          { value: 'price_asc', label: 'Prix croissant' },
          { value: 'price_desc', label: 'Prix décroissant' },
          { value: 'duration_asc', label: 'Durée courte à longue' },
          { value: 'duration_desc', label: 'Durée longue à courte' },
        ]}
        ariaLabel="Tri des formations"
      />
    </FilterBar>
    {(priceHint || durationHint) && (
      <p className="mt-3 text-sm text-stone-500">
        {priceHint ? `Prix disponibles : ${priceHint}. ` : ''}
        {durationHint ? `Durées disponibles : ${durationHint}.` : ''}
      </p>
    )}
  </section>
);
