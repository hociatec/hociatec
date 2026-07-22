import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import {
  fetchMyTrainingEnrollments,
  formatTrainingEnrollmentStatus,
  formatTrainingFormat,
  type TrainingEnrollmentDto,
} from '@/features/trainings/api';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents, formatFrenchDateTimeFull, formatFrenchTime } from '@/shared/lib/formatters';

const statusClassName = (status: string) => {
  if (status === 'confirmed' || status === 'paid' || status === 'completed') {
    return 'bg-emerald-100 text-emerald-800';
  }

  if (status === 'cancelled') {
    return 'bg-brand-50 text-stone-700';
  }

  return 'bg-orange-100 text-orange-800';
};

export const MyTrainingDetailPage = () => {
  const { enrollmentId } = useParams();
  const navigate = useNavigate();
  const [items, setItems] = useState<TrainingEnrollmentDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const enrollment = useMemo(
    () => items.find((item) => item.id === Number(enrollmentId)) ?? null,
    [items, enrollmentId],
  );

  useDocumentTitle(enrollment ? enrollment.session.training.title : 'Détail formation');

  useEffect(() => {
    setLoading(true);
    setError(null);

    void fetchMyTrainingEnrollments()
      .then(setItems)
      .catch((err: Error) => setError(err.message || 'Chargement impossible.'))
      .finally(() => setLoading(false));
  }, []);

  return (
    <SiteLayout headerVariant="light">
      <main className="mx-auto flex w-full max-w-5xl flex-col gap-6 px-6 py-12">
        <header className="rounded-2xl border border-brand-100 bg-white p-8 shadow-sm">
          <Link to="/trainings/me" className="text-sm font-medium text-stone-600 underline">
            Retour à mes formations
          </Link>
          <div className="mt-5 flex flex-wrap items-start justify-between gap-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.22em] text-stone-500">Formation</p>
              <h1 className="mt-2 text-3xl font-semibold text-brand-900">
                {enrollment?.session.training.title ?? 'Détail formation'}
              </h1>
              {enrollment?.session.training.shortDescription ? (
                <p className="mt-3 max-w-3xl text-stone-600">{enrollment.session.training.shortDescription}</p>
              ) : null}
            </div>
            {enrollment ? (
              <span className={`rounded-full px-3 py-1 text-xs font-semibold ${statusClassName(enrollment.status)}`}>
                {formatTrainingEnrollmentStatus(enrollment.status)}
              </span>
            ) : null}
          </div>
        </header>

        {loading ? <LoadingState>Chargement...</LoadingState> : null}
        {error ? <FeedbackMessage>{error}</FeedbackMessage> : null}
        {!loading && !error && !enrollment ? (
          <div className="rounded-2xl border border-dashed border-brand-100 bg-white p-8 text-center text-stone-600">
            <p>Formation introuvable dans votre espace.</p>
            <button type="button" className="mt-4 rounded-full bg-brand-900 px-5 py-2 text-sm font-semibold text-white" onClick={() => navigate('/trainings/me')}>
              Retour
            </button>
          </div>
        ) : null}

        {enrollment ? (
          <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <section className="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm">
              <h2 className="text-xl font-semibold text-brand-900">Feuille de route</h2>
              {enrollment.session.training.roadmap.length > 0 ? (
                <ol className="mt-5 grid gap-3 text-sm text-stone-700">
                  {enrollment.session.training.roadmap.map((step) => (
                    <li key={step.id} className="rounded-xl border border-brand-100 bg-brand-50 px-4 py-3">
                      {step.title}
                    </li>
                  ))}
                </ol>
              ) : (
                <p className="mt-4 text-sm text-stone-600">Feuille de route à venir.</p>
              )}
            </section>

            <aside className="grid gap-4">
              <section className="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm">
                <h2 className="text-lg font-semibold text-brand-900">Session</h2>
                <dl className="mt-4 grid gap-3 text-sm">
                  <div>
                    <dt className="text-stone-500">Créneau réservé</dt>
                    <dd className="font-medium text-brand-900">
                      {formatFrenchDateTimeFull(enrollment.scheduledStartsAt)} - {formatFrenchTime(enrollment.scheduledEndsAt)}
                    </dd>
                  </div>
                  <div>
                    <dt className="text-stone-500">Format</dt>
                    <dd className="font-medium text-brand-900">{formatTrainingFormat(enrollment.session.format)}</dd>
                  </div>
                  <div>
                    <dt className="text-stone-500">{enrollment.session.format === 'remote' ? 'Lien' : 'Lieu'}</dt>
                    <dd className="font-medium text-brand-900">
                      {enrollment.session.format === 'remote'
                        ? enrollment.session.meetingUrl || 'Lien transmis après confirmation'
                        : enrollment.session.location || 'Lieu à confirmer'}
                    </dd>
                  </div>
                </dl>
              </section>

              <section className="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm">
                <h2 className="text-lg font-semibold text-brand-900">Inscription</h2>
                <dl className="mt-4 grid gap-3 text-sm">
                  <div>
                    <dt className="text-stone-500">Prix</dt>
                    <dd className="font-medium text-brand-900">{formatEuroCents(enrollment.priceCents)}</dd>
                  </div>
                  <div>
                    <dt className="text-stone-500">Statut</dt>
                    <dd className="font-medium text-brand-900">{formatTrainingEnrollmentStatus(enrollment.status)}</dd>
                  </div>
                  <div>
                    <dt className="text-stone-500">Réservée le</dt>
                    <dd className="font-medium text-brand-900">{formatFrenchDateTimeFull(enrollment.createdAt)}</dd>
                  </div>
                </dl>
              </section>
            </aside>
          </div>
        ) : null}
      </main>
    </SiteLayout>
  );
};
