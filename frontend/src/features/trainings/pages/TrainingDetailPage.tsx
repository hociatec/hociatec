import { Link } from 'react-router';

import { TrainingSessionsBooking } from '@/features/trainings/components/TrainingSessionsBooking';
import { useTrainingDetail } from '@/features/trainings/hooks/useTrainingDetail';
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
          <ErrorState>{error ?? 'Formation introuvable.'}</ErrorState>
        ) : (
          <>
            <header className="rounded-xl border border-brand-100 bg-white p-8 shadow-sm">
              <Link
                to="/formations"
                className="text-sm font-semibold text-stone-600 hover:text-brand-900"
              >
                ← Toutes les formations
              </Link>
              <h1 className="mt-4 text-4xl font-semibold tracking-tight text-brand-900">
                {training.title}
              </h1>
              <p className="mt-4 max-w-3xl text-base leading-7 text-stone-600">
                {training.objective || training.shortDescription ||
                  'Formation accompagnée avec feuille de route.'}
              </p>
              <div className="mt-5 flex flex-wrap gap-2">
                {training.availableFormatDetails.map((format) => (
                  <span
                    key={format.value}
                    className="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800"
                  >
                    {format.label}
                  </span>
                ))}
                <span className="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-stone-700">
                  {formatEuroCents(training.priceCents)}
                </span>
              </div>
            </header>
            <section className="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
              <article className="rounded-xl border border-brand-100 bg-white p-6 shadow-sm">
                <h2 className="text-xl font-semibold text-brand-900">Feuille de route</h2>
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
