import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { TrainingCatalogFilters } from '@/features/trainings/components/TrainingCatalogFilters';
import { TrainingCatalogGrid } from '@/features/trainings/components/TrainingCatalogGrid';
import { TrainingCatalogPagination } from '@/features/trainings/components/TrainingCatalogPagination';
import { useTrainingCatalogController } from '@/features/trainings/hooks/useTrainingCatalogController';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { SITE_URL } from '@/shared/config/seoConfig';

export const TrainingsCatalogPage = () => {
  useDocumentTitle('Formations');
  useMetaTags({
    title: 'Formations — Hociatec',
    description: 'Formations Hociatec en présentiel ou distanciel, organisées autour de feuilles de route concrètes.',
    canonicalUrl: `${SITE_URL}/formations`,
  });
  const controller = useTrainingCatalogController();

  return (
    <SiteLayout headerVariant="light">
      <main className="public-directory-page mx-auto flex w-full max-w-6xl flex-col gap-8 px-6 py-12">
        <header className="rounded-xl border border-brand-100 bg-white p-8 shadow-sm">
          <span className="inline-flex w-fit rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-orange-800">Formations Hociatec</span>
          <h1 className="mt-4 text-4xl font-semibold tracking-tight text-brand-900">Formations accompagnées</h1>
          <p className="mt-4 max-w-3xl text-base leading-7 text-stone-600">Des sessions en présentiel ou en distanciel, animées autour d’une feuille de route pratique.</p>
        </header>
        {controller.loading ? (
          <div className="rounded-xl border border-dashed border-brand-100 bg-white p-8 text-center text-stone-600">Chargement des formations...</div>
        ) : controller.error ? (
          <div className="rounded-xl border border-red-200 bg-red-50 p-8 text-center text-red-700">{controller.error}</div>
        ) : controller.trainings.length === 0 ? (
          <div className="rounded-xl border border-dashed border-brand-100 bg-white p-8 text-center text-stone-600">Aucune formation publiée pour le moment.</div>
        ) : (
          <>
            <TrainingCatalogFilters
              resultSummary={controller.resultSummary}
              category={controller.category}
              format={controller.format}
              sort={controller.sort}
              minPrice={controller.minPrice}
              maxPrice={controller.maxPrice}
              minDuration={controller.minDuration}
              maxDuration={controller.maxDuration}
              categoryOptions={controller.categoryOptions}
              formatOptions={controller.formatOptions}
              priceHint={controller.priceHint}
              durationHint={controller.durationHint}
              updateParam={controller.updateParam}
              updateRange={controller.updateRange}
              resetFilters={controller.resetFilters}
            />
            <TrainingCatalogGrid trainings={controller.paginatedTrainings} categoryName={controller.categoryName} />
            <TrainingCatalogPagination currentPage={controller.currentPage} totalPages={controller.totalPages} updatePage={(nextPage) => controller.updateParam('page', String(nextPage))} />
          </>
        )}
      </main>
    </SiteLayout>
  );
};
