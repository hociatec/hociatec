import { useEffect, useId, useRef, useState } from "react";
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
import { fetchPublicProducts, shareProductByEmail, type CatalogProduct } from "@/features/catalog/api";
import { ProductMetaBadges } from "@/features/catalog/components/ProductMetaBadges";
import { ProductCartActions } from "@/features/cart/components/ProductCartActions";
import { Mail, Facebook } from "lucide-react";
import { createPortal } from "react-dom";
import { Link } from "react-router-dom";
import { ORGANIZATION_SCHEMA, WEBSITE_SCHEMA, SITE_URL, LOCAL_BUSINESS_SCHEMA } from "@/shared/config/seoConfig";
import { useToast } from "@/shared/components/ui/toast";

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export const HomePage = () => {
  useDocumentTitle("Le numÃ©rique Ã  taille humaine");
  useMetaTags({
    title: 'Hociatec â€” Le numÃ©rique Ã  taille humaine',
    description:
      'Vente/location de matÃ©riel, formation, conception, audits. Une approche accessible, durable et pensÃ©e pour vous.',
    type: 'website',
    canonicalUrl: SITE_URL,
    structuredData: [ORGANIZATION_SCHEMA, WEBSITE_SCHEMA, LOCAL_BUSINESS_SCHEMA],
  });

  // Ferme la section ouverte quand on appuie sur Ã‰chap
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
  const [shareDialogProduct, setShareDialogProduct] = useState<CatalogProduct | null>(null);
  const [shareEmails, setShareEmails] = useState<Record<number, string>>({});
  const [shareFeedback, setShareFeedback] = useState<
    { productId: number; type: 'error' | 'info'; message: string } | null
  >(null);
  const [shareSubmitting, setShareSubmitting] = useState(false);
  const shareInputRef = useRef<HTMLInputElement | null>(null);
  const shareCancelButtonRef = useRef<HTMLButtonElement | null>(null);
  const shareTriggerRefs = useRef<Record<number, HTMLButtonElement | null>>({});
  const shareDialogTitleId = useId();
  const shareDialogDescriptionId = useId();
  const { show: showToast } = useToast();

  const closeShareDialog = () => {
    const productId = shareDialogProduct?.id ?? null;
    setShareDialogProduct(null);
    setShareFeedback((current) => (current?.productId === productId ? null : current));
    if (productId !== null) {
      window.requestAnimationFrame(() => {
        shareTriggerRefs.current[productId]?.focus();
      });
    }
  };

  const openShareDialog = (product: CatalogProduct) => {
    setShareDialogProduct(product);
    setShareFeedback((current) => (current?.productId === product.id ? null : current));
  };

  useEffect(() => {
    setLoadingProducts(true);
    setErrorProducts(null);
    void fetchPublicProducts({ homepage: true })
      .then((items) => setProducts(items))
      .catch((err: Error) => setErrorProducts(err.message || "Impossible de charger les produits."))
      .finally(() => setLoadingProducts(false));
  }, []);

  useEffect(() => {
    if (!shareDialogProduct) {
      return;
    }

    shareInputRef.current?.focus();
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        closeShareDialog();
        return;
      }

      if (event.key !== 'Tab') {
        return;
      }

      const container = document.getElementById('product-share-dialog');
      if (!container) {
        return;
      }

      const focusable = container.querySelectorAll<HTMLElement>(
        'button:not([disabled]), input:not([disabled]), [href], [tabindex]:not([tabindex="-1"])',
      );
      if (focusable.length === 0) {
        return;
      }

      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    };

    window.addEventListener('keydown', handleKeyDown);

    return () => {
      window.removeEventListener('keydown', handleKeyDown);
      document.body.style.overflow = previousOverflow;
    };
  }, [shareDialogProduct]);

  const origin = typeof window !== "undefined" ? window.location.origin : "";
  const activeShareProduct = shareDialogProduct;
  const shareDialogEmail = activeShareProduct ? shareEmails[activeShareProduct.id] ?? '' : '';

  const handleShareSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (!activeShareProduct) {
      return;
    }

    const rawEmail = shareDialogEmail;
    const normalizedEmail = rawEmail.trim();

    if (normalizedEmail === '') {
      setShareFeedback({
        productId: activeShareProduct.id,
        type: 'error',
        message: 'Veuillez renseigner l’adresse email du destinataire.',
      });
      return;
    }

    if (!EMAIL_REGEX.test(normalizedEmail)) {
      setShareFeedback({
        productId: activeShareProduct.id,
        type: 'error',
        message: 'Cette adresse email ne semble pas valide.',
      });
      return;
    }

    try {
      setShareSubmitting(true);
      await shareProductByEmail(activeShareProduct.slug, { email: normalizedEmail });
      setShareFeedback({
        productId: activeShareProduct.id,
        type: 'info',
        message: 'Le produit a ete envoye par e-mail.',
      });
      showToast('Le produit a ete envoye par e-mail.', { variant: 'success' });
      closeShareDialog();
    } catch (error) {
      const message =
        error instanceof Error
          ? error.message
          : "Impossible d'envoyer le produit par e-mail.";
      setShareFeedback({
        productId: activeShareProduct.id,
        type: 'error',
        message,
      });
      showToast(message, { variant: 'error' });
    } finally {
      setShareSubmitting(false);
    }
  };

  return (
    <SiteLayout>
      <div className="py-20 bg-gradient-to-br from-white via-gray-50 to-blue-50 text-gray-800">
        {/* HERO */}
        <section className="text-center mx-auto max-w-4xl px-6 mb-12">
          <h1 className="text-4xl md:text-6xl font-extrabold text-gray-900">
            Hociatec, une entreprise Ã  taille humaine
          </h1>
          <p className="mt-4 text-lg text-gray-700">
            Le numÃ©rique, oui â€” mais accessible, durable et pensÃ© pour vous.
          </p>
          <p className="mt-3 text-gray-600 max-w-2xl mx-auto">
            Vente, reprise, formation, conception, location : Hociatec vous accompagne Ã  chaque Ã©tape,
            que vous soyez particulier ou professionnel. Une approche simple, concrÃ¨te et humaine
            pour avancer sereinement dans le monde numÃ©rique.
          </p>
        </section>

        {/* TITRE ACCROCHEUR */}
        <div className="text-center mb-12 px-6">
          <p className="text-3xl md:text-4xl font-bold text-gray-900">
            Des solutions concrÃ¨tes pour tous vos besoins numÃ©riques
          </p>
          <p className="mt-2 text-gray-600 max-w-2xl mx-auto">
            DÃ©couvrez nos services et choisissez ce qui correspond Ã  vos besoins.
          </p>
        </div>

        {/* ACCORDÃ‰ON DES SERVICES */}
        <section className="mx-auto max-w-4xl px-6">
          <Accordion type="single" collapsible className="space-y-3">
            {/* 1ï¸âƒ£ Vente + ReconditionnÃ© */}
            <AccordionItem value="vente-recond-rachat">
              <AccordionTrigger className="text-left text-xl font-semibold text-gray-900">
                Du matÃ©riel neuf, reconditionnÃ© et revalorisÃ©, sans compromis
              </AccordionTrigger>
              <AccordionContent className="text-gray-700 leading-relaxed">
                Chez Hociatec, nous proposons un large choix de matÃ©riel informatique : ordinateurs,
                Ã©crans, composants et accessoires â€” du neuf, du reconditionnÃ© testÃ© et garanti.
                Chaque produit est sÃ©lectionnÃ© pour sa fiabilitÃ©, ses performances et son impact Ã©cologique limitÃ©.
                <br /><br />
                Nous reprenons Ã©galement vos anciens appareils pour les remettre Ã  neuf.
                Ce que nous pouvons rÃ©parer, nous le faisons. Ce que nous pouvons rÃ©utiliser, nous le revalorisons.
                RÃ©sultat : moins de dÃ©chets, plus de durabilitÃ©, et un choix responsable sans sacrifier la qualitÃ©.
              </AccordionContent>
            </AccordionItem>

            {/* 2ï¸âƒ£ Formations */}
            <AccordionItem value="formations">
              <AccordionTrigger className="text-left text-xl font-semibold text-gray-900">
                Apprenez Ã  maÃ®triser le numÃ©rique
              </AccordionTrigger>
              <AccordionContent className="text-gray-700 leading-relaxed">
                Que vous soyez novice ou confirmÃ©, nos formations sont conÃ§ues pour sâ€™adapter Ã  vous.
                En individuel ou en petit groupe, sur site ou Ã  distance, nous vous aidons Ã  comprendre
                et utiliser vos outils numÃ©riques au quotidien.
                <br /><br />
                Bureautique, cybersÃ©curitÃ©, dÃ©veloppement, crÃ©ation de site â€” nos formateurs
                vous accompagnent pas Ã  pas pour que la technologie devienne un atout, pas une contrainte.
              </AccordionContent>
            </AccordionItem>

            {/* 3ï¸âƒ£ CrÃ©ation site / logiciel */}
            <AccordionItem value="creation">
              <AccordionTrigger className="text-left text-xl font-semibold text-gray-900">
                Concevez vos outils digitaux sur mesure
              </AccordionTrigger>
              <AccordionContent className="text-gray-700 leading-relaxed">
                Besoin dâ€™un site internet, dâ€™un logiciel professionnel ou dâ€™une application ?
                Notre Ã©quipe dÃ©veloppe des solutions sur mesure, Ã©volutives et simples Ã  utiliser.
                <br /><br />
                Nous vous accompagnons Ã  chaque Ã©tape â€” conception, dÃ©veloppement, mise en ligne
                et maintenance â€” avec des conseils clairs et une approche personnalisÃ©e.
                Lâ€™objectif : crÃ©er des outils utiles, performants et alignÃ©s sur vos besoins rÃ©els.
              </AccordionContent>
            </AccordionItem>

            {/* 4ï¸âƒ£ Location */}
            <AccordionItem value="location">
              <AccordionTrigger className="text-left text-xl font-semibold text-gray-900">
                Louez, testez, Ã©voluez librement
              </AccordionTrigger>
              <AccordionContent className="text-gray-700 leading-relaxed">
                Vous avez besoin dâ€™un poste temporaire, dâ€™un ordinateur pour une mission, une formation
                ou un Ã©vÃ©nement ? Hociatec propose la location de matÃ©riel informatique courte ou longue durÃ©e.
                <br /><br />
                Vous profitez de matÃ©riel fiable et configurÃ© selon vos besoins,
                sans immobiliser votre budget. Une solution souple, Ã©conomique et accompagnÃ©e par notre support technique.
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
                const absoluteUrl = `${origin}/catalogue/produits/${product.slug}`;
                const fbShare = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(absoluteUrl)}`;
                const compactSpecs = [
                  product.brand?.trim(),
                  product.storageCapacity?.trim(),
                  product.memoryRam?.trim(),
                  product.color?.trim(),
                ]
                  .filter(Boolean)
                  .join(' â€¢ ');

                return (
                  <article key={product.id} className="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition p-5 flex flex-col gap-4">
                    <header className="space-y-1">
                      <h3 className="text-lg font-semibold text-slate-900">
                        <Link to={`/catalogue/produits/${product.slug}`} className="hover:underline">
                          {product.name}
                        </Link>
                      </h3>
                      <p className="text-xs text-slate-500 tracking-wide">
                        RÃ©fÃ©rence produit: <span className="font-semibold">{product.sku}</span>
                      </p>
                      <ProductMetaBadges
                        sellingType={product.sellingType}
                        categoryName={product.category.name}
                      />
                      {compactSpecs.length > 0 && (
                        <p className="catalog-product-card__spec-summary" aria-label="CaractÃ©ristiques principales">
                          {compactSpecs}
                        </p>
                      )}
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
                      <div className="flex items-center gap-2" aria-label="Actions du produit">
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
                          ref={(element) => {
                            shareTriggerRefs.current[product.id] = element;
                          }}
                          onClick={() => openShareDialog(product)}
                          className="inline-flex items-center gap-1 rounded-full border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
                          title="Partager par e-mail"
                          aria-label="Partager par e-mail"
                          aria-haspopup="dialog"
                          aria-expanded={activeShareProduct?.id === product.id}
                        >
                          <Mail size={16} />
                          <span>Email</span>
                        </button>
                      </div>
                    </div>
                  </article>
                );
              })}
            </div>
          )}
          {!loadingProducts && !errorProducts && products.length === 0 && (
            <div className="rounded-2xl border border-slate-200 bg-white px-6 py-10 text-center shadow-sm">
              <p className="text-lg font-semibold text-slate-900">Aucun produit mis en avant pour le moment</p>
              <p className="mt-2 text-sm text-slate-600">
                Les produits tendances rÃ©apparaÃ®tront ici dÃ¨s que le catalogue sera rÃ©alimentÃ©.
              </p>
            </div>
          )}
        </section>
        {activeShareProduct &&
          createPortal(
            <div
              className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4 py-6"
              onMouseDown={(event) => {
                if (event.target === event.currentTarget) {
                  closeShareDialog();
                }
              }}
            >
              <section
                id="product-share-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby={shareDialogTitleId}
                aria-describedby={shareDialogDescriptionId}
                className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl"
              >
                <header className="space-y-2">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                    Partage par e-mail
                  </p>
                  <h2 id={shareDialogTitleId} className="text-2xl font-bold text-slate-900">
                    Partager {activeShareProduct.name}
                  </h2>
                  <p id={shareDialogDescriptionId} className="text-sm text-slate-600">
                    Renseignez une adresse e-mail. Le bouton envoyer transmettra le produit par e-mail.
                  </p>
                </header>

                <form onSubmit={handleShareSubmit} className="mt-6 space-y-4" aria-busy={shareSubmitting}>
                  <div className="space-y-2">
                    <label htmlFor="product-share-email" className="block text-sm font-medium text-slate-800">
                      Adresse e-mail du destinataire
                    </label>
                    <input
                      ref={shareInputRef}
                      id="product-share-email"
                      type="email"
                      inputMode="email"
                      autoComplete="email"
                      value={shareDialogEmail}
                      onChange={(event) => {
                        const value = event.target.value;
                        setShareEmails((prev) => ({ ...prev, [activeShareProduct.id]: value }));
                        setShareFeedback((prev) =>
                          prev?.productId === activeShareProduct.id ? null : prev,
                        );
                      }}
                      aria-invalid={shareFeedback?.productId === activeShareProduct.id && shareFeedback.type === 'error'}
                      aria-describedby="product-share-email-hint product-share-email-feedback"
                      placeholder="ami@exemple.com"
                      className="w-full rounded-xl border border-slate-300 px-4 py-3 text-base text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                      required
                      disabled={shareSubmitting}
                    />
                    <p id="product-share-email-hint" className="text-sm text-slate-500">
                      Le message sera prÃ©rempli avec le nom du produit et son lien direct.
                    </p>
                    <p
                      id="product-share-email-feedback"
                      role="status"
                      aria-live="polite"
                      className={`text-sm ${
                        shareFeedback?.productId === activeShareProduct.id && shareFeedback.type === 'error'
                          ? 'text-red-600'
                          : 'text-emerald-700'
                      }`}
                    >
                      {shareFeedback?.productId === activeShareProduct.id ? shareFeedback.message : ''}
                    </p>
                  </div>

                  <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                      ref={shareCancelButtonRef}
                      type="button"
                      onClick={closeShareDialog}
                      disabled={shareSubmitting}
                      className="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200"
                    >
                      Annuler
                    </button>
                    <button
                      type="submit"
                      disabled={shareSubmitting}
                      className="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"
                    >
                      {shareSubmitting ? 'Envoi en cours...' : 'Envoyer par e-mail'}
                    </button>
                  </div>
                </form>
              </section>
            </div>,
            document.body,
          )}
      </div>
    </SiteLayout>
  );
};



