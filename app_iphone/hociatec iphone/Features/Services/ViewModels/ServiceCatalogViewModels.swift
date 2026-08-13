import Combine
import SwiftUI

@MainActor
final class ServiceDetailViewModel: ObservableObject {
    @Published var service: QuoteService?
    @Published var isLoading = false
    @Published var error: String?

    private let serviceCatalog: ServiceCatalogServing
    private let serviceID: Int

    init(serviceCatalog: ServiceCatalogServing, serviceID: Int) {
        self.serviceCatalog = serviceCatalog
        self.serviceID = serviceID
    }

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            service = try await serviceCatalog.publicService(id: serviceID)
        } catch {
            self.error = error.localizedDescription
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

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await serviceCatalog.quoteServices(page: page, perPage: 7, query: appliedSearch.isEmpty ? nil : appliedSearch)
            services = data.items
            totalPages = max(1, data.meta?.totalPages ?? 1)
        } catch {
            self.error = error.localizedDescription
        }
    }
}
