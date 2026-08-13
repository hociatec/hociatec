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

    private func loadProducts(query: String) async throws -> [Product] {
        guard shouldLoad(.products) else { return [] }
        return try await productsService.products(search: query, categorySlug: nil, sellingType: nil)
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
}
