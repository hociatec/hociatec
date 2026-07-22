import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';

import { fetchPublicQuoteService } from '@/features/quotes/api';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { SITE_URL } from '@/shared/config/seoConfig';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { formatEuroCents } from '@/shared/lib/formatters';

type PublicService = {
  id: number;
  title: string;
  description?: string | null;
  unit?: string | null;
  durationValue?: number | null;
  durationUnit?: 'hour' | 'day' | null;
  durationLabel?: string | null;
  priceCents: number;
  vatRate?: number;
};

export const ServiceDetailPage = () => {
  const params = useParams<{ serviceId: string }>();
  const serviceId = Number.parseInt(params.serviceId ?? '', 10);

  const [service, setService] = useState<PublicService | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useDocumentTitle(service ? service.title : 'Service');

  useMetaTags({
    title: service ? `${service.title} — Hociatec` : 'Service — Hociatec',
    description:
      service?.description?.trim() ||
      'Découvrez le détail du service, sa durée estimée et sa base tarifaire.',
    canonicalUrl: Number.isFinite(serviceId) ? `${SITE_URL}/services/${serviceId}` : `${SITE_URL}/services`,
  });

  useEffect(() => {
    if (!Number.isFinite(serviceId)) {
      setError('Service introuvable.');
      setLoading(false);
      return;
    }

    setLoading(true);
    setError(null);

    void fetchPublicQuoteService(serviceId)
      .then((item) => setService(item))
      .catch((err: Error) => setError(err.message || 'Impossible de charger ce service.'))
      .finally(() => setLoading(false));
  }, [serviceId]);

  return (
    <SiteLayout headerVariant="light">
      <div className="mx-auto flex w-full max-w-4xl flex-col gap-8 px-6 py-12">
        <Link to="/services" className="inline-flex w-fit items-center text-sm font-semibold text-brand-700 hover:text-brand-900">
          Retour au catalogue
        </Link>

        {loading ? (
          <LoadingState>Chargement du service...</LoadingState>
        ) : error || !service ? (
          <ErrorState>{error || 'Service introuvable.'}</ErrorState>
        ) : (
          <article className="rounded-xl border border-brand-100 bg-white p-8 shadow-sm">
            <span className="inline-flex rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">
              Service Hociatec
            </span>
            <h1 className="mt-4 text-4xl font-semibold tracking-tight text-brand-900">{service.title}</h1>
            <p className="mt-5 text-base leading-7 text-stone-600">
              {service.description?.trim() || 'Les informations détaillées de ce service seront précisées avec votre besoin.'}
            </p>

            <div className="mt-8 grid gap-4 md:grid-cols-3">
              <div className="rounded-2xl border border-brand-100 bg-brand-50 p-5">
                <p className="text-xs uppercase tracking-[0.18em] text-stone-500">Base tarifaire</p>
                <p className="mt-2 text-2xl font-semibold text-brand-900">{formatEuroCents(service.priceCents)}</p>
              </div>
              <div className="rounded-2xl border border-brand-100 bg-brand-50 p-5">
                <p className="text-xs uppercase tracking-[0.18em] text-stone-500">Mode de facturation</p>
                <p className="mt-2 text-2xl font-semibold text-brand-900">{service.unit?.trim() || 'Prix fixe'}</p>
              </div>
              <div className="rounded-2xl border border-brand-100 bg-brand-50 p-5">
                <p className="text-xs uppercase tracking-[0.18em] text-stone-500">Durée estimée</p>
                <p className="mt-2 text-2xl font-semibold text-brand-900">{service.durationLabel || 'Sur étude'}</p>
              </div>
            </div>
          </article>
        )}
      </div>
    </SiteLayout>
  );
};
