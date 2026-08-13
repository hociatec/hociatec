import SwiftUI

struct ProductsListToolbarSection: View {
    @ObservedObject var viewModel: ProductsViewModel
    let summaryText: String
    let onOpenFilters: () -> Void
    let onOpenSort: () -> Void
    let onClearCategory: () -> Void
    let onClearSellingType: () -> Void

    var body: some View {
        Section {
            ProductCatalogToolbar(
                selectedCategory: viewModel.selectedCategory,
                selectedSellingType: viewModel.selectedSellingType,
                sort: viewModel.sort,
                summaryText: summaryText,
                onOpenFilters: onOpenFilters,
                onOpenSort: onOpenSort,
                onClearCategory: onClearCategory,
                onClearSellingType: onClearSellingType
            )
        }
    }
}
