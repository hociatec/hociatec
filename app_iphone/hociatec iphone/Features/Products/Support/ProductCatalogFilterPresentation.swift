import Foundation

enum ProductCatalogFilterPresentation {
    static func sortLabel(for sort: ProductSortOption) -> String {
        switch sort {
        case .relevance: return "Pertinence"
        case .priceLowHigh: return "Prix croissant"
        case .priceHighLow: return "Prix décroissant"
        case .newest: return "Nouveautés"
        }
    }

    static func sellingTypeLabel(for sellingType: SellingType) -> String {
        sellingType == .rental ? "Location" : "Vente"
    }
}
