import Foundation
import Combine

@MainActor
final class FavoritesViewModel: ObservableObject {
    @Published var items: [Product] = []
    @Published var isLoading = false
    @Published var error: String?

    private let service: FavoritesServing
    private var loadRequestID = 0

    init(service: FavoritesServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil
        do {
            let favs = try await service.listFavorites()
            guard requestID == loadRequestID else { return }
            items = favs.map { $0.product }
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
        }
        if requestID == loadRequestID {
            isLoading = false
        }
    }

    func add(product: Product) async {
        do {
            _ = try await service.addFavorite(productId: product.id)
            await load(force: true)
        } catch {
            self.error = error.localizedDescription
        }
    }

    func remove(product: Product) async {
        do {
            _ = try await service.removeFavorite(productId: product.id)
            await load(force: true)
        } catch {
            self.error = error.localizedDescription
        }
    }
}
