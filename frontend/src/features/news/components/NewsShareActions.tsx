import { useState, type FormEvent } from 'react';
import { Facebook, Mail } from 'lucide-react';

import { NewsApiError, shareNewsArticleByEmail, type NewsArticleDto } from '@/features/news/api/newsApi';
import { useToast } from '@/shared/components/ui/toast';
import { SITE_URL } from '@/shared/config/seoConfig';

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

type NewsShareActionsProps = {
  article: NewsArticleDto;
  compact?: boolean;
};

export const NewsShareActions = ({ article, compact = false }: NewsShareActionsProps) => {
  const [isEmailDialogOpen, setIsEmailDialogOpen] = useState(false);
  const [shareEmail, setShareEmail] = useState('');
  const [shareFeedback, setShareFeedback] = useState<{
    type: 'error' | 'info';
    message: string;
  } | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { show } = useToast();
  const absoluteUrl = `${SITE_URL}/actualites/${article.slug}`;
  const facebookShareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(absoluteUrl)}`;
  const mailtoSubject = `À lire : ${article.title}`;
  const mailtoBody = [
    'Bonjour,',
    '',
    `Je te partage cette actualité Hociatec : ${article.title}`,
    '',
    `Lien direct : ${absoluteUrl}`,
    '',
    article.excerpt,
  ].join('\n');

  const closeDialog = () => {
    setShareFeedback(null);
    setIsEmailDialogOpen(false);
  };

  const openMailClientFallback = (recipientEmail: string) => {
    window.location.href = `mailto:${encodeURIComponent(recipientEmail)}?subject=${encodeURIComponent(mailtoSubject)}&body=${encodeURIComponent(mailtoBody)}`;
  };

  const handleEmailSubmit = async (event: FormEvent<HTMLFormElement>) => {
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
      await shareNewsArticleByEmail(article.slug, { email: normalizedEmail });
      show('L’actualité a été envoyée par e-mail.', { variant: 'success' });
      setShareEmail('');
      closeDialog();
    } catch (error) {
      if (error instanceof NewsApiError && error.statusCode === 503) {
        openMailClientFallback(normalizedEmail);
        const fallbackMessage =
          'Le service e-mail est indisponible. Votre messagerie a été ouverte avec l’actualité préremplie.';
        setShareFeedback({ type: 'info', message: fallbackMessage });
        show(fallbackMessage, { variant: 'info' });
        closeDialog();
        return;
      }

      const message =
        error instanceof Error ? error.message : "Impossible d'envoyer l’actualité par e-mail.";
      setShareFeedback({ type: 'error', message });
      show(message, { variant: 'error' });
    } finally {
      setIsSubmitting(false);
    }
  };

  const buttonClassName = compact
    ? 'inline-flex h-9 items-center gap-2 rounded-full border border-brand-100 px-3 text-sm font-semibold text-brand-800 transition hover:border-brand-300 hover:bg-brand-50'
    : 'inline-flex items-center gap-2 rounded-full border border-brand-100 px-4 py-2 text-sm font-semibold text-brand-800 transition hover:border-brand-300 hover:bg-brand-50';

  return (
    <>
      <div className="flex flex-wrap gap-2" aria-label="Partager l’actualité">
        <button
          type="button"
          onClick={() => window.open(facebookShareUrl, '_blank', 'noopener,noreferrer')}
          className={buttonClassName}
          title="Partager sur Facebook"
          aria-label="Partager cette actualité sur Facebook"
        >
          <Facebook size={16} />
          <span>Facebook</span>
        </button>
        <button
          type="button"
          onClick={() => setIsEmailDialogOpen(true)}
          className={buttonClassName}
          title="Partager par e-mail"
          aria-label="Partager cette actualité par e-mail"
          aria-haspopup="dialog"
        >
          <Mail size={16} />
          <span>Email</span>
        </button>
      </div>

      {isEmailDialogOpen ? (
        <div
          className="fixed inset-0 z-50 overflow-y-auto bg-brand-900/70 px-4 py-4 sm:py-6"
          role="dialog"
          aria-modal="true"
          aria-labelledby={`news-share-title-${article.id}`}
          aria-describedby={`news-share-description-${article.id}`}
        >
          <div className="mx-auto w-full max-w-lg rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
            <header className="space-y-2">
              <h2 id={`news-share-title-${article.id}`} className="text-2xl font-bold text-brand-900">
                Partager l’actualité
              </h2>
              <p id={`news-share-description-${article.id}`} className="text-sm text-stone-600">
                Renseignez une adresse e-mail. Le bouton envoyer transmettra l’actualité par e-mail.
              </p>
            </header>

            <form onSubmit={handleEmailSubmit} className="mt-6 space-y-4" aria-busy={isSubmitting}>
              <div className="space-y-2">
                <label
                  htmlFor={`news-share-email-${article.id}`}
                  className="block text-sm font-medium text-stone-800"
                >
                  Adresse e-mail du destinataire
                </label>
                <input
                  id={`news-share-email-${article.id}`}
                  type="email"
                  inputMode="email"
                  autoComplete="email"
                  value={shareEmail}
                  onChange={(event) => {
                    setShareEmail(event.target.value);
                    setShareFeedback(null);
                  }}
                  aria-invalid={shareFeedback?.type === 'error'}
                  aria-describedby={`news-share-hint-${article.id} news-share-feedback-${article.id}`}
                  placeholder="ami@exemple.com"
                  className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                  required
                  disabled={isSubmitting}
                />
                <p id={`news-share-hint-${article.id}`} className="text-sm text-stone-600">
                  Le message sera prérempli avec le titre de l’actualité et son lien direct.
                </p>
                <p
                  id={`news-share-feedback-${article.id}`}
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
          </div>
        </div>
      ) : null}
    </>
  );
};
