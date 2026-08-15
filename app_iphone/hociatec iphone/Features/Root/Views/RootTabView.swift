import SwiftUI

struct ContentView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @EnvironmentObject private var navigation: AppNavigationState
    @State private var productFiltersBadge: Int? = nil
    @State private var didRefreshCartOnce = false

    var body: some View {
        RootTabContainer(
            services: container.services,
            cartAccessibilityLabel: cartAccessibilityLabel,
            cartBadge: cart.cart?.totalQuantity ?? 0,
            selectedTab: $navigation.selectedTab,
            productFiltersBadge: $productFiltersBadge
        )
        .task {
            guard !didRefreshCartOnce else { return }
            didRefreshCartOnce = true
            await cart.refresh()
        }
        .sheet(item: $navigation.presentedSheet) { route in
            NavigationStack {
                sheetDestination(for: route)
            }
        }
        .feedbackDialog(appDialogBinding)
    }

    private var appDialogBinding: Binding<FeedbackDialogState?> {
        Binding(
            get: { container.feedbackCenter.dialog },
            set: { container.feedbackCenter.dialog = $0 }
        )
    }

    private var cartAccessibilityLabel: String {
        guard let cart = cart.cart else { return "Panier, chargement…" }
        let count = cart.totalQuantity
        return count == 1 ? "Panier, 1 article" : "Panier, \(count) articles"
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
