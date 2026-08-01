import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { cguContent, CGU_UPDATED_AT } from '@/features/legal/cguContent';

export const CguPage = () => {
  useDocumentTitle('Conditions générales d’utilisation (CGU)');
  useMetaTags({
    title: 'CGU — Hociatec',
    description:
      'Conditions générales d’utilisation du site, du compte client et des services numériques Hociatec.',
    type: 'article',
  });

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        eyebrow="Informations légales"
        title="Conditions générales d’utilisation (CGU)"
        description={`Dernière mise à jour: ${CGU_UPDATED_AT}`}
      >
        <PublicPageSection>
          <div className="prose prose-stone max-w-none">{cguContent}</div>
        </PublicPageSection>
      </PublicPageShell>
    </SiteLayout>
  );
};
