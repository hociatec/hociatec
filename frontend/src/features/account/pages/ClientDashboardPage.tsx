import { useAuth } from '@/features/auth/publicApi';
import { DashboardWorkspace } from '@/features/account/components/dashboard/DashboardWorkspace';
import { DashboardStatusNotice } from '@/features/account/components/dashboard/DashboardPanel';
import { useClientDashboard } from '@/features/account/hooks/useClientDashboard';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';

import '@/features/account/ClientDashboardPage.css';
import '@/app/styles/features/account.css';

export const ClientDashboardPage = () => {
  useDocumentTitle('Mon espace');
  const { user } = useAuth();
  const controller = useClientDashboard();
  const firstName = user?.firstName?.trim() || 'Bonjour';
  const isLoading = controller.state === 'loading' || controller.loading;
  const hasError = controller.state === 'error' || controller.hasError;

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

        {isLoading && <LoadingState>Chargement de votre espace...</LoadingState>}
        {hasError && (
          <ErrorState onAction={() => void controller.refresh()} actionLabel="Réessayer">
            {controller.error ?? 'Votre espace client est indisponible.'}
          </ErrorState>
        )}
        {!isLoading && <DashboardStatusNotice state={controller.state} />}
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
