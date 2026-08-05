import type { FormEvent } from 'react';
import { Search } from 'lucide-react';

interface GlobalSearchHeaderProps {
  query: string;
  draftQuery: string;
  filter: string;
  resultsTotal: number;
  onDraftQueryChange: (value: string) => void;
  onFilterChange: (value: string) => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
}

export const GlobalSearchHeader = ({
  query,
  draftQuery,
  filter,
  resultsTotal,
  onDraftQueryChange,
  onFilterChange,
  onSubmit,
}: GlobalSearchHeaderProps) => (
  <header className="public-directory-page__hero rounded-2xl border border-brand-100 bg-white p-8 shadow-sm">
    <h1 className="text-4xl font-semibold tracking-tight text-brand-900">
      Trouver un produit, un service ou une formation
    </h1>
    <p className="mt-4 max-w-3xl text-base leading-7 text-stone-600">
      Lancez une recherche globale, puis ouvrez la fiche qui correspond à votre besoin.
    </p>

    <form onSubmit={onSubmit} className="mt-6 flex flex-col gap-3 sm:flex-row" role="search">
      <label htmlFor="global-search" className="sr-only">
        Rechercher sur tout le site
      </label>
      <div className="relative flex-1">
        <Search
          className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-stone-400"
          aria-hidden="true"
        />
        <input
          id="global-search"
          type="search"
          value={draftQuery}
          onChange={(event) => onDraftQueryChange(event.target.value)}
          placeholder="Exemple : ordinateur, audit, sécurité..."
          className="w-full rounded-full border border-brand-200 py-3 pl-12 pr-4 text-base text-brand-900 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
        />
      </div>
      <button
        type="submit"
        className="rounded-full bg-brand-900 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-800"
      >
        Rechercher
      </button>
    </form>
    <div className="mt-4 flex flex-wrap gap-2" aria-label="Filtrer les résultats">
      {([
        ['all', 'Tout'],
        ['products', 'Produits'],
        ['services', 'Services'],
        ['trainings', 'Formations'],
        ['news', 'Actualités'],
      ] satisfies Array<[string, string]>).map(([value, label]) => (
        <button
          key={value}
          type="button"
          onClick={() => onFilterChange(value)}
          className={`rounded-full border px-4 py-2 text-sm font-semibold ${
            filter === value
              ? 'border-brand-900 bg-brand-900 text-white'
              : 'border-brand-200 bg-white text-brand-800 hover:border-brand-500'
          }`}
        >
          {label}
        </button>
      ))}
    </div>

    <p className="mt-4 text-sm text-stone-500" aria-live="polite">
      {query
        ? `${resultsTotal} résultat${resultsTotal > 1 ? 's' : ''} pour "${query}"`
        : 'Saisissez un mot-clé pour cibler les résultats.'}
    </p>
  </header>
);
