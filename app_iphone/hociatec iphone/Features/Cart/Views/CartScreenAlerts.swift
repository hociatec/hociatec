import SwiftUI

extension View {
    func cartScreenAlerts(
        screenState: Binding<CartScreenState>,
        cart: CartViewModel
    ) -> some View {
        self
            .feedbackDialog(
                Binding(
                    get: {
                        if screenState.wrappedValue.showingClearConfirm {
                            return FeedbackDialogState(
                                title: "Vider le panier ?",
                                message: "Cette action supprimera tous les articles de votre panier. Voulez-vous continuer ?",
                                primaryButton: .cancel("Annuler"),
                                secondaryButton: .destructive("Vider") {
                                    Task { await cart.clear() }
                                }
                            )
                        }

                        if let item = screenState.wrappedValue.itemPendingRemoval {
                            return FeedbackDialogState(
                                title: "Supprimer cet article ?",
                                message: "Voulez-vous retirer \(item.product.name) du panier ?",
                                primaryButton: .cancel("Annuler"),
                                secondaryButton: .destructive("Supprimer") {
                                    Task { await cart.remove(item: item) }
                                }
                            )
                        }

                        return screenState.wrappedValue.checkoutDialog
                    },
                    set: { newValue in
                        screenState.wrappedValue.checkoutDialog = newValue
                        if newValue == nil {
                            screenState.wrappedValue.showingClearConfirm = false
                            screenState.wrappedValue.itemPendingRemoval = nil
                        }
                    }
                )
            )
    }
}
