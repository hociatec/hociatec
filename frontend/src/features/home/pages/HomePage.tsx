import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import { SiteLayout } from "../../../shared/components/SiteLayout";
import { useDocumentTitle } from "../../../shared/hooks/useDocumentTitle";
import { useMetaTags } from "@/shared/hooks/useMetaTags";
import { fetchPublicProducts, type CatalogProduct } from "@/features/catalog/api";
import { ProductMetaBadges } from "@/features/catalog/components/ProductMetaBadges";
import { ProductCartActions } from "@/features/cart/components/ProductCartActions";
import { Mail, Facebook } from "lucide-react";
import { Link } from "react-router-dom";
import { ORGANIZATION_SCHEMA, WEBSITE_SCHEMA, SITE_URL, LOCAL_BUSINESS_SCHEMA } from "@/shared/config/seoConfig";

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

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

  // Ferme la section ouverte quand on appuie sur Échap
  useEffect(() => {
    const handler = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
        const openItems = document.querySelectorAll("[data-state=open]");
        openItems.forEach((t) => (t as HTMLElement).click());
      }
    };
    window.addEventListener("keydown", handler);
    return () => window.removeEventListener("keydown", handler);
  }, []);

  // Produits mis en avant (accueil)
  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [loadingProducts, setLoadingProducts] = useState(false);
  const [errorProducts, setErrorProducts] = useState<string | null>(null);
  const [shareFormProductId, setShareFormProductId] = useState<number | null>(null);
  const [shareEmails, setShareEmails] = useState<Record<number, string>>({});
  const [shareFeedback, setShareFeedback] = useState<
    { productId: number; type: 'error' | 'info'; message: string } | null
  >(null);

  useEffect(() => {
    setLoadingProducts(true);
    setErrorProducts(null);
    void fetchPublicProducts({ homepage: true })
      .then((items) => setProducts(items))
      .catch((err: Error) => setErrorProducts(err.message || "Impossible de charger les produits."))
      .finally(() => setLoadingProducts(false));
  }, []);

  const origin = typeof window !== "undefined" ? window.location.origin : "";
  const buildProductUrl = (slug: string) => `${origin}/catalogue/produits/${slug}`;

  return (
    <SiteLayout>
      <main className="py-20 bg-gradient-to-br from-white via-gray-50 to-blue-50 text-gray-800">
        {/* HERO */}
        <section className="text-center mx-auto max-w-4xl px-6 mb-12">
          <h1 className="text-4xl md:text-6xl font-extrabold text-gray-900">
            Hociatec, une entreprise à taille humaine
          </h1>
          <p className="mt-4 text-lg text-gray-700">
            Le numérique, oui — mais accessible, durable et pensé pour vous.
          </p>
          <p className="mt-3 text-gray-600 max-w-2xl mx-auto">
            Vente, reprise, formation, conception, location : Hociatec vous accompagne à chaque étape,
            que vous soyez particulier ou professionnel. Une approche simple, concrète et humaine
            pour avancer sereinement dans le monde numérique.
          </p>
        </section>

        {/* TITRE ACCROCHEUR */}
        <div className="text-center mb-12 px-6">
          <p className="text-3xl md:text-4xl font-bold text-gray-900">
            Des solutions concrètes pour tous vos besoins numériques
          </p>
          <p className="mt-2 text-gray-600 max-w-2xl mx-auto">
            Découvrez nos services et choisissez ce qui correspond à vos besoins.
          </p>
        </div>

        {/* ACCORDÉON DES SERVICES */}
        <section className="mx-auto max-w-4xl px-6">
          <Accordion type="single" collapsible className="space-y-3">
            {/* 1️⃣ Vente + Reconditionné */}
            <AccordionItem value="vente-recond-rachat">
              <AccordionTrigger className="text-left text-xl font-semibold text-gray-900">
                Du matériel neuf, reconditionné et revalorisé, sans compromis
              </AccordionTrigger>
              <AccordionContent className="text-gray-700 leading-relaxed">
                Chez Hociatec, nous proposons un large choix de matériel informatique : ordinateurs,
                écrans, composants et accessoires — du neuf, du reconditionné testé et garanti.
                Chaque produit est sélectionné pour sa fiabilité, ses performances et son impact écologique limité.
                <br /><br />
                Nous reprenons également vos anciens appareils pour les remettre à neuf.
                Ce que nous pouvons réparer, nous le faisons. Ce que nous pouvons réutiliser, nous le revalorisons.
                Résultat : moins de déchets, plus de durabilité, et un choix responsable sans sacrifier la qualité.
              </AccordionContent>
            </AccordionItem>

            {/* 2️⃣ Formations */}
            <AccordionItem value="formations">
              <AccordionTrigger className="text-left text-xl font-semibold text-gray-900">
                Apprenez à maîtriser le numérique
              </AccordionTrigger>
              <AccordionContent className="text-gray-700 leading-relaxed">
                Que vous soyez novice ou confirmé, nos formations sont conçues pour s’adapter à vous.
                En individuel ou en petit groupe, sur site ou à distance, nous vous aidons à comprendre
                et utiliser vos outils numériques au quotidien.
                <br /><br />
                Bureautique, cybersécurité, développement, création de site — nos formateurs
                vous accompagnent pas à pas pour que la technologie devienne un atout, pas une contrainte.
              </AccordionContent>
            </AccordionItem>

            {/* 3️⃣ Création site / logiciel */}
            <AccordionItem value="creation">
              <AccordionTrigger className="text-left text-xl font-semibold text-gray-900">
                Concevez vos outils digitaux sur mesure
              </AccordionTrigger>
              <AccordionContent className="text-gray-700 leading-relaxed">
                Besoin d’un site internet, d’un logiciel professionnel ou d’une application ?
                Notre équipe développe des solutions sur mesure, évolutives et simples à utiliser.
                <br /><br />
                Nous vous accompagnons à chaque étape — conception, développement, mise en ligne
                et maintenance — avec des conseils clairs et une approche personnalisée.
                L’objectif : créer des outils utiles, performants et alignés sur vos besoins réels.
              </AccordionContent>
            </AccordionItem>

            {/* 4️⃣ Location */}
            <AccordionItem value="location">
              <AccordionTrigger className="text-left text-xl font-semibold text-gray-900">
                Louez, testez, évoluez librement
              </AccordionTrigger>
              <AccordionContent className="text-gray-700 leading-relaxed">
                Vous avez besoin d’un poste temporaire, d’un ordinateur pour une mission, une formation
                ou un événement ? Hociatec propose la location de matériel informatique courte ou longue durée.
                <br /><br />
                Vous profitez de matériel fiable et configuré selon vos besoins,
                sans immobiliser votre budget. Une solution souple, économique et accompagnée par notre support technique.
              </AccordionContent>
            </AccordionItem>
          </Accordion>
        </section>

        {/* PRODUITS TENDANCES */}
        <section className="mx-auto max-w-6xl px-6 mt-20">
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900 text-center mb-8">
            Produits tendances
          </h2>
          {loadingProducts && (
            <p className="text-center text-gray-600">Chargement des produits...</p>
          )}
          {errorProducts && (
            <div className="text-center text-red-600">{errorProducts}</div>
          )}
          {!loadingProducts && !errorProducts && products.length > 0 && (
            <div className="grid gap-6 sm:grid-cols-2 md:grid-cols-3">
              {products.map((product) => {
                const absoluteUrl = buildProductUrl(product.slug);
                const fbShare = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(absoluteUrl)}`;
                const mailSubject = encodeURIComponent(`Découvrir: ${product.name}`);
                const mailBody = encodeURIComponent(`${product.shortDescription ?? ''}\r\n\r\n${absoluteUrl}`);
                const handleShareSubmit = (event: FormEvent<HTMLFormElement>) => {
                  event.preventDefault();
                  const rawEmail = shareEmails[product.id] ?? '';
                  const normalizedEmail = rawEmail.trim();
                  if (normalizedEmail === '') {
                    setShareFeedback({
                      productId: product.id,
                      type: 'error',
                      message: 'Veuillez renseigner l\'adresse email du destinataire.',
                    });
                    return;
                  }
                  if (!EMAIL_REGEX.test(normalizedEmail)) {
                    setShareFeedback({
                      productId: product.id,
                      type: 'error',
                      message: 'Cette adresse email ne semble pas valide.',
                    });
                    return;
                  }
                  const mailto = `mailto:${encodeURIComponent(normalizedEmail)}?subject=${mailSubject}&body=${mailBody}`;
                  const link = document.createElement('a');
                  link.href = mailto;
                  link.target = '_self';
                  document.body.appendChild(link);
                  link.click();
                  link.remove();
                  setShareFeedback({
                    productId: product.id,
                    type: 'info',
                    message: 'Votre application de messagerie s\'ouvre avec le produit pré-rempli.',
                  });
                };

                return (
                  <article key={product.id} className="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition p-5 flex flex-col gap-4">
                    <header className="space-y-1">
                      <h3 className="text-lg font-semibold text-slate-900">
                        <Link to={`/catalogue/produits/${product.slug}`} className="hover:underline">
                          {product.name}
                        </Link>
                      </h3>
                      <p className="text-xs text-slate-500 tracking-wide">
                        Référence produit: <span className="font-semibold">{product.sku}</span>
                      </p>
                      <ProductMetaBadges
                        sellingType={product.sellingType}
                        categoryName={product.category.name}
                      />
                    </header>

                    <Link to={`/catalogue/produits/${product.slug}`} className="block">
                      {product.imageUrl ? (
                        <img
                          src={product.imageUrl}
                          alt={product.imageAlt ?? product.name}
                          className="w-full aspect-[4/3] object-cover rounded-lg border border-gray-200"
                        />
                      ) : (
                        <div className="w-full aspect-[4/3] grid place-content-center rounded-lg border border-dashed border-gray-300 text-4xl font-bold text-slate-400">
                          {product.name.charAt(0).toUpperCase()}
                        </div>
                      )}
                    </Link>

                    {product.shortDescription && (
                      <p className="text-sm text-slate-600">{product.shortDescription}</p>
                    )}

                    <div className="flex items-center justify-between gap-3">
                      <span className="text-blue-700 font-bold">
                        {(product.priceCents / 100).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' })}
                      </span>
                      <div className="flex items-center gap-2" role="toolbar" aria-label="Actions du produit">
                        <ProductCartActions product={product} />
                        <button
                          type="button"
                          onClick={() => window.open(fbShare, '_blank', 'noopener,noreferrer')}
                          className="inline-flex items-center gap-1 rounded-full border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
                          title="Partager sur Facebook"
                          aria-label="Partager sur Facebook"
                        >
                          <Facebook size={16} />
                          <span>Facebook</span>
                        </button>
                        <button
                          type="button"
                          onClick={() => {
                            setShareFeedback(null);
                            setShareFormProductId((current) => current === product.id ? null : product.id);
                          }}
                          className="inline-flex items-center gap-1 rounded-full border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
                          title="Partager par e-mail"
                          aria-label="Partager par e-mail"
                        >
                          <Mail size={16} />
                          <span>Email</span>
                        </button>
                      </div>
                    </div>
                    {shareFormProductId === product.id && (
                      <form onSubmit={handleShareSubmit} className="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 shadow-inner space-y-3">
                        <div className="flex items-center justify-between gap-3">
                          <p className="font-semibold text-slate-800">Partager ce produit par email</p>
                          <button
                            type="button"
                            onClick={() => {
                              setShareFormProductId(null);
                              setShareFeedback(null);
                            }}
                            className="text-xs text-slate-500 hover:text-slate-700"
                          >
                            Fermer
                          </button>
                        </div>
                        <label htmlFor={`share-email-${product.id}`} className="text-xs font-medium uppercase tracking-wide text-slate-500">
                          Email du destinataire
                        </label>
                        <input
                          id={`share-email-${product.id}`}
                          type="email"
                          value={shareEmails[product.id] ?? ''}
                          onChange={(event) => {
                            const value = event.target.value;
                            setShareEmails((prev) => ({ ...prev, [product.id]: value }));
                            setShareFeedback((prev) => (prev?.productId === product.id ? null : prev));
                          }}
                          placeholder="ami@exemple.com"
                          className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring focus:ring-blue-100"
                          required
                        />
                        {shareFeedback?.productId === product.id && (
                          <p className={`text-xs ${shareFeedback.type === 'error' ? 'text-red-600' : 'text-emerald-600'}`}>
                            {shareFeedback.message}
                          </p>
                        )}
                        <p className="text-xs text-slate-500">
                          Un nouvel email s'ouvrira avec un message pré-rempli contenant ce produit et son lien détaillé.
                        </p>
                        <div className="flex justify-end gap-2">
                          <button
                            type="button"
                            onClick={() => {
                              setShareFormProductId(null);
                              setShareFeedback(null);
                            }}
                            className="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-white"
                          >
                            Annuler
                          </button>
                          <button
                            type="submit"
                            className="inline-flex items-center gap-1 rounded-full bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                          >
                            Envoyer
                          </button>
                        </div>
                      </form>
                    )}
                  </article>
                );
              })}
            </div>
          )}
          {!loadingProducts && !errorProducts && products.length === 0 && (
          <div className="grid gap-6 sm:grid-cols-2 md:grid-cols-3">
            {[
              {
                name: "Laptop Vega Air 13",
                desc: "13 pouces léger, silencieux et performant.",
                price: "989,00 €",
              },
              {
                name: "Gaming Laptop Nova X15",
                desc: "15 pouces, écran 240 Hz, idéal pour le jeu et la création.",
                price: "1 899,00 €",
              },
              {
                name: "Chromebook Flexia 12",
                desc: "Ordinateur convertible, compact et tactile.",
                price: "649,00 €",
              },
            ].map((p, i) => (
              <div
                key={i}
                className="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition p-5"
              >
                <p className="text-xl font-semibold text-gray-900 mb-2">{p.name}</p>
                <p className="text-gray-600 mb-3">{p.desc}</p>
                <p className="text-blue-700 font-bold">{p.price}</p>
              </div>
            ))}
          </div>
          )}
        </section>
      </main>
    </SiteLayout>
  );
};
