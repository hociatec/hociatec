import Foundation

extension ProductDetailViewModel {
    func refreshFavorite() async {
        guard !hasLoadedFavoriteOnce else { return }
        do {
            isFavorite = try await loadFavoriteStatusUseCase.execute(productId: product.id)
            hasLoadedFavoriteOnce = true
        } catch {
            isFavorite = false
        }
    }

    func toggleFavorite() async {
        do {
            isFavorite = try await toggleFavoriteUseCase.execute(productId: product.id, isFavorite: isFavorite)
            hasLoadedFavoriteOnce = true
            favoriteFeedback = .success(isFavorite ? "Favori ajouté." : "Favori retiré.")
        } catch {
            favoriteFeedback = .error("Impossible de mettre à jour ce favori. \(error.localizedDescription)")
        }
    }
}
