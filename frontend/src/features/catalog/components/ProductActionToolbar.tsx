import { createPortal } from 'react-dom';
import { useEffect, useId, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { Facebook, Mail } from 'lucide-react';

import { ProductCartActions } from '@/features/cart/components/ProductCartActions';
import { CatalogApiError, shareProductByEmail, type CatalogProduct } from '@/features/catalog/api';
import { useToast } from '@/shared/components/ui/toast';
import { getCatalogProductDisplayName } from '@/features/catalog/utils/productDisplay';

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

interface ProductActionToolbarProps {
  product: CatalogProduct;
}

export const ProductActionToolbar = ({ product }: ProductActionToolbarProps) => {
  const [isShareDialogOpen, setIsShareDialogOpen] = useState(false);
  const [shareEmail, setShareEmail] = useState('');
  const [shareFeedback, setShareFeedback] = useState<{ type: 'error' | 'info'; message: string } | null>(null);
  const [isShareSubmitting, setIsShareSubmitting] = useState(false);
  const shareInputRef = useRef<HTMLInputElement | null>(null);
  const shareTriggerRef = useRef<HTMLButtonElement | null>(null);
  const shareDialogTitleId = useId();
  const shareDialogDescriptionId = useId();
  const { show } = useToast();
  const productDisplayName = getCatalogProductDisplayName(product);

  const origin = typeof window !== 'undefined' ? window.location.origin : '';
  const absoluteUrl = `${origin}/catalogue/produits/${product.slug}`;
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
    window.requestAnimationFrame(() => {
      shareTriggerRef.current?.focus();
    });
  };

  useEffect(() => {
    if (!isShareDialogOpen) {
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

      const container = document.getElementById(`product-share-dialog-${product.id}`);
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
  }, [isShareDialogOpen, product.id]);

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
        error instanceof Error
          ? error.message
          : "Impossible d'envoyer le produit par e-mail.";

      if (error instanceof CatalogApiError && error.statusCode === 503) {
        openMailClientFallback(normalizedEmail);
        const fallbackMessage = 'Le service e-mail est indisponible. Votre messagerie a été ouverte avec le produit prérempli.';
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
          className="inline-flex items-center gap-1 rounded-full border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
          title="Partager sur Facebook"
          aria-label="Partager sur Facebook"
        >
          <Facebook size={16} />
          <span>Facebook</span>
        </button>
        <button
          ref={shareTriggerRef}
          type="button"
          onClick={() => {
            setIsShareDialogOpen(true);
            setShareFeedback(null);
          }}
          className="inline-flex items-center gap-1 rounded-full border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
          title="Partager par e-mail"
          aria-label="Partager par e-mail"
          aria-haspopup="dialog"
          aria-expanded={isShareDialogOpen}
        >
          <Mail size={16} />
          <span>Email</span>
        </button>
      </div>
      {isShareDialogOpen &&
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
              id={`product-share-dialog-${product.id}`}
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
                  Partager {productDisplayName}
                </h2>
                <p id={shareDialogDescriptionId} className="text-sm text-slate-600">
                  Renseignez une adresse e-mail. Le bouton envoyer transmettra le produit par e-mail.
                </p>
              </header>

              <form onSubmit={handleShareSubmit} className="mt-6 space-y-4" aria-busy={isShareSubmitting}>
                <div className="space-y-2">
                  <label htmlFor={`product-share-email-${product.id}`} className="block text-sm font-medium text-slate-800">
                    Adresse e-mail du destinataire
                  </label>
                  <input
                    ref={shareInputRef}
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
                    aria-describedby={`product-share-email-hint-${product.id} product-share-email-feedback-${product.id}`}
                    placeholder="ami@exemple.com"
                    className="w-full rounded-xl border border-slate-300 px-4 py-3 text-base text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                    required
                    disabled={isShareSubmitting}
                  />
                  <p id={`product-share-email-hint-${product.id}`} className="text-sm text-slate-500">
                    Le message sera prérempli avec le nom du produit et son lien direct.
                  </p>
                  <p
                    id={`product-share-email-feedback-${product.id}`}
                    role="status"
                    aria-live="polite"
                    className={`text-sm ${shareFeedback?.type === 'error' ? 'text-red-600' : 'text-emerald-700'}`}
                  >
                    {shareFeedback?.message ?? ''}
                  </p>
                </div>

                <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                  <button
                    type="button"
                    onClick={closeShareDialog}
                    disabled={isShareSubmitting}
                    className="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200"
                  >
                    Annuler
                  </button>
                  <button
                    type="submit"
                    disabled={isShareSubmitting}
                    className="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"
                  >
                    {isShareSubmitting ? 'Envoi en cours...' : 'Envoyer par e-mail'}
                  </button>
                </div>
              </form>
            </section>
          </div>,
          document.body,
        )}
    </>
  );
};
