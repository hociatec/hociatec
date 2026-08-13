import Foundation
import Combine

@MainActor
final class HomeViewModel: ObservableObject {
    @Published var featured: [Product] = []
    @Published var services: [QuoteService] = []
    @Published var news: [NewsArticle] = []
    @Published var isLoading = false
    @Published var error: String?

    private let productsService: ProductServing
    private let serviceCatalogService: ServiceCatalogServing
    private let newsService: NewsServing

    init(productsService: ProductServing, serviceCatalogService: ServiceCatalogServing, newsService: NewsServing) {
        self.productsService = productsService
        self.serviceCatalogService = serviceCatalogService
        self.newsService = newsService
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil

        do {
            async let featuredProducts = productsService.featuredProducts()
            async let availableServices = serviceCatalogService.quoteServices(page: nil, perPage: nil, query: nil)
            async let latestArticles = newsService.latestNews(limit: 3)

            featured = try await featuredProducts
            services = selectFeaturedServices(from: try await availableServices.items)
            news = try await latestArticles
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    private func selectFeaturedServices(from services: [QuoteService], limit: Int = 6) -> [QuoteService] {
        let explicit = services.filter { $0.isFeaturedHome }
        if !explicit.isEmpty {
            return Array(explicit.prefix(limit))
        }

        let defaultMatches = [
            "vente de materiel informatique",
            "reparation d'ordinateurs",
            "maintenance informatique",
            "creation de sites web",
            "formation numerique",
            "informatique professionnelle"
        ]

        func normalize(_ value: String?) -> String {
            (value ?? "")
                .folding(options: .diacriticInsensitive, locale: .current)
                .lowercased()
        }

        return services
            .compactMap { service -> (QuoteService, Int)? in
                let title = normalize(service.title)
                guard let rank = defaultMatches.firstIndex(where: { title.contains($0) }) else {
                    return nil
                }
                return (service, rank)
            }
            .sorted { $0.1 < $1.1 }
            .prefix(limit)
            .map(\.0)
    }
}
