import type { BetaCampaign } from '../../api/betaApi';
import { formatDate } from '../../lib/betaLabels';

interface BetaCampaignsSectionProps {
  campaigns: BetaCampaign[];
  canReport: boolean;
  onOpenCampaign: (campaign: BetaCampaign) => void;
}

export const BetaCampaignsSection = ({
  campaigns,
  canReport,
  onOpenCampaign,
}: BetaCampaignsSectionProps) => (
  <section className="mb-8">
    <article className="mb-6 rounded-3xl border border-brand-100 bg-brand-50 p-6">
      <h2 className="text-xl font-bold text-brand-900">Consignes bêta</h2>
      <div className="mt-3 grid gap-3 text-sm leading-6 text-stone-700 md:grid-cols-3">
        <p><span className="font-semibold">Reproduire :</span> indiquez les étapes exactes pour retrouver le problème.</p>
        <p><span className="font-semibold">Comparer :</span> précisez le résultat attendu et le résultat constaté.</p>
        <p><span className="font-semibold">Illustrer :</span> ajoutez une capture si elle permet de comprendre plus vite.</p>
      </div>
    </article>
    <div className="mb-4 flex items-center justify-between">
      <div>
        <h2 className="text-2xl font-bold text-brand-900">Campagnes à tester</h2>
        <p className="mt-1 text-sm text-stone-600">
          Cliquez sur une campagne pour consulter les consignes avant de créer un signalement lié.
        </p>
      </div>
    </div>
    {campaigns.length === 0 ? (
      <div className="rounded-2xl border border-dashed border-stone-300 bg-white p-6 text-sm text-stone-600">
        {canReport ? 'Aucune campagne disponible actuellement.' : 'Les campagnes apparaîtront ici après acceptation de votre profil.'}
      </div>
    ) : (
      <div className="grid gap-4 md:grid-cols-2">
        {campaigns.map((campaign) => (
          <article key={campaign.id} className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div className="flex items-start justify-between gap-4">
              <button
                type="button"
                onClick={() => onOpenCampaign(campaign)}
                className="text-left text-lg font-bold text-brand-900 underline-offset-4 transition hover:text-brand-700 hover:underline focus:outline-none focus:ring-4 focus:ring-brand-100"
              >
                {campaign.name}
              </button>
              <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                Active
              </span>
            </div>
            <p className="mt-3 text-sm leading-6 text-stone-600">{campaign.description}</p>
            <div className="mt-4 grid gap-2 border-t border-stone-100 pt-4 text-xs text-stone-500">
              <p>Début : {formatDate(campaign.startsAt)}</p>
              <p>Fin : {formatDate(campaign.endsAt)}</p>
            </div>
          </article>
        ))}
      </div>
    )}
  </section>
);
