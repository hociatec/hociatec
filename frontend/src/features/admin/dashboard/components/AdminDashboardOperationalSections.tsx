import { type AdminDashboardDto } from '@/features/admin/customers/api';
import { OrdersCustomersSection } from './operational/OrdersCustomersSection';
import { PaymentsListsSection } from './operational/PaymentsListsSection';
import { RecentEventsSection } from './operational/RecentEventsSection';

export const AdminDashboardOperationalSections = ({
  dashboard,
}: {
  dashboard: AdminDashboardDto;
}) => (
  <>
    <OrdersCustomersSection dashboard={dashboard} />
    <PaymentsListsSection dashboard={dashboard} />
    <RecentEventsSection dashboard={dashboard} />
  </>
);
