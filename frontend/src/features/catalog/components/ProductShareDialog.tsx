import { useState, type FormEvent } from 'react';

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

type ProductShareDialogProps = {
  product: CatalogProduct;
  open: boolean;
  onClose: () => void;
};

export const ProductShareDialog = ({ product, open, onClose }: ProductShareDialogProps) => {
  const [shareEmail, setShareEmail] = useState('');
  const [shareFeedback, setShareFeedback] = useState<{
    type: 'error' | 'info';
    message: string;
  } | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { show } = useToast();
  const productDisplayName = getCatalogProductDisplayName(product);
  const absoluteUrl = `${SITE_URL}/catalogue/produits/${product.slug}`;
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

  const closeDialog = () => {
    setShareFeedback(null);
    onClose();
  };

  const openMailClientFallback = (recipientEmail: string) => {
    window.location.href = `mailto:${encodeURIComponent(recipientEmail)}?subject=${encodeURIComponent(mailtoSubject)}&body=${encodeURIComponent(mailtoBody)}`;
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
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
      setShareFeedback({ type: 'error', message: 'Cette adresse email ne semble pas valide.' });
      return;
    }

    try {
      setIsSubmitting(true);
      await shareProductByEmail(product.slug, { email: normalizedEmail });
      show('Le produit a été envoyé par e-mail.', { variant: 'success' });
      setShareFeedback({ type: 'info', message: 'Le produit a été envoyé par e-mail.' });
      setShareEmail('');
      closeDialog();
    } catch (error) {
      const message =
        error instanceof Error ? error.message : "Impossible d'envoyer le produit par e-mail.";

      if (error instanceof CatalogApiError && error.statusCode === 503) {
        openMailClientFallback(normalizedEmail);
        const fallbackMessage =
          'Le service e-mail est indisponible. Votre messagerie a été ouverte avec le produit prérempli.';
        setShareFeedback({ type: 'info', message: fallbackMessage });
        show(fallbackMessage, { variant: 'info' });
        closeDialog();
        return;
      }

      setShareFeedback({ type: 'error', message });
      show(message, { variant: 'error' });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Dialog open={open} onClose={closeDialog} className="relative z-50">
      <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
      <div className="fixed inset-0 flex items-center justify-center px-4 py-6">
        <DialogPanel className="w-full max-w-lg rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
          <header className="space-y-2">
            <DialogTitle className="text-2xl font-bold text-brand-900">
              Partager {productDisplayName}
            </DialogTitle>
            <DialogDescription className="text-sm text-stone-600">
              Renseignez une adresse e-mail. Le bouton envoyer transmettra le produit par e-mail.
            </DialogDescription>
          </header>

          <form onSubmit={handleSubmit} className="mt-6 space-y-4" aria-busy={isSubmitting}>
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
                disabled={isSubmitting}
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
                onClick={closeDialog}
                disabled={isSubmitting}
                className="inline-flex items-center justify-center rounded-lg border border-brand-100 px-4 py-3 text-sm font-semibold text-stone-700 transition hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
              >
                Annuler
              </button>
              <button
                type="submit"
                disabled={isSubmitting}
                className="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100"
              >
                {isSubmitting ? 'Envoi en cours...' : 'Envoyer par e-mail'}
              </button>
            </div>
          </form>
        </DialogPanel>
      </div>
    </Dialog>
  );
};
