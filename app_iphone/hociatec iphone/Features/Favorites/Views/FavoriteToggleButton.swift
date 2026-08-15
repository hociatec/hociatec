import SwiftUI

struct FavoriteToggleButton: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel

    let category: FavoriteCategory
    let targetId: Int
    var onRemoved: (() -> Void)? = nil

    @State private var isFavorite = false
    @State private var isLoading = false
    @State private var feedbackDialog: FeedbackDialogState?

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
        .overlay {
            if !account.isLoggedIn {
                Circle()
                    .fill(Color.clear)
                    .frame(width: 44, height: 44)
                    .contentShape(Circle())
                    .onTapGesture {}
                    .accessibilityHidden(true)
            }
        }
        .accessibilityLabel(isFavorite ? "Retirer des favoris" : "Ajouter aux favoris")
        .accessibilityHint(account.isLoggedIn ? "Met à jour ce favori" : "Connectez-vous pour ajouter ce produit aux favoris")
        .accessibilityAddTraits(isFavorite ? .isSelected : [])
        .task(id: account.isLoggedIn) {
            await loadStatus()
        }
        .feedbackDialog($feedbackDialog)
    }

    private var buttonForegroundColor: Color {
        if isFavorite {
            return .red
        }

        return account.isLoggedIn ? .primary : .secondary
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
                onRemoved?()
                feedbackDialog = .success("Favori retiré.")
            } else {
                _ = try await container.services.favorites.addFavorite(category: category, targetId: targetId)
                isFavorite = true
                feedbackDialog = .success("Favori ajouté.")
            }
        } catch {
            feedbackDialog = .error("Impossible de mettre à jour ce favori. \(error.localizedDescription)")
        }
    }
}
