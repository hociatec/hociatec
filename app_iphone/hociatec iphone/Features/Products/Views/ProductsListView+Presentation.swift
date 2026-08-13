import Foundation

extension ProductsListView {
    var summaryText: String {
        var parts: [String] = []
        if let category = viewModel.selectedCategory {
            parts.append("Catégorie: \(category.name)")
        }
        if let type = viewModel.selectedSellingType {
            parts.append("Type: " + (type == .rental ? "Location" : "Vente"))
        }
        return parts.joined(separator: " • ")
    }

    func updateFiltersBadge() {
        let count = (viewModel.selectedCategory == nil ? 0 : 1)
            + (viewModel.selectedSellingType == nil ? 0 : 1)
        filtersBadge = count == 0 ? nil : count
    }
}
