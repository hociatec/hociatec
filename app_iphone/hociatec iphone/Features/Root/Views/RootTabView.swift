import SwiftUI

private struct RootMessageDialog: Identifiable {
    let id = UUID()
    let title: String
    let message: String
}

struct ContentView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @EnvironmentObject private var navigation: AppNavigationState
    @State private var productFiltersBadge: Int? = nil
    @State private var dialog: RootMessageDialog?

    var body: some View {
        RootTabContainer(
            services: container.services,
            cartAccessibilityLabel: cartAccessibilityLabel,
            cartBadge: cart.cart?.totalQuantity ?? 0,
            selectedTab: $navigation.selectedTab,
            productFiltersBadge: $productFiltersBadge
        )
        .task { await cart.refresh() }
        .sheet(item: $navigation.presentedSheet) { route in
            NavigationStack {
                sheetDestination(for: route)
            }
        }
        .alert(item: $dialog) { dialog in
            Alert(
                title: Text(dialog.title),
                message: Text(dialog.message),
                dismissButton: .default(Text("OK"))
            )
        }
        .onChangeCompat(container.cart.statusMessage) { newValue in
            showDialog(newValue, isError: false)
        }
        .onChangeCompat(container.account.statusMessage) { newValue in
            showDialog(newValue, isError: false)
        }
        .onChangeCompat(container.cart.error) { newValue in
            showDialog(newValue, isError: true)
        }
        .onChangeCompat(container.account.error) { newValue in
            showDialog(newValue, isError: true)
        }
    }

    private var cartAccessibilityLabel: String {
        guard let cart = cart.cart else { return "Panier, chargement…" }
        let count = cart.totalQuantity
        return count == 1 ? "Panier, 1 article" : "Panier, \(count) articles"
    }

    private func showDialog(_ newValue: String?, isError: Bool) {
        guard let msg = newValue, !msg.isEmpty else { return }
        dialog = RootMessageDialog(
            title: isError ? "Erreur" : "Information",
            message: msg
        )
    }

    @ViewBuilder
    private func sheetDestination(for route: AppNavigationState.SheetRoute) -> some View {
        switch route {
        case let .resetPassword(token):
            ResetPasswordView(service: container.services.account, initialToken: token, allowsTokenEditing: false)
                .toolbar {
                    ToolbarItem(placement: .topBarTrailing) {
                        Button("Fermer") {
                            navigation.dismissSheet()
                        }
                    }
                }
        case let .activateAccount(token):
            AccountActivationView(service: container.services.account, token: token)
                .toolbar {
                    ToolbarItem(placement: .topBarTrailing) {
                        Button("Fermer") {
                            navigation.dismissSheet()
                        }
                    }
                }
        }
    }
}
