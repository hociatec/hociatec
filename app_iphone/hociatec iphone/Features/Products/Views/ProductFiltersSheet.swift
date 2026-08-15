import SwiftUI

struct ProductFiltersSheet: View {
    let categories: [CategorySummary]
    let facets: ProductSearchFacets
    @Binding var selectedCategoryID: Int?
    @Binding var selectedBrand: String?
    @Binding var selectedAttributeFilters: [String: String]
    @Binding var minPrice: String
    @Binding var maxPrice: String
    @Binding var inStockOnly: Bool
    let currentCategoryID: Int?
    let currentBrand: String?
    let currentAttributeFilters: [String: String]
    let currentMinPrice: Double?
    let currentMaxPrice: Double?
    let currentInStockOnly: Bool
    @Binding var didInitDraftFilters: Bool
    let onClose: () -> Void
    let onApply: () -> Void

    private var hasChanges: Bool {
        selectedCategoryID != currentCategoryID
            || selectedBrand != currentBrand
            || selectedAttributeFilters != currentAttributeFilters
            || minPrice != ProductCatalogFilterPresentation.priceFieldValue(currentMinPrice)
            || maxPrice != ProductCatalogFilterPresentation.priceFieldValue(currentMaxPrice)
            || inStockOnly != currentInStockOnly
    }

    var body: some View {
        NavigationStack {
            Form {
                ProductCatalogCategoryFilterSection(
                    categories: categories,
                    categoryFacets: facets.categories,
                    selectedCategoryID: $selectedCategoryID
                )
                ProductCatalogBrandFilterSection(brands: facets.brands, selectedBrand: $selectedBrand)
                ProductCatalogPriceFilterSection(
                    minPrice: $minPrice,
                    maxPrice: $maxPrice,
                    availableRange: facets.price
                )
                ProductCatalogStockFilterSection(inStockOnly: $inStockOnly)
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
                selectedBrand = currentBrand
                selectedAttributeFilters = currentAttributeFilters
                minPrice = ProductCatalogFilterPresentation.priceFieldValue(currentMinPrice)
                maxPrice = ProductCatalogFilterPresentation.priceFieldValue(currentMaxPrice)
                inStockOnly = currentInStockOnly
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
                        selectedBrand: $selectedBrand,
                        selectedAttributeFilters: $selectedAttributeFilters,
                        minPrice: $minPrice,
                        maxPrice: $maxPrice,
                        inStockOnly: $inStockOnly
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
