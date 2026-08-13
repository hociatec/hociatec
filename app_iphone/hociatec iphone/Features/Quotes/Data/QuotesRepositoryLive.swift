import Foundation

struct QuotesRepositoryLive: QuotesRepository {
    let quoteService: QuoteServing
    let productService: ProductServing

    func fetchQuoteServices(query: String?) async throws -> [QuoteService] {
        try await quoteService.quoteServices(page: nil, perPage: nil, query: query).items
    }

    func searchProducts(query: String) async throws -> [Product] {
        try await productService.products(search: query, categorySlug: nil, sellingType: nil)
    }

    func createQuote(name: String, email: String, company: String?, address: String?, items: [QuoteRequestItem]) async throws -> QuoteSummary {
        try await quoteService.createQuote(name: name, email: email, company: company, address: address, items: items)
    }

    func fetchMyQuotes() async throws -> [QuoteSummary] {
        try await quoteService.myQuotes()
    }

    func downloadQuotePdf(id: Int) async throws -> Data {
        try await quoteService.myQuotePdf(id: id)
    }

    func deleteQuote(id: Int) async throws {
        try await quoteService.deleteQuote(id: id)
    }
}
