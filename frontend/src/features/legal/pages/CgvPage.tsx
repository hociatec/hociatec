import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';

export const CgvPage = () => {
  useDocumentTitle('Conditions générales de vente (CGV)');
  useMetaTags({
    title: 'CGV — hociatec',
    description: 'Conditions générales de vente applicables aux services et produits Hociatec.',
    type: 'article',
  });

  return (
    <SiteLayout>
      <div className="container mx-auto max-w-3xl px-4 py-10">
        <h1 className="text-3xl font-semibold mb-6">Conditions générales de vente (CGV)</h1>
        <p className="text-sm text-gray-600 mb-6">Dernière mise à jour: {new Date().toLocaleDateString('fr-FR')}</p>
        <div className="prose prose-slate max-w-none">
          <h2>Objet</h2>
          <p>Les présentes CGV régissent les ventes de produits et services proposés par Hociatec.</p>
          <h2>Tarifs et facturation</h2>
          <p>Les prix sont indiqués en euros, toutes taxes comprises sauf indication contraire.</p>
          <h2>Commandes</h2>
          <p>La commande est ferme après confirmation. Hociatec se réserve le droit de refuser toute commande anormale ou de mauvaise foi.</p>
          <h2>Livraison et disponibilité</h2>
          <p>Les délais sont indicatifs. Les ruptures de stock ou retards ne sauraient engager la responsabilité de Hociatec au-delà d’un remboursement des sommes versées le cas échéant.</p>
          <h2>Rétractation</h2>
          <p>Conformément à la loi, un droit de rétractation peut s’appliquer selon la nature du bien ou service.</p>
          <h2>Garanties et SAV</h2>
          <p>Les produits bénéficient des garanties légales. Les modalités de SAV sont précisées sur le site ou le devis.</p>
        </div>
      </div>
    </SiteLayout>
  );
};

