interface BetaDashboardStatsProps {
  campaignsCount: number;
  openReports: number;
  resolvedReports: number;
}

export const BetaDashboardStats = ({
  campaignsCount,
  openReports,
  resolvedReports,
}: BetaDashboardStatsProps) => (
  <section className="mb-8 grid gap-4 md:grid-cols-3">
    <article className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
      <p className="text-sm font-medium text-stone-500">Campagnes disponibles</p>
      <p className="mt-2 text-3xl font-bold text-brand-900">{campaignsCount}</p>
    </article>
    <article className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
      <p className="text-sm font-medium text-stone-500">Signalements ouverts</p>
      <p className="mt-2 text-3xl font-bold text-brand-900">{openReports}</p>
    </article>
    <article className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
      <p className="text-sm font-medium text-stone-500">Corrections confirmées</p>
      <p className="mt-2 text-3xl font-bold text-brand-900">{resolvedReports}</p>
    </article>
  </section>
);
