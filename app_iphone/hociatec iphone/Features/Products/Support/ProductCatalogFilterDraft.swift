import Foundation

struct ProductCatalogFilterDraft {
    var selectedCategoryID: Int?
    var selectedSellingType: SellingType?
    var selectedBrand: String?
    var selectedAttributeFilters: [String: String] = [:]
    var didInit = false
}
