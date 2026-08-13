import Foundation

extension APIClient {
    func myVouchers(page: Int = 1, perPage: Int = 10) async throws -> VoucherListData {
        try await request(
            path: "api/vouchers/me",
            query: [
                URLQueryItem(name: "page", value: String(page)),
                URLQueryItem(name: "perPage", value: String(perPage))
            ],
            authorized: true,
            attachCartToken: false
        )
    }
}
