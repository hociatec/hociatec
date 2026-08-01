import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { cgvContent, CGV_UPDATED_AT } from '@/features/legal/cgvContent';

export const CgvPage = () => {
  useDocumentTitle('Conditions générales de vente (CGV)');
  useMetaTags({
    title: 'CGV — Hociatec',
    description:
      'Conditions générales de vente applicables aux produits, locations, devis et prestations Hociatec.',
    type: 'article',
  });

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        eyebrow="Informations légales"
        title="Conditions générales de vente (CGV)"
        description={`Dernière mise à jour: ${CGV_UPDATED_AT}`}
      >
        <PublicPageSection>
          <div className="prose prose-stone max-w-none">{cgvContent}</div>
        </PublicPageSection>
      </PublicPageShell>
    </SiteLayout>
  );
};
