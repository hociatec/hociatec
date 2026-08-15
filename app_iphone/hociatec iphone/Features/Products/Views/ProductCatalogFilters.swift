import SwiftUI

struct ProductCatalogToolbar: View {
    let selectedCategory: CategorySummary?
    let selectedBrand: String?
    let selectedAttributeFilters: [String: String]
    let minPrice: Double?
    let maxPrice: Double?
    let inStockOnly: Bool
    let availableAttributeFacets: [CatalogAttributeFacet]
    let sort: ProductSortOption
    let summaryText: String
    let onOpenFilters: () -> Void
    let onOpenSort: () -> Void
    let onClearCategory: () -> Void
    let onClearBrand: () -> Void
    let onClearAttributeFilter: (String) -> Void
    let onClearPriceRange: () -> Void
    let onClearInStock: () -> Void

    private var filtersCount: Int {
        (selectedCategory == nil ? 0 : 1)
            + (selectedBrand == nil ? 0 : 1)
            + selectedAttributeFilters.count
            + ((minPrice == nil && maxPrice == nil) ? 0 : 1)
            + (inStockOnly ? 1 : 0)
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack {
                Button(action: onOpenFilters) {
                    Label(filtersCount > 0 ? "Filtres (\(filtersCount))" : "Filtres", systemImage: "line.3.horizontal.decrease.circle")
                }
                Spacer()
                Button(action: onOpenSort) {
                    Label("Trier (\(ProductCatalogFilterPresentation.sortLabel(for: sort)))", systemImage: "arrow.up.arrow.down")
                }
            }

            if selectedCategory != nil || selectedBrand != nil || !selectedAttributeFilters.isEmpty || minPrice != nil || maxPrice != nil || inStockOnly {
                Text(summaryText)
                    .font(.footnote)
                    .foregroundStyle(.secondary)

                ProductCatalogActiveFiltersRow(
                    selectedCategory: selectedCategory,
                    selectedBrand: selectedBrand,
                    selectedAttributeFilters: selectedAttributeFilters,
                    minPrice: minPrice,
                    maxPrice: maxPrice,
                    inStockOnly: inStockOnly,
                    availableAttributeFacets: availableAttributeFacets,
                    onClearCategory: onClearCategory,
                    onClearBrand: onClearBrand,
                    onClearAttributeFilter: onClearAttributeFilter,
                    onClearPriceRange: onClearPriceRange,
                    onClearInStock: onClearInStock
                )
            }
        }
    }
}
