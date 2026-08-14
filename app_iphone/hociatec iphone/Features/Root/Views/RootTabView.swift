import SwiftUI

struct ContentView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @EnvironmentObject private var navigation: AppNavigationState
    @State private var productFiltersBadge: Int? = nil
    @State private var dialog: FeedbackDialogState?

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
        .feedbackDialog($dialog)
        .onChangeCompat(container.cart.statusMessage) { newValue in
            showDialog(newValue, isError: false)
            if newValue != nil {
                container.cart.statusMessage = nil
            }
        }
        .onChangeCompat(container.account.statusMessage) { newValue in
            showDialog(newValue, isError: false)
            if newValue != nil {
                container.account.statusMessage = nil
            }
        }
        .onChangeCompat(container.cart.error) { newValue in
            showDialog(newValue, isError: true)
            if newValue != nil {
                container.cart.error = nil
            }
        }
        .onChangeCompat(container.account.error) { newValue in
            showDialog(newValue, isError: true)
            if newValue != nil {
                container.account.error = nil
            }
        }
    }

    private var cartAccessibilityLabel: String {
        guard let cart = cart.cart else { return "Panier, chargement…" }
        let count = cart.totalQuantity
        return count == 1 ? "Panier, 1 article" : "Panier, \(count) articles"
    }

    private func showDialog(_ newValue: String?, isError: Bool) {
        guard let msg = newValue, !msg.isEmpty else { return }
        dialog = isError ? .error(msg, title: "Échec") : .success(msg, title: "Succès")
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
