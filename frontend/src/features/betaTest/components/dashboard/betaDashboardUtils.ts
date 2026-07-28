import type { BetaCampaign } from '../../api/betaApi';

export const badgeClassName = (value: string) => {
  if (['accepted', 'resolved', 'active'].includes(value)) {
    return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
  }

  if (['pending', 'submitted', 'under_review', 'draft'].includes(value)) {
    return 'bg-amber-50 text-amber-700 ring-amber-200';
  }

  if (['rejected', 'closed', 'critical', 'high'].includes(value)) {
    return 'bg-red-50 text-red-700 ring-red-200';
  }

  return 'bg-stone-50 text-stone-700 ring-stone-200';
};

export const isCampaignOpenForReports = (campaign: BetaCampaign) => {
  const now = Date.now();
  const startsAt = campaign.startsAt ? new Date(campaign.startsAt).getTime() : null;
  const endsAt = campaign.endsAt ? new Date(campaign.endsAt).getTime() : null;

  return campaign.status === 'active'
    && (startsAt === null || startsAt <= now)
    && (endsAt === null || endsAt >= now);
};
