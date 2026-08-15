import SwiftUI

struct ProductsListToolbarSection: View {
    @ObservedObject var viewModel: ProductsViewModel
    let summaryText: String
    let onOpenFilters: () -> Void
    let onOpenSort: () -> Void
    let onClearCategory: () -> Void
    let onClearBrand: () -> Void
    let onClearAttributeFilter: (String) -> Void
    let onClearPriceRange: () -> Void
    let onClearInStock: () -> Void

    var body: some View {
        Section {
            ProductCatalogToolbar(
                selectedCategory: viewModel.selectedCategory,
                selectedBrand: viewModel.selectedBrand,
                selectedAttributeFilters: viewModel.selectedAttributeFilters,
                minPrice: viewModel.minPrice,
                maxPrice: viewModel.maxPrice,
                inStockOnly: viewModel.inStockOnly,
                availableAttributeFacets: viewModel.availableFacets.attributes,
                sort: viewModel.sort,
                summaryText: summaryText,
                onOpenFilters: onOpenFilters,
                onOpenSort: onOpenSort,
                onClearCategory: onClearCategory,
                onClearBrand: onClearBrand,
                onClearAttributeFilter: onClearAttributeFilter,
                onClearPriceRange: onClearPriceRange,
                onClearInStock: onClearInStock
            )
        }
    }
}
