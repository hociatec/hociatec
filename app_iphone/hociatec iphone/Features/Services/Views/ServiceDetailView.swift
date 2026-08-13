import SwiftUI

struct ServiceDetailView: View {
    let serviceCatalog: ServiceCatalogServing
    let serviceID: Int
    @EnvironmentObject private var container: AppContainer
    @StateObject private var viewModel: ServiceDetailViewModel

    init(api: ServiceCatalogServing, serviceID: Int) {
        self.serviceCatalog = api
        self.serviceID = serviceID
        _viewModel = StateObject(wrappedValue: ServiceDetailViewModel(serviceCatalog: api, serviceID: serviceID))
    }

    var body: some View {
        List {
            if viewModel.isLoading && viewModel.service == nil {
                ServiceDetailLoadingSection()
            } else if let error = viewModel.error {
                ServiceDetailErrorSection(error: error)
            } else if let service = viewModel.service {
                ServiceDetailHeroSectionView(
                    service: service,
                    imageURL: serviceCatalog.assetURL(for: service.imageUrl)
                )
                ServiceDetailFactsSection(service: service)
                ServiceDetailActionsSection(container: container)
            }
        }
        .navigationTitle(viewModel.service?.title ?? "Service")
        .navigationBarTitleDisplayMode(.inline)
        .task { await viewModel.load() }
    }
}
