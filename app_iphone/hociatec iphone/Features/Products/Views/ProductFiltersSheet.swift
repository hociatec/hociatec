import SwiftUI

struct ProductFiltersSheet: View {
    let categories: [CategorySummary]
    let facets: ProductSearchFacets
    @Binding var selectedCategoryID: Int?
    @Binding var selectedSellingType: SellingType?
    @Binding var selectedBrand: String?
    @Binding var selectedAttributeFilters: [String: String]
    let currentCategoryID: Int?
    let currentSellingType: SellingType?
    let currentBrand: String?
    let currentAttributeFilters: [String: String]
    @Binding var didInitDraftFilters: Bool
    let onClose: () -> Void
    let onApply: () -> Void

    private var hasChanges: Bool {
        selectedCategoryID != currentCategoryID
            || selectedSellingType != currentSellingType
            || selectedBrand != currentBrand
            || selectedAttributeFilters != currentAttributeFilters
    }

    var body: some View {
        NavigationStack {
            Form {
                ProductCatalogCategoryFilterSection(
                    categories: categories,
                    selectedCategoryID: $selectedCategoryID
                )
                ProductCatalogSellingTypeFilterSection(selectedSellingType: $selectedSellingType)
                ProductCatalogBrandFilterSection(brands: facets.brands, selectedBrand: $selectedBrand)
                ForEach(facets.attributes) { facet in
                    ProductCatalogAttributeFilterSection(
                        facet: facet,
                        selectedValue: Binding(
                            get: { selectedAttributeFilters[facet.code] },
                            set: { nextValue in
                                if let nextValue, !nextValue.isEmpty {
                                    selectedAttributeFilters[facet.code] = nextValue
                                } else {
                                    selectedAttributeFilters.removeValue(forKey: facet.code)
                                }
                            }
                        )
                    )
                }
            }
            .onAppear {
                guard !didInitDraftFilters else { return }
                selectedCategoryID = currentCategoryID
                selectedSellingType = currentSellingType
                selectedBrand = currentBrand
                selectedAttributeFilters = currentAttributeFilters
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
                        selectedSellingType: $selectedSellingType,
                        selectedBrand: $selectedBrand,
                        selectedAttributeFilters: $selectedAttributeFilters
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
