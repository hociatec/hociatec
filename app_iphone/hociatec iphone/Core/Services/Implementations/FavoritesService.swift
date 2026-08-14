import Foundation

struct FavoritesService: FavoritesServing {
    let api: APIClient

    func listFavorites(category: FavoriteCategory?) async throws -> [FavoriteEntry] { try await api.listFavorites(category: category) }
    func addFavorite(category: FavoriteCategory, targetId: Int) async throws -> AddFavoriteResponse { try await api.addFavorite(category: category, targetId: targetId) }
    func removeFavorite(category: FavoriteCategory, targetId: Int) async throws -> Bool { try await api.removeFavorite(category: category, targetId: targetId) }
    func favoriteStatus(category: FavoriteCategory, targetId: Int) async throws -> FavoriteStatusResponse { try await api.favoriteStatus(category: category, targetId: targetId) }
}
