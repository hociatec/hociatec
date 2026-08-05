import { formatEuroCents, formatFrenchDate } from '@/shared/lib/formatters';
import type { TradeInDto } from '@/features/tradeIns/publicApi';
import { InfoItem } from '../AdminTradeInDetailFields';

export const AdminTradeInSummarySections = ({ selected }: { selected: TradeInDto }) => (
  <>
    <section aria-labelledby="trade-in-contact-title" className="rounded-lg bg-stone-50 p-4">
      <h3
        id="trade-in-contact-title"
        className="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-600"
      >
        Coordonnées du demandeur
      </h3>
      <dl className="grid gap-3 sm:grid-cols-2">
        <InfoItem
          label="Nom"
          value={`${selected.contact?.firstName ?? ''} ${selected.contact?.lastName ?? ''}`}
        />
        <InfoItem label="E-mail" value={selected.contact?.email ?? 'Non renseigné'} />
        <InfoItem label="Téléphone" value={selected.contact?.phone ?? 'Non renseigné'} />
        <InfoItem
          label="Demande créée le"
          value={formatFrenchDate(selected.createdAt) ?? 'Date inconnue'}
        />
      </dl>
    </section>

    <section aria-labelledby="trade-in-equipment-title" className="rounded-lg border border-brand-100 p-4">
      <h3
        id="trade-in-equipment-title"
        className="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-600"
      >
        Informations sur le matériel
      </h3>
      <dl className="grid gap-3 sm:grid-cols-2">
        <InfoItem label="Catégorie" value={selected.categoryLabel} />
        <InfoItem label="Produit / modèle" value={selected.productName} />
        <InfoItem label="Marque" value={selected.brand ?? 'Non renseignée'} />
        <InfoItem
          label="Prix payé à l’achat"
          value={formatEuroCents(selected.purchasePriceCents)}
        />
        <InfoItem label="Année d’achat" value={String(selected.purchaseYear)} />
        <InfoItem label="État déclaré" value={selected.conditionLabel} />
        <InfoItem label="Fonctionnel" value={selected.functional ? 'Oui' : 'Non'} />
        <InfoItem label="Accessoires" value={selected.hasAccessories ? 'Oui' : 'Non'} />
        <InfoItem
          label="Preuve d’achat"
          value={selected.hasProofOfPurchase ? 'Oui' : 'Non'}
        />
      </dl>
      <div className="mt-4 rounded-md bg-brand-50 p-3">
        <p className="text-sm text-stone-600">Description et défauts signalés</p>
        <p className="mt-1 whitespace-pre-wrap">{selected.description}</p>
      </div>
    </section>

    <section aria-labelledby="trade-in-estimate-title" className="rounded-lg bg-emerald-50 p-4">
      <h3
        id="trade-in-estimate-title"
        className="text-sm font-semibold uppercase tracking-wide text-emerald-900"
      >
        Estimation indicative
      </h3>
      <p className="mt-2 text-2xl font-bold text-emerald-950">
        {formatEuroCents(selected.estimatedMinCents)} – {formatEuroCents(selected.estimatedMaxCents)}
      </p>
      <p className="mt-1 text-sm text-emerald-900">
        Cette estimation doit être confirmée par l’équipe Hociatec après contrôle.
      </p>
    </section>
  </>
);
