import { Link } from 'react-router-dom';

import { formatTrainingFormat } from '@/features/trainings/api/trainingsApi';
import type { TrainingDto } from '@/features/trainings/api/trainingsApi';
import { formatTrainingDuration } from '@/features/trainings/lib/trainingCatalog';
import { formatEuroCents } from '@/shared/lib/formatters';

export const TrainingCatalogGrid = ({ trainings, categoryName }: { trainings: TrainingDto[]; categoryName: (slug: string) => string }) => (
  <section className="grid gap-4 md:grid-cols-2">
    {trainings.map((training) => (
      <article key={training.id} className="flex h-full flex-col rounded-xl border border-brand-100 bg-white p-6 shadow-sm">
        <div className="flex flex-wrap gap-2">
          <span className="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800">{categoryName(training.category)}</span>
          {training.availableFormats.map((format) => <span key={format} className="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-stone-700">{formatTrainingFormat(format)}</span>)}
        </div>
        <h2 className="mt-4 text-2xl font-semibold text-brand-900">{training.title}</h2>
        <p className="mt-3 min-h-[4rem] text-sm leading-6 text-stone-600">{training.shortDescription || training.objective || 'Formation accompagnée avec feuille de route.'}</p>
        <div className="mt-5 grid gap-2 border-t border-brand-100 pt-4 text-sm text-stone-600">
          <div className="flex justify-between gap-4"><span>Durée</span><strong className="text-brand-900">{formatTrainingDuration(training.durationMinutes)}</strong></div>
          <div className="flex justify-between gap-4"><span>Tarif</span><strong className="text-brand-900">{formatEuroCents(training.priceCents)}</strong></div>
        </div>
        <Link to={`/formations/${training.slug}`} className="mt-6 inline-flex w-fit rounded-full bg-brand-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">Voir la formation</Link>
      </article>
    ))}
  </section>
);
