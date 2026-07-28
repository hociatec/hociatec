export interface CampaignFormState {
  name: string;
  description: string;
  status: string;
  startsAt: string;
  endsAt: string;
}

const formatDateInput = (date: Date) => date.toISOString().slice(0, 10);

export const defaultCampaignDates = () => {
  const startsAt = new Date();
  const endsAt = new Date(startsAt);
  endsAt.setDate(startsAt.getDate() + 30);

  return {
    startsAt: formatDateInput(startsAt),
    endsAt: formatDateInput(endsAt),
  };
};

export const emptyCampaignForm = (): CampaignFormState => ({
  name: '',
  description: '',
  status: 'draft',
  ...defaultCampaignDates(),
});
