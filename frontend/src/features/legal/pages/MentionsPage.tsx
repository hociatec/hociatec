import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';

export const MentionsPage = () => {
  useDocumentTitle('Mentions légales');
  useMetaTags({
    title: 'Mentions légales — hociatec',
    description: 'Informations légales: éditeur du site, hébergeur, contacts et droits.',
    type: 'article',
  });

  return (
    <SiteLayout>
      <div className="container mx-auto max-w-3xl px-4 py-10">
        <h1 className="text-3xl font-semibold mb-6">Mentions légales</h1>
        <p className="text-sm text-gray-600 mb-6">
          Dernière mise à jour: {new Date().toLocaleDateString('fr-FR')}
        </p>
        <div className="prose prose-slate max-w-none">
          <h2>Éditeur du site</h2>
          <p>
            Le site <strong>hociatec.fr</strong> est édité par <strong>Hociatec</strong>.
            L&apos;activité couvre la vente et la location de matériel informatique, la
            conception de solutions numériques, l&apos;accompagnement technique, les audits et
            les prestations associées.
          </p>
          <p>
            Adresse de contact: <a href="mailto:contact@hociatec.fr">contact@hociatec.fr</a>.
          </p>
          <p>
            Adresse postale connue du site: 2 allée Anatoli Vaisser, 92600 Asnières-sur-Seine,
            France.
          </p>
          <p>
            Numéro SIREN: 934 814 559.
          </p>
          <p>
            Numéro SIRET du siège: 934 814 559 00019.
          </p>

          <h2>Direction de la publication</h2>
          <p>
            La direction de la publication est assurée par la direction de Hociatec.
          </p>

          <h2>Hébergement</h2>
          <p>
            Le site est hébergé par <strong>OVH SAS</strong> (OVHcloud), dont le siège est situé
            au <strong>2 rue Kellermann, 59100 Roubaix, France</strong>.
          </p>

          <h2>Propriété intellectuelle</h2>
          <p>
            L&apos;ensemble des contenus présents sur le site, notamment les textes, visuels,
            logos, interfaces, photos, vidéos, graphismes et éléments de marque, est protégé par
            le droit de la propriété intellectuelle. Sauf autorisation écrite préalable, toute
            reproduction, représentation, adaptation, diffusion ou exploitation, totale ou
            partielle, est interdite.
          </p>

          <h2>Données personnelles</h2>
          <p>
            Les données collectées via les formulaires du site sont utilisées pour répondre aux
            demandes, gérer les comptes clients, traiter les devis, commandes, rendez-vous et
            audits, puis assurer le suivi de la relation commerciale. Les personnes concernées
            peuvent exercer leurs droits d&apos;accès, de rectification, d&apos;effacement,
            d&apos;opposition et de limitation en écrivant à l&apos;adresse de contact.
          </p>

          <h2>Responsabilité</h2>
          <p>
            Hociatec s&apos;efforce de fournir des informations exactes et à jour, mais ne peut
            garantir l&apos;absence totale d&apos;erreur ou d&apos;omission. L&apos;utilisation du
            site se fait sous la responsabilité de l&apos;utilisateur. Les liens externes sont
            fournis à titre informatif et Hociatec ne contrôle pas leur contenu.
          </p>

          <h2>Contact</h2>
          <p>
            Pour toute question relative aux présentes mentions légales, utilisez la page Contact
            ou écrivez directement à <a href="mailto:contact@hociatec.fr">contact@hociatec.fr</a>.
          </p>
        </div>
      </div>
    </SiteLayout>
  );
};

