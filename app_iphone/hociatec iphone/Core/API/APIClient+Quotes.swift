import Foundation

extension APIClient {
    func quoteServices(page: Int? = nil, perPage: Int? = nil, query search: String? = nil) async throws -> QuoteServiceList {
        var query: [URLQueryItem] = []
        if let page { query.append(URLQueryItem(name: "page", value: String(page))) }
        if let perPage { query.append(URLQueryItem(name: "perPage", value: String(perPage))) }
        if let search, !search.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            query.append(URLQueryItem(name: "q", value: search))
        }
        let data: QuoteServiceList = try await request(
            path: "api/public/services",
            query: query.isEmpty ? nil : query
        )
        return data
    }

    func publicService(id: Int) async throws -> QuoteService {
        try await request(path: "api/public/services/\(id)")
    }

    func createQuote(
        name: String,
        email: String,
        company: String?,
        address: String?,
        items: [QuoteRequestItem]
    ) async throws -> QuoteSummary {
        var customer: [String: Any] = [
            "name": name,
            "email": email
        ]
        if let company { customer["company"] = company }
        if let address { customer["address"] = address }

        let body: [String: Any] = [
            "customer": customer,
            "items": items.map { $0.toPayload() }
        ]
        return try await request(
            path: "api/public/quotes",
            method: "POST",
            body: body,
            authorized: false,
            attachCartToken: false
        )
    }

    func myQuotes() async throws -> [QuoteSummary] {
        let data: QuoteListData = try await request(
            path: "api/quotes/me",
            authorized: true,
            attachCartToken: false
        )
        return data.items
    }

    func deleteQuote(id: Int) async throws {
        try await send(
            path: "api/quotes/me/\(id)",
            method: "DELETE",
            authorized: true,
            attachCartToken: false
        )
    }
}
