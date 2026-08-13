import Foundation

struct QuotesUseCases {
    let loadServices: LoadQuoteServicesUseCase
    let searchProducts: SearchQuoteProductsUseCase
    let submitQuote: SubmitQuoteUseCase
    let loadMyQuotes: LoadMyQuotesUseCase
    let deleteQuote: DeleteQuoteUseCase
}

struct LoadQuoteServicesUseCase {
    let repository: QuotesRepository

    func execute(query: String?) async throws -> [QuoteService] {
        try await repository.fetchQuoteServices(query: query)
    }
}

struct SearchQuoteProductsUseCase {
    let repository: QuotesRepository

    func execute(query: String) async throws -> [Product] {
        try await repository.searchProducts(query: query)
    }
}

struct SubmitQuoteUseCase {
    let repository: QuotesRepository

    func execute(name: String, email: String, company: String?, address: String?, items: [QuoteRequestItem]) async throws -> QuoteSummary {
        try await repository.createQuote(name: name, email: email, company: company, address: address, items: items)
    }
}

struct LoadMyQuotesUseCase {
    let repository: QuotesRepository

    func execute() async throws -> [QuoteSummary] {
        try await repository.fetchMyQuotes()
    }
}

struct DeleteQuoteUseCase {
    let repository: QuotesRepository

    func execute(id: Int) async throws {
        try await repository.deleteQuote(id: id)
    }
}
