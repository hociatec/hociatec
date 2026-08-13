import Foundation

protocol QuotesRepository {
    func fetchQuoteServices(query: String?) async throws -> [QuoteService]
    func searchProducts(query: String) async throws -> [Product]
    func createQuote(name: String, email: String, company: String?, address: String?, items: [QuoteRequestItem]) async throws -> QuoteSummary
    func fetchMyQuotes() async throws -> [QuoteSummary]
    func deleteQuote(id: Int) async throws
}
