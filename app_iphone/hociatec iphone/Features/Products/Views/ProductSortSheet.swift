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
                ProductSortOptionRow(title: "Du plus récent au moins récent", isSelected: selectedSort == .releaseYearDesc) {
                    onSelect(.releaseYearDesc)
                }
                ProductSortOptionRow(title: "Du moins récent au plus récent", isSelected: selectedSort == .releaseYearAsc) {
                    onSelect(.releaseYearAsc)
                }
                ProductSortOptionRow(title: "Du moins cher au plus cher", isSelected: selectedSort == .priceAsc) {
                    onSelect(.priceAsc)
                }
                ProductSortOptionRow(title: "Du plus cher au moins cher", isSelected: selectedSort == .priceDesc) {
                    onSelect(.priceDesc)
                }
                ProductSortOptionRow(title: "Stock le plus élevé", isSelected: selectedSort == .stockDesc) {
                    onSelect(.stockDesc)
                }
                ProductSortOptionRow(title: "Stock le moins élevé", isSelected: selectedSort == .stockAsc) {
                    onSelect(.stockAsc)
                }
                ProductSortOptionRow(title: "De A à Z", isSelected: selectedSort == .nameAsc) {
                    onSelect(.nameAsc)
                }
                ProductSortOptionRow(title: "De Z à A", isSelected: selectedSort == .nameDesc) {
                    onSelect(.nameDesc)
                }
                ProductSortOptionRow(title: "Derniers ajoutés", isSelected: selectedSort == .createdDesc) {
                    onSelect(.createdDesc)
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
