import { Link } from 'react-router';
import { Pencil, ShieldCheck } from 'lucide-react';

import { betaProfileStatusLabels, formatBetaLabel } from '../../lib/betaLabels';
import { badgeClassName } from './betaDashboardUtils';

interface BetaProfileSummaryProps {
  canReport: boolean;
  profileStatus: string;
}

export const BetaProfileSummary = ({ canReport, profileStatus }: BetaProfileSummaryProps) => (
  <section className="mb-8">
    <article className="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
      <div className="flex items-start gap-4">
        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
          <ShieldCheck size={24} />
        </div>
        <div>
          <h2 className="text-xl font-bold text-brand-900">Votre profil bêta</h2>
          <p className="mt-1 text-sm leading-6 text-stone-600">
            Votre accès aux campagnes dépend de la validation de ce profil.
          </p>
        </div>
      </div>
      <div className="mt-5 rounded-2xl bg-stone-50 p-4">
        <div className="space-y-2">
          <p id="beta-profile-state-label" className="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">
            État actuel
          </p>
          <p>
            <span
              className={`inline-flex rounded-full px-3 py-1 text-sm font-bold ring-1 ${badgeClassName(profileStatus)}`}
              aria-labelledby="beta-profile-state-label"
            >
              {formatBetaLabel(profileStatus, betaProfileStatusLabels)}
            </span>
          </p>
        </div>
        {!canReport && (
          <p className="mt-3 text-sm leading-6 text-stone-600">
            Votre profil doit être accepté avant d’accéder aux campagnes et d’envoyer des signalements.
          </p>
        )}
      </div>
      <Link className="mt-5 inline-flex items-center gap-2 rounded-xl border border-brand-100 px-4 py-3 text-sm font-semibold text-brand-700 transition hover:bg-brand-50" to="/beta/profile">
        <Pencil size={16} aria-hidden="true" />
        Modifier mon profil
      </Link>
    </article>
  </section>
);
