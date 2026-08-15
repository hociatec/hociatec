import Foundation

enum SellingType: String, Decodable {
    case sale
    case rental
    case unknown

    init(from decoder: Decoder) throws {
        let container = try decoder.singleValueContainer()
        let raw = (try? container.decode(String.self)) ?? ""
        self = SellingType(rawValue: raw) ?? .unknown
    }
}

struct CategorySummary: Decodable, Equatable, Identifiable {
    let id: Int
    let name: String
    let slug: String
}

struct ProductAttribute: Decodable, Equatable, Identifiable {
    var id: String { code }
    let code: String
    let label: String
    let value: String
}

struct ProductAttributeSummary: Decodable, Equatable, Identifiable {
    var id: String { code }
    let code: String
    let label: String
    let values: [String]
}

struct CatalogFacetCount: Decodable, Equatable, Identifiable {
    var id: String { value + "|" + (extra ?? "") }
    let value: String
    let count: Int
    let extra: String?
}

struct CatalogAttributeFacet: Decodable, Equatable, Identifiable {
    var id: String { code }
    let code: String
    let label: String
    let values: [CatalogFacetCount]
}

struct ProductSearchFacets: Decodable, Equatable {
    let brands: [CatalogFacetCount]
    let categories: [CatalogFacetCount]
    let attributes: [CatalogAttributeFacet]
    let price: CatalogPriceRange

    static let empty = ProductSearchFacets(
        brands: [],
        categories: [],
        attributes: [],
        price: CatalogPriceRange(min: nil, max: nil)
    )
}

struct CatalogPriceRange: Decodable, Equatable {
    let min: Int?
    let max: Int?
}

struct Product: Decodable, Identifiable {
    let id: Int
    let name: String
    let slug: String
    let sku: String
    let shortDescription: String
    let description: String
    let priceCents: Int
    let sellingType: SellingType
    let sellingTypeLabel: String?
    let priceUnitLabel: String?
    let availableForSale: Bool?
    let availableForRental: Bool?
    let availableModes: [SellingType]?
    let salePriceCents: Int?
    let rentalPriceCents: Int?
    let effectivePriceCents: Int
    let brand: String?
    let variantsCount: Int?
    let variantColors: [String]?
    let variantStorages: [String]?
    let variantMemoryRams: [String]?
    let variantAttributes: [ProductAttributeSummary]?
    let attributes: [ProductAttribute]?
    let storageCapacity: String?
    let memoryRam: String?
    let color: String?
    let stock: Int
    let isPublished: Bool
    let isFeaturedHome: Bool
    let imageUrl: String?
    let imageAlt: String?
    let createdAt: Date?
    let updatedAt: Date?
    let category: CategorySummary

    var supportsSale: Bool {
        availableForSale ?? (sellingType == .sale)
    }

    var supportsRental: Bool {
        availableForRental ?? (sellingType == .rental)
    }
}

struct ProductListData: Decodable {
    let items: [Product]
    let meta: PaginationMeta
    let facets: ProductSearchFacets?
}

struct CategoryListData: Decodable {
    let items: [CategorySummary]
}
