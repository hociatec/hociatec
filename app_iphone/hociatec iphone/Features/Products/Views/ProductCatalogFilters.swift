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

struct ProductSortSheet: View {
    let selectedSort: ProductSortOption
    let onSelect: (ProductSortOption) -> Void
    let onClose: () -> Void

    var body: some View {
        NavigationStack {
            List {
                ProductSortOptionRow(title: "Pertinence", isSelected: selectedSort == .relevance) {
                    onSelect(.relevance)
                }
                ProductSortOptionRow(title: "Prix croissant", isSelected: selectedSort == .priceLowHigh) {
                    onSelect(.priceLowHigh)
                }
                ProductSortOptionRow(title: "Prix décroissant", isSelected: selectedSort == .priceHighLow) {
                    onSelect(.priceHighLow)
                }
                ProductSortOptionRow(title: "Nouveautés", isSelected: selectedSort == .newest) {
                    onSelect(.newest)
                }
            }
            .navigationTitle("Trier")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Fermer", action: onClose)
                }
            }
        }
    }
}

struct ProductFiltersSheet: View {
    let categories: [CategorySummary]
    @Binding var selectedCategoryID: Int?
    @Binding var selectedSellingType: SellingType?
    let currentCategoryID: Int?
    let currentSellingType: SellingType?
    @Binding var didInitDraftFilters: Bool
    let onClose: () -> Void
    let onApply: () -> Void

    private var hasChanges: Bool {
        selectedCategoryID != currentCategoryID || selectedSellingType != currentSellingType
    }

    var body: some View {
        NavigationStack {
            Form {
                ProductCatalogCategoryFilterSection(
                    categories: categories,
                    selectedCategoryID: $selectedCategoryID
                )
                ProductCatalogSellingTypeFilterSection(selectedSellingType: $selectedSellingType)
            }
            .onAppear {
                guard !didInitDraftFilters else { return }
                selectedCategoryID = currentCategoryID
                selectedSellingType = currentSellingType
                didInitDraftFilters = true
            }
            .onDisappear {
                didInitDraftFilters = false
            }
            .navigationTitle("Filtres")
            .interactiveDismissDisabled(true)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Annuler", action: onClose)
                }
                ToolbarItem(placement: .bottomBar) {
                    ProductCatalogResetFiltersButton(
                        selectedCategoryID: $selectedCategoryID,
                        selectedSellingType: $selectedSellingType
                    )
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Appliquer", action: onApply)
                        .disabled(!hasChanges)
                }
            }
        }
    }
}
