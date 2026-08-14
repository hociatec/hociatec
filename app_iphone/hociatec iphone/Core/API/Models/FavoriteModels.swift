import Foundation

enum FavoriteCategory: String, Decodable, CaseIterable, Identifiable {
    case product
    case service
    case news
    case podcast

    var id: String { rawValue }

    var label: String {
        switch self {
        case .product: return "Produits"
        case .service: return "Services"
        case .news: return "Actualités"
        case .podcast: return "Podcasts"
        }
    }
}

struct FavoriteEntry: Decodable, Identifiable {
    let category: FavoriteCategory
    let targetId: Int
    let addedAt: Date
    let product: Product?
    let service: QuoteService?
    let article: NewsArticle?

    var id: String { "\(category.rawValue):\(targetId)" }
}

struct AddFavoriteResponse: Decodable {
    let favorite: FavoriteEntry?
    let alreadyFavorite: Bool
}

struct RemoveFavoriteResponse: Decodable {
    let removed: Bool
}

struct FavoriteStatusResponse: Decodable {
    let category: FavoriteCategory
    let targetId: Int
    let isFavorite: Bool
}
