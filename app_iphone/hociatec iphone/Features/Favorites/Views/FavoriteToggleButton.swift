import SwiftUI

struct FavoriteToggleButton: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel

    let category: FavoriteCategory
    let targetId: Int

    @State private var isFavorite = false
    @State private var isLoading = false

    var body: some View {
        Button {
            Task { await toggle() }
        } label: {
            Image(systemName: isFavorite ? "heart.fill" : "heart")
                .foregroundStyle(isFavorite ? .red : .secondary)
        }
        .buttonStyle(.plain)
        .disabled(!account.isLoggedIn || isLoading)
        .accessibilityLabel(isFavorite ? "Retirer des favoris" : "Ajouter aux favoris")
        .accessibilityValue(isFavorite ? "Sélectionné" : "Non sélectionné")
        .accessibilityHint("Met à jour ce favori")
        .accessibilityAddTraits(isFavorite ? .isSelected : [])
        .task(id: account.isLoggedIn) {
            await loadStatus()
        }
    }

    private func loadStatus() async {
        guard account.isLoggedIn else {
            isFavorite = false
            return
        }

        do {
            isFavorite = try await container.services.favorites.favoriteStatus(category: category, targetId: targetId).isFavorite
        } catch {
            isFavorite = false
        }
    }

    private func toggle() async {
        guard account.isLoggedIn else { return }

        isLoading = true
        defer { isLoading = false }

        do {
            if isFavorite {
                _ = try await container.services.favorites.removeFavorite(category: category, targetId: targetId)
                isFavorite = false
                container.feedbackCenter.presentSuccess("Favori retiré.")
            } else {
                _ = try await container.services.favorites.addFavorite(category: category, targetId: targetId)
                isFavorite = true
                container.feedbackCenter.presentSuccess("Favori ajouté.")
            }
        } catch {
            container.feedbackCenter.presentError("Impossible de mettre à jour ce favori. \(error.localizedDescription)")
        }
    }
}
