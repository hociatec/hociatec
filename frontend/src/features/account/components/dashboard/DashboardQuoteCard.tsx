import { FileText } from 'lucide-react';

import type { DashboardAction } from '@/features/account/types/dashboard';
import { DashboardActionCard } from './DashboardOrderCard';

export const DashboardQuoteCard = ({ action }: { action: DashboardAction }) => (
  <DashboardActionCard action={action} icon={<FileText />} />
);
