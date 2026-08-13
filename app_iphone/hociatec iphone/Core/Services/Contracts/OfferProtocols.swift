import Foundation

protocol QuoteServing {
    func quoteServices(page: Int?, perPage: Int?, query: String?) async throws -> QuoteServiceList
    func createQuote(name: String, email: String, company: String?, address: String?, items: [QuoteRequestItem]) async throws -> QuoteSummary
    func myQuotes() async throws -> [QuoteSummary]
    func myQuotePdf(id: Int) async throws -> Data
    func deleteQuote(id: Int) async throws
}

protocol ServiceCatalogServing: AssetServing {
    func quoteServices(page: Int?, perPage: Int?, query: String?) async throws -> QuoteServiceList
    func publicService(id: Int) async throws -> QuoteService
}

protocol TrainingServing {
    func trainingCategories() async throws -> [TrainingCategory]
    func trainings(page: Int, perPage: Int, query: String?, category: String?) async throws -> TrainingListData
    func training(slug: String) async throws -> TrainingDetailData
    func enroll(sessionId: Int, startsAt: Date) async throws -> TrainingEnrollmentCheckoutResult
    func myEnrollments(page: Int, perPage: Int) async throws -> TrainingEnrollmentListData
}
