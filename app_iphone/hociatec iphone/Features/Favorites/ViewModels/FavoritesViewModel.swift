import Foundation
import Combine

@MainActor
final class FavoritesViewModel: ObservableObject {
    @Published var items: [FavoriteEntry] = []
    @Published var isLoading = false
    @Published var error: String?
    @Published var selectedCategory: FavoriteCategory?

    private let service: FavoritesServing
    private var loadRequestID = 0
    private var hasLoadedOnce = false

    init(service: FavoritesServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if (isLoading || hasLoadedOnce) && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil
        do {
            let favs = try await service.listFavorites(category: selectedCategory)
            guard requestID == loadRequestID else { return }
            items = favs
            hasLoadedOnce = true
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
        }
        if requestID == loadRequestID {
            isLoading = false
        }
    }

    func setCategory(_ category: FavoriteCategory?) async {
        selectedCategory = category
        await load(force: true)
    }

    func add(category: FavoriteCategory, targetId: Int) async {
        do {
            _ = try await service.addFavorite(category: category, targetId: targetId)
            await load(force: true)
        } catch {
            self.error = error.localizedDescription
        }
    }

    func remove(category: FavoriteCategory, targetId: Int) async {
        do {
            _ = try await service.removeFavorite(category: category, targetId: targetId)
            await load(force: true)
        } catch {
            self.error = error.localizedDescription
        }
    }

    func removeLocally(category: FavoriteCategory, targetId: Int) {
        items.removeAll { $0.category == category && $0.targetId == targetId }
    }
}
