import { useMemo } from 'react';
import { Link, useSearchParams } from 'react-router-dom';

import {
  formatTrainingCategory,
  formatTrainingFormat,
} from '@/features/trainings/api/trainingsApi';
import { usePublicTrainingsCatalogData } from '@/features/trainings/hooks/usePublicTrainingsCatalogData';
import {
  filterAndSortTrainings,
  formatTrainingDuration,
  getActiveTrainingCategories,
  normalizeTrainingParam,
  normalizeTrainingSort,
  toNullableNumber,
  TRAINING_CATALOG_ALL as ALL,
  TRAINING_CATALOG_PER_PAGE as PER_PAGE,
} from '../lib/trainingCatalog';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { ResetFiltersButton } from '@/shared/components/filters/ResetFiltersButton';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { SITE_URL } from '@/shared/config/seoConfig';
import { formatEuroCents } from '@/shared/lib/formatters';

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
}: RangeInputsProps) => (
  <div className="flex items-center gap-2">
    <input
      type="number"
      min={0}
      step={step}
      value={min ?? ''}
      onChange={(event) => onChange({ min: toNullableNumber(event.target.value), max })}
      placeholder={minPlaceholder}
      aria-label={minLabel}
      className="w-28 rounded-full border border-brand-200 px-4 py-2 text-sm"
    />
    <span className="text-sm text-stone-500">à</span>
    <input
      type="number"
      min={0}
      step={step}
      value={max ?? ''}
      onChange={(event) => onChange({ min, max: toNullableNumber(event.target.value) })}
      placeholder={maxPlaceholder}
      aria-label={maxLabel}
      className="w-28 rounded-full border border-brand-200 px-4 py-2 text-sm"
    />
  </div>
);

