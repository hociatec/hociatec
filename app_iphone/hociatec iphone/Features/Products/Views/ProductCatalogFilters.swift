import SwiftUI

struct ProductCatalogToolbar: View {
    let selectedCategory: CategorySummary?
    let selectedSellingType: SellingType?
    let sort: ProductSortOption
    let summaryText: String
    let onOpenFilters: () -> Void
    let onOpenSort: () -> Void
    let onClearCategory: () -> Void
    let onClearSellingType: () -> Void

    private var filtersCount: Int {
        (selectedCategory == nil ? 0 : 1) + (selectedSellingType == nil ? 0 : 1)
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

            if selectedCategory != nil || selectedSellingType != nil {
                Text(summaryText)
                    .font(.footnote)
                    .foregroundStyle(.secondary)

                ProductCatalogActiveFiltersRow(
                    selectedCategory: selectedCategory,
                    selectedSellingType: selectedSellingType,
                    onClearCategory: onClearCategory,
                    onClearSellingType: onClearSellingType
                )
            }
        }
    }
}
