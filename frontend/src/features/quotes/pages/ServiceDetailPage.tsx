import { Link } from 'react-router';

import { usePublicServiceDetail } from '../hooks/usePublicServiceDetail';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { SITE_URL } from '@/shared/config/seoConfig';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { formatEuroCents } from '@/shared/lib/formatters';
import { formatServiceBillingMode } from '@/features/quotes/lib/serviceBillingMode';
import { resolveServiceIllustration } from '@/features/quotes/lib/servicePresentation';

export const ServiceDetailPage = () => {
  const { serviceId, service, loading, error, retry } = usePublicServiceDetail();
  const illustration = service ? resolveServiceIllustration(service) : null;

  useDocumentTitle(service ? service.title : 'Service');

  useMetaTags({
    title: service ? `${service.title} — Hociatec` : 'Service — Hociatec',
    description:
      service?.description?.trim() ||
      'Découvrez le détail du service, sa durée estimée et sa base tarifaire.',
    canonicalUrl: serviceId !== null
      ? `${SITE_URL}/services/${serviceId}`
      : `${SITE_URL}/services`,
  });

  return (
    <SiteLayout headerVariant="light">
      <div className="mx-auto flex w-full max-w-4xl flex-col gap-8 px-6 py-12">
        <Link
          to="/services"
          className="inline-flex w-fit items-center text-sm font-semibold text-brand-700 hover:text-brand-900"
        >
          Retour au catalogue
        </Link>

        {loading ? (
          <LoadingState>Chargement du service...</LoadingState>
        ) : error || !service ? (
          <ErrorState onAction={error ? () => void retry() : undefined}>
            {error || 'Service introuvable.'}
          </ErrorState>
        ) : (
          <article className="rounded-xl border border-brand-100 bg-white p-8 shadow-sm">
            {illustration ? (
              <div className="mb-8 flex min-h-[240px] items-center justify-center rounded-3xl bg-[linear-gradient(135deg,rgba(255,247,236,0.95),rgba(245,250,255,0.92))] p-8">
                <img
                  src={illustration.imageUrl}
                  alt={illustration.imageAlt || service.title}
                  className="max-h-[220px] w-full max-w-[320px] object-contain"
                  width={640}
                  height={360}
                  loading="lazy"
                  decoding="async"
                />
              </div>
            ) : null}
            <h1 className="text-4xl font-semibold tracking-tight text-brand-900">
              {service.title}
            </h1>
            <p className="mt-5 text-base leading-7 text-stone-600">
              {service.description?.trim() ||
                'Les informations détaillées de ce service seront précisées avec votre besoin.'}
            </p>

            <div className="mt-8 grid gap-4 md:grid-cols-3">
              <div className="rounded-2xl border border-brand-100 bg-brand-50 p-5">
                <p className="text-xs uppercase tracking-[0.18em] text-stone-500">Base tarifaire</p>
                <p className="mt-2 text-2xl font-semibold text-brand-900">
                  {formatEuroCents(service.priceCents)}
                </p>
              </div>
              <div className="rounded-2xl border border-brand-100 bg-brand-50 p-5">
                <p className="text-xs uppercase tracking-[0.18em] text-stone-500">
                  Mode de facturation
                </p>
                <p className="mt-2 text-2xl font-semibold text-brand-900">
                  {formatServiceBillingMode(service.unit)}
                </p>
              </div>
              <div className="rounded-2xl border border-brand-100 bg-brand-50 p-5">
                <p className="text-xs uppercase tracking-[0.18em] text-stone-500">Durée estimée</p>
                <p className="mt-2 text-2xl font-semibold text-brand-900">
                  {service.durationLabel || 'Sur étude'}
                </p>
              </div>
            </div>
          </article>
        )}
      </div>
    </SiteLayout>
  );
};
