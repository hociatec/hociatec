import { Link } from 'react-router';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PRIVATE_ROBOTS_CONTENT, SITE_URL } from '@/shared/config/seoConfig';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useAuth } from '@/features/auth/publicApi';
import { isFeatureEnabled } from '@/shared/config/featureFlags';

export const BetaTestPage = () => {
  useDocumentTitle('Programme bêta');
  useMetaTags({
    title: 'Programme bêta — Hociatec',
    description: 'Rejoignez le programme bêta Hociatec.',
    canonicalUrl: `${SITE_URL}/beta-test`,
    robots: PRIVATE_ROBOTS_CONTENT,
  });
  const { status } = useAuth();
  const isBetaProgramEnabled = isFeatureEnabled('betaProgram');
  const isAuthenticated = status === 'authenticated';
  const targetLink = isAuthenticated ? '/beta' : '/register?beta=1';

  if (!isBetaProgramEnabled) {
    return (
      <SiteLayout headerVariant="light">
        <main className="container mx-auto max-w-4xl p-4 md:p-8">
          <h1 className="mb-4 text-3xl font-semibold md:text-4xl">Programme bêta fermé</h1>
          <p className="max-w-2xl text-base leading-7 text-stone-700">
            Les inscriptions au programme bêta sont temporairement suspendues.
          </p>
        </main>
      </SiteLayout>
    );
  }

  return (
    <SiteLayout headerVariant="light">
      <main className="container mx-auto max-w-4xl p-4 md:p-8">
        <h1 className="mb-4 text-3xl font-semibold md:text-4xl">Participez à l’amélioration de Hociatec</h1>
        <p className="mb-8 max-w-2xl text-base leading-7 text-stone-700">
          Rejoignez notre communauté de bêta-testeurs, testez les nouvelles fonctionnalités et contribuez à rendre le site plus accessible et agréable.
        </p>
        <div className="mb-8 grid gap-4 md:grid-cols-3">
          {[
            ['1', 'Créez votre espace', 'Votre profil et vos préférences de test sont enregistrés avec votre compte.'],
            ['2', 'Découvrez les campagnes', 'Accédez aux campagnes adaptées à votre profil.'],
            ['3', 'Partagez vos retours', 'Envoyez des signalements détaillés et suivez leur traitement.']
          ].map(([number, title, text]) => (
            <article key={number} className="rounded-lg border border-stone-200 bg-stone-50 p-5">
              <span className="mb-3 inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-700 font-semibold text-white">
                {number}
              </span>
              <h2 className="mb-1 font-semibold">{title}</h2>
              <p className="text-sm leading-6 text-stone-600">{text}</p>
            </article>
          ))}
        </div>
        <Link
          to={targetLink}
          className="inline-flex rounded-lg bg-brand-700 px-5 py-3 font-semibold text-white hover:bg-brand-800"
        >
          Rejoindre le programme bêta
        </Link>
      </main>
    </SiteLayout>
  );
};
