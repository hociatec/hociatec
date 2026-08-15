import SwiftUI

struct RootTabContainer: View {
    let services: AppServices
    let cartAccessibilityLabel: String
    let cartBadge: Int
    @Binding var selectedTab: Int
    @Binding var productFiltersBadge: Int?

    var body: some View {
        TabView(selection: $selectedTab) {
            RootHomeTab(services: services)
                .tag(0)

            RootOfferTab(
                services: services,
                selectedTab: $selectedTab,
                productFiltersBadge: $productFiltersBadge
            )
            .tag(1)

            RootCartTab(cartAccessibilityLabel: cartAccessibilityLabel, cartBadge: cartBadge)
                .tag(2)

            RootNewsTab(services: services)
                .tag(3)

            RootAccountTab()
                .tag(4)
        }
    }
}

private struct RootHomeTab: View {
    let services: AppServices

    var body: some View {
        NavigationStack {
            HomeScreen(services: services)
        }
        .tabItem { Label("Accueil", systemImage: "house") }
    }
}

private struct RootOfferTab: View {
    let services: AppServices
    @Binding var selectedTab: Int
    @Binding var productFiltersBadge: Int?

    var body: some View {
        NavigationStack {
            OfferHubView(
                services: services,
                selectedTab: $selectedTab,
                filtersBadge: $productFiltersBadge
            )
        }
        .tabItem { Label("Notre offre", systemImage: "square.grid.2x2") }
        .badge(productFiltersBadge.map { Text("\($0)") })
    }
}

private struct RootCartTab: View {
    let cartAccessibilityLabel: String
    let cartBadge: Int

    var body: some View {
        NavigationStack {
            CartScreen()
        }
        .tabItem {
            Label("Panier", systemImage: "cart")
                .accessibilityLabel(cartAccessibilityLabel)
        }
        .badge(cartBadge)
    }
}

private struct RootNewsTab: View {
    let services: AppServices

    var body: some View {
        NavigationStack {
            NewsListView(api: services.news)
        }
        .tabItem { Label("Actualités", systemImage: "newspaper") }
    }
}

private struct RootAccountTab: View {
    @EnvironmentObject private var account: AccountViewModel

    var body: some View {
        NavigationStack {
            AccountScreen()
        }
        .id("account-nav-\(account.isLoggedIn ? "logged-in" : "logged-out")")
        .tabItem { Label("Compte", systemImage: "person") }
    }
}

struct RootBannerOverlay: View {
    let message: String?
    let isError: Bool

    var body: some View {
        if let message {
            BannerView(message: message, isError: isError)
                .transition(.move(edge: .top).combined(with: .opacity))
                .padding(.top, 8)
        }
    }
}
