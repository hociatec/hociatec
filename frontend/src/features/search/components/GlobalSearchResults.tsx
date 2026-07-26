import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';

import type { CatalogProduct } from '@/features/catalog/apiTypes';
import { getCatalogProductDisplayName } from '@/features/catalog/utils/productDisplay';
import type { QuoteServiceDto } from '@/features/quotes/api/quotesApi';
import {
  formatTrainingCategory,
  formatTrainingFormat,
  type TrainingDto,
} from '@/features/trainings/api/trainingsApi';
import { formatEuroCents } from '@/shared/lib/formatters';

const formatDuration = (minutes: number) => {
  const hours = Math.floor(minutes / 60);
  const rest = minutes % 60;

  return hours > 0
    ? `${hours}h${rest ? String(rest).padStart(2, '0') : ''}`
    : `${minutes} min`;
};

interface ResultSectionProps {
  title: string;
  count: number;
  viewAllTo: string;
  children: ReactNode;
}

export const SearchResultSection = ({ title, count, viewAllTo, children }: ResultSectionProps) => (
  <section
    className="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm"
    aria-labelledby={`${title}-title`}
  >
    <div className="flex flex-wrap items-center justify-between gap-3">
      <div>
        <p className="text-sm font-medium text-stone-500">
          {count} résultat{count > 1 ? 's' : ''}
        </p>
        <h2 id={`${title}-title`} className="text-2xl font-semibold text-brand-900">
          {title}
        </h2>
      </div>
      <Link to={viewAllTo} className="text-sm font-semibold text-brand-700 hover:text-brand-900">
        Voir tout
      </Link>
    </div>
    {children}
  </section>
);

interface SearchCardProps {
  to: string;
  label: string;
  title: string;
  description?: string | null;
  price: string;
}

const SearchCard = ({ to, label, title, description, price }: SearchCardProps) => (
  <Link
    to={to}
    className="flex h-full flex-col rounded-xl border border-brand-100 bg-brand-50 p-4 transition hover:border-brand-300 hover:bg-white"
  >
    <span className="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">
      {label}
    </span>
    <strong className="mt-3 text-base text-brand-900">{title}</strong>
    {description ? (
      <span className="mt-2 line-clamp-2 text-sm leading-6 text-stone-600">{description}</span>
    ) : null}
    <span className="mt-auto pt-4 text-sm font-semibold text-brand-900">{price}</span>
  </Link>
);

export const ProductSearchResults = ({ products }: { products: CatalogProduct[] }) => (
  <div className="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    {products.map((product) => (
      <SearchCard
        key={product.id}
        to={`/catalogue/produits/${product.slug}`}
        label={`${product.sellingType === 'rental' ? 'Location' : 'Vente'} · ${product.category.name}`}
        title={getCatalogProductDisplayName(product)}
        description={product.shortDescription}
        price={`${formatEuroCents(product.priceCents)}${product.sellingType === 'rental' ? ' / mois' : ''}`}
      />
    ))}
  </div>
);

export const ServiceSearchResults = ({ services }: { services: QuoteServiceDto[] }) => (
  <div className="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    {services.map((service) => (
      <SearchCard
        key={service.id}
        to={`/services/${service.id}`}
        label={service.durationLabel || 'Sur étude'}
        title={service.title}
        description={service.description}
        price={formatEuroCents(service.priceCents)}
      />
    ))}
  </div>
);

export const TrainingSearchResults = ({ trainings }: { trainings: TrainingDto[] }) => (
  <div className="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    {trainings.map((training) => (
      <SearchCard
        key={training.id}
        to={`/formations/${training.slug}`}
        label={`${formatTrainingCategory(training.category)} · ${formatDuration(training.durationMinutes)}`}
        title={training.title}
        description={training.shortDescription}
        price={`${formatEuroCents(training.priceCents)} · ${training.availableFormats.map(formatTrainingFormat).join(', ')}`}
      />
    ))}
  </div>
);

export const EmptySearchResults = ({ label }: { label: string }) => (
  <p className="mt-5 text-sm text-stone-500">{label}</p>
);