export const TrainingsCatalogPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  useDocumentTitle('Formations');
  useMetaTags({
    title: 'Formations — Hociatec',
    description:
      'Formations Hociatec en présentiel ou distanciel, organisées autour de feuilles de route concrètes.',
    canonicalUrl: `${SITE_URL}/formations`,
  });

  const { trainings, categories, loading, error } = usePublicTrainingsCatalogData();

  const category = normalizeTrainingParam(searchParams.get('category'));
  const query = searchParams.get('q')?.trim() ?? '';
  const format = normalizeTrainingParam(searchParams.get('format'));
  const sort = normalizeTrainingSort(searchParams.get('sort'));
  const minPrice = toNullableNumber(searchParams.get('minPrice'));
  const maxPrice = toNullableNumber(searchParams.get('maxPrice'));
  const minDuration = toNullableNumber(searchParams.get('minDuration'));
  const maxDuration = toNullableNumber(searchParams.get('maxDuration'));
  const page = Math.max(1, toNullableNumber(searchParams.get('page')) ?? 1);

  const availableCategories = useMemo(
    () => getActiveTrainingCategories(categories, trainings),
    [categories, trainings],
  );

  const categoryOptions = useMemo(
    () => [
      { value: ALL, label: 'Toutes les catégories' },
      ...availableCategories.map((item) => ({
        value: item.slug,
        label: `${item.name} (${trainings.filter((training) => training.category === item.slug).length})`,
      })),
    ],
    [availableCategories, trainings],
  );

  const formatOptions = useMemo(
    () => [
      { value: ALL, label: 'Tous les formats' },
      {
        value: 'onsite',
        label: `Présentiel (${trainings.filter((training) => training.availableFormats.includes('onsite')).length})`,
      },
      {
        value: 'remote',
        label: `Distanciel (${trainings.filter((training) => training.availableFormats.includes('remote')).length})`,
      },
    ],
    [trainings],
  );

  const categoryName = (slug: string) =>
    categories.find((item) => item.slug === slug)?.name ?? formatTrainingCategory(slug);

  const filteredTrainings = useMemo(
    () =>
      filterAndSortTrainings(
        trainings,
        { category, format, query, sort, minPrice, maxPrice, minDuration, maxDuration },
        categoryName,
      ),
    [
      category,
      format,
      maxDuration,
      maxPrice,
      minDuration,
      minPrice,
      query,
      sort,
      trainings,
      categories,
    ],
  );

  const updateParam = (key: string, value: string | null) => {
    const next = new URLSearchParams(searchParams);
    if (value === null || value === '' || value === ALL) next.delete(key);
    else next.set(key, value);
    if (key !== 'page') next.delete('page');
    setSearchParams(next, { replace: true });
  };

  const updateRange = (
    minKey: string,
    maxKey: string,
    nextRange: { min: number | null; max: number | null },
  ) => {
    const next = new URLSearchParams(searchParams);
    if (nextRange.min === null) next.delete(minKey);
    else next.set(minKey, String(nextRange.min));
    if (nextRange.max === null) next.delete(maxKey);
    else next.set(maxKey, String(nextRange.max));
    next.delete('page');
    setSearchParams(next, { replace: true });
  };

  const resetFilters = () => {
    setSearchParams(new URLSearchParams(), { replace: true });
  };

  const totalPages = Math.max(1, Math.ceil(filteredTrainings.length / PER_PAGE));
  const currentPage = Math.min(page, totalPages);
  const paginatedTrainings = filteredTrainings.slice(
    (currentPage - 1) * PER_PAGE,
    currentPage * PER_PAGE,
  );
  const pageNumbers = Array.from({ length: totalPages }, (_, index) => index + 1);
  const resultSummary = query
    ? `${filteredTrainings.length} formation${filteredTrainings.length > 1 ? 's' : ''} pour "${query}"`
    : `${filteredTrainings.length} formation${filteredTrainings.length > 1 ? 's' : ''} affichée${filteredTrainings.length > 1 ? 's' : ''}`;
  const priceValues = trainings.map((training) => training.priceCents / 100);
  const durationValues = trainings.map((training) => training.durationMinutes);
  const hasPriceRange = priceValues.length > 0;
  const hasDurationRange = durationValues.length > 0;
  const priceHint = hasPriceRange
    ? `${Math.min(...priceValues)} € à ${Math.max(...priceValues)} €`
    : null;
  const durationHint = hasDurationRange
    ? `${Math.min(...durationValues)} à ${Math.max(...durationValues)} min`
    : null;

  return (
    <SiteLayout headerVariant="light">
      <main className="public-directory-page mx-auto flex w-full max-w-6xl flex-col gap-8 px-6 py-12">
        <header className="rounded-xl border border-brand-100 bg-white p-8 shadow-sm">
          <span className="inline-flex w-fit rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-orange-800">
            Formations Hociatec
          </span>
          <h1 className="mt-4 text-4xl font-semibold tracking-tight text-brand-900">
            Formations accompagnées
          </h1>
          <p className="mt-4 max-w-3xl text-base leading-7 text-stone-600">
            Des sessions en présentiel ou en distanciel, animées autour d’une feuille de route
            pratique.
          </p>
        </header>

        {loading ? (
          <div className="rounded-xl border border-dashed border-brand-100 bg-white p-8 text-center text-stone-600">
            Chargement des formations...
          </div>
        ) : error ? (
          <div className="rounded-xl border border-red-200 bg-red-50 p-8 text-center text-red-700">
            {error}
          </div>
        ) : trainings.length === 0 ? (
          <div className="rounded-xl border border-dashed border-brand-100 bg-white p-8 text-center text-stone-600">
            Aucune formation publiée pour le moment.
          </div>
        ) : (
          <>
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

            <section className="grid gap-4 md:grid-cols-2">
              {paginatedTrainings.map((training) => (
                <article
                  key={training.id}
                  className="flex h-full flex-col rounded-xl border border-brand-100 bg-white p-6 shadow-sm"
                >
                  <div className="flex flex-wrap gap-2">
                    <span className="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800">
                      {categoryName(training.category)}
                    </span>
                    {training.availableFormats.map((format) => (
                      <span
                        key={format}
                        className="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-stone-700"
                      >
                        {formatTrainingFormat(format)}
                      </span>
                    ))}
                  </div>
                  <h2 className="mt-4 text-2xl font-semibold text-brand-900">{training.title}</h2>
                  <p className="mt-3 min-h-[4rem] text-sm leading-6 text-stone-600">
                    {training.shortDescription ||
                      training.objective ||
                      'Formation accompagnée avec feuille de route.'}
                  </p>
                  <div className="mt-5 grid gap-2 border-t border-brand-100 pt-4 text-sm text-stone-600">
                    <div className="flex justify-between gap-4">
                      <span>Durée</span>
                      <strong className="text-brand-900">
                        {formatTrainingDuration(training.durationMinutes)}
                      </strong>
                    </div>
                    <div className="flex justify-between gap-4">
                      <span>Tarif</span>
                      <strong className="text-brand-900">
                        {formatEuroCents(training.priceCents)}
                      </strong>
                    </div>
                  </div>
                  <Link
                    to={`/formations/${training.slug}`}
                    className="mt-6 inline-flex w-fit rounded-full bg-brand-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-800"
                  >
                    Voir la formation
                  </Link>
                </article>
              ))}
            </section>
            {totalPages > 1 ? (
              <nav
                className="flex flex-wrap items-center justify-center gap-2"
                aria-label="Pagination des formations"
              >
                <button
                  type="button"
                  className="rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 disabled:opacity-40"
                  disabled={currentPage === 1}
                  onClick={() => updateParam('page', String(currentPage - 1))}
                >
                  Précédent
                </button>
                {pageNumbers.map((pageNumber) => (
                  <button
                    key={pageNumber}
                    type="button"
                    className={`rounded-full border px-4 py-2 text-sm font-semibold ${pageNumber === currentPage ? 'border-brand-900 bg-brand-900 text-white' : 'border-brand-200 text-stone-700'}`}
                    aria-current={pageNumber === currentPage ? 'page' : undefined}
                    onClick={() => updateParam('page', String(pageNumber))}
                  >
                    {pageNumber}
                  </button>
                ))}
                <button
                  type="button"
                  className="rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 disabled:opacity-40"
                  disabled={currentPage === totalPages}
                  onClick={() => updateParam('page', String(currentPage + 1))}
                >
                  Suivant
                </button>
              </nav>
            ) : null}
          </>
        )}
      </main>
    </SiteLayout>
  );
};
