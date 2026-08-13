import SwiftUI

struct ContentView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @State private var selectedTab: Int = 0
    @State private var productFiltersBadge: Int? = nil
    @State private var bannerMessage: String? = nil
    @State private var bannerIsError: Bool = false

    var body: some View {
        TabView(selection: $selectedTab) {
            NavigationStack {
                HomeScreen(services: container.services)
            }
            .tabItem { Label("Accueil", systemImage: "house") }
            .tag(0)

            NavigationStack {
                OfferHubView(services: container.services, selectedTab: $selectedTab, filtersBadge: $productFiltersBadge)
            }
            .tabItem { Label("Notre offre", systemImage: "square.grid.2x2") }
            .badge(productFiltersBadge.map { Text("\($0)") })
            .tag(1)

            NavigationStack {
                CartScreen()
            }
            .tabItem {
                Label("Panier", systemImage: "cart")
                    .accessibilityLabel(cartAccessibilityLabel)
            }
            .badge(cart.cart?.totalQuantity ?? 0)
            .tag(2)

            NavigationStack {
                NewsListView(api: container.services.news)
            }
            .tabItem { Label("Actualités", systemImage: "newspaper") }
            .tag(3)

            NavigationStack {
                AccountScreen()
            }
            .tabItem { Label("Compte", systemImage: "person") }
            .tag(4)
        }
        .task { await cart.refresh() }
        .overlay(alignment: .top) {
            if let message = bannerMessage {
                BannerView(message: message, isError: bannerIsError)
                    .transition(.move(edge: .top).combined(with: .opacity))
                    .padding(.top, 8)
            }
        }
        .animation(.spring(), value: bannerMessage)
        .onChangeCompat(container.cart.statusMessage) { newValue in
            guard let msg = newValue, !msg.isEmpty else { return }
            bannerIsError = false
            bannerMessage = msg
            DispatchQueue.main.asyncAfter(deadline: .now() + 2.5) {
                if bannerMessage == msg { bannerMessage = nil }
            }
        }
        .onChangeCompat(container.account.statusMessage) { newValue in
            guard let msg = newValue, !msg.isEmpty else { return }
            bannerIsError = false
            bannerMessage = msg
            DispatchQueue.main.asyncAfter(deadline: .now() + 2.5) {
                if bannerMessage == msg { bannerMessage = nil }
            }
        }
        .onChangeCompat(container.cart.error) { newValue in
            guard let msg = newValue, !msg.isEmpty else { return }
            bannerIsError = true
            bannerMessage = msg
            DispatchQueue.main.asyncAfter(deadline: .now() + 4.0) {
                if bannerMessage == msg { bannerMessage = nil }
            }
        }
        .onChangeCompat(container.account.error) { newValue in
            guard let msg = newValue, !msg.isEmpty else { return }
            bannerIsError = true
            bannerMessage = msg
            DispatchQueue.main.asyncAfter(deadline: .now() + 4.0) {
                if bannerMessage == msg { bannerMessage = nil }
            }
        }
    }

    private var cartAccessibilityLabel: String {
        guard let cart = cart.cart else { return "Panier, chargement…" }
        let count = cart.totalQuantity
        return count == 1 ? "Panier, 1 article" : "Panier, \(count) articles"
    }
}
