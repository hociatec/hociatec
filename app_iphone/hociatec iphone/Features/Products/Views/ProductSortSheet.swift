import SwiftUI

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
