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
                .font(.system(size: 16, weight: .semibold))
                .frame(width: 44, height: 44)
                .background(isFavorite ? Color.red.opacity(0.12) : Color(.secondarySystemBackground))
                .foregroundStyle(buttonForegroundColor)
                .clipShape(Circle())
                .overlay(
                    Circle()
                        .stroke(isFavorite ? Color.red.opacity(0.35) : Color(.separator), lineWidth: 1)
                )
                .contentShape(Circle())
                .opacity(account.isLoggedIn ? 1 : 0.45)
        }
        .buttonStyle(.borderless)
        .disabled(!account.isLoggedIn || isLoading)
        .accessibilityLabel(isFavorite ? "Retirer des favoris" : "Ajouter aux favoris")
        .accessibilityHint(account.isLoggedIn ? "Met à jour ce favori" : "Connectez-vous pour ajouter ce produit aux favoris")
        .accessibilityValue(accessibilityValue)
        .accessibilityAddTraits(isFavorite ? .isSelected : [])
        .task(id: account.isLoggedIn) {
            await loadStatus()
        }
    }

    private var buttonForegroundColor: Color {
        if isFavorite {
            return .red
        }

        return account.isLoggedIn ? .primary : .secondary
    }

    private var accessibilityValue: String {
        if !account.isLoggedIn {
            return "Indisponible"
        }

        return isFavorite ? "Sélectionné" : ""
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
