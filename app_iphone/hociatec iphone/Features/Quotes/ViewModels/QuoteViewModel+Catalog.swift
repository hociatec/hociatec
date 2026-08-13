import Foundation

extension QuoteViewModel {
    func loadServices() async {
        if isLoadingServices { return }
        isLoadingServices = true
        error = nil
        do {
            services = try await loadServicesUseCase.execute(query: nil)
        } catch let err {
            error = err.localizedDescription
        }
        isLoadingServices = false
    }

    func searchProducts() async {
        let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !query.isEmpty else {
            productResults = []
            return
        }
        isSearching = true
        defer { isSearching = false }
        do {
            productResults = try await searchProductsUseCase.execute(query: query)
        } catch let err {
            error = err.localizedDescription
        }
    }

    func filteredServices(matching query: String) -> [QuoteService] {
        let trimmedQuery = query.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmedQuery.isEmpty else { return services }

        let normalizedQuery = normalized(trimmedQuery)
        return services.filter { service in
            normalized(service.title).contains(normalizedQuery)
                || normalized(service.description ?? "").contains(normalizedQuery)
        }
    }

    func normalized(_ value: String) -> String {
        value
            .folding(options: .diacriticInsensitive, locale: .current)
            .lowercased()
    }
}
