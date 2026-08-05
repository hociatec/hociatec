import { formatApiDateForDateInput } from '@/shared/lib/formatters';
import type { BetaCampaignStatus } from '@/shared/contracts/statuses';

export interface CampaignFormState {
  name: string;
  description: string;
  status: BetaCampaignStatus;
  startsAt: string;
  endsAt: string;
}

export const defaultCampaignDates = () => {
  const startsAt = new Date();
  const endsAt = new Date(startsAt);
  endsAt.setDate(startsAt.getDate() + 30);

  return {
    startsAt: formatApiDateForDateInput(startsAt),
    endsAt: formatApiDateForDateInput(endsAt),
  };
};

export const emptyCampaignForm = (): CampaignFormState => ({
  name: '',
  description: '',
  status: 'draft',
  ...defaultCampaignDates(),
});
