import type { DashboardAction, DashboardConversionState } from '@/features/account/types/dashboard';
import type { LoyaltyBalanceDto } from '@/features/loyalty/api/loyaltyApi';
import { DashboardAccessLinks } from './DashboardAccessLinks';
import { DashboardAppointmentCard } from './DashboardAppointmentCard';
import { DashboardEmptyState } from './DashboardEmptyState';
import { DashboardOrderCard } from './DashboardOrderCard';
import { DashboardPanel } from './DashboardPanel';
import { DashboardQuoteCard } from './DashboardQuoteCard';
import { DashboardTrainingCard } from './DashboardTrainingCard';
import { DashboardVoucherCard } from './DashboardVoucherCard';

export const DashboardWorkspace = ({
  conversionEuroCents,
  conversionPoints,
  conversionState,
  convertPoints,
  dashboardActions,
  hasConvertiblePoints,
  loyalty,
  onConvert,
  onConvertPointsChange,
}: {
  conversionEuroCents: number;
  conversionPoints: number;
  conversionState: DashboardConversionState;
  convertPoints: string;
  dashboardActions: DashboardAction[];
  hasConvertiblePoints: boolean;
  loyalty: LoyaltyBalanceDto;
  onConvert: () => void;
  onConvertPointsChange: (value: string) => void;
}) => (
  <section className="client-dashboard__workspace" aria-label="Tableau de bord">
    <div className="client-dashboard__main-column">
      <DashboardPanel
        heading="À faire maintenant"
        id="dashboard-actions-title"
        className="client-dashboard__panel--focus"
      >
        {dashboardActions.length > 0 ? (
          <div className="client-dashboard__action-list">
            {dashboardActions.map((action) => (
              <ActionCard key={`${action.to}-${action.title}`} action={action} />
            ))}
          </div>
        ) : (
          <DashboardEmptyState>
            Vos prochaines commandes, rendez-vous, devis ou formations apparaîtront ici dès qu'une
            action sera utile.
          </DashboardEmptyState>
        )}
      </DashboardPanel>
      <DashboardVoucherCard
        conversionEuroCents={conversionEuroCents}
        conversionPoints={conversionPoints}
        conversionState={conversionState}
        convertPoints={convertPoints}
        hasConvertiblePoints={hasConvertiblePoints}
        loyalty={loyalty}
        onConvert={onConvert}
        onConvertPointsChange={onConvertPointsChange}
      />
    </div>
    <aside className="client-dashboard__side-column">
      <DashboardAccessLinks />
    </aside>
  </section>
);

const ActionCard = ({ action }: { action: DashboardAction }) => {
  if (action.kind === 'appointment') return <DashboardAppointmentCard action={action} />;
  if (action.kind === 'quote') return <DashboardQuoteCard action={action} />;
  if (action.kind === 'training') return <DashboardTrainingCard action={action} />;
  return <DashboardOrderCard action={action} />;
};
