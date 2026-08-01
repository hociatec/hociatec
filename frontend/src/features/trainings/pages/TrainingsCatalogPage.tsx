import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { TrainingCatalogFilters } from '@/features/trainings/components/TrainingCatalogFilters';
import { TrainingCatalogGrid } from '@/features/trainings/components/TrainingCatalogGrid';
import { TrainingCatalogPagination } from '@/features/trainings/components/TrainingCatalogPagination';
import { useTrainingCatalogController } from '@/features/trainings/hooks/useTrainingCatalogController';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { SITE_URL } from '@/shared/config/seoConfig';
import { EmptyState, ErrorState, LoadingState } from '@/shared/components/ui/page-state';

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
      <PublicPageShell
        eyebrow="Formations Hociatec"
        title="Formations accompagnées"
        description="Des sessions en présentiel ou en distanciel, animées autour d’une feuille de route pratique."
      >
        {controller.loading ? (
          <LoadingState>Chargement des formations...</LoadingState>
        ) : controller.error ? (
          <ErrorState>{controller.error}</ErrorState>
        ) : controller.trainings.length === 0 ? (
          <EmptyState>Aucune formation publiée pour le moment.</EmptyState>
        ) : (
          <>
            <PublicPageSection className="p-4 sm:p-6">
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
            </PublicPageSection>
            <TrainingCatalogGrid trainings={controller.paginatedTrainings} categoryName={controller.categoryName} />
            <TrainingCatalogPagination currentPage={controller.currentPage} totalPages={controller.totalPages} updatePage={(nextPage) => controller.updateParam('page', String(nextPage))} />
          </>
        )}
      </PublicPageShell>
    </SiteLayout>
  );
};
