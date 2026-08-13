import SwiftUI

extension View {
    func cartScreenAlerts(
        screenState: Binding<CartScreenState>,
        cart: CartViewModel
    ) -> some View {
        self
            .alert("Vider le panier ?", isPresented: screenState.showingClearConfirm) {
                Button("Annuler", role: .cancel) {
                    screenState.wrappedValue.showingClearConfirm = false
                }
                Button("Vider", role: .destructive) {
                    Task { await cart.clear() }
                }
            } message: {
                Text("Cette action supprimera tous les articles de votre panier. Voulez-vous continuer ?")
            }
            .alert(
                "Supprimer cet article ?",
                isPresented: Binding(
                    get: { screenState.wrappedValue.isShowingRemovalAlert },
                    set: { newValue in
                        if !newValue {
                            screenState.wrappedValue.itemPendingRemoval = nil
                        }
                    }
                )
            ) {
                Button("Annuler", role: .cancel) {
                    screenState.wrappedValue.itemPendingRemoval = nil
                }
                Button("Supprimer", role: .destructive) {
                    guard let item = screenState.wrappedValue.itemPendingRemoval else { return }
                    Task { await cart.remove(item: item) }
                    screenState.wrappedValue.itemPendingRemoval = nil
                }
            } message: {
                if let item = screenState.wrappedValue.itemPendingRemoval {
                    Text("Voulez-vous retirer \(item.product.name) du panier ?")
                } else {
                    Text("")
                }
            }
            .alert(item: screenState.checkoutDialog) { dialog in
                Alert(
                    title: Text(dialog.title),
                    message: Text(dialog.message),
                    dismissButton: .default(Text("OK")) {
                        screenState.wrappedValue.checkoutDialog = nil
                    }
                )
            }
    }
}
