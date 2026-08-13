import SwiftUI

struct OfferHubView: View {
    let services: AppServices
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel
    @Binding var selectedTab: Int
    @Binding var filtersBadge: Int?

    var body: some View {
        List {
            Section("Produits") {
                NavigationLink {
                    ProductsListView(
                        viewModel: container.makeProductsViewModel(),
                        selectedTab: $selectedTab,
                        filtersBadge: $filtersBadge,
                        navigationTitle: "Produits"
                    )
                } label: {
                    Label("Tous les produits", systemImage: "shippingbox")
                }

                NavigationLink {
                    ProductsListView(
                        viewModel: container.makeProductsViewModel(initialSellingType: .sale),
                        selectedTab: $selectedTab,
                        filtersBadge: $filtersBadge,
                        navigationTitle: "Produits en vente"
                    )
                } label: {
                    Label("Vente", systemImage: "cart")
                }

                NavigationLink {
                    ProductsListView(
                        viewModel: container.makeProductsViewModel(initialSellingType: .rental),
                        selectedTab: $selectedTab,
                        filtersBadge: $filtersBadge,
                        navigationTitle: "Produits en location"
                    )
                } label: {
                    Label("Location", systemImage: "clock.arrow.circlepath")
                }
            }

            Section("Services") {
                NavigationLink {
                    ServicesCatalogView(api: services.serviceCatalog)
                } label: {
                    Label("Services", systemImage: "wrench.and.screwdriver")
                }

                NavigationLink {
                    TrainingsCatalogView(api: services.training)
                } label: {
                    Label("Formation", systemImage: "graduationcap")
                }

                NavigationLink {
                    TradeInRequestView(service: services.tradeIn, account: account)
                } label: {
                    Label("Reprise de matériel", systemImage: "arrow.triangle.2.circlepath")
                }
            }
        }
        .navigationTitle("Notre offre")
    }
}
