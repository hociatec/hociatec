import Foundation

struct FavoritesService: FavoritesServing {
    let api: APIClient

    func listFavorites() async throws -> [FavoriteEntry] { try await api.listFavorites() }
    func addFavorite(productId: Int) async throws -> AddFavoriteResponse { try await api.addFavorite(productId: productId) }
    func removeFavorite(productId: Int) async throws -> Bool { try await api.removeFavorite(productId: productId) }
}
