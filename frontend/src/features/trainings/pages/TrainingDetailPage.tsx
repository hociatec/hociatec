import { TrainingSessionsBooking } from '@/features/trainings/components/TrainingSessionsBooking';
import { useTrainingDetail } from '@/features/trainings/hooks/useTrainingDetail';
import {
  formatTrainingDelivery,
  formatTrainingDuration,
} from '@/features/trainings/lib/trainingCatalog';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents } from '@/shared/lib/formatters';

export const TrainingDetailPage = () => {
  const {
    training,
    sessions,
    loading,
    error,
    retry,
    message,
    submittingId,
    slotForms,
    updateSlot,
    handleEnroll,
  } = useTrainingDetail();
  useDocumentTitle(training ? training.title : 'Formation');

  return (
    <SiteLayout headerVariant="light">
      <main className="mx-auto flex w-full max-w-6xl flex-col gap-8 px-6 py-12">
        {loading ? (
          <LoadingState>Chargement de la formation...</LoadingState>
        ) : error || !training ? (
          <ErrorState onAction={error ? () => void retry() : undefined}>
            {error ?? 'Formation introuvable.'}
          </ErrorState>
        ) : (
          <>
            <header className="rounded-xl border border-brand-100 bg-white p-8 shadow-sm">
              <h1 className="text-4xl font-semibold tracking-tight text-brand-900">{training.title}</h1>
              <p className="mt-4 max-w-3xl text-base leading-7 text-stone-600">
                {training.objective || training.shortDescription ||
                  'Formation accompagnée avec feuille de route.'}
              </p>
              <div className="mt-6 grid gap-3 border-t border-brand-100 pt-5 text-sm text-stone-700 sm:grid-cols-2 xl:grid-cols-4">
                <p>
                  <span className="font-semibold text-brand-900">Catégorie : </span>
                  {training.categoryDetails?.name ?? training.category}
                </p>
                <p>
                  <span className="font-semibold text-brand-900">Modalité : </span>
                  {formatTrainingDelivery(training.availableFormats)}
                </p>
                <p>
                  <span className="font-semibold text-brand-900">Durée : </span>
                  {formatTrainingDuration(training.durationMinutes)}
                </p>
                <p>
                  <span className="font-semibold text-brand-900">Tarif : </span>
                  {formatEuroCents(training.priceCents)}
                </p>
              </div>
              {training.shortDescription && training.shortDescription !== training.objective ? (
                <p className="mt-4 text-sm leading-7 text-stone-600">
                  <span className="font-semibold text-brand-900">Présentation : </span>
                  {training.shortDescription}
                </p>
              ) : null}
              {training.audience ? (
                <p className="mt-3 text-sm leading-7 text-stone-600">
                  <span className="font-semibold text-brand-900">Public concerné : </span>
                  {training.audience}
                </p>
              ) : null}
              {training.objective && training.shortDescription && training.objective !== training.shortDescription ? (
                <p className="mt-3 text-sm leading-7 text-stone-600">
                  <span className="font-semibold text-brand-900">Objectif : </span>
                  {training.objective}
                </p>
              ) : null}
            </header>
            <section className="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
              <article className="rounded-xl border border-brand-100 bg-white p-6 shadow-sm">
                <h2 className="text-xl font-semibold text-brand-900">Feuille de route</h2>
                {training.roadmap.length === 0 ? (
                  <p className="mt-5 text-sm text-stone-600">
                    Le programme détaillé sera communiqué avec les informations de session.
                  </p>
                ) : (
                  <ol className="mt-5 grid gap-3">
                    {training.roadmap.map((item) => (
                      <li
                        key={item.id}
                        className="flex gap-3 rounded-2xl bg-brand-50 p-4 text-sm text-stone-700"
                      >
                        <strong className="text-brand-900">{item.position}.</strong>
                        <span>{item.title}</span>
                      </li>
                    ))}
                  </ol>
                )}
              </article>
              <TrainingSessionsBooking
                training={training}
                sessions={sessions}
                message={message}
                submittingId={submittingId}
                slotForms={slotForms}
                updateSlot={updateSlot}
                handleEnroll={handleEnroll}
              />
            </section>
          </>
        )}
      </main>
    </SiteLayout>
  );
};
