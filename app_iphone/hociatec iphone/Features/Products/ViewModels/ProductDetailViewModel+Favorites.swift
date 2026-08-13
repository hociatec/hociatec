import Foundation

extension ProductDetailViewModel {
    func refreshFavorite() async {
        do {
            isFavorite = try await loadFavoriteStatusUseCase.execute(productId: product.id)
        } catch {
            isFavorite = false
        }
    }

    func toggleFavorite() async {
        do {
            isFavorite = try await toggleFavoriteUseCase.execute(productId: product.id, isFavorite: isFavorite)
        } catch {
        }
    }
}
