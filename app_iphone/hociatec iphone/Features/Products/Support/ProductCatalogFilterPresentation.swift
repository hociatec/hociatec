import Foundation

enum ProductCatalogFilterPresentation {
    static func sortLabel(for sort: ProductSortOption) -> String {
        switch sort {
        case .relevance: return "Pertinence"
        case .priceAsc: return "Du moins cher au plus cher"
        case .priceDesc: return "Du plus cher au moins cher"
        case .releaseYearDesc: return "Du plus récent au moins récent"
        case .releaseYearAsc: return "Du moins récent au plus récent"
        case .stockDesc: return "Stock le plus élevé"
        case .stockAsc: return "Stock le moins élevé"
        case .nameAsc: return "De A à Z"
        case .nameDesc: return "De Z à A"
        case .createdDesc: return "Derniers ajoutés"
        }
    }

    static func sellingTypeLabel(for sellingType: SellingType) -> String {
        sellingType == .rental ? "Location" : "Vente"
    }

    static func priceFieldValue(_ value: Double?) -> String {
        guard let value else { return "" }
        let integerValue = Int(value)
        if Double(integerValue) == value {
            return String(integerValue)
        }

        return String(value)
    }

    static func availablePriceLabel(for range: CatalogPriceRange) -> String {
        let minLabel = range.min.map(PriceFormatter.format(cents:)) ?? "-"
        let maxLabel = range.max.map(PriceFormatter.format(cents:)) ?? "-"
        return "\(minLabel) à \(maxLabel)"
    }
}
