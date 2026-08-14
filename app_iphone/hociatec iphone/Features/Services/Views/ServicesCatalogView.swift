import SwiftUI

struct ServicesCatalogView: View {
    let serviceCatalog: ServiceCatalogServing
    @StateObject private var viewModel: ServicesCatalogViewModel

    init(api: ServiceCatalogServing, initialSearch: String = "") {
        self.serviceCatalog = api
        let viewModel = ServicesCatalogViewModel(serviceCatalog: api)
        viewModel.searchText = initialSearch
        viewModel.appliedSearch = initialSearch.trimmingCharacters(in: .whitespacesAndNewlines)
        _viewModel = StateObject(wrappedValue: viewModel)
    }

    var body: some View {
        List {
            ServicesCatalogSearchSection(viewModel: viewModel, onSearch: performSearch)
            ServicesCatalogResultsSection(serviceCatalog: serviceCatalog, viewModel: viewModel)
            ServicesCatalogPaginationSection(
                viewModel: viewModel,
                onPrevious: {
                    viewModel.previousPage()
                    Task { await viewModel.load(force: true) }
                },
                onNext: {
                    viewModel.nextPage()
                    Task { await viewModel.load(force: true) }
                }
            )
        }
        .navigationTitle("Services")
        .task { await viewModel.load() }
        .feedbackDialog(error: $viewModel.error)
    }

    private func performSearch() {
        viewModel.applySearch()
        Task { await viewModel.load(force: true) }
    }
}
