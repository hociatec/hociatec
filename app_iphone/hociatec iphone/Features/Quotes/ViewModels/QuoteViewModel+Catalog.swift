import Foundation

extension QuoteViewModel {
    func loadServices() async {
        if isLoadingServices { return }
        servicesRequestID += 1
        let requestID = servicesRequestID
        isLoadingServices = true
        error = nil
        do {
            let loadedServices = try await loadServicesUseCase.execute(query: nil)
            guard requestID == servicesRequestID else { return }
            services = loadedServices
        } catch let err {
            guard requestID == servicesRequestID else { return }
            error = err.localizedDescription
        }
        if requestID == servicesRequestID {
            isLoadingServices = false
        }
    }

    func searchProducts() async {
        let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !query.isEmpty else {
            productResults = []
            return
        }
        productSearchRequestID += 1
        let requestID = productSearchRequestID
        isSearching = true
        do {
            let results = try await searchProductsUseCase.execute(query: query)
            guard requestID == productSearchRequestID else { return }
            productResults = results
        } catch let err {
            guard requestID == productSearchRequestID else { return }
            error = err.localizedDescription
        }
        if requestID == productSearchRequestID {
            isSearching = false
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
