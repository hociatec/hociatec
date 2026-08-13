import SwiftUI

struct ContentView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @EnvironmentObject private var navigation: AppNavigationState
    @State private var selectedTab: Int = 0
    @State private var productFiltersBadge: Int? = nil
    @State private var bannerMessage: String? = nil
    @State private var bannerIsError: Bool = false

    var body: some View {
        RootTabContainer(
            services: container.services,
            cartAccessibilityLabel: cartAccessibilityLabel,
            cartBadge: cart.cart?.totalQuantity ?? 0,
            selectedTab: $selectedTab,
            productFiltersBadge: $productFiltersBadge
        )
        .task { await cart.refresh() }
        .sheet(item: $navigation.presentedSheet) { route in
            NavigationStack {
                sheetDestination(for: route)
            }
        }
        .overlay(alignment: .top) {
            RootBannerOverlay(message: bannerMessage, isError: bannerIsError)
        }
        .animation(.spring(), value: bannerMessage)
        .onChangeCompat(container.cart.statusMessage) { newValue in
            showBanner(newValue, isError: false, duration: 2.5)
        }
        .onChangeCompat(container.account.statusMessage) { newValue in
            showBanner(newValue, isError: false, duration: 2.5)
        }
        .onChangeCompat(container.cart.error) { newValue in
            showBanner(newValue, isError: true, duration: 4.0)
        }
        .onChangeCompat(container.account.error) { newValue in
            showBanner(newValue, isError: true, duration: 4.0)
        }
    }

    private var cartAccessibilityLabel: String {
        guard let cart = cart.cart else { return "Panier, chargement…" }
        let count = cart.totalQuantity
        return count == 1 ? "Panier, 1 article" : "Panier, \(count) articles"
    }

    private func showBanner(_ newValue: String?, isError: Bool, duration: TimeInterval) {
        guard let msg = newValue, !msg.isEmpty else { return }
        bannerIsError = isError
        bannerMessage = msg
        DispatchQueue.main.asyncAfter(deadline: .now() + duration) {
            if bannerMessage == msg {
                bannerMessage = nil
            }
        }
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
