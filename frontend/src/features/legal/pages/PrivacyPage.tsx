import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { privacyContent, PRIVACY_UPDATED_AT } from '@/features/legal/privacyContent';

export const PrivacyPage = () => {
  useDocumentTitle('Politique de confidentialité');
  useMetaTags({
    title: 'Politique de confidentialité — Hociatec',
    description:
      'Informations sur la collecte, l’usage, les bases légales, les durées de conservation et les droits liés aux données personnelles.',
    type: 'article',
  });

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        eyebrow="Informations légales"
        title="Politique de confidentialité"
        description={`Dernière mise à jour: ${PRIVACY_UPDATED_AT}`}
      >
        <PublicPageSection>
          <div className="prose prose-stone max-w-none">{privacyContent}</div>
        </PublicPageSection>
      </PublicPageShell>
    </SiteLayout>
  );
};
