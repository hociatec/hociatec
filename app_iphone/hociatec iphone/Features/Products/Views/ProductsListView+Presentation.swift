import Foundation

extension ProductsListView {
    var summaryText: String {
        var parts: [String] = []
        if !viewModel.search.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            parts.append("\(viewModel.totalResults) résultat\(viewModel.totalResults > 1 ? "s" : "")")
        }
        if let category = viewModel.selectedCategory {
            parts.append("Catégorie: \(category.name)")
        }
        if let type = viewModel.selectedSellingType {
            parts.append("Type: " + (type == .rental ? "Location" : "Vente"))
        }
        if let brand = viewModel.selectedBrand, !brand.isEmpty {
            parts.append("Marque: \(brand)")
        }
        for facet in viewModel.availableFacets.attributes {
            if let value = viewModel.selectedAttributeFilters[facet.code], !value.isEmpty {
                parts.append("\(facet.label): \(value)")
            }
        }
        return parts.joined(separator: " • ")
    }

    func updateFiltersBadge() {
        let count = (viewModel.selectedCategory == nil ? 0 : 1)
            + (viewModel.selectedSellingType == nil ? 0 : 1)
            + (viewModel.selectedBrand == nil ? 0 : 1)
            + viewModel.selectedAttributeFilters.count
        filtersBadge = count == 0 ? nil : count
    }
}
