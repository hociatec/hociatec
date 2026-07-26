import { useState } from 'react';
import type { FormEvent } from 'react';
import { Facebook, Mail } from 'lucide-react';

import { ProductCartActions } from '@/features/cart/components/ProductCartActions';
import { CatalogApiError, shareProductByEmail, type CatalogProduct } from '@/features/catalog/api';
import { useToast } from '@/shared/components/ui/toast';
import {
  Dialog,
  DialogBackdrop,
  DialogDescription,
  DialogPanel,
  DialogTitle,
} from '@/shared/components/ui/dialog';
import { SITE_URL } from '@/shared/config/seoConfig';
import { getCatalogProductDisplayName } from '@/features/catalog/utils/productDisplay';

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

interface ProductActionToolbarProps {
  product: CatalogProduct;
}

export const ProductActionToolbar = ({ product }: ProductActionToolbarProps) => {
  const [isShareDialogOpen, setIsShareDialogOpen] = useState(false);
  const [shareEmail, setShareEmail] = useState('');
  const [shareFeedback, setShareFeedback] = useState<{
    type: 'error' | 'info';
    message: string;
  } | null>(null);
  const [isShareSubmitting, setIsShareSubmitting] = useState(false);
  const { show } = useToast();
  const productDisplayName = getCatalogProductDisplayName(product);

  const absoluteUrl = `${SITE_URL}/catalogue/produits/${product.slug}`;
  const facebookShareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(absoluteUrl)}`;
  const mailtoSubject = `Découvrir : ${productDisplayName}`;
  const mailtoBody = [
    'Bonjour,',
    '',
    `Je te partage ce produit : ${productDisplayName}`,
    '',
    `Lien direct : ${absoluteUrl}`,
    '',
    product.shortDescription ?? 'Consulte la fiche produit pour obtenir tous les détails.',
  ].join('\n');

  const openMailClientFallback = (recipientEmail: string) => {
    window.location.href = `mailto:${encodeURIComponent(recipientEmail)}?subject=${encodeURIComponent(mailtoSubject)}&body=${encodeURIComponent(mailtoBody)}`;
  };

  const closeShareDialog = () => {
    setIsShareDialogOpen(false);
    setShareFeedback(null);
  };

  const handleShareSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    const normalizedEmail = shareEmail.trim();

    if (normalizedEmail === '') {
      setShareFeedback({
        type: 'error',
        message: 'Veuillez renseigner l’adresse email du destinataire.',
      });
      return;
    }

    if (!EMAIL_REGEX.test(normalizedEmail)) {
      setShareFeedback({
        type: 'error',
        message: 'Cette adresse email ne semble pas valide.',
      });
      return;
    }

    try {
      setIsShareSubmitting(true);
      await shareProductByEmail(product.slug, { email: normalizedEmail });
      show('Le produit a été envoyé par e-mail.', { variant: 'success' });
      setShareFeedback({
        type: 'info',
        message: 'Le produit a été envoyé par e-mail.',
      });
      setShareEmail('');
      closeShareDialog();
    } catch (error) {
      const message =
        error instanceof Error ? error.message : "Impossible d'envoyer le produit par e-mail.";

      if (error instanceof CatalogApiError && error.statusCode === 503) {
        openMailClientFallback(normalizedEmail);
        const fallbackMessage =
          'Le service e-mail est indisponible. Votre messagerie a été ouverte avec le produit prérempli.';
        setShareFeedback({
          type: 'info',
          message: fallbackMessage,
        });
        show(fallbackMessage, { variant: 'info' });
        closeShareDialog();
        return;
      }

      setShareFeedback({
        type: 'error',
        message,
      });
      show(message, { variant: 'error' });
    } finally {
      setIsShareSubmitting(false);
    }
  };

  return (
    <>
      <div className="product-action-toolbar" aria-label="Actions du produit">
        <ProductCartActions product={product} />
        <button
          type="button"
          onClick={() => window.open(facebookShareUrl, '_blank', 'noopener,noreferrer')}
          className="inline-flex items-center gap-1 rounded-full border border-brand-100 px-3 py-1.5 text-sm text-stone-700 hover:bg-brand-50"
          title="Partager sur Facebook"
          aria-label="Partager sur Facebook"
        >
          <Facebook size={16} />
          <span>Facebook</span>
        </button>
        <button
          type="button"
          onClick={() => {
            setIsShareDialogOpen(true);
            setShareFeedback(null);
          }}
          className="inline-flex items-center gap-1 rounded-full border border-brand-100 px-3 py-1.5 text-sm text-stone-700 hover:bg-brand-50"
          title="Partager par e-mail"
          aria-label="Partager par e-mail"
          aria-haspopup="dialog"
        >
          <Mail size={16} />
          <span>Email</span>
        </button>
      </div>
      <Dialog open={isShareDialogOpen} onClose={closeShareDialog} className="relative z-50">
        <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
        <div className="fixed inset-0 flex items-center justify-center px-4 py-6">
          <DialogPanel className="w-full max-w-lg rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
            <header className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">
                Partage par e-mail
              </p>
              <DialogTitle className="text-2xl font-bold text-brand-900">
                Partager {productDisplayName}
              </DialogTitle>
              <DialogDescription className="text-sm text-stone-600">
                Renseignez une adresse e-mail. Le bouton envoyer transmettra le produit par e-mail.
              </DialogDescription>
            </header>

            <form
              onSubmit={handleShareSubmit}
              className="mt-6 space-y-4"
              aria-busy={isShareSubmitting}
            >
              <div className="space-y-2">
                <label
                  htmlFor={`product-share-email-${product.id}`}
                  className="block text-sm font-medium text-stone-800"
                >
                  Adresse e-mail du destinataire
                </label>
                <input
                  id={`product-share-email-${product.id}`}
                  type="email"
                  inputMode="email"
                  autoComplete="email"
                  value={shareEmail}
                  onChange={(event) => {
                    setShareEmail(event.target.value);
                    setShareFeedback(null);
                  }}
                  aria-invalid={shareFeedback?.type === 'error'}
                  aria-describedby={`product-share-hint-${product.id} product-share-feedback-${product.id}`}
                  placeholder="ami@exemple.com"
                  className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                  required
                  disabled={isShareSubmitting}
                />
                <p id={`product-share-hint-${product.id}`} className="text-sm text-stone-600">
                  Le message sera prérempli avec le nom du produit et son lien direct.
                </p>
                <p
                  id={`product-share-feedback-${product.id}`}
                  role={shareFeedback?.type === 'error' ? 'alert' : 'status'}
                  aria-live={shareFeedback?.type === 'error' ? 'assertive' : 'polite'}
                  aria-atomic="true"
                  className={`text-sm ${shareFeedback?.type === 'error' ? 'text-red-700' : 'text-emerald-800'}`}
                >
                  {shareFeedback?.message ?? ''}
                </p>
              </div>

              <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                  type="button"
                  onClick={closeShareDialog}
                  disabled={isShareSubmitting}
                  className="inline-flex items-center justify-center rounded-lg border border-brand-100 px-4 py-3 text-sm font-semibold text-stone-700 transition hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  disabled={isShareSubmitting}
                  className="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100"
                >
                  {isShareSubmitting ? 'Envoi en cours...' : 'Envoyer par e-mail'}
                </button>
              </div>
            </form>
          </DialogPanel>
        </div>
      </Dialog>
    </>
  );
};
