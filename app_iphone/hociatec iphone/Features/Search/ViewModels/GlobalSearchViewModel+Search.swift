import Foundation

extension GlobalSearchViewModel {
    func submit() async {
        query = draftQuery.trimmingCharacters(in: .whitespacesAndNewlines)
        await search()
    }

    func search() async {
        guard !isLoading else { return }

        let trimmed = query.trimmingCharacters(in: .whitespacesAndNewlines)
        resetResults()

        guard !trimmed.isEmpty else { return }

        isLoading = true
        defer { isLoading = false }

        do {
            async let productsTask = loadProducts(query: trimmed)
            async let servicesTask = loadServices(query: trimmed)
            async let trainingsTask = loadTrainings(query: trimmed)
            async let newsTask = loadNews(query: trimmed)

            let loadedProducts = try await productsTask
            let loadedServices = try await servicesTask
            let loadedTrainings = try await trainingsTask
            let loadedNews = try await newsTask

            products = sortProducts(loadedProducts.items)
            services = sortServices(loadedServices.items)
            trainings = sortTrainings(loadedTrainings.items)
            news = sortNews(loadedNews.items)
            productTotal = loadedProducts.meta.total
            serviceTotal = loadedServices.meta?.total ?? loadedServices.items.count
            trainingTotal = loadedTrainings.meta.total
            newsTotal = loadedNews.meta.total
        } catch {
            self.error = error.localizedDescription
        }
    }

    private func resetResults() {
        products = []
        services = []
        trainings = []
        news = []
        productTotal = 0
        serviceTotal = 0
        trainingTotal = 0
        newsTotal = 0
        error = nil
    }

    private func loadProducts(query: String) async throws -> ProductListData {
        guard shouldLoad(.products) else {
            return ProductListData(
                items: [],
                meta: PaginationMeta(page: 1, perPage: 6, total: 0, totalPages: 1),
                facets: nil
            )
        }

        return try await productsService.productList(
            search: query,
            categorySlug: nil,
            sellingType: nil,
            brand: nil,
            attributeFilters: [:],
            page: 1,
            perPage: 6
        )
    }

    private func loadServices(query: String) async throws -> QuoteServiceList {
        guard shouldLoad(.services) else {
            return QuoteServiceList(items: [], meta: nil)
        }

        return try await servicesService.quoteServices(page: 1, perPage: 6, query: query)
    }

    private func loadTrainings(query: String) async throws -> TrainingListData {
        guard shouldLoad(.trainings) else {
            return TrainingListData(
                items: [],
                meta: PaginationMeta(page: 1, perPage: 6, total: 0, totalPages: 1)
            )
        }

        return try await trainingService.trainings(page: 1, perPage: 6, query: query, category: nil)
    }

    private func loadNews(query: String) async throws -> NewsArticleListData {
        guard shouldLoad(.news) else {
            return NewsArticleListData(
                items: [],
                meta: PaginationMeta(page: 1, perPage: 6, total: 0, totalPages: 1)
            )
        }

        return try await newsService.newsArticles(page: 1, perPage: 6, query: query)
    }

    func applyCurrentSort() {
        products = sortProducts(products)
        services = sortServices(services)
        trainings = sortTrainings(trainings)
        news = sortNews(news)
    }

    private func sortProducts(_ items: [Product]) -> [Product] {
        switch selectedSort {
        case .relevance:
            return items
        case .alphabeticalAsc:
            return items.sorted { $0.name.localizedCaseInsensitiveCompare($1.name) == .orderedAscending }
        case .alphabeticalDesc:
            return items.sorted { $0.name.localizedCaseInsensitiveCompare($1.name) == .orderedDescending }
        }
    }

    private func sortServices(_ items: [QuoteService]) -> [QuoteService] {
        switch selectedSort {
        case .relevance:
            return items
        case .alphabeticalAsc:
            return items.sorted { $0.title.localizedCaseInsensitiveCompare($1.title) == .orderedAscending }
        case .alphabeticalDesc:
            return items.sorted { $0.title.localizedCaseInsensitiveCompare($1.title) == .orderedDescending }
        }
    }

    private func sortTrainings(_ items: [Training]) -> [Training] {
        switch selectedSort {
        case .relevance:
            return items
        case .alphabeticalAsc:
            return items.sorted { $0.title.localizedCaseInsensitiveCompare($1.title) == .orderedAscending }
        case .alphabeticalDesc:
            return items.sorted { $0.title.localizedCaseInsensitiveCompare($1.title) == .orderedDescending }
        }
    }

    private func sortNews(_ items: [NewsArticle]) -> [NewsArticle] {
        switch selectedSort {
        case .relevance:
            return items
        case .alphabeticalAsc:
            return items.sorted { $0.title.localizedCaseInsensitiveCompare($1.title) == .orderedAscending }
        case .alphabeticalDesc:
            return items.sorted { $0.title.localizedCaseInsensitiveCompare($1.title) == .orderedDescending }
        }
    }
}
