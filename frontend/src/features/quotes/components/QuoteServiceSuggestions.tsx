import { formatEuroCents } from '@/shared/lib/formatters';
import type { QuoteDraft } from '@/features/quotes/hooks/useCreateQuote';
import type { QuoteServiceDto } from '@/features/quotes/types/quoteTypes';

type QuoteServiceSuggestionsProps = {
  form: QuoteDraft;
  filteredServices: QuoteServiceDto[];
  searchQuery: string;
  addServiceLine: (serviceId: number) => void;
  toggleServiceLine: (serviceId: number) => void;
};

export const QuoteServiceSuggestions = ({
  form,
  filteredServices,
  searchQuery,
  addServiceLine,
  toggleServiceLine,
}: QuoteServiceSuggestionsProps) => {
  if (searchQuery.trim().length < 2 || filteredServices.length === 0) {
    return null;
  }

  return (
    <div>
      <h2 className="text-lg font-semibold mb-2">
        Services suggérés ({filteredServices.length})
      </h2>
      <div className="space-y-2 max-h-64 overflow-auto">
        {filteredServices.map((service) => {
          const selected = (form.items ?? []).some(
            (item) => item.type === 'service' && item.serviceId === service.id,
          );

          return (
            <div key={service.id} className="rounded border border-brand-100 p-2">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-sm font-semibold">{service.title}</div>
                  <div className="text-xs text-stone-500">
                    {formatEuroCents(service.priceCents ?? 0)}
                  </div>
                </div>
                <button
                  type="button"
                  className={
                    selected
                      ? 'catalog-admin-actions__delete'
                      : 'register-form__submit quote-builder__small-button'
                  }
                  onClick={() =>
                    selected ? toggleServiceLine(service.id) : addServiceLine(service.id)
                  }
                >
                  {selected ? 'Retirer' : 'Ajouter'}
                </button>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};
