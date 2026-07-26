import { useAuth } from '@/features/auth/hooks/useAuth';
import { DashboardWorkspace } from '@/features/account/components/dashboard/DashboardWorkspace';
import { DashboardStatusNotice } from '@/features/account/components/dashboard/DashboardPanel';
import { useClientDashboard } from '@/features/account/hooks/useClientDashboard';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

import '@/features/account/ClientDashboardPage.css';

export const ClientDashboardPage = () => {
  useDocumentTitle('Mon espace');
  const { user } = useAuth();
  const controller = useClientDashboard();
  const firstName = user?.firstName?.trim() || 'Bonjour';

  return (
    <SiteLayout headerVariant="light">
      <main
        className="client-dashboard client-dashboard--refresh"
        aria-labelledby="client-dashboard-title"
      >
        <header className="client-dashboard__hero client-dashboard__hero--compact">
          <div>
            <h1 id="client-dashboard-title">{firstName}, votre espace en un coup d'oeil</h1>
            <p>
              Suivez vos dossiers actifs, vos avantages et vos prochaines actions depuis une seule
              page.
            </p>
          </div>
        </header>

        <DashboardStatusNotice state={controller.state} />
        <DashboardWorkspace
          conversionEuroCents={controller.conversionEuroCents}
          conversionPoints={controller.conversionPoints}
          conversionState={controller.conversionState}
          convertPoints={controller.convertPoints}
          dashboardActions={controller.dashboardActions}
          hasConvertiblePoints={controller.hasConvertiblePoints}
          loyalty={controller.loyalty}
          onConvert={controller.handleConvert}
          onConvertPointsChange={controller.setConvertPoints}
        />
      </main>
    </SiteLayout>
  );
};
