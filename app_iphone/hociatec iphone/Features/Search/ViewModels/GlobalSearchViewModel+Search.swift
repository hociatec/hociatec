import Foundation

extension GlobalSearchViewModel {
    func submit() async {
        query = draftQuery.trimmingCharacters(in: .whitespacesAndNewlines)
        resetPagination()
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
            productTotalPages = max(1, loadedProducts.meta.totalPages)
            serviceTotal = loadedServices.meta?.total ?? loadedServices.items.count
            serviceTotalPages = max(1, loadedServices.meta?.totalPages ?? 1)
            trainingTotal = loadedTrainings.meta.total
            trainingTotalPages = max(1, loadedTrainings.meta.totalPages)
            newsTotal = loadedNews.meta.total
            newsTotalPages = max(1, loadedNews.meta.totalPages)
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
        productTotalPages = 1
        serviceTotalPages = 1
        trainingTotalPages = 1
        newsTotalPages = 1
        error = nil
    }

    private func resetPagination() {
        productPage = 1
        servicePage = 1
        trainingPage = 1
        newsPage = 1
    }

    private func loadProducts(query: String) async throws -> ProductListData {
        guard shouldLoad(.products) else {
            return ProductListData(
                items: [],
                meta: PaginationMeta(page: 1, perPage: resultsPerPage, total: 0, totalPages: 1),
                facets: nil
            )
        }

        return try await productsService.productList(
            search: query,
            categorySlug: nil,
            sellingType: nil,
            brand: nil,
            attributeFilters: [:],
            minPrice: nil,
            maxPrice: nil,
            inStock: nil,
            sort: nil,
            page: productPage,
            perPage: resultsPerPage
        )
    }

    private func loadServices(query: String) async throws -> QuoteServiceList {
        guard shouldLoad(.services) else {
            return QuoteServiceList(items: [], meta: nil)
        }

        return try await servicesService.quoteServices(page: servicePage, perPage: resultsPerPage, query: query)
    }

    private func loadTrainings(query: String) async throws -> TrainingListData {
        guard shouldLoad(.trainings) else {
            return TrainingListData(
                items: [],
                meta: PaginationMeta(page: 1, perPage: resultsPerPage, total: 0, totalPages: 1)
            )
        }

        return try await trainingService.trainings(page: trainingPage, perPage: resultsPerPage, query: query, category: nil)
    }

    private func loadNews(query: String) async throws -> NewsArticleListData {
        guard shouldLoad(.news) else {
            return NewsArticleListData(
                items: [],
                meta: PaginationMeta(page: 1, perPage: resultsPerPage, total: 0, totalPages: 1)
            )
        }

        return try await newsService.newsArticles(page: newsPage, perPage: resultsPerPage, query: query)
    }

    func goToPreviousPage(for filter: GlobalSearchFilter) async {
        updatePage(for: filter, increment: -1)
        await search()
    }

    func goToNextPage(for filter: GlobalSearchFilter) async {
        updatePage(for: filter, increment: 1)
        await search()
    }

    private func updatePage(for filter: GlobalSearchFilter, increment: Int) {
        switch filter {
        case .all:
            break
        case .products:
            productPage = min(max(1, productPage + increment), productTotalPages)
        case .services:
            servicePage = min(max(1, servicePage + increment), serviceTotalPages)
        case .trainings:
            trainingPage = min(max(1, trainingPage + increment), trainingTotalPages)
        case .news:
            newsPage = min(max(1, newsPage + increment), newsTotalPages)
        }
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
