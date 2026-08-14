import Foundation

extension APIClient {
    struct FavoriteListData: Decodable {
        let items: [FavoriteEntry]
    }

    func listFavorites(category: FavoriteCategory? = nil) async throws -> [FavoriteEntry] {
        let data: FavoriteListData = try await request(
            path: "api/favorites",
            query: category.map { [URLQueryItem(name: "category", value: $0.rawValue)] },
            authorized: true,
            attachCartToken: false
        )
        return data.items
    }

    @discardableResult
    func addFavorite(category: FavoriteCategory, targetId: Int) async throws -> AddFavoriteResponse {
        let resp: AddFavoriteResponse = try await request(
            path: "api/favorites/\(category.rawValue)/\(targetId)",
            method: "POST",
            authorized: true,
            attachCartToken: false
        )
        return resp
    }

    func removeFavorite(category: FavoriteCategory, targetId: Int) async throws -> Bool {
        let resp: RemoveFavoriteResponse = try await request(
            path: "api/favorites/\(category.rawValue)/\(targetId)",
            method: "DELETE",
            authorized: true,
            attachCartToken: false
        )
        return resp.removed
    }

    func favoriteStatus(category: FavoriteCategory, targetId: Int) async throws -> FavoriteStatusResponse {
        try await request(
            path: "api/favorites/\(category.rawValue)/\(targetId)/status",
            authorized: true,
            attachCartToken: false
        )
    }
}
