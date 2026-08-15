import Foundation

struct ProductCatalogFilterDraft {
    var selectedCategoryID: Int?
    var selectedSellingType: SellingType?
    var selectedBrand: String?
    var selectedAttributeFilters: [String: String] = [:]
    var minPrice: String = ""
    var maxPrice: String = ""
    var inStockOnly = false
    var didInit = false
}
