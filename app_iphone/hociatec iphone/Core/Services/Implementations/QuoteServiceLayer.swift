import Foundation

struct QuoteServiceLayer: QuoteServing {
    let api: APIClient

    func quoteServices(page: Int?, perPage: Int?, query: String?) async throws -> QuoteServiceList {
        try await api.quoteServices(page: page, perPage: perPage, query: query)
    }
    func createQuote(name: String, email: String, company: String?, address: String?, items: [QuoteRequestItem]) async throws -> QuoteSummary {
        try await api.createQuote(name: name, email: email, company: company, address: address, items: items)
    }
    func myQuotes() async throws -> [QuoteSummary] { try await api.myQuotes() }
    func deleteQuote(id: Int) async throws { try await api.deleteQuote(id: id) }
}
