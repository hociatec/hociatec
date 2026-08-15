import Combine
import SwiftUI

@MainActor
final class ServiceDetailViewModel: ObservableObject {
    @Published var service: QuoteService?
    @Published var isLoading = false
    @Published var error: String?

    private let serviceCatalog: ServiceCatalogServing
    private let serviceID: Int
    private var loadRequestID = 0
    private var hasLoadedOnce = false

    init(serviceCatalog: ServiceCatalogServing, serviceID: Int) {
        self.serviceCatalog = serviceCatalog
        self.serviceID = serviceID
    }

    func load(force: Bool = false) async {
        if (isLoading || hasLoadedOnce) && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil

        do {
            let loadedService = try await serviceCatalog.publicService(id: serviceID)
            guard requestID == loadRequestID else { return }
            service = loadedService
            hasLoadedOnce = true
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
        }

        if requestID == loadRequestID {
            isLoading = false
        }
    }
}

@MainActor
final class ServicesCatalogViewModel: ObservableObject {
    @Published var services: [QuoteService] = []
    @Published var page = 1
    @Published var totalPages = 1
    @Published var searchText = ""
    @Published var appliedSearch = ""
    @Published var isLoading = false
    @Published var error: String?

    private let serviceCatalog: ServiceCatalogServing
    private var loadRequestID = 0
    private var hasLoadedOnce = false

    init(serviceCatalog: ServiceCatalogServing) {
        self.serviceCatalog = serviceCatalog
    }

    func applySearch() {
        appliedSearch = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        page = 1
    }

    func previousPage() {
        guard page > 1 else { return }
        page -= 1
    }

    func nextPage() {
        guard page < totalPages else { return }
        page += 1
    }

    func load(force: Bool = false) async {
        if (isLoading || hasLoadedOnce) && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil
        let requestedPage = page
        let requestedSearch = appliedSearch.isEmpty ? nil : appliedSearch

        do {
            let data = try await serviceCatalog.quoteServices(page: requestedPage, perPage: 7, query: requestedSearch)
            guard requestID == loadRequestID else { return }
            services = data.items
            totalPages = max(1, data.meta?.totalPages ?? 1)
            hasLoadedOnce = true
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
        }

        if requestID == loadRequestID {
            isLoading = false
        }
    }
}
