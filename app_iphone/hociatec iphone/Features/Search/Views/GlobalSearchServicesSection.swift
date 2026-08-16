import SwiftUI

struct GlobalSearchServicesSection: View {
    @EnvironmentObject private var container: AppContainer

    @ObservedObject var viewModel: GlobalSearchViewModel

    var body: some View {
        Section {
            if viewModel.services.isEmpty {
                GlobalSearchEmptyRow(message: "Aucun résultat service.")
            } else {
                ForEach(viewModel.services) { service in
                    VStack(alignment: .leading, spacing: 6) {
                        Text(service.title)
                            .fontWeight(.semibold)
                            .multilineTextAlignment(.leading)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .accessibilityAddTraits(.isHeader)

                        GlobalSearchServiceRow(service: service, showsTitle: false)
                        NavigationLink {
                            ServiceDetailView(api: container.services.serviceCatalog, serviceID: service.id)
                        } label: {
                            Label("Voir le détail", systemImage: "arrow.right.circle")
                                .font(.footnote.weight(.semibold))
                        }
                        .buttonStyle(.borderless)
                    }
                    .padding(.vertical, 4)
                    .accessibilityElement(children: .contain)
                }
            }
        } header: {
            GlobalSearchResultHeader(title: "Services", total: viewModel.serviceTotal, query: viewModel.query) {
                ServicesCatalogView(
                    api: container.services.serviceCatalog,
                    initialSearch: viewModel.query
                )
            }
        } footer: {
            GlobalSearchPaginationSection(viewModel: viewModel, filter: .services)
        }
    }
}
