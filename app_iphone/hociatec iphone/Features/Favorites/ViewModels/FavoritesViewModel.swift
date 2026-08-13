import Foundation
import Combine

@MainActor
final class FavoritesViewModel: ObservableObject {
    @Published var items: [Product] = []
    @Published var isLoading = false
    @Published var error: String?

    private let service: FavoritesServing

    init(service: FavoritesServing) {
        self.service = service
    }

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }
        do {
            let favs = try await service.listFavorites()
            items = favs.map { $0.product }
        } catch {
            self.error = error.localizedDescription
        }
    }

    func add(product: Product) async {
        do {
            _ = try await service.addFavorite(productId: product.id)
            await load()
        } catch {
            self.error = error.localizedDescription
        }
    }

    func remove(product: Product) async {
        do {
            _ = try await service.removeFavorite(productId: product.id)
            await load()
        } catch {
            self.error = error.localizedDescription
        }
    }
}
