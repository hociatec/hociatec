import { GraduationCap } from 'lucide-react';

import type { DashboardAction } from '@/features/account/types/dashboard';
import { DashboardActionCard } from './DashboardOrderCard';

export const DashboardTrainingCard = ({ action }: { action: DashboardAction }) => (
  <DashboardActionCard action={action} icon={<GraduationCap />} />
);
