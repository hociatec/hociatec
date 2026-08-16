import Foundation
import Combine

@MainActor
final class GlobalSearchViewModel: ObservableObject {
    let resultsPerPage = 6

    @Published var query = ""
    @Published var draftQuery = ""
    @Published var selectedFilter: GlobalSearchFilter = .all
    @Published var selectedSort: GlobalSearchSortOption = .relevance
    @Published var products: [Product] = []
    @Published var services: [QuoteService] = []
    @Published var trainings: [Training] = []
    @Published var news: [NewsArticle] = []
    @Published var productTotal = 0
    @Published var serviceTotal = 0
    @Published var trainingTotal = 0
    @Published var newsTotal = 0
    @Published var productPage = 1
    @Published var servicePage = 1
    @Published var trainingPage = 1
    @Published var newsPage = 1
    @Published var productTotalPages = 1
    @Published var serviceTotalPages = 1
    @Published var trainingTotalPages = 1
    @Published var newsTotalPages = 1
    @Published var isLoading = false
    @Published var error: String?

    let productsService: ProductServing
    let servicesService: ServiceCatalogServing
    let trainingService: TrainingServing
    let newsService: NewsServing

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
}
