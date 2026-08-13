import Foundation

extension APIClient {
    struct FavoriteListData: Decodable {
        let items: [FavoriteEntry]
    }

    func listFavorites() async throws -> [FavoriteEntry] {
        let data: FavoriteListData = try await request(
            path: "api/favorites",
            authorized: true,
            attachCartToken: false
        )
        return data.items
    }

    @discardableResult
    func addFavorite(productId: Int) async throws -> AddFavoriteResponse {
        let resp: AddFavoriteResponse = try await request(
            path: "api/favorites/\(productId)",
            method: "POST",
            authorized: true,
            attachCartToken: false
        )
        return resp
    }

    func removeFavorite(productId: Int) async throws -> Bool {
        let resp: RemoveFavoriteResponse = try await request(
            path: "api/favorites/\(productId)",
            method: "DELETE",
            authorized: true,
            attachCartToken: false
        )
        return resp.removed
    }
}
