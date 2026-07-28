import { MetricCard } from '@/shared/components/ui/MetricCard';

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
    <MetricCard label="Campagnes disponibles" value={campaignsCount} />
    <MetricCard label="Signalements ouverts" value={openReports} />
    <MetricCard label="Corrections confirmées" value={resolvedReports} />
  </section>
);
