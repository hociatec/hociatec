import SwiftUI

struct OfferHubView: View {
    let services: AppServices
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel
    @Binding var selectedTab: Int
    @Binding var filtersBadge: Int?

    var body: some View {
        List {
            Section {
                Text("Retrouvez en un seul endroit notre catalogue, nos prestations et les parcours pour acheter, louer, planifier ou demander un accompagnement.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }

            Section("Catalogue") {
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
                
                NavigationLink {
                    ServicesCatalogView(api: services.serviceCatalog)
                } label: {
                    Label("Services", systemImage: "wrench.and.screwdriver")
                }

                NavigationLink {
                    TrainingsCatalogView(api: services.training)
                } label: {
                    Label("Formations", systemImage: "graduationcap")
                }
            }

            Section("Prestations") {
                NavigationLink {
                    TradeInRequestView(service: services.tradeIn, account: account)
                } label: {
                    Label("Reprise", systemImage: "arrow.triangle.2.circlepath")
                }

                NavigationLink {
                    AppointmentBookingView(service: services.appointments)
                } label: {
                    Label("Prendre rendez-vous", systemImage: "calendar")
                }

                NavigationLink {
                    QuoteRequestView(viewModel: container.makeQuoteViewModel())
                } label: {
                    Label("Créer un devis", systemImage: "doc.text")
                }

                NavigationLink {
                    RequestAuditView(viewModel: AuditsViewModel(service: services.audits))
                } label: {
                    Label("Demander un audit", systemImage: "checklist")
                }
            }
        }
        .navigationTitle("Notre offre")
    }
}
