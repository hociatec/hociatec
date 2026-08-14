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
    let effectivePriceCents: Int
    let brand: String?
    let variantsCount: Int?
    let variantColors: [String]?
    let variantStorages: [String]?
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
}

struct ProductListData: Decodable {
    let items: [Product]
    let meta: PaginationMeta
}

struct CategoryListData: Decodable {
    let items: [CategorySummary]
}
