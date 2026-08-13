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
                    NavigationLink {
                        ServiceDetailView(api: container.services.serviceCatalog, serviceID: service.id)
                    } label: {
                        GlobalSearchServiceRow(service: service)
                    }
                }
            }
        } header: {
            GlobalSearchResultHeader(title: "Services", total: viewModel.serviceTotal, query: viewModel.query) {
                ServicesCatalogView(
                    api: container.services.serviceCatalog,
                    initialSearch: viewModel.query
                )
            }
        }
    }
}
