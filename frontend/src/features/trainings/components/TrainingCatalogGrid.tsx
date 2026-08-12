import { Link } from 'react-router';

import type { TrainingDto } from '@/features/trainings/api/trainingsApi';
import {
  formatTrainingDelivery,
  formatTrainingDuration,
} from '@/features/trainings/lib/trainingCatalog';
import { formatEuroCents } from '@/shared/lib/formatters';

export const TrainingCatalogGrid = ({
  trainings,
}: {
  trainings: TrainingDto[];
}) => {
  return (
    <section>
      <div className="grid gap-4 md:grid-cols-2" role="list">
        {trainings.map((training) => (
          <article
            key={training.id}
            role="listitem"
            className="flex h-full flex-col rounded-xl border border-brand-100 bg-white p-6 shadow-sm"
            style={{ contentVisibility: 'auto', containIntrinsicSize: '320px' }}
          >
            <h3 className="mt-4 text-2xl font-semibold text-brand-900">
              <Link to={`/formations/${training.slug}`} className="transition hover:text-brand-700">
                {training.title}
              </Link>
            </h3>
            <p className="mt-3 min-h-[4rem] text-sm leading-6 text-stone-600">
              {training.shortDescription ||
                training.objective ||
                'Formation accompagnée avec feuille de route.'}
            </p>
            <div className="mt-5 grid gap-2 border-t border-brand-100 pt-4 text-sm text-stone-600">
              <p>
                <span>Modalité : </span>
                <span className="font-semibold text-brand-900">
                  {formatTrainingDelivery(training.availableFormats)}
                </span>
              </p>
              <p>
                <span>Durée : </span>
                <span className="font-semibold text-brand-900">
                  {formatTrainingDuration(training.durationMinutes)}
                </span>
              </p>
              <p>
                <span>Tarif : </span>
                <span className="font-semibold text-brand-900">
                  {formatEuroCents(training.priceCents)}
                </span>
              </p>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
};
