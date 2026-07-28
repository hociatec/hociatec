import { Link } from 'react-router';
import { FlaskConical } from 'lucide-react';

export const BetaEmptyProfileState = () => (
  <div className="mx-auto my-8 max-w-2xl rounded-2xl border border-brand-100 bg-white p-8 text-center shadow-lg">
    <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-700">
      <FlaskConical size={32} />
    </div>
    <h2 className="mt-4 text-2xl font-bold text-brand-900">
      Rejoignez le programme Bêta Hociatec
    </h2>
    <p className="mx-auto mt-4 max-w-lg text-sm leading-relaxed text-stone-600">
      Votre compte n'est pas encore inscrit au programme de bêta-test. Créez votre profil bêta-testeur pour accéder aux campagnes, tester de nouvelles fonctionnalités et soumettre vos signalements.
    </p>
    <div className="flex flex-wrap justify-center gap-3 pt-4">
      <Link
        to="/beta/profile"
        className="inline-flex items-center justify-center rounded-lg bg-brand-700 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800"
      >
        Activer mon profil bêta-testeur
      </Link>
      <Link
        to="/beta-test"
        className="inline-flex items-center justify-center rounded-lg border border-stone-300 bg-white px-6 py-3 text-sm font-semibold text-stone-700 transition hover:bg-stone-50"
      >
        Découvrir le programme
      </Link>
    </div>
  </div>
);
