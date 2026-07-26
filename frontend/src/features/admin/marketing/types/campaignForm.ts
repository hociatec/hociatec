export type CampaignFormState = {
  name: string;
  templateId: string;
  segmentKey: string;
  minimumOrders: string;
  inactiveDays: string;
  registeredDays: string;
  recentDays: string;
  minimumTotalCents: string;
  minimumPendingReviews: string;
  subject: string;
  htmlBody: string;
  textBody: string;
};

export const emptyCampaignForm: CampaignFormState = {
  name: '',
  templateId: '',
  segmentKey: 'customers_without_review',
  minimumOrders: '3',
  inactiveDays: '90',
  registeredDays: '30',
  recentDays: '30',
  minimumTotalCents: '50000',
  minimumPendingReviews: '2',
  subject: '',
  htmlBody: '',
  textBody: '',
};
