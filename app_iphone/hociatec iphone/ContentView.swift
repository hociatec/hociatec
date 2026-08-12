import SwiftUI
import Foundation
#if canImport(UIKit)
import UIKit
#endif

private struct CartTabLabel: View {
    let cart: Cart?
    var body: some View {
        let label: String = {
            guard let cart else { return "Panier, chargement…" }
            let count = cart.totalQuantity
            return count == 1 ? "Panier, 1 article" : "Panier, \(count) articles"
        }()
        return Label("Panier", systemImage: "cart")
            .accessibilityLabel(label)
    }
}

struct ContentView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel
    @State private var selectedTab: Int = 0
    @State private var productFiltersBadge: Int? = nil
    @StateObject private var cartVM: CartViewModel = CartViewModel(api: APIClient(sessionStore: SessionStore()))
    @State private var bannerMessage: String? = nil
    @State private var bannerIsError: Bool = false

    var body: some View {
        TabView(selection: $selectedTab) {
            NavigationStack {
                HomeScreen(api: container.api, selectedTab: $selectedTab)
            }
            .tabItem { Label("Accueil", systemImage: "house") }
            .tag(0)

            NavigationStack {
                ProductsListView(api: container.api, selectedTab: $selectedTab, filtersBadge: $productFiltersBadge)
            }
            .tabItem { Label("Produits", systemImage: "square.grid.2x2") }
            .badge(productFiltersBadge.map { Text("\($0)") })
            .tag(1)

            NavigationStack {
                CartScreen()
            }
            .tabItem {
                CartTabLabel(cart: cart.cart)
            }
            .badge(cart.cart?.totalQuantity ?? 0)
            .tag(2)

            NavigationStack {
                AccountScreen()
            }
            .tabItem { Label("Compte", systemImage: "person") }
            .tag(3)
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
}

private struct HomeScreen: View {
    @StateObject private var home: HomeViewModel
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel
    @Binding private var selectedTab: Int

    init(api: APIClient, selectedTab: Binding<Int>) {
        _home = StateObject(wrappedValue: HomeViewModel(api: api))
        _selectedTab = selectedTab
    }

    var body: some View {
        List {
            Section("Produits en vedette") {
                if home.isLoading && home.featured.isEmpty {
                    ProgressView("Chargement...")
                        .frame(maxWidth: .infinity, alignment: .center)
                } else if let error = home.error {
                    Text(error)
                        .foregroundStyle(.red)
                } else if home.featured.isEmpty {
                    Text("Aucun produit disponible pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(home.featured.prefix(5)) { product in
                        NavigationLink {
                            // Navigate to detail
                            ProductDetailView(
                                product: product,
                                imageURL: nil,
                                cart: container.cart,
                                selectedTab: .constant(0)
                            )
                            .environmentObject(container)
                        } label: {
                            VStack(alignment: .leading, spacing: 4) {
                                Text(product.name)
                                    .fontWeight(.semibold)
                                Text(product.shortDescription)
                                    .lineLimit(2)
                                    .foregroundStyle(.secondary)
                            }
                            .accessibilityElement(children: .ignore)
                            .accessibilityLabel("Produit: \(product.name)")
                            .accessibilityHint("Afficher le détail du produit")
                        }
                    }
                }

                Button {
                    selectedTab = 1
                } label: {
                    HStack {
                        Label("Voir tous les produits", systemImage: "square.grid.2x2")
                            .fontWeight(.semibold)
                        Spacer()
                        Image(systemName: "chevron.right")
                            .foregroundStyle(.tertiary)
                    }
                }
                .accessibilityHint("Ouvrir l’onglet Produits")
            }

            Section {
                if account.isLoggedIn {
                    NavigationLink {
                        AppointmentBookingView(api: container.api)
                    } label: {
                        Label("Prendre rendez-vous", systemImage: "calendar.badge.plus")
                    }
                    NavigationLink {
                        QuoteRequestView(api: container.api, account: account)
                    } label: {
                        Label("Demander un devis", systemImage: "doc.badge.plus")
                    }
                } else {
                    Button {
                        selectedTab = 3
                    } label: {
                        Label("Se connecter pour accéder aux services", systemImage: "person")
                    }
                }
            }
        }
        .navigationTitle("Accueil")
        .task { await home.load() }
    }
}

private struct BannerView: View {
    let message: String
    var isError: Bool = false
    var body: some View {
        Text(message)
            .font(.subheadline)
            .padding(.horizontal, 12)
            .padding(.vertical, 8)
            .foregroundStyle(isError ? Color.white : Color.primary)
            .background(isError ? Color.red.opacity(0.9) : Color(.systemBackground).opacity(0.9))
            .clipShape(Capsule())
            .shadow(radius: 3)
            .accessibilityLabel(isError ? "Erreur: \(message)" : message)
    }
}
