import Foundation

struct ServiceCatalogService: ServiceCatalogServing {
    let api: APIClient

    func assetURL(for path: String?) -> URL? { api.assetURL(for: path) }
    func quoteServices(page: Int?, perPage: Int?, query: String?) async throws -> QuoteServiceList {
        try await api.quoteServices(page: page, perPage: perPage, query: query)
    }
    func publicService(id: Int) async throws -> QuoteService { try await api.publicService(id: id) }
}
