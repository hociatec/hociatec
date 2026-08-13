import Foundation

enum GlobalSearchFilter: String, CaseIterable, Identifiable {
    case all
    case products
    case services
    case trainings
    case news

    var id: String { rawValue }

    var label: String {
        switch self {
        case .all: return "Tout"
        case .products: return "Produits"
        case .services: return "Services"
        case .trainings: return "Formations"
        case .news: return "Actualités"
        }
    }
}
