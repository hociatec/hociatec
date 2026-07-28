import type { AdminBugReportDashboardDto } from '../../api';

interface AdminBugReportDashboardStatsProps {
  dashboard: AdminBugReportDashboardDto | undefined;
}

export const AdminBugReportDashboardStats = ({ dashboard }: AdminBugReportDashboardStatsProps) => {
  if (!dashboard) return null;

  return (
    <section className="mb-6 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
      {[
        ['Signalements ouverts', dashboard.stats.openReports],
        ['Critiques ou hauts', dashboard.stats.criticalOrHigh],
        ['Réponse admin attendue', dashboard.stats.awaitingAdminReply],
        ['Réponse client attendue', dashboard.stats.awaitingUserReply],
        ['Corrigés récemment', dashboard.stats.recentFixed],
        ['Campagnes actives', dashboard.stats.activeCampaigns],
      ].map(([label, value]) => (
        <article key={label} className="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
          <p className="text-xs font-semibold uppercase tracking-[0.12em] text-stone-500">{label}</p>
          <p className="mt-2 text-2xl font-bold text-brand-900">{value}</p>
        </article>
      ))}
    </section>
  );
};
