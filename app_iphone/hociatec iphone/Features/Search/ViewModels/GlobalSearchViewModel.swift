import Foundation
import Combine

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

@MainActor
final class GlobalSearchViewModel: ObservableObject {
    @Published var query = ""
    @Published var draftQuery = ""
    @Published var selectedFilter: GlobalSearchFilter = .all
    @Published var products: [Product] = []
    @Published var services: [QuoteService] = []
    @Published var trainings: [Training] = []
    @Published var news: [NewsArticle] = []
    @Published var productTotal = 0
    @Published var serviceTotal = 0
    @Published var trainingTotal = 0
    @Published var newsTotal = 0
    @Published var isLoading = false
    @Published var error: String?

    private let productsService: ProductServing
    private let servicesService: ServiceCatalogServing
    private let trainingService: TrainingServing
    private let newsService: NewsServing

    init(
        productsService: ProductServing,
        servicesService: ServiceCatalogServing,
        trainingService: TrainingServing,
        newsService: NewsServing
    ) {
        self.productsService = productsService
        self.servicesService = servicesService
        self.trainingService = trainingService
        self.newsService = newsService
    }

    func submit() async {
        query = draftQuery.trimmingCharacters(in: .whitespacesAndNewlines)
        await search()
    }

    func search() async {
        guard !isLoading else { return }
        let trimmed = query.trimmingCharacters(in: .whitespacesAndNewlines)

        products = []
        services = []
        trainings = []
        news = []
        productTotal = 0
        serviceTotal = 0
        trainingTotal = 0
        newsTotal = 0
        error = nil

        guard !trimmed.isEmpty else { return }

        isLoading = true
        defer { isLoading = false }

        do {
            async let productsTask = shouldLoad(.products) ? productsService.products(search: trimmed, categorySlug: nil, sellingType: nil) : []
            async let servicesTask = shouldLoad(.services) ? servicesService.quoteServices(page: 1, perPage: 6, query: trimmed) : QuoteServiceList(items: [], meta: nil)
            async let trainingsTask = shouldLoad(.trainings) ? trainingService.trainings(page: 1, perPage: 6, query: trimmed, category: nil) : TrainingListData(items: [], meta: PaginationMeta(page: 1, perPage: 6, total: 0, totalPages: 1))
            async let newsTask = shouldLoad(.news) ? newsService.newsArticles(page: 1, perPage: 6, query: trimmed) : NewsArticleListData(items: [], meta: PaginationMeta(page: 1, perPage: 6, total: 0, totalPages: 1))

            let loadedProducts = try await productsTask
            let loadedServices = try await servicesTask
            let loadedTrainings = try await trainingsTask
            let loadedNews = try await newsTask

            products = Array(loadedProducts.prefix(6))
            services = loadedServices.items
            trainings = loadedTrainings.items
            news = loadedNews.items
            productTotal = loadedProducts.count
            serviceTotal = loadedServices.meta?.total ?? loadedServices.items.count
            trainingTotal = loadedTrainings.meta.total
            newsTotal = loadedNews.meta.total
        } catch {
            self.error = error.localizedDescription
        }
    }

    var totalResults: Int {
        visibleTotal(for: .products, count: productTotal)
            + visibleTotal(for: .services, count: serviceTotal)
            + visibleTotal(for: .trainings, count: trainingTotal)
            + visibleTotal(for: .news, count: newsTotal)
    }

    func sectionTotal(for filter: GlobalSearchFilter) -> Int {
        switch filter {
        case .all:
            return totalResults
        case .products:
            return productTotal
        case .services:
            return serviceTotal
        case .trainings:
            return trainingTotal
        case .news:
            return newsTotal
        }
    }

    private func shouldLoad(_ filter: GlobalSearchFilter) -> Bool {
        selectedFilter == .all || selectedFilter == filter
    }

    private func visibleTotal(for filter: GlobalSearchFilter, count: Int) -> Int {
        shouldLoad(filter) ? count : 0
    }
}
