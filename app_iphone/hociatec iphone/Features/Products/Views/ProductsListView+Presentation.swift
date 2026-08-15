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
        if let brand = viewModel.selectedBrand, !brand.isEmpty {
            parts.append("Marque: \(brand)")
        }
        if viewModel.minPrice != nil || viewModel.maxPrice != nil {
            parts.append("Prix: \(priceRangeLabel(min: viewModel.minPrice, max: viewModel.maxPrice))")
        }
        if viewModel.inStockOnly {
            parts.append("Stock: disponible")
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
            + (viewModel.selectedBrand == nil ? 0 : 1)
            + viewModel.selectedAttributeFilters.count
            + ((viewModel.minPrice == nil && viewModel.maxPrice == nil) ? 0 : 1)
            + (viewModel.inStockOnly ? 1 : 0)
        filtersBadge = count == 0 ? nil : count
    }
}

func parseCatalogPriceInput(_ value: String) -> Double? {
    let trimmed = value.trimmingCharacters(in: .whitespacesAndNewlines)
    guard !trimmed.isEmpty else { return nil }
    let normalized = trimmed.replacingOccurrences(of: ",", with: ".")
    guard let parsed = Double(normalized), parsed >= 0 else { return nil }
    return parsed
}

func priceRangeLabel(min: Double?, max: Double?) -> String {
    let minLabel = min.map { String(format: "%.2f €", $0) } ?? "-"
    let maxLabel = max.map { String(format: "%.2f €", $0) } ?? "-"
    return "\(minLabel) à \(maxLabel)"
}
