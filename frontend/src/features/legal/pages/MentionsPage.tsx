import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { mentionsContent, LEGAL_UPDATED_AT } from '@/features/legal/mentionsContent';

export const MentionsPage = () => {
  useDocumentTitle('Mentions légales');
  useMetaTags({
    title: 'Mentions légales — Hociatec',
    description:
      'Identification de l’éditeur du site Hociatec, hébergement, propriété intellectuelle, données personnelles et contact.',
    type: 'article',
  });

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        eyebrow="Informations légales"
        title="Mentions légales"
        description={`Dernière mise à jour: ${LEGAL_UPDATED_AT}`}
      >
        <PublicPageSection>
          <div className="prose prose-stone max-w-none">{mentionsContent}</div>
        </PublicPageSection>
      </PublicPageShell>
    </SiteLayout>
  );
};
