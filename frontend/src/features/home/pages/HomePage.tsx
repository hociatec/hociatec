import { useEffect, useState } from "react";
import { SiteLayout } from "../../../shared/components/SiteLayout";
import { useDocumentTitle } from "../../../shared/hooks/useDocumentTitle";
import { useMetaTags } from "@/shared/hooks/useMetaTags";
import { fetchPublicProducts, type CatalogProduct } from "@/features/catalog/api";
import { ProductActionToolbar } from "@/features/catalog/components/ProductActionToolbar";
import { ProductMetaBadges } from "@/features/catalog/components/ProductMetaBadges";
import { getCatalogProductDisplayName } from "@/features/catalog/utils/productDisplay";
import { Link } from "react-router-dom";
import { ORGANIZATION_SCHEMA, WEBSITE_SCHEMA, SITE_URL, LOCAL_BUSINESS_SCHEMA } from "@/shared/config/seoConfig";

const serviceHighlights = [
  {
    title: 'Matériel informatique',
    text: 'Vente, location, reprise et reconditionnement avec une sélection pensée pour durer.',
    to: '/catalogue/vente',
  },
  {
    title: 'Interventions et rendez-vous',
    text: 'Un accompagnement clair pour vos installations, besoins techniques et formations.',
    to: '/appointments/book',
  },
  {
    title: 'Projets sur mesure',
    text: 'Devis, audits, sites, logiciels et conseils adaptés à votre contexte réel.',
    to: '/devis/nouveau',
  },
];

const HomeProductMedia = ({ product }: { product: CatalogProduct }) => {
  const [imageFailed, setImageFailed] = useState(false);
  const productDisplayName = getCatalogProductDisplayName(product);

  return (
    <Link to={`/catalogue/produits/${product.slug}`} className="home-product-card__media">
      {product.imageUrl && !imageFailed ? (
        <img
          src={product.imageUrl}
          alt={product.imageAlt ?? productDisplayName}
          onError={() => setImageFailed(true)}
        />
      ) : (
        <div className="home-product-card__placeholder">
          Produit
        </div>
      )}
    </Link>
  );
};

export const HomePage = () => {
  useDocumentTitle("Le numérique à taille humaine");
  useMetaTags({
    title: 'Hociatec — Le numérique à taille humaine',
    description:
      'Vente/location de matériel, formation, conception, audits. Une approche accessible, durable et pensée pour vous.',
    type: 'website',
    canonicalUrl: SITE_URL,
    structuredData: [ORGANIZATION_SCHEMA, WEBSITE_SCHEMA, LOCAL_BUSINESS_SCHEMA],
  });

  // Produits mis en avant (accueil)
  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [loadingProducts, setLoadingProducts] = useState(false);
  const [errorProducts, setErrorProducts] = useState<string | null>(null);
  useEffect(() => {
    setLoadingProducts(true);
    setErrorProducts(null);
    void fetchPublicProducts({ homepage: true })
      .then((items) => setProducts(items))
      .catch((err: Error) => setErrorProducts(err.message || "Impossible de charger les produits."))
      .finally(() => setLoadingProducts(false));
  }, []);

  return (
    <SiteLayout>
      <div className="home-page">
        <section className="home-hero">
          <div className="home-hero__content">
            <p className="home-hero__eyebrow">Informatique, services et accompagnement</p>
            <h1>Hociatec simplifie vos besoins numériques</h1>
            <p>
              Vente, location, reconditionnement, audit et projets sur mesure avec une approche claire,
              durable et humaine.
            </p>
            <div className="home-hero__actions">
              <Link to="/catalogue/vente" className="home-button home-button--primary">Voir le catalogue</Link>
              <Link to="/appointments/book" className="home-button home-button--secondary">Prendre rendez-vous</Link>
            </div>
          </div>
          <div className="home-hero__visual" aria-hidden="true">
            <img src="/hociatec-hero-workbench.png" alt="" />
            <div className="home-hero__metric">
              <strong>Vente · Location · Audit</strong>
              <span>Un interlocuteur pour avancer plus vite.</span>
            </div>
          </div>
        </section>

        <section className="home-services" aria-label="Services Hociatec">
          <div className="home-section-heading">
            <p>Solutions Hociatec</p>
            <h2>Des services lisibles, du besoin jusqu'au suivi</h2>
          </div>
          <div className="home-services__grid">
            {serviceHighlights.map((service) => (
              <Link key={service.title} to={service.to} className="home-service-card">
                <span>{service.title}</span>
                <p>{service.text}</p>
              </Link>
            ))}
          </div>
        </section>

        <section className="home-feature-strip" aria-label="Engagements">
          <div>
            <strong>Conseil direct</strong>
            <span>Une réponse claire avant de choisir.</span>
          </div>
          <div>
            <strong>Matériel valorisé</strong>
            <span>Neuf, reconditionné ou repris selon le besoin.</span>
          </div>
          <div>
            <strong>Suivi centralisé</strong>
            <span>Commandes, devis, audits et rendez-vous au même endroit.</span>
          </div>
        </section>

        <section className="home-products">
          <div className="home-section-heading home-section-heading--row">
            <div>
              <p>Catalogue</p>
              <h2>Produits tendances</h2>
            </div>
            <Link to="/catalogue/vente" className="home-button home-button--secondary">Tous les produits</Link>
          </div>
          {loadingProducts && (
            <p className="home-loading">Chargement des produits...</p>
          )}
          {errorProducts && (
            <div className="home-alert">{errorProducts}</div>
          )}
          {!loadingProducts && !errorProducts && products.length > 0 && (
            <div className="home-products__grid">
              {products.map((product) => {
                const compactSpecs = [
                  product.brand?.trim(),
                  product.storageCapacity?.trim(),
                  product.memoryRam?.trim(),
                  product.color?.trim(),
                ]
                  .filter(Boolean)
                  .join(' • ');

                return (
                  <article key={product.id} className="home-product-card">
                    <header>
                      <h3>
                        <Link to={`/catalogue/produits/${product.slug}`}>
                          {getCatalogProductDisplayName(product)}
                        </Link>
                      </h3>
                      <p className="home-product-card__sku">
                        Référence produit: <span className="font-semibold">{product.sku}</span>
                      </p>
                      <ProductMetaBadges
                        sellingType={product.sellingType}
                        categoryName={product.category.name}
                      />
                      {compactSpecs.length > 0 && (
                        <p className="catalog-product-card__spec-summary" aria-label="Caractéristiques principales">
                          {compactSpecs}
                        </p>
                      )}
                    </header>

                    <HomeProductMedia product={product} />

                    {product.shortDescription && (
                      <p className="home-product-card__description">{product.shortDescription}</p>
                    )}

                    <div className="home-product-card__footer">
                      <span>
                        {(product.priceCents / 100).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' })}
                      </span>
                      <ProductActionToolbar product={product} />
                    </div>
                  </article>
                );
              })}
            </div>
          )}
          {!loadingProducts && !errorProducts && products.length === 0 && (
            <div className="home-empty">
              <p>Aucun produit mis en avant pour le moment</p>
              <span>
                Les produits tendances réapparaîtront ici dès que le catalogue sera réalimenté.
              </span>
            </div>
          )}
        </section>
      </div>
    </SiteLayout>
  );
};
