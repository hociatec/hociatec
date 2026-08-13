import SwiftUI

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
