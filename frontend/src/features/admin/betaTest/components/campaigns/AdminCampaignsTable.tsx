import { Edit, Eye, Trash2 } from 'lucide-react';

import type { AdminCampaignDto } from '../../api';
import { campaignStateLabels, formatBetaLabel, formatDate } from '@/features/betaTest/lib/betaLabels';

interface AdminCampaignsTableProps {
  campaigns: AdminCampaignDto[];
  onDelete: (campaign: AdminCampaignDto) => void;
  onEdit: (campaign: AdminCampaignDto) => void;
  onOpenDetail: (campaign: AdminCampaignDto) => void;
}

const campaignStatusClassName = (status: string) => {
  if (status === 'active') return 'bg-green-100 text-green-800';
  if (status === 'closed') return 'bg-red-100 text-red-800';
  return 'bg-stone-100 text-stone-800';
};

export const AdminCampaignsTable = ({
  campaigns,
  onDelete,
  onEdit,
  onOpenDetail,
}: AdminCampaignsTableProps) => (
  <div className="overflow-x-auto rounded-lg border border-stone-200 bg-white shadow-sm">
    <table className="w-full border-collapse text-left">
      <thead>
        <tr className="border-b border-stone-200 bg-stone-50 text-sm font-semibold text-stone-600">
          <th className="p-4">Nom de la campagne</th>
          <th className="p-4">Description</th>
          <th className="p-4">Période</th>
          <th className="p-4">Date de création</th>
          <th className="p-4">État</th>
          <th className="p-4">Actions</th>
        </tr>
      </thead>
      <tbody className="divide-y divide-stone-200 text-sm">
        {campaigns.map((campaign) => (
          <tr key={campaign.id} className="transition hover:bg-stone-50">
            <td className="p-4 font-semibold text-stone-900">{campaign.name}</td>
            <td className="max-w-xs truncate p-4 text-stone-600">{campaign.description}</td>
            <td className="p-4 text-stone-500">
              <span className="block">Début : {formatDate(campaign.startsAt)}</span>
              <span className="block">Fin : {formatDate(campaign.endsAt)}</span>
            </td>
            <td className="p-4 text-stone-500">{formatDate(campaign.createdAt)}</td>
            <td className="p-4">
              <span className={`inline-flex rounded px-2 py-1 text-xs font-semibold ${campaignStatusClassName(campaign.status)}`}>
                {formatBetaLabel(campaign.status, campaignStateLabels)}
              </span>
            </td>
            <td className="p-4">
              <div className="flex gap-2">
                <button
                  type="button"
                  onClick={() => onOpenDetail(campaign)}
                  className="rounded bg-stone-50 p-1.5 text-stone-600 transition hover:bg-stone-100 hover:text-brand-700"
                  title="Consulter"
                >
                  <Eye size={16} />
                </button>
                <button
                  type="button"
                  onClick={() => onEdit(campaign)}
                  className="rounded bg-stone-50 p-1.5 text-stone-600 transition hover:bg-stone-100 hover:text-brand-700"
                  title="Modifier"
                >
                  <Edit size={16} />
                </button>
                <button
                  type="button"
                  onClick={() => onDelete(campaign)}
                  className="rounded bg-red-50 p-1.5 text-red-600 transition hover:bg-red-100 hover:text-red-800"
                  title="Supprimer"
                >
                  <Trash2 size={16} />
                </button>
              </div>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  </div>
);
