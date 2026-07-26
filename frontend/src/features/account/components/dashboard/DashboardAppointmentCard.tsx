import { CalendarDays } from 'lucide-react';

import type { DashboardAction } from '@/features/account/types/dashboard';
import { DashboardActionCard } from './DashboardOrderCard';

export const DashboardAppointmentCard = ({ action }: { action: DashboardAction }) => (
  <DashboardActionCard action={action} icon={<CalendarDays />} />
);
